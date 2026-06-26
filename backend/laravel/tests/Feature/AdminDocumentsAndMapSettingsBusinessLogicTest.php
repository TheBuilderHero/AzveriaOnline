<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDocumentsAndMapSettingsBusinessLogicTest extends TestCase
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

        File::delete(storage_path('app/map_settings.json'));
        File::delete(storage_path('app/game_document_visibility.json'));
        File::delete(storage_path('app/resource_topbar_config.json'));
        File::delete(storage_path('app/combat_rating_config.json'));
        File::delete(storage_path('app/visibility_defaults.json'));
        File::delete(storage_path('app/developer_logs.json'));
        Storage::disk('public')->delete('maps/editor-state-active.json');
        Storage::disk('public')->delete('maps/editor-state-draft.json');
        Storage::disk('public')->delete('maps/editor-state.json');

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_admin_map_settings_validates_formula_and_scales_existing_terrain_data(): void
    {
        $admin = $this->createUser('admin.map.settings@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('nation_terrain_stats')->insert([
            'nation_id' => 1,
            'grassland_pct' => 25,
            'mountain_pct' => 25,
            'freshwater_pct' => 25,
            'hills_pct' => 25,
            'desert_pct' => 0,
            'seafront_pct' => 0,
            'square_miles_json' => json_encode([
                'grassland' => 10,
                'mountain' => 10,
                'freshwater' => 10,
                'hills' => 10,
                'desert' => 0,
                'seafront' => 0,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $invalid = $this->patchJson('/api/admin/map-settings', [
            'map_pixels_to_square_miles_formula' => 'PIXELS + 10',
        ]);
        $invalid->assertStatus(422);
        $invalid->assertJsonPath('message', 'Map formula must be proportional to PIXELS (for example: PIXELS, PIXELS*0.25, PIXELS/4).');

        $valid = $this->patchJson('/api/admin/map-settings', [
            'map_max_zoom_pct' => 240,
            'map_show_nation_names' => true,
            'map_popup_fields' => ['alliance', 'owned_terrain_pixels', 'alliance'],
            'map_pixels_to_square_miles_formula' => 'PIXELS*2',
            'map_terrain_color_overrides' => [
                'water' => '#22AAFF',
                'invalid_key' => '#AABBCC',
            ],
        ]);
        $valid->assertOk();
        $valid->assertJsonPath('message', 'Map settings saved.');

        $settingsPath = storage_path('app/map_settings.json');
        $this->assertTrue(File::exists($settingsPath));
        $saved = json_decode((string) File::get($settingsPath), true) ?: [];

        $this->assertSame(240, (int) ($saved['map_max_zoom_pct'] ?? 0));
        $this->assertSame(true, (bool) ($saved['map_show_nation_names'] ?? false));
        $this->assertSame('PIXELS*2', (string) ($saved['map_pixels_to_square_miles_formula'] ?? ''));
        $this->assertSame(['alliance', 'owned_terrain_square_miles'], array_values($saved['map_popup_fields'] ?? []));
        $this->assertSame('#22AAFF', (string) (($saved['map_terrain_color_overrides'] ?? [])['water'] ?? ''));
        $this->assertArrayNotHasKey('invalid_key', $saved['map_terrain_color_overrides'] ?? []);

        $updatedSq = json_decode((string) DB::table('nation_terrain_stats')->where('nation_id', 1)->value('square_miles_json'), true) ?: [];
        $this->assertSame(20.0, (float) ($updatedSq['grassland'] ?? 0));
        $this->assertSame(20.0, (float) ($updatedSq['mountain'] ?? 0));
    }

    public function test_custom_game_document_visibility_controls_player_access(): void
    {
        $admin = $this->createUser('admin.docs@example.test', 'admin');
        $player = $this->createUser('player.docs@example.test', 'player');

        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/admin/game-documents', [
            'title' => 'Private Strategy Notes',
            'code' => 'private_strategy_notes',
            'content_text' => 'Top secret content',
        ]);
        $create->assertStatus(201);
        $create->assertJsonPath('code', 'private_strategy_notes');

        $adminList = $this->getJson('/api/admin/game-documents');
        $adminList->assertOk();
        $adminCodes = array_map(static fn ($row) => (string) ($row['code'] ?? ''), $adminList->json() ?: []);
        $this->assertContains('private_strategy_notes', $adminCodes);

        Sanctum::actingAs($player);
        $playerListBefore = $this->getJson('/api/game-documents');
        $playerListBefore->assertOk();
        $playerCodesBefore = array_map(static fn ($row) => (string) ($row['code'] ?? ''), $playerListBefore->json() ?: []);
        $this->assertNotContains('private_strategy_notes', $playerCodesBefore);

        Sanctum::actingAs($admin);
        $vis = $this->putJson('/api/admin/game-documents/private_strategy_notes/visibility', [
            'visibility_type' => 'all',
        ]);
        $vis->assertOk();
        $vis->assertJsonPath('message', 'Visibility updated');

        Sanctum::actingAs($player);
        $playerListAfter = $this->getJson('/api/game-documents');
        $playerListAfter->assertOk();
        $playerCodesAfter = array_map(static fn ($row) => (string) ($row['code'] ?? ''), $playerListAfter->json() ?: []);
        $this->assertContains('private_strategy_notes', $playerCodesAfter);

        $doc = $this->getJson('/api/game-documents/private_strategy_notes');
        $doc->assertOk();
        $doc->assertJsonPath('code', 'private_strategy_notes');
        $doc->assertJsonPath('content_text', 'Top secret content');
    }

    public function test_get_game_document_visibility_returns_admin_default_when_unconfigured(): void
    {
        $admin = $this->createUser('admin.visibility.default@example.test', 'admin');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/game-documents/elves/visibility');
        $response->assertOk();
        $response->assertJsonPath('document_code', 'elves');
        $response->assertJsonPath('visibility_type', 'admin');
        $response->assertJsonPath('player_ids', []);
    }

    public function test_notifications_list_filters_by_type_and_marks_unread_as_read(): void
    {
        $admin = $this->createUser('admin.notifications@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('admin_notifications')->insert([
            [
                'type' => 'nation.updated',
                'title' => 'Nation Updated',
                'body' => 'Nation alpha changed',
                'meta_json' => json_encode(['nation_id' => 1]),
                'is_read' => 0,
                'created_at' => now()->subMinutes(5),
            ],
            [
                'type' => 'nation.updated',
                'title' => 'Nation Updated 2',
                'body' => 'Nation beta changed',
                'meta_json' => json_encode(['nation_id' => 2]),
                'is_read' => 0,
                'created_at' => now()->subMinutes(1),
            ],
            [
                'type' => 'chat.created',
                'title' => 'Chat Created',
                'body' => 'Global chat created',
                'meta_json' => json_encode(['actor_user_id' => 7]),
                'is_read' => 0,
                'created_at' => now()->subMinutes(10),
            ],
        ]);

        $response = $this->getJson('/api/admin/notifications?type=nation.updated');
        $response->assertOk();

        $rows = $response->json() ?: [];
        $this->assertCount(2, $rows);
        $this->assertSame('Nation Updated 2', (string) ($rows[0]['title'] ?? ''));
        $this->assertSame('Nation Updated', (string) ($rows[1]['title'] ?? ''));

        $unreadCount = (int) DB::table('admin_notifications')->where('is_read', 0)->count();
        $this->assertSame(0, $unreadCount);
    }

    public function test_delete_notification_removes_target_row(): void
    {
        $admin = $this->createUser('admin.notifications.delete@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('admin_notifications')->insert([
            'type' => 'nation.updated',
            'title' => 'Delete Me',
            'body' => 'Will be deleted',
            'meta_json' => json_encode(['nation_id' => 9]),
            'is_read' => 0,
            'created_at' => now(),
        ]);
        $id = (int) DB::table('admin_notifications')->where('title', 'Delete Me')->value('id');

        $response = $this->deleteJson('/api/admin/notifications/' . $id);
        $response->assertOk();
        $response->assertJsonPath('message', 'Notification deleted');

        $this->assertFalse(DB::table('admin_notifications')->where('id', $id)->exists());
    }

    public function test_update_time_tracker_applies_manual_year_offset_without_processing_effects(): void
    {
        $admin = $this->createUser('admin.time.update@example.test', 'admin');
        Sanctum::actingAs($admin);

        $response = $this->patchJson('/api/admin/time-tracker', [
            'hours_per_year' => 2,
            'seconds_per_year' => 5400,
            'elapsed_hours_in_year' => 99,
            'auto_increment_enabled' => false,
            'current_game_year' => 5,
            'apply_year_change_effects' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('seconds_per_year', 5400);
        $response->assertJsonPath('auto_increment_enabled', false);
        $response->assertJsonPath('processed_years', 0);
        $response->assertJsonPath('year_label_offset', 4);
        $response->assertJsonPath('current_game_year', 5);
        $response->assertJsonPath('elapsed_hours_in_year', 1.5);

        $row = DB::table('game_time')->where('id', 1)->first();
        $this->assertNotNull($row);
        $this->assertSame(5400, (int) $row->seconds_per_year);
        $this->assertSame(0, (int) $row->processed_years);
        $this->assertSame(4, (int) $row->year_label_offset);
        $this->assertEquals(1.5, (float) $row->elapsed_hours_in_year);
        $this->assertSame(0, (int) $row->auto_increment_enabled);
    }

    public function test_pause_and_resume_time_tracker_updates_state_and_history(): void
    {
        $admin = $this->createUser('admin.time.pause@example.test', 'admin');
        Sanctum::actingAs($admin);

        $pause = $this->postJson('/api/admin/time-tracker/pause', [
            'pause_note' => 'Maintenance window',
        ]);
        $pause->assertOk();
        $pause->assertJsonPath('is_paused', true);

        $statePaused = DB::table('game_time')->where('id', 1)->first();
        $this->assertNotNull($statePaused);
        $this->assertSame(1, (int) $statePaused->is_paused);
        $this->assertNotNull($statePaused->paused_at);

        $historyOpen = DB::table('game_time_pause_history')->orderByDesc('id')->first();
        $this->assertNotNull($historyOpen);
        $this->assertSame((int) $admin->id, (int) $historyOpen->paused_by_user_id);
        $this->assertSame('Maintenance window', (string) ($historyOpen->pause_note ?? ''));
        $this->assertNull($historyOpen->resumed_at);

        $resume = $this->postJson('/api/admin/time-tracker/resume');
        $resume->assertOk();
        $resume->assertJsonPath('is_paused', false);

        $stateResumed = DB::table('game_time')->where('id', 1)->first();
        $this->assertNotNull($stateResumed);
        $this->assertSame(0, (int) $stateResumed->is_paused);
        $this->assertNull($stateResumed->paused_at);

        $historyClosed = DB::table('game_time_pause_history')->orderByDesc('id')->first();
        $this->assertNotNull($historyClosed);
        $this->assertNotNull($historyClosed->resumed_at);
        $this->assertSame((int) $admin->id, (int) $historyClosed->resumed_by_user_id);

        $historyResponse = $this->getJson('/api/admin/time-tracker/pause-history');
        $historyResponse->assertOk();
        $rows = $historyResponse->json() ?: [];
        $this->assertNotEmpty($rows);
        $this->assertSame('admin.time.pause', (string) ($rows[0]['paused_by_name'] ?? ''));
    }

    public function test_advance_year_without_effects_increments_label_offset_and_resets_elapsed_hours(): void
    {
        $admin = $this->createUser('admin.time.advance@example.test', 'admin');
        Sanctum::actingAs($admin);

        $this->patchJson('/api/admin/time-tracker', [
            'elapsed_hours_in_year' => 1.25,
            'auto_increment_enabled' => false,
        ])->assertOk();

        $advance = $this->postJson('/api/admin/time-tracker/next-year', [
            'apply_effects' => false,
        ]);

        $advance->assertOk();
        $advance->assertJsonPath('message', 'Year advanced');
        $advance->assertJsonPath('apply_effects', false);

        $row = DB::table('game_time')->where('id', 1)->first();
        $this->assertNotNull($row);
        $this->assertSame(1, (int) $row->year_label_offset);
        $this->assertEquals(0.0, (float) $row->elapsed_hours_in_year);
    }

    public function test_update_time_tracker_with_apply_effects_processes_forward_years(): void
    {
        $admin = $this->createUser('admin.time.effects.update@example.test', 'admin');
        Sanctum::actingAs($admin);

        $response = $this->patchJson('/api/admin/time-tracker', [
            'current_game_year' => 3,
            'apply_year_change_effects' => true,
            'auto_increment_enabled' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('processed_years', 2);
        $response->assertJsonPath('year_label_offset', 0);
        $response->assertJsonPath('current_game_year', 3);

        $state = DB::table('game_time')->where('id', 1)->first();
        $this->assertNotNull($state);
        $this->assertSame(2, (int) $state->processed_years);
        $this->assertSame(0, (int) $state->year_label_offset);

        $announcements = DB::table('announcements')->count();
        $this->assertSame(2, (int) $announcements);
    }

    public function test_advance_year_with_effects_increments_processed_years(): void
    {
        $admin = $this->createUser('admin.time.effects.advance@example.test', 'admin');
        Sanctum::actingAs($admin);

        $advance = $this->postJson('/api/admin/time-tracker/next-year', [
            'apply_effects' => true,
        ]);

        $advance->assertOk();
        $advance->assertJsonPath('message', 'Year advanced');
        $advance->assertJsonPath('apply_effects', true);

        $state = DB::table('game_time')->where('id', 1)->first();
        $this->assertNotNull($state);
        $this->assertSame(1, (int) $state->processed_years);
        $this->assertSame(0, (int) $state->year_label_offset);

        $this->assertGreaterThanOrEqual(1, (int) DB::table('announcements')->count());
    }

    public function test_resource_topbar_config_uses_default_global_when_no_saved_config_exists(): void
    {
        $admin = $this->createUser('admin.topbar.default@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('resource_definitions')->insert([
            [
                'type' => 'base',
                'group' => 'primary',
                'order' => 1,
                'name' => 'cow',
                'display_name' => 'COW',
            ],
            [
                'type' => 'base',
                'group' => 'primary',
                'order' => 2,
                'name' => 'wood',
                'display_name' => 'WOOD',
            ],
            [
                'type' => 'base',
                'group' => 'primary',
                'order' => 3,
                'name' => 'ore',
                'display_name' => 'ORE',
            ],
            [
                'type' => 'base',
                'group' => 'primary',
                'order' => 4,
                'name' => 'food',
                'display_name' => 'FOOD',
            ],
            [
                'type' => 'advanced',
                'group' => 'magic',
                'order' => 1,
                'name' => 'mana',
                'display_name' => 'MANA',
            ],
        ]);

        $response = $this->getJson('/api/admin/resource-topbar-config');
        $response->assertOk();

        $global = $response->json('global') ?: [];
        $this->assertCount(4, $global);
        $this->assertSame(['type' => 'base', 'name' => 'cow'], $global[0]);
        $this->assertSame(['type' => 'base', 'name' => 'wood'], $global[1]);
        $this->assertSame(['type' => 'base', 'name' => 'ore'], $global[2]);
        $this->assertSame(['type' => 'base', 'name' => 'food'], $global[3]);
    }

    public function test_update_resource_topbar_config_normalizes_and_persists_global_and_overrides(): void
    {
        $admin = $this->createUser('admin.topbar.update@example.test', 'admin');
        $player = $this->createUser('player.topbar.update@example.test', 'player');
        Sanctum::actingAs($admin);

        DB::table('resource_definitions')->insert([
            [
                'type' => 'base',
                'group' => 'primary',
                'order' => 1,
                'name' => 'cow',
                'display_name' => 'COW',
            ],
            [
                'type' => 'base',
                'group' => 'primary',
                'order' => 2,
                'name' => 'wood',
                'display_name' => 'WOOD',
            ],
            [
                'type' => 'advanced',
                'group' => 'magic',
                'order' => 1,
                'name' => 'mana',
                'display_name' => 'MANA',
            ],
            [
                'type' => 'advanced',
                'group' => 'magic',
                'order' => 2,
                'name' => 'crystal',
                'display_name' => 'CRYSTAL',
            ],
        ]);

        $save = $this->putJson('/api/admin/resource-topbar-config', [
            'global' => [
                ['type' => 'base', 'name' => 'cow'],
                ['type' => 'base', 'name' => 'cow'],
                ['type' => 'advanced', 'name' => 'mana'],
                ['type' => 'advanced', 'name' => 'does_not_exist'],
            ],
            'overrides' => [
                [
                    'user_id' => $player->id,
                    'mode' => 'append',
                    'resources' => [
                        ['type' => 'advanced', 'name' => 'mana'],
                        ['type' => 'advanced', 'name' => 'mana'],
                    ],
                ],
                [
                    'user_id' => $player->id,
                    'mode' => 'replace',
                    'resources' => [
                        ['type' => 'base', 'name' => 'wood'],
                        ['type' => 'base', 'name' => 'invalid'],
                    ],
                ],
            ],
        ]);

        $save->assertOk();
        $save->assertJsonPath('message', 'Topbar resource configuration saved.');

        $storedPath = storage_path('app/resource_topbar_config.json');
        $this->assertTrue(File::exists($storedPath));

        $stored = json_decode((string) File::get($storedPath), true) ?: [];
        $this->assertSame([
            ['type' => 'base', 'name' => 'cow'],
            ['type' => 'advanced', 'name' => 'mana'],
        ], $stored['global'] ?? []);

        $overrides = $stored['overrides'] ?? [];
        $this->assertCount(1, $overrides);
        $this->assertSame((int) $player->id, (int) ($overrides[0]['user_id'] ?? 0));
        $this->assertSame('replace', (string) ($overrides[0]['mode'] ?? ''));
        $this->assertSame([
            ['type' => 'base', 'name' => 'wood'],
        ], $overrides[0]['resources'] ?? []);

        $response = $this->getJson('/api/admin/resource-topbar-config');
        $response->assertOk();
        $response->assertJsonPath('global.0.type', 'base');
        $response->assertJsonPath('global.0.name', 'cow');
        $response->assertJsonPath('global.1.type', 'advanced');
        $response->assertJsonPath('global.1.name', 'mana');
        $response->assertJsonPath('overrides.0.user_id', (int) $player->id);
        $response->assertJsonPath('overrides.0.mode', 'replace');
        $response->assertJsonPath('overrides.0.resources.0.type', 'base');
        $response->assertJsonPath('overrides.0.resources.0.name', 'wood');
    }

    public function test_builtin_game_document_falls_back_to_file_when_db_row_is_not_admin_saved(): void
    {
        $admin = $this->createUser('admin.docs.fallback@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('game_documents')->insert([
            'code' => 'elves',
            'title' => 'Elves (DB Title)',
            'content_text' => 'DB_CONTENT_SHOULD_NOT_WIN',
            'updated_by_user_id' => null,
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/game-documents/elves');
        $response->assertOk();
        $response->assertJsonPath('code', 'elves');
        $response->assertJsonPath('title', 'Elves (DB Title)');

        $content = (string) ($response->json('content_text') ?? '');
        $this->assertStringContainsString('Azveria elf unit stats', $content);
        $this->assertNotSame('DB_CONTENT_SHOULD_NOT_WIN', $content);
    }

    public function test_builtin_game_document_prefers_db_content_when_admin_saved(): void
    {
        $admin = $this->createUser('admin.docs.dbwins@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('game_documents')->insert([
            'code' => 'elves',
            'title' => 'Elves (Admin Saved)',
            'content_text' => 'DB_CONTENT_MUST_WIN',
            'updated_by_user_id' => (int) $admin->id,
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/game-documents/elves');
        $response->assertOk();
        $response->assertJsonPath('code', 'elves');
        $response->assertJsonPath('title', 'Elves (Admin Saved)');
        $response->assertJsonPath('content_text', 'DB_CONTENT_MUST_WIN');
    }

    public function test_custom_game_document_uses_headline_title_when_db_title_is_blank(): void
    {
        $admin = $this->createUser('admin.docs.customtitle@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('game_documents')->insert([
            'code' => 'custom_doctrine',
            'title' => '',
            'content_text' => 'Custom doctrine body.',
            'updated_by_user_id' => (int) $admin->id,
            'updated_at' => now(),
        ]);

        $list = $this->getJson('/api/admin/game-documents');
        $list->assertOk();
        $rows = $list->json() ?: [];
        $found = collect($rows)->firstWhere('code', 'custom_doctrine');
        $this->assertNotNull($found);
        $this->assertSame('Custom Doctrine', (string) ($found['title'] ?? ''));

        $doc = $this->getJson('/api/admin/game-documents/custom_doctrine');
        $doc->assertOk();
        $doc->assertJsonPath('code', 'custom_doctrine');
        $doc->assertJsonPath('title', 'Custom Doctrine');
        $doc->assertJsonPath('content_text', 'Custom doctrine body.');
    }

    public function test_update_combat_rating_config_requires_exact_confirmation_phrase(): void
    {
        $admin = $this->createUser('admin.combat.confirm@example.test', 'admin');
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/combat/rating-config', [
            'formula_expression' => 'ATK+DEF',
            'apply_confirmation' => 'apply rating formula',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Final apply confirmation did not match.');
        $this->assertFalse(File::exists(storage_path('app/combat_rating_config.json')));
    }

    public function test_update_combat_rating_config_rejects_invalid_formula_and_persists_valid_formula(): void
    {
        $admin = $this->createUser('admin.combat.save@example.test', 'admin');
        Sanctum::actingAs($admin);

        $invalid = $this->putJson('/api/admin/combat/rating-config', [
            'formula_expression' => 'ATK+BADVAR',
            'apply_confirmation' => 'APPLY RATING FORMULA',
        ]);
        $invalid->assertStatus(422);
        $this->assertStringStartsWith('Formula is invalid:', (string) ($invalid->json('message') ?? ''));

        $valid = $this->putJson('/api/admin/combat/rating-config', [
            'formula_expression' => 'ATK+DEF*2',
            'apply_confirmation' => 'APPLY RATING FORMULA',
        ]);
        $valid->assertOk();
        $valid->assertJsonPath('message', 'Combat rating config saved.');

        $path = storage_path('app/combat_rating_config.json');
        $this->assertTrue(File::exists($path));
        $saved = json_decode((string) File::get($path), true) ?: [];
        $this->assertSame('ATK+DEF*2', (string) ($saved['formula_expression'] ?? ''));
        $this->assertSame('floor', (string) ($saved['rounding_mode'] ?? ''));
        $this->assertSame((int) $admin->id, (int) ($saved['updated_by_user_id'] ?? 0));
    }

    public function test_preview_combat_rating_config_returns_breakdown_for_valid_formula(): void
    {
        $admin = $this->createUser('admin.combat.preview@example.test', 'admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/combat/rating-config/preview', [
            'formula_expression' => 'ATK+DEF*2',
            'stats' => [
                'ATK' => 3,
                'DEF' => 2,
                'DMG' => 9,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Preview generated.');
        $response->assertJsonPath('breakdown.source', 'formula');
        $response->assertJsonPath('breakdown.formula_expression', 'ATK+DEF*2');
        $response->assertJsonPath('breakdown.normalized_expression', 'ATK+DEF*2');
        $response->assertJsonPath('breakdown.evaluated_expression', '3+2*2');
        $response->assertJsonPath('breakdown.raw_result', 7);
        $response->assertJsonPath('breakdown.rating', 7);
        $response->assertJsonPath('breakdown.inputs.DMG', 9);
        $response->assertJsonPath('breakdown.inputs.HP', 0);
    }

    public function test_visibility_rules_layers_global_viewer_subject_and_pair_override(): void
    {
        $admin = $this->createUser('admin.visibility.layers@example.test', 'admin');
        $viewer = $this->createUser('viewer.visibility.layers@example.test', 'player');
        $subject = $this->createUser('subject.visibility.layers@example.test', 'player');
        Sanctum::actingAs($admin);

        $this->putJson('/api/admin/visibility/rules', [
            'viewer_user_id' => 'All',
            'subject_user_id' => 'All',
            'field_key' => 'resources_base',
            'is_allowed' => false,
        ])->assertOk();

        $this->putJson('/api/admin/visibility/rules', [
            'viewer_user_id' => (string) $viewer->id,
            'subject_user_id' => 'All',
            'field_key' => 'units',
            'is_allowed' => false,
        ])->assertOk();

        $this->putJson('/api/admin/visibility/rules', [
            'viewer_user_id' => 'All',
            'subject_user_id' => (string) $subject->id,
            'field_key' => 'buildings',
            'is_allowed' => false,
        ])->assertOk();

        $this->putJson('/api/admin/visibility/rules', [
            'viewer_user_id' => (string) $viewer->id,
            'subject_user_id' => (string) $subject->id,
            'field_key' => 'buildings',
            'is_allowed' => true,
        ])->assertOk();

        $response = $this->getJson('/api/admin/visibility/rules?viewer_user_id=' . $viewer->id . '&subject_user_id=' . $subject->id);
        $response->assertOk();

        $rows = collect($response->json() ?: []);
        $this->assertSame(false, (bool) ($rows->firstWhere('field_key', 'resources_base')['is_allowed'] ?? true));
        $this->assertSame(false, (bool) ($rows->firstWhere('field_key', 'units')['is_allowed'] ?? true));
        $this->assertSame(true, (bool) ($rows->firstWhere('field_key', 'buildings')['is_allowed'] ?? false));
        $this->assertSame(true, (bool) ($rows->firstWhere('field_key', 'army_rating')['is_allowed'] ?? false));
    }

    public function test_visibility_rules_rejects_bulk_update_when_selector_contains_all_without_field_key(): void
    {
        $admin = $this->createUser('admin.visibility.bulkreject@example.test', 'admin');
        $subject = $this->createUser('subject.visibility.bulkreject@example.test', 'player');
        Sanctum::actingAs($admin);

        $response = $this->putJson('/api/admin/visibility/rules', [
            'viewer_user_id' => 'All',
            'subject_user_id' => (string) $subject->id,
            'rules' => [
                ['field_key' => 'units', 'is_allowed' => false],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['field_key']);
    }

    public function test_visibility_rules_bulk_replace_for_specific_pair_and_invalid_selector_validation(): void
    {
        $admin = $this->createUser('admin.visibility.bulkpair@example.test', 'admin');
        $viewer = $this->createUser('viewer.visibility.bulkpair@example.test', 'player');
        $subject = $this->createUser('subject.visibility.bulkpair@example.test', 'player');
        Sanctum::actingAs($admin);

        $save = $this->putJson('/api/admin/visibility/rules', [
            'viewer_user_id' => (string) $viewer->id,
            'subject_user_id' => (string) $subject->id,
            'rules' => [
                ['field_key' => 'units', 'is_allowed' => false],
                ['field_key' => 'terrain', 'is_allowed' => false],
                ['field_key' => 'not_a_real_field', 'is_allowed' => false],
            ],
        ]);
        $save->assertOk();
        $save->assertJsonPath('message', 'Visibility rules saved');

        $stored = DB::table('player_visibility_rules')
            ->where('viewer_user_id', (int) $viewer->id)
            ->where('subject_user_id', (int) $subject->id)
            ->orderBy('field_key')
            ->pluck('is_allowed', 'field_key')
            ->toArray();
        $stored = array_map(static fn ($value) => (int) $value, $stored);

        $this->assertSame(['terrain' => 0, 'units' => 0], $stored);

        $invalidSelector = $this->getJson('/api/admin/visibility/rules?viewer_user_id=abc&subject_user_id=All');
        $invalidSelector->assertStatus(422);
        $invalidSelector->assertJsonValidationErrors(['viewer_user_id']);
    }

    public function test_cleanup_zombie_data_dry_run_reports_counts_without_mutating_files(): void
    {
        $admin = $this->createUser('admin.cleanup.dryrun@example.test', 'admin');
        $validUser = $this->createUser('valid.cleanup.dryrun@example.test', 'player');
        Sanctum::actingAs($admin);

        DB::table('nations')->insert([
            'id' => 1,
            'name' => 'Valid Nation',
        ]);

        $mapState = [
            'political_nations' => [
                ['id' => 1, 'name' => 'Valid Nation'],
                ['id' => 999, 'name' => 'Zombie Nation'],
            ],
            'political_strokes' => [
                ['nation_id' => 1, 'tool' => 'brush', 'x' => 1, 'y' => 2],
                ['nation_id' => 999, 'tool' => 'brush', 'x' => 3, 'y' => 4],
                'invalid-row-shape',
            ],
        ];
        Storage::disk('public')->put('maps/editor-state-active.json', json_encode($mapState, JSON_UNESCAPED_SLASHES));

        File::put(storage_path('app/resource_topbar_config.json'), json_encode([
            'global' => [['type' => 'base', 'name' => 'cow']],
            'overrides' => [
                ['user_id' => (int) $validUser->id, 'mode' => 'replace', 'resources' => [['type' => 'base', 'name' => 'cow']]],
                ['user_id' => 999, 'mode' => 'append', 'resources' => [['type' => 'base', 'name' => 'wood']]],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put(storage_path('app/developer_logs.json'), json_encode([
            ['id' => 'keep-1', 'actor_user_id' => (int) $validUser->id, 'level' => 'info', 'summary' => 'valid actor'],
            ['id' => 'remove-1', 'actor_user_id' => 999, 'level' => 'warning', 'summary' => 'zombie actor'],
            ['id' => 'keep-2', 'level' => 'info', 'summary' => 'missing actor key should remain'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $beforeMap = (string) Storage::disk('public')->get('maps/editor-state-active.json');
        $beforeTopbar = (string) File::get(storage_path('app/resource_topbar_config.json'));
        $beforeLogs = (string) File::get(storage_path('app/developer_logs.json'));

        $response = $this->postJson('/api/admin/developer/cleanup-zombie-data', [
            'dry_run' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Zombie-data cleanup preview generated.');
        $response->assertJsonPath('dry_run', true);
        $response->assertJsonPath('details.map_editor_political_nations_removed', 1);
        $response->assertJsonPath('details.map_editor_political_strokes_removed', 2);
        $response->assertJsonPath('details.resource_topbar_overrides_removed', 1);
        $response->assertJsonPath('details.developer_logs_removed', 1);

        $this->assertSame($beforeMap, (string) Storage::disk('public')->get('maps/editor-state-active.json'));
        $this->assertSame($beforeTopbar, (string) File::get(storage_path('app/resource_topbar_config.json')));
        $this->assertSame($beforeLogs, (string) File::get(storage_path('app/developer_logs.json')));
    }

    public function test_cleanup_zombie_data_requires_confirmation_and_then_persists_filtered_files(): void
    {
        $admin = $this->createUser('admin.cleanup.persist@example.test', 'admin');
        $validUser = $this->createUser('valid.cleanup.persist@example.test', 'player');
        Sanctum::actingAs($admin);

        DB::table('nations')->insert([
            'id' => 2,
            'name' => 'Nation Two',
        ]);

        Storage::disk('public')->put('maps/editor-state-active.json', json_encode([
            'political_nations' => [
                ['id' => 2, 'name' => 'Nation Two'],
                ['id' => 404, 'name' => 'Ghost Nation'],
            ],
            'political_strokes' => [
                ['nation_id' => 404, 'tool' => 'line', 'x' => 8, 'y' => 9],
                ['nation_id' => 2, 'tool' => 'line', 'x' => 1, 'y' => 1],
            ],
        ], JSON_UNESCAPED_SLASHES));

        File::put(storage_path('app/resource_topbar_config.json'), json_encode([
            'global' => [['type' => 'base', 'name' => 'cow']],
            'overrides' => [
                ['user_id' => 12345, 'mode' => 'replace', 'resources' => [['type' => 'base', 'name' => 'cow']]],
                ['user_id' => (int) $validUser->id, 'mode' => 'replace', 'resources' => [['type' => 'base', 'name' => 'wood']]],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        File::put(storage_path('app/developer_logs.json'), json_encode([
            ['id' => 'drop', 'actor_user_id' => 12345, 'level' => 'error', 'summary' => 'invalid actor'],
            ['id' => 'keep', 'actor_user_id' => (int) $validUser->id, 'level' => 'info', 'summary' => 'valid actor'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $missingConfirmation = $this->postJson('/api/admin/developer/cleanup-zombie-data', [
            'dry_run' => false,
            'confirmation_text' => 'NOPE',
        ]);
        $missingConfirmation->assertStatus(422);
        $missingConfirmation->assertJsonValidationErrors(['confirmation_text']);

        $apply = $this->postJson('/api/admin/developer/cleanup-zombie-data', [
            'dry_run' => false,
            'confirmation_text' => 'PURGE ZOMBIE DATA',
        ]);

        $apply->assertOk();
        $apply->assertJsonPath('message', 'Zombie-data cleanup complete.');
        $apply->assertJsonPath('dry_run', false);
        $apply->assertJsonPath('details.map_editor_political_nations_removed', 1);
        $apply->assertJsonPath('details.map_editor_political_strokes_removed', 1);
        $apply->assertJsonPath('details.resource_topbar_overrides_removed', 1);
        $apply->assertJsonPath('details.developer_logs_removed', 1);

        $mapDecoded = json_decode((string) Storage::disk('public')->get('maps/editor-state-active.json'), true) ?: [];
        $remainingNationIds = array_values(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $mapDecoded['political_nations'] ?? []));
        $remainingStrokeNationIds = array_values(array_map(static fn ($row) => (int) (($row['nation_id'] ?? 0)), $mapDecoded['political_strokes'] ?? []));
        $this->assertSame([2], $remainingNationIds);
        $this->assertSame([2], $remainingStrokeNationIds);

        $topbarDecoded = json_decode((string) File::get(storage_path('app/resource_topbar_config.json')), true) ?: [];
        $topbarUserIds = array_values(array_map(static fn ($row) => (int) ($row['user_id'] ?? 0), $topbarDecoded['overrides'] ?? []));
        $this->assertSame([(int) $validUser->id], $topbarUserIds);

        $logsDecoded = json_decode((string) File::get(storage_path('app/developer_logs.json')), true) ?: [];
        $logActorIds = array_values(array_map(static fn ($row) => (int) ($row['actor_user_id'] ?? 0), $logsDecoded));
        $this->assertSame([(int) $validUser->id], $logActorIds);
    }

    public function test_update_combat_order_status_updates_review_fields_for_combat_order(): void
    {
        $admin = $this->createUser('admin.combat.order.status@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('admin_notifications')->insert([
            'type' => 'combat_order',
            'title' => 'Order #1',
            'body' => 'Pending order',
            'meta_json' => json_encode(['actor_user_id' => (int) $admin->id]),
            'is_read' => 0,
            'created_at' => now(),
        ]);
        $notificationId = (int) DB::table('admin_notifications')->where('title', 'Order #1')->value('id');

        $response = $this->putJson('/api/admin/combat/orders/' . $notificationId . '/status', [
            'order_status' => 'approved',
            'review_note' => 'Looks good.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Combat order status updated');

        $row = DB::table('admin_notifications')->where('id', $notificationId)->first();
        $this->assertNotNull($row);
        $this->assertSame('approved', (string) ($row->order_status ?? ''));
        $this->assertSame('Looks good.', (string) ($row->review_note ?? ''));
        $this->assertSame((int) $admin->id, (int) ($row->reviewed_by_user_id ?? 0));
        $this->assertNotNull($row->reviewed_at);
    }

    public function test_update_combat_order_status_returns_not_found_for_non_combat_notification(): void
    {
        $admin = $this->createUser('admin.combat.order.notfound@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('admin_notifications')->insert([
            'type' => 'nation.updated',
            'title' => 'Not a combat order',
            'body' => 'Body',
            'meta_json' => json_encode([]),
            'is_read' => 0,
            'created_at' => now(),
        ]);
        $notificationId = (int) DB::table('admin_notifications')->where('title', 'Not a combat order')->value('id');

        $response = $this->putJson('/api/admin/combat/orders/' . $notificationId . '/status', [
            'order_status' => 'denied',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Combat order not found');
    }

    public function test_update_combat_unit_stats_updates_global_override_and_emits_notification(): void
    {
        $admin = $this->createUser('admin.combat.unit.global@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('nations')->insert([
            'id' => 77,
            'name' => 'Test Nation 77',
        ]);

        DB::table('nation_units')->insert([
            'id' => 501,
            'nation_id' => 77,
            'custom_name' => 'Original Name',
            'stats_override_json' => json_encode(['existing' => 1]),
            'updated_at' => now()->subHour(),
        ]);

        $response = $this->putJson('/api/admin/combat/units/501/stats', [
            'custom_name' => 'Renamed Unit',
            'stats_override_json' => ['ATK' => 7, 'tag' => 'x'],
            'class_name' => 'Knight',
            'status' => 'Ready',
            'race' => 'Human',
            'terrain' => 'Grassland',
            'admin_note' => 'Manual tuning',
            'rating' => 12.349,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Unit stats updated');

        $unit = DB::table('nation_units')->where('id', 501)->first();
        $this->assertNotNull($unit);
        $this->assertSame('Renamed Unit', (string) ($unit->custom_name ?? ''));

        $decoded = json_decode((string) ($unit->stats_override_json ?? '{}'), true) ?: [];
        $this->assertSame(7, (int) ($decoded['ATK'] ?? 0));
        $this->assertSame('x', (string) ($decoded['tag'] ?? ''));
        $this->assertSame('Knight', (string) ($decoded['class_name'] ?? ''));
        $this->assertSame('Ready', (string) ($decoded['status'] ?? ''));
        $this->assertSame('Human', (string) ($decoded['race'] ?? ''));
        $this->assertSame('Grassland', (string) ($decoded['terrain'] ?? ''));
        $this->assertSame('Manual tuning', (string) ($decoded['admin_note'] ?? ''));
        $this->assertSame(12.35, (float) ($decoded['rating'] ?? 0));

        $notification = DB::table('admin_notifications')->where('type', 'combat_unit_update')->orderByDesc('id')->first();
        $this->assertNotNull($notification);
        $meta = json_decode((string) ($notification->meta_json ?? '{}'), true) ?: [];
        $this->assertSame(77, (int) ($meta['nation_id'] ?? 0));
        $this->assertSame(501, (int) ($meta['nation_unit_id'] ?? 0));
        $this->assertArrayHasKey('instance_index', $meta);
        $this->assertNull($meta['instance_index']);
    }

    public function test_update_combat_unit_stats_instance_mode_preserves_root_custom_name_and_writes_instance_override(): void
    {
        $admin = $this->createUser('admin.combat.unit.instance@example.test', 'admin');
        Sanctum::actingAs($admin);

        DB::table('nations')->insert([
            'id' => 88,
            'name' => 'Test Nation 88',
        ]);

        DB::table('nation_units')->insert([
            'id' => 777,
            'nation_id' => 88,
            'custom_name' => 'Root Unit Name',
            'stats_override_json' => json_encode(['_instances' => ['2' => ['old' => 1]]]),
            'updated_at' => now()->subHour(),
        ]);

        $response = $this->putJson('/api/admin/combat/units/777/stats', [
            'instance_index' => 2,
            'custom_name' => 'Instance Two',
            'stats_override_json' => ['ATK' => 9],
            'status' => 'Veteran',
            'rating' => 3.2,
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Unit stats updated');

        $unit = DB::table('nation_units')->where('id', 777)->first();
        $this->assertNotNull($unit);
        $this->assertSame('Root Unit Name', (string) ($unit->custom_name ?? ''));

        $decoded = json_decode((string) ($unit->stats_override_json ?? '{}'), true) ?: [];
        $instance = $decoded['_instances']['2'] ?? [];
        $this->assertSame(9, (int) ($instance['ATK'] ?? 0));
        $this->assertSame('Veteran', (string) ($instance['status'] ?? ''));
        $this->assertSame('Instance Two', (string) ($instance['custom_name'] ?? ''));
        $this->assertSame(3.2, (float) ($instance['rating'] ?? 0));
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

        Schema::create('game_documents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('title', 200)->nullable();
            $table->longText('content_text')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
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
            $table->timestamps();
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
        });

        Schema::create('nation_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nation_id');
            $table->string('custom_name')->nullable();
            $table->text('stats_override_json')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('resource_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32);
            $table->string('group', 64)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('name', 120);
            $table->string('display_name', 160)->nullable();
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

        Schema::create('nations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_user_id')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('player_visibility_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('viewer_user_id');
            $table->unsignedBigInteger('subject_user_id');
            $table->string('field_key', 80);
            $table->boolean('is_allowed')->default(true);
            $table->timestamp('updated_at')->nullable();
        });
    }
}
