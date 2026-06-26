<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiEndpointsAuthenticatedSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $player;

    private int $nationId;
    private int $chatId;
    private int $notificationId;
    private int $unitId;
    private int $shopCategoryId;
    private int $shopItemId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        $this->ensureSupportSchema();
        $this->seedBaselineData();
    }

    public function test_player_can_hit_all_non_admin_authenticated_endpoints_without_server_errors(): void
    {
        $routes = $this->authenticatedRoutes(false);
        $this->assertNotEmpty($routes, 'No authenticated non-admin API routes were discovered.');

        foreach ($routes as $route) {
            if ($this->shouldSkipRoute((string) $route->uri())) {
                continue;
            }
            $this->exerciseRoute($route, $this->player, false);
        }
    }

    public function test_admin_can_hit_all_authenticated_endpoints_without_server_errors(): void
    {
        $routes = $this->authenticatedRoutes(true);
        $this->assertNotEmpty($routes, 'No authenticated API routes were discovered.');

        foreach ($routes as $route) {
            if ($this->shouldSkipRoute((string) $route->uri())) {
                continue;
            }
            $this->exerciseRoute($route, $this->admin, true);
        }
    }

    private function shouldSkipRoute(string $uri): bool
    {
        return false;
    }

    private function authenticatedRoutes(bool $includeAdmin): array
    {
        return array_values(array_filter(Route::getRoutes()->getRoutes(), function ($route) use ($includeAdmin) {
            $uri = (string) $route->uri();
            if (!str_starts_with($uri, 'api/')) {
                return false;
            }

            $middleware = $route->gatherMiddleware();
            if (!in_array('auth:sanctum', $middleware, true)) {
                return false;
            }

            if (!$includeAdmin && in_array('role:admin', $middleware, true)) {
                return false;
            }

            return true;
        }));
    }

    private function exerciseRoute($route, User $actor, bool $isAdmin): void
    {
        Sanctum::actingAs($actor);

        $method = $this->primaryMethod($route->methods());
        if ($method === null) {
            return;
        }

        $uriTemplate = (string) $route->uri();
        $uri = '/' . ltrim($this->sampleUri($uriTemplate), '/');
        $payload = $this->samplePayload($method, $uriTemplate, $isAdmin);

        DB::beginTransaction();
        try {
            $response = $this->json($method, $uri, $payload);
            $status = $response->getStatusCode();

            $this->assertTrue(
                $status < 500,
                sprintf('%s %s returned %d with body: %s', $method, $uri, $status, $response->getContent())
            );

            $this->assertTrue(
                $this->isExpectedApiStatus($status),
                sprintf('%s %s returned unexpected status %d with body: %s', $method, $uri, $status, $response->getContent())
            );

            $this->assertRouteContract($uri, $status, (string) $response->getContent());
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    private function primaryMethod(array $methods): ?string
    {
        foreach ($methods as $method) {
            if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                continue;
            }
            return $method;
        }

        return null;
    }

    private function isExpectedApiStatus(int $status): bool
    {
        if ($status >= 200 && $status < 300) {
            return true;
        }

        return in_array($status, [401, 403, 404, 405, 409, 410, 412, 415, 422], true);
    }

    private function assertRouteContract(string $uri, int $status, string $content): void
    {
        if ($status !== 200) {
            return;
        }

        $jsonArrayRoutes = [
            '/api/me/combat/orders',
            '/api/admin/combat/orders',
            '/api/shop/items',
            '/api/admin/notifications',
        ];

        if (!in_array($uri, $jsonArrayRoutes, true)) {
            return;
        }

        $decoded = json_decode($content, true);
        $this->assertIsArray($decoded, sprintf('%s should return a JSON array', $uri));
    }

    private function sampleUri(string $uri): string
    {
        return (string) preg_replace_callback('/\{[^}]+\}/', function ($match) {
            $token = strtolower(trim((string) $match[0], '{}?'));

            if (str_contains($token, 'layertype')) {
                return 'main';
            }
            if (str_contains($token, 'code')) {
                return 'elves';
            }
            if (str_contains($token, 'chatid')) {
                return (string) $this->chatId;
            }
            if (str_contains($token, 'nationid')) {
                return (string) $this->nationId;
            }
            if (str_contains($token, 'notificationid')) {
                return (string) $this->notificationId;
            }
            if (str_contains($token, 'nationunitid')) {
                return (string) $this->unitId;
            }
            if (str_contains($token, 'userid')) {
                return (string) $this->player->id;
            }
            if (str_contains($token, 'itemid')) {
                return (string) $this->shopItemId;
            }
            if (str_contains($token, 'structureid')) {
                return '1';
            }
            if (str_contains($token, 'unlockid')) {
                return '1';
            }
            if (str_contains($token, 'requestid')) {
                return '1';
            }

            return '1';
        }, $uri);
    }

    private function samplePayload(string $method, string $uriTemplate, bool $isAdmin): array
    {
        $uri = strtolower($uriTemplate);
        $verb = strtoupper($method);

        if ($verb === 'GET') {
            return [];
        }

        if (str_contains($uri, 'auth/logout')) {
            return [];
        }
        if (str_contains($uri, 'auth/password')) {
            return [
                'current_password' => 'password123',
                'password' => 'Newpassword1',
                'password_confirmation' => 'Newpassword1',
            ];
        }
        if (str_contains($uri, 'me/about')) {
            return ['about_text' => 'smoke'];
        }
        if (str_contains($uri, 'me/settings')) {
            return ['chat_text_size' => 'md'];
        }
        if (str_contains($uri, 'me/units/') && str_contains($uri, '/name')) {
            return ['custom_name' => 'Smoke'];
        }
        if (str_contains($uri, 'me/combat/orders') && $verb === 'POST') {
            return ['target_nation_id' => $this->nationId, 'orders_text' => 'Hold'];
        }
        if (str_contains($uri, 'announcements') && $verb === 'POST') {
            return ['body' => 'Smoke announcement'];
        }
        if (str_contains($uri, 'maps/layers/') && $verb === 'POST') {
            return ['image_path' => 'maps/main.png'];
        }
        if (str_contains($uri, 'maps/editor-state') && $verb === 'POST' && str_contains($uri, 'activate')) {
            return [];
        }
        if (str_contains($uri, 'maps/editor-state') && $verb === 'POST') {
            return [
                'width' => 1200,
                'height' => 700,
                'terrain_strokes' => [],
                'political_nations' => [],
                'political_strokes' => [],
            ];
        }
        if (str_contains($uri, 'maps/terrain-stats/bulk-sync')) {
            return ['nation_stats' => []];
        }
        if (str_contains($uri, 'chats') && str_ends_with($uri, 'api/chats') && $verb === 'POST') {
            return [
                'name' => $isAdmin ? 'Admin Chat' : 'Player Chat',
                'type' => $isAdmin ? 'group' : 'group',
                'member_ids' => [(int) $this->player->id],
            ];
        }
        if (str_contains($uri, '/messages') && $verb === 'POST') {
            return ['message' => 'Smoke message'];
        }
        if (str_contains($uri, 'exchange-requests') && $verb === 'POST' && !str_contains($uri, '/accept') && !str_contains($uri, '/refuse')) {
            return [
                'mode' => 'direct_exchange',
                'recipient_nation_id' => $this->nationId,
                'offer' => [],
                'receive' => [],
            ];
        }
        if (str_contains($uri, '/exchange-requests/') && ($verb === 'POST' || $verb === 'DELETE')) {
            return [];
        }
        if (str_contains($uri, 'shop/buy')) {
            return ['item_id' => $this->shopItemId, 'qty' => 1];
        }
        if (str_contains($uri, '/admin/users') && $verb === 'POST') {
            return [
                'name' => 'Smoke User',
                'email' => 'smoke.user+' . uniqid() . '@example.test',
                'password' => 'Newpassword1',
                'role' => 'player',
            ];
        }
        if (str_contains($uri, '/admin/users/') && str_contains($uri, '/password') && $verb === 'PATCH') {
            return ['password' => 'Newpassword1', 'password_confirmation' => 'Newpassword1'];
        }
        if (str_contains($uri, '/admin/users/') && $verb === 'DELETE') {
            return ['confirmation_name' => (string) $this->player->name];
        }
        if (str_contains($uri, '/admin/nations') && $verb === 'POST') {
            return ['name' => 'Placeholder Nation'];
        }
        if (str_contains($uri, '/admin/nations/') && $verb === 'PUT') {
            return ['name' => 'Renamed'];
        }
        if (str_contains($uri, '/admin/nations/') && str_contains($uri, '/units') && $verb === 'POST') {
            return ['unit_code' => 'smoke_unit', 'qty' => 1];
        }
        if (str_contains($uri, '/admin/nations/') && str_contains($uri, '/buildings') && $verb === 'POST') {
            return ['building_code' => 'smoke_building', 'level' => 1];
        }
        if (str_contains($uri, '/admin/combat/orders/') && str_contains($uri, '/status')) {
            return ['order_status' => 'pending'];
        }
        if (str_contains($uri, '/admin/combat/units/') && str_contains($uri, '/stats')) {
            return ['stats_override_json' => []];
        }
        if (str_contains($uri, '/admin/combat/rating-config') && $verb === 'PUT') {
            return ['formula_expression' => 'ATK+DEF', 'apply_confirmation' => 'APPLY RATING FORMULA'];
        }
        if (str_contains($uri, '/admin/combat/rating-config/preview')) {
            return ['formula_expression' => 'ATK+DEF', 'stats' => []];
        }
        if (str_contains($uri, '/admin/time-tracker') && $verb === 'PATCH') {
            return ['auto_increment_enabled' => false];
        }
        if (str_contains($uri, '/admin/time-tracker/next-year')) {
            return ['apply_effects' => false];
        }
        if (str_contains($uri, '/admin/time-tracker/pause')) {
            return ['pause_note' => 'smoke'];
        }
        if (str_contains($uri, '/admin/resource-topbar-config') && $verb === 'PUT') {
            return ['global' => [['type' => 'base', 'name' => 'cow']]];
        }
        if (str_contains($uri, '/admin/map-settings') && $verb === 'PATCH') {
            return ['map_max_zoom_pct' => 180];
        }
        if (str_contains($uri, '/admin/developer/logs') && $verb === 'POST') {
            return ['level' => 'info', 'summary' => 'smoke'];
        }
        if (str_contains($uri, '/admin/developer/log-settings') && $verb === 'PUT') {
            return ['enabled' => true];
        }
        if (str_contains($uri, '/admin/developer/cleanup-zombie-data')) {
            return ['dry_run' => true];
        }
        if (str_contains($uri, '/admin/shop/items') && $verb === 'POST') {
            return [
                'category_id' => $this->shopCategoryId,
                'code' => 'smoke_item_admin',
                'display_name' => 'Smoke Item',
                'cost_json' => [],
            ];
        }
        if (str_contains($uri, '/admin/shop/items/') && $verb === 'PUT') {
            return ['display_name' => 'Updated Smoke Item'];
        }
        if (str_contains($uri, '/admin/visibility/rules') && $verb === 'PUT') {
            return [
                'viewer_user_id' => (string) $this->player->id,
                'subject_user_id' => 'All',
                'field_key' => 'units',
                'is_allowed' => false,
            ];
        }
        if (str_contains($uri, '/admin/game-documents') && $verb === 'POST') {
            return ['title' => 'Smoke Doc', 'content_text' => 'Body'];
        }
        if (str_contains($uri, '/admin/game-documents/') && str_contains($uri, '/visibility') && $verb === 'PUT') {
            return ['visibility_type' => 'admin'];
        }
        if (str_contains($uri, '/admin/game-documents/') && $verb === 'PUT') {
            return ['content_text' => 'Updated body'];
        }
        if (str_contains($uri, '/admin/structures') && $verb === 'POST') {
            return ['code' => 'smoke_struct', 'display_name' => 'Smoke Structure'];
        }
        if (str_contains($uri, '/admin/structures/') && $verb === 'PATCH') {
            return ['display_name' => 'Updated Structure'];
        }
        if (str_contains($uri, '/admin/structures/') && $verb === 'DELETE') {
            return ['confirm_code' => 'smoke_struct'];
        }

        return [];
    }

    private function seedBaselineData(): void
    {
        $this->admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin.route.smoke@example.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->player = User::query()->create([
            'name' => 'Player User',
            'email' => 'player.route.smoke@example.test',
            'password' => 'password123',
            'role' => 'player',
        ]);

        $this->nationId = (int) DB::table('nations')->insertGetId([
            'owner_user_id' => $this->player->id,
            'name' => 'Smoke Nation',
            'leader_name' => 'Smoke Leader',
            'alliance_name' => 'Smoke Alliance',
            'about_text' => 'Smoke about',
            'is_placeholder' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nation_resources')->insert([
            'nation_id' => $this->nationId,
            'cow' => 10,
            'wood' => 10,
            'ore' => 10,
            'food' => 10,
            'extra_json' => json_encode([
                'base' => [],
                'advanced' => [],
                'currencies' => ['gb' => 100],
            ]),
            'updated_at' => now(),
        ]);

        DB::table('nation_terrain_stats')->insert([
            'nation_id' => $this->nationId,
            'square_miles_json' => json_encode([]),
            'updated_at' => now(),
        ]);

        $this->chatId = (int) DB::table('chats')->insertGetId([
            'name' => 'Smoke Chat',
            'type' => 'group',
            'created_by_user_id' => $this->player->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chat_members')->insert([
            'chat_id' => $this->chatId,
            'user_id' => $this->player->id,
            'deleted_at' => null,
            'archived_at' => null,
            'last_read_message_id' => 0,
        ]);

        DB::table('chat_messages')->insert([
            'chat_id' => $this->chatId,
            'sender_user_id' => $this->player->id,
            'message' => 'baseline',
            'created_at' => now(),
        ]);

        DB::table('resource_definitions')->insert([
            ['type' => 'base', 'group' => 'primary', 'group_order' => 1, 'order' => 1, 'name' => 'cow', 'display_name' => 'COW', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'base', 'group' => 'primary', 'group_order' => 1, 'order' => 2, 'name' => 'wood', 'display_name' => 'WOOD', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'base', 'group' => 'primary', 'group_order' => 1, 'order' => 3, 'name' => 'ore', 'display_name' => 'ORE', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'base', 'group' => 'primary', 'group_order' => 1, 'order' => 4, 'name' => 'food', 'display_name' => 'FOOD', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'advanced', 'group' => 'magic', 'group_order' => 2, 'order' => 1, 'name' => 'mana', 'display_name' => 'MANA', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->shopCategoryId = (int) DB::table('shop_categories')->insertGetId([
            'code' => 'smoke_cat',
            'display_name' => 'Smoke Category',
        ]);

        $this->shopItemId = (int) DB::table('shop_items')->insertGetId([
            'category_id' => $this->shopCategoryId,
            'code' => 'smoke_item',
            'display_name' => 'Smoke Item',
            'cost_json' => json_encode(['currencies:gb' => 1]),
            'description_text' => 'Smoke item',
            'is_active' => 1,
            'effect_json' => json_encode([]),
            'maintenance_json' => json_encode([]),
            'yearly_effect_json' => json_encode([]),
            'requirement_json' => json_encode([]),
            'visibility_json' => null,
        ]);

        $this->notificationId = (int) DB::table('admin_notifications')->insertGetId([
            'type' => 'combat_order',
            'title' => 'Smoke Combat Order',
            'body' => 'Smoke body',
            'meta_json' => json_encode(['actor_user_id' => $this->player->id]),
            'is_read' => 0,
            'created_at' => now(),
        ]);

        $this->unitId = (int) DB::table('nation_units')->insertGetId([
            'nation_id' => $this->nationId,
            'unit_catalog_id' => null,
            'custom_name' => 'Smoke Unit',
            'qty' => 1,
            'stats_override_json' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureSupportSchema(): void
    {
        if (!Schema::hasTable('admin_notifications')) {
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
            });
        }

        if (!Schema::hasTable('player_visibility_rules')) {
            Schema::create('player_visibility_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('viewer_user_id');
                $table->unsignedBigInteger('subject_user_id');
                $table->string('field_key', 80);
                $table->boolean('is_allowed')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('game_time')) {
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
        }

        if (!Schema::hasTable('game_time_pause_history')) {
            Schema::create('game_time_pause_history', function (Blueprint $table) {
                $table->id();
                $table->timestamp('paused_at');
                $table->timestamp('resumed_at')->nullable();
                $table->unsignedBigInteger('paused_by_user_id')->nullable();
                $table->unsignedBigInteger('resumed_by_user_id')->nullable();
                $table->text('pause_note')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('nation_assets')) {
            Schema::create('nation_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('nation_id');
                $table->unsignedBigInteger('shop_item_id');
                $table->unsignedInteger('qty')->default(1);
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('game_documents')) {
            Schema::create('game_documents', function (Blueprint $table) {
                $table->string('code', 80)->primary();
                $table->string('title', 200);
                $table->longText('content_text')->nullable();
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (!Schema::hasTable('game_document_visibility')) {
            Schema::create('game_document_visibility', function (Blueprint $table) {
                $table->id();
                $table->string('document_code', 80)->unique();
                $table->string('visibility_type', 40)->default('admin');
                $table->string('role_name', 40)->nullable();
                $table->text('player_ids')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (Schema::hasTable('chat_members') && !Schema::hasColumn('chat_members', 'last_read_message_id')) {
            Schema::table('chat_members', function (Blueprint $table) {
                $table->unsignedBigInteger('last_read_message_id')->nullable()->default(0);
            });
        }

        if (Schema::hasTable('shop_items')) {
            $missing = [
                'description_text' => function (Blueprint $table) {
                    $table->text('description_text')->nullable();
                },
                'maintenance_json' => function (Blueprint $table) {
                    $table->json('maintenance_json')->nullable();
                },
                'yearly_effect_json' => function (Blueprint $table) {
                    $table->json('yearly_effect_json')->nullable();
                },
                'visibility_json' => function (Blueprint $table) {
                    $table->json('visibility_json')->nullable();
                },
            ];

            foreach ($missing as $column => $adder) {
                if (!Schema::hasColumn('shop_items', $column)) {
                    Schema::table('shop_items', $adder);
                }
            }
        }
    }
}
