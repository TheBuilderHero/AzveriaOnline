<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MapEndpointsBusinessLogicTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::dropAllTables();
        $this->createCoreSchema();

        Storage::fake('public');
        $this->withoutMiddleware(ThrottleRequests::class);

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_player_can_list_map_layers_ordered_by_id(): void
    {
        $player = $this->createUser('map.player@example.test', 'player');

        DB::table('map_layers')->insert([
            ['layer_type' => 'terrain', 'image_path' => 'maps/terrain.png', 'uploaded_by_user_id' => $player->id, 'created_at' => now(), 'updated_at' => now()],
            ['layer_type' => 'main', 'image_path' => 'maps/main.png', 'uploaded_by_user_id' => $player->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Sanctum::actingAs($player);
        $response = $this->getJson('/api/maps/layers');

        $response->assertOk();
        $this->assertSame('terrain', (string) data_get($response->json(), '0.layer_type'));
        $this->assertSame('main', (string) data_get($response->json(), '1.layer_type'));
    }

    public function test_editor_state_returns_default_shape_when_no_active_file_exists(): void
    {
        $player = $this->createUser('map.default@example.test', 'player');

        Sanctum::actingAs($player);
        $response = $this->getJson('/api/maps/editor-state');

        $response->assertOk();
        $response->assertJsonPath('width', 1200);
        $response->assertJsonPath('height', 700);
        $response->assertJsonPath('terrain_strokes.0.tool', 'fill');
        $response->assertJsonPath('terrain_strokes.0.terrain', 'water');
    }

    public function test_admin_save_activate_and_status_lifecycle_for_editor_state(): void
    {
        $admin = $this->createUser('map.admin@example.test', 'admin');
        Sanctum::actingAs($admin);

        $baseline = $this->getJson('/api/admin/maps/editor-state');
        $baseline->assertOk();
        $baseline->assertJsonPath('status.has_unpublished_changes', false);

        $save = $this->postJson('/api/admin/maps/editor-state', [
            'width' => 1600,
            'height' => 900,
            'terrain_color_overrides' => [
                'water' => '#11AAFF',
                'bad' => 'not-a-color',
            ],
            'terrain_strokes' => [
                ['tool' => 'fill', 'terrain' => 'forest', 'x' => 10, 'y' => 20],
                ['tool' => 'brush', 'terrain' => '', 'x' => 1, 'y' => 1],
            ],
            'political_nations' => [
                ['id' => 7, 'name' => 'Test Nation', 'alliance_name' => 'Alliance', 'pixels' => 123, 'races' => ['Human', '']],
            ],
            'political_strokes' => [
                ['tool' => 'brush', 'nation_id' => 7, 'x' => 2, 'y' => 3, 'size' => 4],
            ],
        ]);
        $save->assertOk();
        $save->assertJsonPath('message', 'Draft map editor state saved');

        $this->assertTrue(Storage::disk('public')->exists('maps/editor-state-draft.json'));
        $draft = json_decode((string) Storage::disk('public')->get('maps/editor-state-draft.json'), true);
        $this->assertSame(1600, (int) ($draft['width'] ?? 0));
        $this->assertSame('#11AAFF', (string) data_get($draft, 'terrain_color_overrides.water'));
        $this->assertNull(data_get($draft, 'terrain_color_overrides.bad'));
        $this->assertSame('forest', (string) data_get($draft, 'terrain_strokes.0.terrain'));
        $this->assertSame($admin->id, (int) ($draft['saved_by_user_id'] ?? 0));

        $afterSave = $this->getJson('/api/admin/maps/editor-state');
        $afterSave->assertOk();
        $afterSave->assertJsonPath('status.has_unpublished_changes', true);

        $activate = $this->postJson('/api/admin/maps/editor-state/activate');
        $activate->assertOk();
        $activate->assertJsonPath('message', 'Draft map is now active.');

        $this->assertTrue(Storage::disk('public')->exists('maps/editor-state-active.json'));

        $afterActivate = $this->getJson('/api/admin/maps/editor-state');
        $afterActivate->assertOk();
        $afterActivate->assertJsonPath('status.has_unpublished_changes', false);
        $this->assertSame($admin->id, (int) data_get($afterActivate->json(), 'status.published_by_user_id'));
    }

    public function test_admin_upload_layer_validates_type_and_upserts_layer_path(): void
    {
        $admin = $this->createUser('map.upload@example.test', 'admin');
        Sanctum::actingAs($admin);

        $invalid = $this->postJson('/api/admin/maps/layers/bad-layer', [
            'image_path' => 'maps/invalid.png',
        ]);
        $invalid->assertStatus(422);
        $invalid->assertJsonPath('message', 'Invalid layer type');

        $first = $this->postJson('/api/admin/maps/layers/main', [
            'image_path' => 'maps/main-v1.png',
        ]);
        $first->assertOk();
        $first->assertJsonPath('image_path', 'maps/main-v1.png');

        $second = $this->postJson('/api/admin/maps/layers/main', [
            'image_path' => 'maps/main-v2.png',
        ]);
        $second->assertOk();
        $second->assertJsonPath('image_path', 'maps/main-v2.png');

        $count = DB::table('map_layers')->where('layer_type', 'main')->count();
        $path = DB::table('map_layers')->where('layer_type', 'main')->value('image_path');

        $this->assertSame(1, $count);
        $this->assertSame('maps/main-v2.png', $path);
    }

    public function test_admin_bulk_sync_updates_known_nations_and_skips_unknown_ids(): void
    {
        $admin = $this->createUser('map.sync@example.test', 'admin');
        Sanctum::actingAs($admin);

        $knownNationId = $this->createNation($admin->id, 'Known Nation');

        $response = $this->postJson('/api/admin/maps/terrain-stats/bulk-sync', [
            'nation_stats' => [
                [
                    'nation_id' => $knownNationId,
                    'terrain_square_miles' => [
                        'grassland' => 10,
                        'seafront' => 10,
                    ],
                ],
                [
                    'nation_id' => 999999,
                    'terrain_square_miles' => [
                        'grassland' => 5,
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('updated_count', 1);
        $response->assertJsonPath('failed_count', 0);
        $response->assertJsonPath('skipped_unknown_nations', 1);

        $row = DB::table('nation_terrain_stats')->where('nation_id', $knownNationId)->first();
        $this->assertNotNull($row);
        $this->assertSame(50.0, (float) ($row->grassland_pct ?? 0));
        $this->assertSame(50.0, (float) ($row->seafront_pct ?? 0));
    }

    public function test_admin_reset_map_clears_layers_resets_terrain_and_deletes_state_files(): void
    {
        $admin = $this->createUser('map.reset@example.test', 'admin');
        Sanctum::actingAs($admin);

        $nationId = $this->createNation($admin->id, 'Reset Nation');

        DB::table('map_layers')->insert([
            'layer_type' => 'main',
            'image_path' => 'maps/main.png',
            'uploaded_by_user_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nation_terrain_stats')->insert([
            'nation_id' => $nationId,
            'grassland_pct' => 20,
            'mountain_pct' => 20,
            'freshwater_pct' => 20,
            'hills_pct' => 20,
            'desert_pct' => 10,
            'seafront_pct' => 10,
            'square_miles_json' => json_encode(['grassland' => 20, 'seafront' => 10]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('public')->put('maps/editor-state-active.json', json_encode(['width' => 1]));
        Storage::disk('public')->put('maps/editor-state-draft.json', json_encode(['width' => 2]));

        $response = $this->postJson('/api/admin/maps/reset');
        $response->assertOk();
        $response->assertJsonPath('message', 'Map reset completed');

        $this->assertSame(0, DB::table('map_layers')->count());

        $updated = DB::table('nation_terrain_stats')->where('nation_id', $nationId)->first();
        $this->assertSame(0.0, (float) ($updated->grassland_pct ?? -1));
        $this->assertSame(0.0, (float) ($updated->seafront_pct ?? -1));

        $squareMiles = json_decode((string) ($updated->square_miles_json ?? '{}'), true) ?: [];
        $this->assertSame(0.0, (float) ($squareMiles['water'] ?? -1));
        $this->assertSame(0.0, (float) ($squareMiles['seafront'] ?? -1));

        $this->assertFalse(Storage::disk('public')->exists('maps/editor-state-active.json'));
        $this->assertFalse(Storage::disk('public')->exists('maps/editor-state-draft.json'));
    }

    private function createUser(string $email, string $role): User
    {
        return User::query()->create([
            'name' => strstr($email, '@', true) ?: $email,
            'email' => $email,
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    private function createNation(int $ownerUserId, string $name): int
    {
        return (int) DB::table('nations')->insertGetId([
            'owner_user_id' => $ownerUserId,
            'name' => $name,
            'leader_name' => 'Leader',
            'alliance_name' => 'Alliance',
            'about_text' => 'About',
            'is_placeholder' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCoreSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('player');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('nations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('name');
            $table->string('leader_name')->nullable();
            $table->string('alliance_name')->nullable();
            $table->text('about_text')->nullable();
            $table->boolean('is_placeholder')->default(false);
            $table->timestamps();
        });

        Schema::create('map_layers', function (Blueprint $table) {
            $table->id();
            $table->string('layer_type')->unique();
            $table->string('image_path');
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('nation_terrain_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('nation_id')->primary();
            $table->double('grassland_pct')->default(0);
            $table->double('mountain_pct')->default(0);
            $table->double('freshwater_pct')->default(0);
            $table->double('hills_pct')->default(0);
            $table->double('desert_pct')->default(0);
            $table->double('seafront_pct')->default(0);
            $table->text('square_miles_json')->nullable();
            $table->timestamps();
        });
    }
}
