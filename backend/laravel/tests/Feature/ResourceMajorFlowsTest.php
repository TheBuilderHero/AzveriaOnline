<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResourceMajorFlowsTest extends TestCase
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

    public function test_me_resources_reads_currencies_only_from_canonical_bucket(): void
    {
        $user = $this->createUser('player1@example.test', 'player');
        $nationId = $this->createNation($user->id, 'Player One Nation');

        $this->insertResourceDefinitions();

        DB::table('nation_resources')->insert([
            'nation_id' => $nationId,
            'cow' => 10,
            'wood' => 0,
            'ore' => 0,
            'food' => 0,
            'extra_json' => json_encode([
                'base' => ['wood' => 7],
                'advanced' => [],
                'currencies' => ['gb' => 50],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me/resources');

        $response->assertOk();
        $this->assertSame(50.0, (float) data_get($response->json(), 'currencies.gb', 0));
        $response->assertJsonPath('base.gb', null);
        $this->assertSame(7.0, (float) data_get($response->json(), 'base.wood', 0));
    }

    public function test_nation_show_hides_all_base_resources_when_visibility_forbids_it(): void
    {
        $viewer = $this->createUser('viewer@example.test', 'player');
        $subject = $this->createUser('subject@example.test', 'player');
        $subjectNationId = $this->createNation($subject->id, 'Subject Nation');

        DB::table('nation_resources')->insert([
            'nation_id' => $subjectNationId,
            'cow' => 12,
            'wood' => 34,
            'ore' => 56,
            'food' => 78,
            'extra_json' => json_encode([
                'base' => ['custom_base' => 99],
                'advanced' => ['RM' => 2],
                'currencies' => ['gb' => 5],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('player_visibility_rules')->insert([
            'viewer_user_id' => $viewer->id,
            'subject_user_id' => $subject->id,
            'field_key' => 'resources_base',
            'is_allowed' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/nations/' . $subjectNationId);

        $response->assertOk();
        $response->assertJsonPath('resources.cow', null);
        $response->assertJsonPath('resources.wood', null);
        $response->assertJsonPath('resources.ore', null);
        $response->assertJsonPath('resources.food', null);

        $extra = json_decode((string) data_get($response->json(), 'resources.extra_json', '{}'), true) ?: [];
        $this->assertSame([], $extra['base'] ?? null);
    }

    public function test_direct_exchange_transfers_currency_and_base_resources_correctly(): void
    {
        $sender = $this->createUser('sender@example.test', 'player');
        $receiver = $this->createUser('receiver@example.test', 'player');
        $senderNationId = $this->createNation($sender->id, 'Sender Nation');
        $receiverNationId = $this->createNation($receiver->id, 'Receiver Nation');

        $this->insertResourceDefinitions();

        DB::table('nation_resources')->insert([
            'nation_id' => $senderNationId,
            'cow' => 0,
            'wood' => 0,
            'ore' => 0,
            'food' => 0,
            'extra_json' => json_encode([
                'base' => [],
                'advanced' => [],
                'currencies' => ['gb' => 100],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nation_resources')->insert([
            'nation_id' => $receiverNationId,
            'cow' => 0,
            'wood' => 10,
            'ore' => 0,
            'food' => 0,
            'extra_json' => json_encode([
                'base' => [],
                'advanced' => [],
                'currencies' => ['gb' => 0],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $chatId = DB::table('chats')->insertGetId([
            'name' => 'Global Diplomacy',
            'type' => 'global',
            'created_by_user_id' => $sender->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($sender);
        $create = $this->postJson('/api/chats/' . $chatId . '/exchange-requests', [
            'mode' => 'direct_exchange',
            'recipient_nation_id' => $receiverNationId,
            'offer' => [
                ['type' => 'currencies', 'name' => 'gb', 'amount' => 25],
            ],
            'receive' => [
                ['type' => 'base', 'name' => 'wood', 'amount' => 1],
            ],
        ]);

        $create->assertCreated();
        $requestId = (int) data_get($create->json(), 'id');

        Sanctum::actingAs($receiver);
        $accept = $this->postJson('/api/chats/' . $chatId . '/exchange-requests/' . $requestId . '/accept');
        $accept->assertOk();

        $senderRes = DB::table('nation_resources')->where('nation_id', $senderNationId)->first();
        $receiverRes = DB::table('nation_resources')->where('nation_id', $receiverNationId)->first();

        $senderExtra = json_decode((string) ($senderRes->extra_json ?? '{}'), true) ?: [];
        $receiverExtra = json_decode((string) ($receiverRes->extra_json ?? '{}'), true) ?: [];

        $this->assertSame(75.0, (float) ($senderExtra['currencies']['gb'] ?? 0));
        $this->assertSame(25.0, (float) ($receiverExtra['currencies']['gb'] ?? 0));
        $this->assertSame(1.0, (float) ($senderRes->wood ?? 0));
        $this->assertSame(9.0, (float) ($receiverRes->wood ?? 0));

        $status = DB::table('chat_exchange_requests')->where('id', $requestId)->value('status');
        $this->assertSame('accepted', $status);
    }

    public function test_dashboard_projection_includes_currency_income_rows(): void
    {
        $user = $this->createUser('dash@example.test', 'player');
        $nationId = $this->createNation($user->id, 'Dashboard Nation');

        DB::table('nation_resources')->insert([
            'nation_id' => $nationId,
            'cow' => 0,
            'wood' => 0,
            'ore' => 0,
            'food' => 0,
            'extra_json' => json_encode([
                'base' => [],
                'advanced' => [],
                'currencies' => ['gb' => 3],
                'income_resources' => [
                    ['type' => 'currencies', 'name' => 'gb', 'amount' => 7],
                ],
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

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/me/dashboard');

        $response->assertOk();
        $this->assertSame(7.0, (float) data_get($response->json(), 'yearly_projection.income.currencies.gb', 0));
        $this->assertSame(7.0, (float) data_get($response->json(), 'yearly_projection.net.currencies.gb', 0));
    }

    public function test_shop_buy_returns_422_when_canonical_currency_balance_is_insufficient(): void
    {
        $player = $this->createUser('shop-low@example.test', 'player');
        $nationId = $this->createNation($player->id, 'Shop Low Nation');

        $this->insertResourceDefinitions();

        DB::table('nation_resources')->insert([
            'nation_id' => $nationId,
            'cow' => 0,
            'wood' => 0,
            'ore' => 0,
            'food' => 0,
            'extra_json' => json_encode([
                'base' => [],
                'advanced' => [],
                'currencies' => ['gb' => 5],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('shop_categories')->insertGetId([
            'code' => 'craft',
            'display_name' => 'Craft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = DB::table('shop_items')->insertGetId([
            'code' => 'not_enough_currency_item',
            'display_name' => 'Not Enough Currency Item',
            'description' => 'Costs more than owned',
            'category_id' => $categoryId,
            'cost_json' => json_encode(['currencies:gb' => 10]),
            'effect_json' => json_encode([]),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($player);

        $response = $this->postJson('/api/shop/buy', [
            'item_id' => $itemId,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Not enough currency: gb');
    }

    public function test_exchange_request_visibility_differs_for_public_direct_and_admin_archived(): void
    {
        $sender = $this->createUser('sender-vis@example.test', 'player');
        $recipient = $this->createUser('recipient-vis@example.test', 'player');
        $outsider = $this->createUser('outsider-vis@example.test', 'player');
        $admin = $this->createUser('admin-vis@example.test', 'admin');

        $senderNationId = $this->createNation($sender->id, 'Sender Vis Nation');
        $recipientNationId = $this->createNation($recipient->id, 'Recipient Vis Nation');
        $outsiderNationId = $this->createNation($outsider->id, 'Outsider Vis Nation');
        $adminNationId = $this->createNation($admin->id, 'Admin Vis Nation');

        DB::table('nation_resources')->insert([
            ['nation_id' => $senderNationId, 'cow' => 0, 'wood' => 50, 'ore' => 0, 'food' => 0, 'extra_json' => json_encode(['base' => [], 'advanced' => [], 'currencies' => ['gb' => 100]]), 'created_at' => now(), 'updated_at' => now()],
            ['nation_id' => $recipientNationId, 'cow' => 0, 'wood' => 50, 'ore' => 0, 'food' => 0, 'extra_json' => json_encode(['base' => [], 'advanced' => [], 'currencies' => ['gb' => 100]]), 'created_at' => now(), 'updated_at' => now()],
            ['nation_id' => $outsiderNationId, 'cow' => 0, 'wood' => 50, 'ore' => 0, 'food' => 0, 'extra_json' => json_encode(['base' => [], 'advanced' => [], 'currencies' => ['gb' => 100]]), 'created_at' => now(), 'updated_at' => now()],
            ['nation_id' => $adminNationId, 'cow' => 0, 'wood' => 50, 'ore' => 0, 'food' => 0, 'extra_json' => json_encode(['base' => [], 'advanced' => [], 'currencies' => ['gb' => 100]]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $chatId = DB::table('chats')->insertGetId([
            'name' => 'Visibility Diplomacy',
            'type' => 'global',
            'created_by_user_id' => $sender->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $publicId = DB::table('chat_exchange_requests')->insertGetId([
            'chat_id' => $chatId,
            'sender_user_id' => $sender->id,
            'sender_nation_id' => $senderNationId,
            'recipient_nation_id' => null,
            'offer_json' => json_encode([['type' => 'currencies', 'name' => 'gb', 'amount' => 1]]),
            'receive_json' => json_encode([['type' => 'base', 'name' => 'wood', 'amount' => 1]]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $directId = DB::table('chat_exchange_requests')->insertGetId([
            'chat_id' => $chatId,
            'sender_user_id' => $sender->id,
            'sender_nation_id' => $senderNationId,
            'recipient_nation_id' => $recipientNationId,
            'offer_json' => json_encode([['type' => 'currencies', 'name' => 'gb', 'amount' => 2]]),
            'receive_json' => json_encode([['type' => 'base', 'name' => 'wood', 'amount' => 2]]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $archivedId = DB::table('chat_exchange_requests')->insertGetId([
            'chat_id' => $chatId,
            'sender_user_id' => $sender->id,
            'sender_nation_id' => $senderNationId,
            'recipient_nation_id' => null,
            'offer_json' => json_encode([['type' => 'currencies', 'name' => 'gb', 'amount' => 3]]),
            'receive_json' => json_encode([['type' => 'base', 'name' => 'wood', 'amount' => 3]]),
            'status' => 'accepted',
            'removed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($outsider);
        $outsiderResponse = $this->getJson('/api/chats/' . $chatId . '/exchange-requests');
        $outsiderResponse->assertOk();
        $outsiderIds = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $outsiderResponse->json() ?: []);
        $this->assertContains($publicId, $outsiderIds);
        $this->assertNotContains($directId, $outsiderIds);
        $this->assertNotContains($archivedId, $outsiderIds);

        Sanctum::actingAs($recipient);
        $recipientResponse = $this->getJson('/api/chats/' . $chatId . '/exchange-requests');
        $recipientResponse->assertOk();
        $recipientIds = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $recipientResponse->json() ?: []);
        $this->assertContains($publicId, $recipientIds);
        $this->assertContains($directId, $recipientIds);
        $this->assertNotContains($archivedId, $recipientIds);

        Sanctum::actingAs($admin);
        $adminNoArchived = $this->getJson('/api/chats/' . $chatId . '/exchange-requests');
        $adminNoArchived->assertOk();
        $adminNoArchivedIds = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $adminNoArchived->json() ?: []);
        $this->assertContains($publicId, $adminNoArchivedIds);
        $this->assertContains($directId, $adminNoArchivedIds);
        $this->assertNotContains($archivedId, $adminNoArchivedIds);

        $adminWithArchived = $this->getJson('/api/chats/' . $chatId . '/exchange-requests?include_archived=1');
        $adminWithArchived->assertOk();
        $adminWithArchivedIds = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $adminWithArchived->json() ?: []);
        $this->assertContains($publicId, $adminWithArchivedIds);
        $this->assertContains($directId, $adminWithArchivedIds);
        $this->assertContains($archivedId, $adminWithArchivedIds);
    }

    public function test_admin_update_nation_updates_core_dynamic_advanced_and_currencies_consistently(): void
    {
        $admin = $this->createUser('admin-update@example.test', 'admin');
        $player = $this->createUser('player-update@example.test', 'player');
        $nationId = $this->createNation($player->id, 'Mutable Nation');

        DB::table('nation_resources')->insert([
            'nation_id' => $nationId,
            'cow' => 1,
            'wood' => 2,
            'ore' => 3,
            'food' => 4,
            'extra_json' => json_encode([
                'base' => ['legacy_key' => 8],
                'advanced' => ['RM' => 1],
                'currencies' => ['gb' => 5],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);
        $response = $this->putJson('/api/admin/nations/' . $nationId, [
            'resources' => [
                'base' => [
                    'wood' => 11,
                    'grain' => 33,
                ],
                'advanced' => [
                    'RM' => 9,
                    'FS' => 2,
                ],
            ],
            'currencies' => [
                'gb' => 12,
                'mk' => 1,
            ],
        ]);

        $response->assertOk();

        $row = DB::table('nation_resources')->where('nation_id', $nationId)->first();
        $extra = json_decode((string) ($row->extra_json ?? '{}'), true) ?: [];

        $this->assertSame(11.0, (float) ($row->wood ?? 0));
        $this->assertSame(33.0, (float) ($extra['base']['grain'] ?? 0));
        $this->assertSame(9.0, (float) ($extra['advanced']['RM'] ?? 0));
        $this->assertSame(2.0, (float) ($extra['advanced']['FS'] ?? 0));
        $this->assertSame(12.0, (float) ($extra['currencies']['gb'] ?? 0));
        $this->assertSame(1.0, (float) ($extra['currencies']['mk'] ?? 0));
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

    private function insertResourceDefinitions(): void
    {
        DB::table('resource_definitions')->insert([
            [
                'name' => 'gb',
                'display_name' => 'Gold Bars',
                'type' => 'base',
                'group' => 'Common',
                'group_order' => 0,
                'order' => 0,
                'meta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'wood',
                'display_name' => 'Wood',
                'type' => 'base',
                'group' => 'Materials',
                'group_order' => 0,
                'order' => 1,
                'meta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
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

        Schema::create('nation_resources', function (Blueprint $table) {
            $table->unsignedBigInteger('nation_id')->primary();
            $table->double('cow')->default(0);
            $table->double('wood')->default(0);
            $table->double('ore')->default(0);
            $table->double('food')->default(0);
            $table->text('extra_json')->nullable();
            $table->timestamps();
        });

        Schema::create('resource_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('type');
            $table->string('group');
            $table->unsignedInteger('group_order')->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('player_visibility_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viewer_user_id');
            $table->unsignedBigInteger('subject_user_id');
            $table->string('field_key');
            $table->boolean('is_allowed')->default(true);
            $table->timestamps();
        });

        Schema::create('nations_unused_guard', function (Blueprint $table) {
            $table->id();
        });
        Schema::drop('nations_unused_guard');

        Schema::create('nation_terrain_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('nation_id')->primary();
            $table->text('square_miles_json')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('display_name')->nullable();
            $table->string('class_name')->nullable();
            $table->text('base_stats_json')->nullable();
            $table->timestamps();
        });

        Schema::create('nation_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nation_id');
            $table->unsignedBigInteger('unit_catalog_id')->nullable();
            $table->integer('qty')->default(1);
            $table->string('status')->default('owned');
            $table->timestamp('training_ready_at')->nullable();
            $table->text('stats_override_json')->nullable();
            $table->timestamps();
        });

        Schema::create('building_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('display_name')->nullable();
            $table->integer('max_level')->default(10);
            $table->timestamps();
        });

            Schema::create('shop_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('display_name');
                $table->timestamps();
            });

        Schema::create('nation_buildings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nation_id');
            $table->unsignedBigInteger('building_catalog_id');
            $table->integer('level')->default(1);
            $table->string('status')->default('built');
            $table->timestamps();
        });

        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('cost_json')->nullable();
            $table->text('effect_json')->nullable();
            $table->text('requirement_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('maintenance_json')->nullable();
            $table->text('yearly_effect_json')->nullable();
            $table->timestamps();
        });

        Schema::create('nation_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nation_id');
            $table->unsignedBigInteger('shop_item_id');
            $table->integer('qty')->default(1);
            $table->timestamps();
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('sender_user_id');
            $table->text('message');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('chat_exchange_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('sender_user_id');
            $table->unsignedBigInteger('sender_nation_id');
            $table->unsignedBigInteger('recipient_nation_id')->nullable();
            $table->text('offer_json');
            $table->text('receive_json');
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('handled_by_user_id')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();
        });
    }
}
