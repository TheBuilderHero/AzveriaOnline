<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@azveria.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $player = User::updateOrCreate(
            ['email' => 'player@azveria.local'],
            [
                'name' => 'DakotianPlayer',
                'password' => Hash::make('password123'),
                'role' => 'player',
            ]
        );

        DB::table('shop_categories')->updateOrInsert(['code' => 'refinement'], ['display_name' => 'Refinement']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'upgrades'], ['display_name' => 'Upgrades']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'recruitment'], ['display_name' => 'Recruitment']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'crafting'], ['display_name' => 'Crafting']);

        DB::table('map_layers')->updateOrInsert(
            ['layer_type' => 'main'],
            ['image_path' => 'maps/main-map.png', 'uploaded_by_user_id' => $admin->id, 'updated_at' => now()]
        );
        DB::table('map_layers')->updateOrInsert(
            ['layer_type' => 'terrain'],
            ['image_path' => 'maps/terrain-map.png', 'uploaded_by_user_id' => $admin->id, 'updated_at' => now()]
        );
        DB::table('map_layers')->updateOrInsert(
            ['layer_type' => 'political'],
            ['image_path' => 'maps/political-map.png', 'uploaded_by_user_id' => $admin->id, 'updated_at' => now()]
        );

        $nationId = DB::table('nations')->updateOrInsert(
            ['name' => 'Dakotians'],
            [
                'owner_user_id' => $player->id,
                'is_placeholder' => 0,
                'leader_name' => 'Dakotian Commander',
                'alliance_name' => 'Neutral Front',
                'about_text' => 'Dakotians: adaptable and ready for war.',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $nation = DB::table('nations')->where('name', 'Dakotians')->first();

        DB::table('nation_resources')->updateOrInsert(
            ['nation_id' => $nation->id],
            [
                'cow' => 250,
                'wood' => 140,
                'ore' => 120,
                'food' => 180,
                'updated_at' => now(),
            ]
        );

        DB::table('nation_terrain_stats')->updateOrInsert(
            ['nation_id' => $nation->id],
            [
                'grassland_pct' => 30,
                'mountain_pct' => 25,
                'freshwater_pct' => 10,
                'hills_pct' => 20,
                'desert_pct' => 15,
                'square_miles_json' => json_encode([
                    'grassland' => 300,
                    'mountain' => 250,
                    'freshwater' => 100,
                    'hills' => 200,
                    'desert' => 150,
                ]),
                'updated_at' => now(),
            ]
        );

        $player2 = User::updateOrCreate(
            ['email' => 'player2@azveria.local'],
            [
                'name' => 'IronwaldPlayer',
                'password' => Hash::make('password123'),
                'role' => 'player',
            ]
        );

        $player3 = User::updateOrCreate(
            ['email' => 'player3@azveria.local'],
            [
                'name' => 'StonemarPlayer',
                'password' => Hash::make('password123'),
                'role' => 'player',
            ]
        );

        foreach ([$player2, $player3] as $p) {
            DB::table('nations')->updateOrInsert(
                ['name' => $p->name . "'s Nation"],
                [
                    'owner_user_id' => $p->id,
                    'is_placeholder' => 0,
                    'leader_name' => $p->name,
                    'alliance_name' => 'Neutral Front',
                    'about_text' => 'A rising nation.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $pNation = DB::table('nations')->where('owner_user_id', $p->id)->first();
            if ($pNation) {
                DB::table('nation_resources')->updateOrInsert(
                    ['nation_id' => $pNation->id],
                    ['cow' => 100, 'wood' => 100, 'ore' => 100, 'food' => 100, 'updated_at' => now()]
                );
                DB::table('nation_terrain_stats')->updateOrInsert(
                    ['nation_id' => $pNation->id],
                    [
                        'grassland_pct' => 40, 'mountain_pct' => 20, 'freshwater_pct' => 10,
                        'hills_pct' => 20, 'desert_pct' => 10,
                        'square_miles_json' => json_encode(['grassland' => 400, 'mountain' => 200, 'freshwater' => 100, 'hills' => 200, 'desert' => 100]),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $existingGlobal = DB::table('chats')->where('name', 'Global Diplomacy')->first();
        if ($existingGlobal) {
            $chatId = $existingGlobal->id;
            DB::table('chats')->where('id', $chatId)->update(['type' => 'global']);
        } else {
            $chatId = DB::table('chats')->insertGetId([
                'name' => 'Global Diplomacy',
                'type' => 'global',
                'created_by_user_id' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([$admin->id, $player->id, $player2->id, $player3->id] as $uid) {
            DB::table('chat_members')->updateOrInsert(['chat_id' => $chatId, 'user_id' => $uid], []);
        }

        $recruitmentCategory = DB::table('shop_categories')->where('code', 'recruitment')->first();
        if ($recruitmentCategory) {
            DB::table('shop_items')->updateOrInsert(
                ['code' => 'buy_light_infantry'],
                [
                    'category_id' => $recruitmentCategory->id,
                    'display_name' => 'Recruit Light Infantry',
                    'cost_json' => json_encode(['cow' => 20, 'food' => 1]),
                    'effect_json' => json_encode(['unit_code' => 'dak_light_infantry', 'qty' => 1]),
                    'is_active' => 1,
                ]
            );
        }
    }
}
