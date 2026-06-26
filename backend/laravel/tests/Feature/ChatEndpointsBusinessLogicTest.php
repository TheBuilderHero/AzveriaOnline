<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatEndpointsBusinessLogicTest extends TestCase
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

    public function test_player_can_create_group_chat_and_memberships_are_created(): void
    {
        $owner = $this->createUser('chat.owner@example.test', 'player');
        $member = $this->createUser('chat.member@example.test', 'player');

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/chats', [
            'name' => 'Diplomacy Group',
            'type' => 'group',
            'member_ids' => [$member->id],
        ]);

        $response->assertCreated();
        $chatId = (int) data_get($response->json(), 'id');

        $this->assertNotSame(0, $chatId);
        $this->assertSame(2, DB::table('chat_members')->where('chat_id', $chatId)->count());
        $this->assertNotNull(DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $owner->id)->first());
        $this->assertNotNull(DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $member->id)->first());
    }

    public function test_non_admin_cannot_create_global_chat(): void
    {
        $player = $this->createUser('chat.player@example.test', 'player');
        Sanctum::actingAs($player);

        $response = $this->postJson('/api/chats', [
            'name' => 'All Players',
            'type' => 'global',
            'member_ids' => [],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Only admins can create chats', (string) data_get($response->json(), 'errors.type.0', ''));
    }

    public function test_global_chat_send_auto_adds_membership_and_revives_deleted_membership(): void
    {
        $player = $this->createUser('chat.global.sender@example.test', 'player');

        $chatId = DB::table('chats')->insertGetId([
            'name' => 'Global',
            'type' => 'global',
            'created_by_user_id' => $player->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chat_members')->insert([
            'chat_id' => $chatId,
            'user_id' => $player->id,
            'deleted_at' => now(),
            'archived_at' => null,
            'last_read_message_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($player);

        $response = $this->postJson('/api/chats/' . $chatId . '/messages', [
            'message' => 'Hello world',
        ]);

        $response->assertCreated();

        $member = DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $player->id)->first();
        $this->assertNotNull($member);
        $this->assertNull($member->deleted_at);

        $messageRow = DB::table('chat_messages')->where('chat_id', $chatId)->first();
        $this->assertSame('Hello world', (string) ($messageRow->message ?? ''));
    }

    public function test_mark_read_and_mark_unread_update_last_read_message_id(): void
    {
        $player = $this->createUser('chat.read@example.test', 'player');

        $chatId = DB::table('chats')->insertGetId([
            'name' => 'Read Test',
            'type' => 'group',
            'created_by_user_id' => $player->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chat_members')->insert([
            'chat_id' => $chatId,
            'user_id' => $player->id,
            'deleted_at' => null,
            'archived_at' => null,
            'last_read_message_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chat_messages')->insert([
            'chat_id' => $chatId,
            'sender_user_id' => $player->id,
            'message' => 'msg',
            'created_at' => now(),
        ]);

        Sanctum::actingAs($player);

        $read = $this->patchJson('/api/chats/' . $chatId . '/read');
        $read->assertOk();

        $latest = (int) DB::table('chat_messages')->where('chat_id', $chatId)->max('id');
        $lastRead = (int) DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $player->id)->value('last_read_message_id');
        $this->assertSame($latest, $lastRead);

        $unread = $this->patchJson('/api/chats/' . $chatId . '/unread');
        $unread->assertOk();

        $lastReadAfterUnread = (int) DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $player->id)->value('last_read_message_id');
        $this->assertSame(0, $lastReadAfterUnread);
    }

    public function test_archive_unarchive_and_remove_for_user_toggle_membership_flags(): void
    {
        $player = $this->createUser('chat.flags@example.test', 'player');

        $chatId = DB::table('chats')->insertGetId([
            'name' => 'Flags',
            'type' => 'group',
            'created_by_user_id' => $player->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('chat_members')->insert([
            'chat_id' => $chatId,
            'user_id' => $player->id,
            'deleted_at' => null,
            'archived_at' => null,
            'last_read_message_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($player);

        $archive = $this->patchJson('/api/chats/' . $chatId . '/archive');
        $archive->assertOk();
        $this->assertNotNull(DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $player->id)->value('archived_at'));

        $unarchive = $this->patchJson('/api/chats/' . $chatId . '/unarchive');
        $unarchive->assertOk();
        $this->assertNull(DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $player->id)->value('archived_at'));

        $remove = $this->deleteJson('/api/chats/' . $chatId);
        $remove->assertOk();
        $this->assertNotNull(DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $player->id)->value('deleted_at'));
    }

    public function test_exchange_request_visibility_and_accept_authorization_rules(): void
    {
        $sender = $this->createUser('chat.sender@example.test', 'player');
        $recipient = $this->createUser('chat.recipient@example.test', 'player');
        $outsider = $this->createUser('chat.outsider@example.test', 'player');

        $senderNation = $this->createNation($sender->id, 'Sender');
        $recipientNation = $this->createNation($recipient->id, 'Recipient');
        $outsiderNation = $this->createNation($outsider->id, 'Outsider');

        DB::table('nation_resources')->insert([
            ['nation_id' => $senderNation, 'cow' => 10, 'wood' => 10, 'ore' => 10, 'food' => 10, 'extra_json' => json_encode(['base' => [], 'advanced' => [], 'currencies' => ['gb' => 100]]), 'created_at' => now(), 'updated_at' => now()],
            ['nation_id' => $recipientNation, 'cow' => 10, 'wood' => 10, 'ore' => 10, 'food' => 10, 'extra_json' => json_encode(['base' => [], 'advanced' => [], 'currencies' => ['gb' => 100]]), 'created_at' => now(), 'updated_at' => now()],
            ['nation_id' => $outsiderNation, 'cow' => 10, 'wood' => 10, 'ore' => 10, 'food' => 10, 'extra_json' => json_encode(['base' => [], 'advanced' => [], 'currencies' => ['gb' => 100]]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $chatId = DB::table('chats')->insertGetId([
            'name' => 'Trade',
            'type' => 'global',
            'created_by_user_id' => $sender->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($sender);
        $create = $this->postJson('/api/chats/' . $chatId . '/exchange-requests', [
            'mode' => 'direct_exchange',
            'recipient_nation_id' => $recipientNation,
            'offer' => [
                ['type' => 'currencies', 'name' => 'gb', 'amount' => 5],
            ],
            'receive' => [
                ['type' => 'base', 'name' => 'wood', 'amount' => 1],
            ],
        ]);
        $create->assertCreated();
        $requestId = (int) data_get($create->json(), 'id');

        Sanctum::actingAs($outsider);
        $listAsOutsider = $this->getJson('/api/chats/' . $chatId . '/exchange-requests');
        $listAsOutsider->assertOk();
        $outsiderIds = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $listAsOutsider->json() ?: []);
        $this->assertNotContains($requestId, $outsiderIds);

        $acceptAsOutsider = $this->postJson('/api/chats/' . $chatId . '/exchange-requests/' . $requestId . '/accept');
        $acceptAsOutsider->assertStatus(403);

        Sanctum::actingAs($recipient);
        $acceptAsRecipient = $this->postJson('/api/chats/' . $chatId . '/exchange-requests/' . $requestId . '/accept');
        $acceptAsRecipient->assertOk();
        $acceptAsRecipient->assertJsonPath('message', 'Exchange accepted and resources transferred.');

        $status = DB::table('chat_exchange_requests')->where('id', $requestId)->value('status');
        $this->assertSame('accepted', $status);
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

        Schema::create('nation_resources', function (Blueprint $table) {
            $table->unsignedBigInteger('nation_id')->primary();
            $table->double('cow')->default(0);
            $table->double('wood')->default(0);
            $table->double('ore')->default(0);
            $table->double('food')->default(0);
            $table->text('extra_json')->nullable();
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
            $table->unsignedBigInteger('removed_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->text('meta_json')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }
}
