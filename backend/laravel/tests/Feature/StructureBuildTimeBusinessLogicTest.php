<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StructureBuildTimeBusinessLogicTest extends TestCase
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

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_admin_structure_update_persists_build_time_map_and_syncs_shop_descriptions(): void
    {
        $admin = $this->createUser('admin.buildtime.update@example.test', 'admin');
        Sanctum::actingAs($admin);

        $catalogId = $this->seedStructureCatalogAndShopLevels();

        $response = $this->patchJson('/api/admin/structures/' . $catalogId, [
            'display_name' => 'Farm',
            'max_level' => 2,
            'list_order' => 1,
            'yearly_production_json' => [
                '1' => ['base:cow' => 5],
                '2' => ['base:cow' => 9],
            ],
            'yearly_maintenance_json' => [],
            'terrain_requirement_json' => [],
            'build_time_years_json' => [
                '1' => 2,
                '2' => 3,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('structure.build_time_years_json.1', 2);
        $response->assertJsonPath('structure.build_time_years_json.2', 3);

        $savedMap = json_decode((string) DB::table('building_catalog')->where('id', $catalogId)->value('build_time_years_json'), true) ?: [];
        $this->assertSame(2, (int) ($savedMap['1'] ?? -1));
        $this->assertSame(3, (int) ($savedMap['2'] ?? -1));

        $levelTwoDescription = (string) DB::table('shop_items')->where('code', 'struct_farm_l2')->value('description_text');
        $this->assertStringContainsString('Build time: 3 game years.', $levelTwoDescription);
    }

    public function test_shop_items_expose_build_time_for_structure_levels(): void
    {
        $admin = $this->createUser('admin.buildtime.items@example.test', 'admin');
        $this->seedStructureCatalogAndShopLevels();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/shop/items?category=build');
        $response->assertOk();

        $items = $response->json() ?: [];
        $levelOne = collect($items)->firstWhere('code', 'struct_farm_l1');
        $levelTwo = collect($items)->firstWhere('code', 'struct_farm_l2');

        $this->assertNotNull($levelOne);
        $this->assertNotNull($levelTwo);
        $this->assertSame(2, (int) ($levelOne['build_time_years_for_level'] ?? -1));
        $this->assertSame(1, (int) ($levelTwo['build_time_years_for_level'] ?? -1));
    }

    public function test_buy_structure_creates_constructing_row_and_gates_yearly_benefits_until_completion(): void
    {
        $admin = $this->createUser('admin.buildtime.gate@example.test', 'admin');
        $player = $this->createUser('player.buildtime.gate@example.test', 'player');
        $nationId = $this->createNation($player->id, 'Construction Nation');
        $this->seedNationResourceState($nationId);
        $this->seedStructureCatalogAndShopLevels();

        Sanctum::actingAs($player);
        $buyResponse = $this->postJson('/api/shop/buy', [
            'item_id' => (int) DB::table('shop_items')->where('code', 'struct_farm_l1')->value('id'),
            'quantity' => 1,
        ]);
        $buyResponse->assertOk();

        $building = DB::table('nation_buildings')->where('nation_id', $nationId)->first();
        $this->assertNotNull($building);
        $this->assertSame('constructing', (string) $building->status);
        $this->assertSame(2, (int) ($building->completes_on_game_year ?? 0));

        $dashboard = $this->getJson('/api/me/dashboard');
        $dashboard->assertOk();
        $this->assertSame(0.0, (float) data_get($dashboard->json(), 'yearly_projection.income.base.cow', 0));

        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/time-tracker/next-year', ['apply_effects' => true])->assertOk();
        $afterYearOne = DB::table('nation_buildings')->where('nation_id', $nationId)->first();
        $this->assertSame('constructing', (string) $afterYearOne->status);

        $this->postJson('/api/admin/time-tracker/next-year', ['apply_effects' => true])->assertOk();
        $afterYearTwo = DB::table('nation_buildings')->where('nation_id', $nationId)->first();
        $this->assertSame('built', (string) $afterYearTwo->status);
        $this->assertNull($afterYearTwo->completes_on_game_year);

        $resourcesAfterTwoYears = DB::table('nation_resources')->where('nation_id', $nationId)->first();
        $this->assertSame(0.0, (float) ($resourcesAfterTwoYears->cow ?? 0));

        $this->postJson('/api/admin/time-tracker/next-year', ['apply_effects' => true])->assertOk();
        $resourcesAfterThreeYears = DB::table('nation_resources')->where('nation_id', $nationId)->first();
        $this->assertSame(5.0, (float) ($resourcesAfterThreeYears->cow ?? 0));
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

    private function seedNationResourceState(int $nationId): void
    {
        DB::table('nation_resources')->insert([
            'nation_id' => $nationId,
            'cow' => 0,
            'wood' => 0,
            'ore' => 0,
            'food' => 0,
            'extra_json' => json_encode([
                'base' => [],
                'advanced' => [],
                'currencies' => [],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nation_terrain_stats')->insert([
            'nation_id' => $nationId,
            'square_miles_json' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedStructureCatalogAndShopLevels(): int
    {
        $buildCategoryId = DB::table('shop_categories')->insertGetId([
            'code' => 'build',
            'display_name' => 'Build',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shop_categories')->insert([
            'code' => 'craft',
            'display_name' => 'Craft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('shop_categories')->insert([
            'code' => 'recruit',
            'display_name' => 'Recruit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('shop_categories')->insert([
            'code' => 'research',
            'display_name' => 'Research',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $catalogId = (int) DB::table('building_catalog')->insertGetId([
            'code' => 'farm',
            'display_name' => 'Farm',
            'max_level' => 2,
            'list_order' => 1,
            'yearly_production_json' => json_encode([
                '1' => ['base:cow' => 5],
                '2' => ['base:cow' => 9],
            ]),
            'yearly_maintenance_json' => json_encode([]),
            'terrain_requirement_json' => json_encode([]),
            'build_time_years_json' => json_encode([
                '1' => 2,
                '2' => 1,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('shop_items')->insert([
            [
                'category_id' => $buildCategoryId,
                'code' => 'struct_farm_l1',
                'display_name' => 'Farm (L1)',
                'description_text' => 'Adds one level 1 Farm structure.',
                'cost_json' => json_encode(new \stdClass()),
                'effect_json' => json_encode(new \stdClass()),
                'requirement_json' => null,
                'maintenance_json' => json_encode(new \stdClass()),
                'yearly_effect_json' => json_encode(['base:cow' => 5]),
                'visibility_json' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'category_id' => $buildCategoryId,
                'code' => 'struct_farm_l2',
                'display_name' => 'Farm (L2)',
                'description_text' => 'Upgrades one Farm structure to level 2.',
                'cost_json' => json_encode(new \stdClass()),
                'effect_json' => json_encode(new \stdClass()),
                'requirement_json' => json_encode([
                    'type' => 'structure',
                    'code' => 'farm',
                    'level' => 1,
                ]),
                'maintenance_json' => json_encode(new \stdClass()),
                'yearly_effect_json' => json_encode(['base:cow' => 9]),
                'visibility_json' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return $catalogId;
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

        Schema::create('nation_resources', function (Blueprint $table) {
            $table->unsignedBigInteger('nation_id')->primary();
            $table->double('cow')->default(0);
            $table->double('wood')->default(0);
            $table->double('ore')->default(0);
            $table->double('food')->default(0);
            $table->text('extra_json')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
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
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('building_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('display_name');
            $table->unsignedInteger('max_level')->default(10);
            $table->unsignedInteger('list_order')->default(0);
            $table->text('yearly_production_json')->nullable();
            $table->text('yearly_maintenance_json')->nullable();
            $table->text('terrain_requirement_json')->nullable();
            $table->text('build_time_years_json')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('nation_buildings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nation_id');
            $table->unsignedBigInteger('building_catalog_id');
            $table->unsignedInteger('level')->default(1);
            $table->string('status')->default('built');
            $table->string('terrain_type')->nullable();
            $table->double('terrain_allocated_square_miles')->default(0);
            $table->timestamp('finishes_at')->nullable();
            $table->unsignedInteger('completes_on_game_year')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('display_name');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('code')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->text('description_text')->nullable();
            $table->text('cost_json')->nullable();
            $table->text('effect_json')->nullable();
            $table->text('requirement_json')->nullable();
            $table->text('maintenance_json')->nullable();
            $table->text('yearly_effect_json')->nullable();
            $table->text('visibility_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('nation_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nation_id');
            $table->unsignedBigInteger('shop_item_id');
            $table->integer('qty')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->text('meta_json')->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('order_status')->nullable();
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('game_time', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('year_started_at')->nullable();
            $table->unsignedInteger('seconds_per_year')->default(172800);
            $table->unsignedInteger('processed_years')->default(0);
            $table->double('elapsed_hours_in_year')->default(0);
            $table->boolean('auto_increment_enabled')->default(true);
            $table->boolean('is_paused')->default(false);
            $table->timestamp('paused_at')->nullable();
            $table->integer('year_label_offset')->default(0);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('unit_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('display_name')->nullable();
            $table->string('class_name')->nullable();
        });

        Schema::create('nation_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nation_id');
            $table->unsignedBigInteger('unit_catalog_id')->nullable();
            $table->integer('qty')->default(1);
            $table->string('status')->default('owned');
            $table->string('custom_name')->nullable();
            $table->text('stats_override_json')->nullable();
            $table->timestamp('training_ready_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
}
