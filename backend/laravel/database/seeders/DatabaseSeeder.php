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
                'seafront_pct' => 0,
                'square_miles_json' => json_encode([
                    'grassland' => 300,
                    'mountain' => 250,
                    'freshwater' => 100,
                    'hills' => 200,
                    'desert' => 150,
                    'seafront' => 0,
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
                        'hills_pct' => 20, 'desert_pct' => 10, 'seafront_pct' => 0,
                        'square_miles_json' => json_encode(['grassland' => 400, 'mountain' => 200, 'freshwater' => 100, 'hills' => 200, 'desert' => 100, 'seafront' => 0]),
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
            DB::table('chat_members')->updateOrInsert(
                ['chat_id' => $chatId, 'user_id' => $uid],
                ['archived_at' => null, 'deleted_at' => null]
            );
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

        // Currency exchange category
        DB::table('shop_categories')->updateOrInsert(
            ['code' => 'currency_exchange'],
            ['display_name' => 'Currency Exchange']
        );

        // ── Crafting items ────────────────────────────────────────────────────────
        // cost_json keys: cow/wood/ore/food = base; ref_X = refined resource;
        //                 cur_X = currency stored in extra_json.currencies
        // effect_json:    refined = {key:qty}, currency = {key:qty}, unit_code+qty
        $craftCategory = DB::table('shop_categories')->where('code', 'refinement')->first();
        if ($craftCategory) {
            $allCraftItems = [
                // Ore chain
                ['craft_ore_to_metal',          'Smelt Ore → Metal',                ['ore' => 10],       ['refined' => ['M'   => 5]]],
                ['craft_ore_to_radioactive',     'Refine Ore → Radioactive Metal',   ['ore' => 15],       ['refined' => ['RM'  => 5]]],
                ['craft_metal_to_fovium_steel',  'Forge Metal → Fovium Steel',       ['ref_M' => 10],     ['refined' => ['FS'  => 4]]],
                ['craft_rm_to_uranium',          'Process RM → Uranium',             ['ref_RM' => 10],    ['refined' => ['URM' => 4]]],
                ['craft_metal_to_aderite',       'Transform Metal → Aderite',        ['ref_M' => 12],     ['refined' => ['AD'  => 3]]],
                ['craft_rm_to_antimatter',       'Synthesize RM → Antimatter',       ['ref_RM' => 12],    ['refined' => ['AM'  => 3]]],
                ['craft_fs_to_dark_matter',      'Compress FS → Dark Matter',        ['ref_FS' => 8],     ['refined' => ['DM'  => 2]]],
                ['craft_uranium_to_dark_energy', 'Fuse Uranium → Dark Energy',       ['ref_URM' => 8],    ['refined' => ['DE'  => 2]]],
                // Wood chain
                ['craft_wood_to_hardwood',       'Mill Wood → Hardwood',             ['wood' => 10],      ['refined' => ['H'   => 5]]],
                ['craft_wood_to_toxic_waste',    'Process Wood → Toxic Waste',       ['wood' => 12],      ['refined' => ['TW'  => 4]]],
                ['craft_hardwood_to_cb',         'Charge Hardwood → Carbon Battery', ['ref_H' => 10],     ['refined' => ['CB'  => 4]]],
                ['craft_tw_to_mycelium',         'Grow TW → Mycelium',               ['ref_TW' => 10],    ['refined' => ['MYC' => 5]]],
                ['craft_mycelium_to_shroomium',  'Cultivate Mycelium → Shroomium',   ['ref_MYC' => 8],    ['refined' => ['SM'  => 3]]],
                ['craft_hardwood_to_cf',         'Weave Hardwood → Carbon Fiber',    ['ref_H' => 12],     ['refined' => ['CFB' => 4]]],
                ['craft_tw_to_bulistium',        'Crystallize TW → Bulistium',       ['ref_TW' => 12],    ['refined' => ['BST' => 3]]],
                ['craft_cb_to_chaos_gem',        'Fuse Carbon Battery → Chaos Gem',  ['ref_CB' => 10],    ['refined' => ['CGM' => 2]]],
                // Food chain
                ['craft_food_to_granola_bars',   'Bake Food → Granola Bars',         ['food' => 10],      ['refined' => ['GBR' => 6]]],
                ['craft_food_to_choc_bar',       'Process Food → Chocolate Bar',     ['food' => 10],      ['refined' => ['CHB' => 6]]],
                ['craft_gbr_to_sushi_rolls',     'Roll GBR → Sushi Rolls',           ['ref_GBR' => 8],    ['refined' => ['SR'  => 5]]],
                ['craft_food_to_zaza',           'Ferment Food → Zaza',              ['food' => 15],      ['refined' => ['ZZ'  => 3]]],
                ['craft_gbr_to_pizza',           'Top GBR → Pizza',                  ['ref_GBR' => 10],   ['refined' => ['PZA' => 4]]],
                ['craft_chb_to_ice_cream',       'Freeze CHB → Ice Cream',           ['ref_CHB' => 8],    ['refined' => ['IC'  => 5]]],
                ['craft_sr_to_whale_sushi',      'Upgrade SR → Whale Sushi',         ['ref_SR' => 6],     ['refined' => ['WSH' => 2]]],
                ['craft_food_to_stardust',       'Crystallize Food → StarDust',      ['food' => 20],      ['refined' => ['SD'  => 2]]],
                ['craft_sd_to_neutron_stardust', 'Amplify StarDust → Neutron StarDust', ['ref_SD' => 5],  ['refined' => ['NS'  => 1]]],
            ];
            foreach ($allCraftItems as [$code, $name, $cost, $effect]) {
                DB::table('shop_items')->updateOrInsert(
                    ['code' => $code],
                    [
                        'category_id' => $craftCategory->id,
                        'display_name' => $name,
                        'cost_json'    => json_encode($cost),
                        'effect_json'  => json_encode($effect),
                        'is_active'    => 1,
                    ]
                );
            }

            $structureItems = [
                ['struct_capital_l1', 'Capital L1', ['cow' => 5, 'ore' => 1, 'food' => 2, 'wood' => 2], ['cow' => 2], ['food' => 1], 'Capital. Takes 10 square miles. Effect: +2C yearly, -1F yearly.'],
                ['struct_refinery_l1', 'Refinery L1', ['cow' => 4, 'ore' => 2, 'food' => 1, 'wood' => 1], [], [], 'Refinery. Takes 10 square miles. Effect: 5-to-1 conversion rate.'],
                ['struct_fort_l1', 'Fort L1', ['cow' => 2, 'food' => 1, 'wood' => 1], [], [], 'Fort. Takes 10 square miles. Effect: +25% to each defensive roll.'],
                ['struct_city_l1', 'City L1', ['wood' => 1], ['cow' => 2], [], 'City. Takes 10 square miles. Effect: +2C yearly.'],
                ['struct_farm_l1', 'Farm L1', ['cow' => 2], ['food' => 1], [], 'Farm. Takes 10 square miles. Produces food, fish, rice, and blank beans.'],
                ['struct_lodging_l1', 'Lodging L1', ['cow' => 3], ['wood' => 1], [], 'Lodging. Takes 10 square miles. Effect: +1W yearly.'],
                ['struct_mine_l1', 'Mine / Excavation L1', ['cow' => 2, 'wood' => 1], ['ore' => 1], [], 'Mine. Takes 10 square miles. Effect: +1O yearly.'],
                ['struct_barracks_l1', 'Barracks L1', ['cow' => 5, 'food' => 1, 'wood' => 1], [], [], 'Barracks. Takes 10 square miles. Effect: +3 infantry, unlocks light infantry.', ['unit_code' => 'dak_light_infantry', 'qty' => 3]],
                ['struct_factory_l1', 'Factory L1', ['cow' => 5, 'ore' => 1, 'wood' => 1], [], [], 'Factory. Takes 10 square miles. Effect: +2 artillery, unlocks light artillery.'],
                ['struct_shipyard_l1', 'Shipyard L1', ['cow' => 5, 'wood' => 3], [], [], 'Shipyard. Takes 10 square miles. Effect: +3 warships, unlocks light cruisers and corvettes.'],
                ['struct_airfield_l1', 'Airfield L1', ['cow' => 9], [], [], 'Airfield. Takes 10 square miles. Effect: +3 aircraft, 2cm range, unlocks recon planes.'],
                ['struct_training_ground_l1', 'Training Ground L1', ['cow' => 8, 'ref_GBR' => 2, 'ref_H' => 1], [], [], 'Training Ground. Takes 10 square miles. Effect: +0.2X chosen attribute(s) per year, capacity 2.'],
                ['struct_trf_l1', 'TRF L1', ['cow' => 20, 'ref_FS' => 1, 'ref_URM' => 1, 'food' => 5, 'ref_CHB' => 1, 'ref_CB' => 1], [], [], 'Technological Research Facility. Takes 10 square miles. Effect: 8-1 conversion rate and SS unlocked.'],
                ['struct_teleporter_l1', 'Teleporter L1', ['cow' => 20, 'ref_FS' => 3, 'ref_DE' => 1, 'ref_CGM' => 1], [], ['cur_CB' => 1], 'Teleporter. Takes 10 square miles. Effect: instant teleport within 1cm capital radius for 1CB yearly maintenance.'],
                ['struct_canal_l1', 'Canal', ['cow' => 9, 'ore' => 4, 'ref_M' => 1, 'food' => 2], [], [], 'Canal. Uses land/water terrain. Effect: changes 0.5cm of land to water.'],
                ['struct_spore_factory_l1', 'Spore Factory L1', [], ['cur_SP' => 1000000, 'ref_MYC' => 1], [], 'Spore Factory. Forced build. Effect: +1mSP and +1MYC yearly.'],
                ['struct_core_detonater_l1', 'Core Detonater L1', ['cur_GB' => 10000, 'ore' => 1, 'food' => 1, 'ref_H' => 1], [], [], 'Core Detonater. Takes 10 square miles. Effect: Deep hole.'],
                ['struct_temple_l1', 'Temple L1', ['cow' => 5, 'ore' => 3], [], [], 'Temple. Takes 10 square miles. Effect: +1FTH, 1st level faith unit unlocked.'],
                ['struct_heavy_mine_l1', 'Heavy Mine L1', ['cow' => 15, 'ref_M' => 2], ['cur_G' => 1], [], 'Heavy Mine. Takes 10 square miles. Effect: +1MTH and chance-based advanced mineral output.'],
                ['struct_grass_farm_l1', 'Grass Farm L1', ['cow' => 2, 'food' => 2], ['ref_ZZ' => 1, 'cur_SP' => 150], [], 'Grass Farm. Takes 10 square miles. Effect: +1ZZ and +150SP yearly.'],
            ];

            $upgradeItems = [
                ['struct_capital_l2', 'Capital L2', ['cow' => 4, 'ore' => 1, 'food' => 1, 'wood' => 1], ['cow' => 4], ['food' => 1], 'Requires Capital L1. Effect: +4C yearly, -1F yearly, +10% defensive roll.'],
                ['struct_capital_l3', 'Capital L3', ['cow' => 7, 'ore' => 2, 'food' => 2, 'ref_H' => 1], ['cow' => 7, 'wood' => 1], ['food' => 2], 'Requires Capital L2. Effect: +7C, +1W, -2F, +20% defensive roll.'],
                ['struct_capital_l4', 'Capital L4', ['cow' => 10, 'ref_M' => 1, 'food' => 3, 'ref_H' => 1], ['cow' => 10, 'wood' => 2], ['food' => 2], 'Requires Capital L3. Effect: +10C, +2W, -2F, +33% defensive roll.'],
                ['struct_capital_l5', 'Capital L5', ['cow' => 14, 'ref_M' => 1, 'ref_RM' => 1, 'food' => 1, 'ref_GBR' => 1], ['cow' => 15, 'ore' => 1, 'wood' => 2], ['food' => 3], 'Requires Capital L4. Effect: +15C, +1O, +2W, -3F, +50% defensive roll.'],
                ['struct_capital_l6', 'Capital L6', ['cow' => 20, 'ref_FS' => 1, 'food' => 2, 'ref_GBR' => 1, 'ref_CB' => 1], ['cow' => 25, 'ore' => 1, 'wood' => 4], ['food' => 4], 'Requires Capital L5. Effect: +25C, +1O, +4W, -4F, +100% defensive roll.'],
                ['struct_capital_l7', 'Capital L7', ['cow' => 35, 'ref_URM' => 1, 'food' => 2, 'ref_CFB' => 1], ['cow' => 40, 'ore' => 2, 'wood' => 5], ['food' => 5], 'Requires Capital L6. Effect: +40C, +2O, +5W, -5F, +150% defensive roll.'],
                ['struct_capital_l8', 'Capital L8', ['cow' => 50, 'ref_FS' => 1, 'ref_AD' => 1, 'food' => 5, 'ref_TW' => 3], ['cow' => 60, 'ore' => 3, 'wood' => 7], ['food' => 6], 'Requires Capital L7. Effect: +60C, +3O, +7W, -6F, +200% defensive roll.'],
                ['struct_capital_l9', 'Capital L9', ['cow' => 70, 'ref_AM' => 1, 'food' => 3, 'ref_SR' => 1], ['cow' => 80, 'ore' => 4, 'wood' => 10], ['food' => 8], 'Requires Capital L8. Effect: +80C, +4O, +10W, -8F, +250% defensive roll.'],
                ['struct_capital_l10', 'Capital L10', ['cow' => 100, 'food' => 10, 'ref_WSH' => 1], ['cow' => 100, 'ore' => 5, 'wood' => 10], ['food' => 10], 'Requires Capital L9. Effect: +100C, +5O, +10W, -10F, +300% defensive roll.'],
                ['struct_refinery_l2', 'Refinery L2', ['cow' => 8, 'ref_M' => 2, 'food' => 1, 'ref_H' => 1], [], [], 'Requires Refinery L1. Conversion rate improves to 4-to-1.'],
                ['struct_refinery_l3', 'Refinery L3', ['cow' => 12, 'ref_M' => 3, 'food' => 1, 'ref_GBR' => 1, 'ref_H' => 2], [], [], 'Requires Refinery L2. Conversion rate improves to 3-to-1.'],
                ['struct_fort_l2', 'Fort L2', ['cow' => 4, 'ore' => 2, 'food' => 1], [], [], 'Requires Fort L1. Effect: +50% to each defensive roll.'],
                ['struct_fort_l3', 'Fort L3', ['cow' => 7, 'ref_GBR' => 1, 'ref_H' => 1], [], [], 'Requires Fort L2. Effect: +100% to each defensive roll.'],
                ['struct_fort_l4', 'Fort L4', ['cow' => 10, 'ref_RM' => 1, 'food' => 2, 'ref_H' => 1], [], [], 'Requires Fort L3. Effect: +200% to each defensive roll.'],
                ['struct_fort_l5', 'Fort L5', ['cow' => 13, 'ref_FS' => 1, 'food' => 2, 'ref_GBR' => 2], [], [], 'Requires Fort L4. Effect: +300% to each defensive roll.'],
                ['struct_city_l2', 'City L2', ['cow' => 2, 'ore' => 1, 'food' => 1], ['cow' => 4], ['food' => 1], 'Requires City L1. Effect: +4C yearly, -1F yearly.'],
                ['struct_city_l3', 'City L3', ['cow' => 3, 'food' => 3, 'ref_H' => 1], ['cow' => 8], ['food' => 2], 'Requires City L2. Effect: +8C yearly, -2F yearly.'],
                ['struct_city_l4', 'City L4', ['cow' => 5, 'ref_M' => 1, 'food' => 2, 'ref_H' => 1], ['cow' => 14], ['food' => 3], 'Requires City L3. Effect: +14C yearly, -3F yearly.'],
                ['struct_city_l5', 'City L5', ['cow' => 7, 'ref_M' => 1, 'food' => 1, 'ref_CB' => 1], ['cow' => 20], ['food' => 4], 'Requires City L4. Effect: +20C yearly, -4F yearly.'],
                ['struct_farm_l2', 'Farm L2', ['cow' => 3, 'wood' => 1], ['food' => 3], [], 'Requires Farm L1. Effect: +3F yearly.'],
                ['struct_farm_l3', 'Farm L3', ['cow' => 7, 'ore' => 1, 'wood' => 1], ['food' => 6], [], 'Requires Farm L2. Effect: +6F yearly.'],
                ['struct_farm_l4', 'Farm L4', ['cow' => 12, 'ref_H' => 2], ['food' => 10], [], 'Requires Farm L3. Effect: +10F yearly.'],
                ['struct_farm_l5', 'Farm L5', ['cow' => 17, 'ref_M' => 1, 'ref_TW' => 1], ['food' => 15], [], 'Requires Farm L4. Effect: +15F yearly.'],
                ['struct_lodging_l2', 'Lodging L2', ['cow' => 3, 'food' => 1, 'wood' => 1], ['wood' => 3], [], 'Requires Lodging L1. Effect: +3W yearly.'],
                ['struct_lodging_l3', 'Lodging L3', ['cow' => 5, 'ore' => 2, 'ref_GBR' => 1], ['wood' => 6], [], 'Requires Lodging L2. Effect: +6W yearly.'],
                ['struct_lodging_l4', 'Lodging L4', ['cow' => 8, 'ref_M' => 1, 'ref_GBR' => 2], ['wood' => 10], [], 'Requires Lodging L3. Effect: +10W yearly.'],
                ['struct_lodging_l5', 'Lodging L5', ['cow' => 11, 'ref_FS' => 1, 'food' => 2], ['wood' => 15], [], 'Requires Lodging L4. Effect: +15W yearly.'],
                ['struct_mine_l2', 'Mine L2', ['cow' => 4, 'food' => 1, 'wood' => 1], ['ore' => 3], [], 'Requires Mine L1. Effect: +3O yearly.'],
                ['struct_mine_l3', 'Mine L3', ['cow' => 7, 'ore' => 1, 'food' => 1, 'ref_H' => 1], ['ore' => 5], [], 'Requires Mine L2. Effect: +5O yearly.'],
                ['struct_mine_l4', 'Mine L4', ['cow' => 10, 'ref_M' => 1, 'ref_GBR' => 1, 'ref_H' => 1], ['ore' => 7], [], 'Requires Mine L3. Effect: +7O yearly.'],
                ['struct_mine_l5', 'Mine L5', ['cow' => 12, 'ref_FS' => 1, 'ref_H' => 1], ['ore' => 10], [], 'Requires Mine L4. Effect: +10O yearly.'],
                ['struct_barracks_l2', 'Barracks L2', ['cow' => 4, 'food' => 1, 'wood' => 1], [], [], 'Requires Barracks L1. Unlocks armored infantry.', ['unit_code' => 'dak_light_infantry', 'qty' => 2]],
                ['struct_barracks_l3', 'Barracks L3', ['cow' => 6, 'food' => 2, 'ref_H' => 1], [], [], 'Requires Barracks L2. Unlocks heavy infantry.', ['unit_code' => 'dak_light_infantry', 'qty' => 2]],
                ['struct_barracks_l4', 'Barracks L4', ['cow' => 10, 'ore' => 2, 'ref_GBR' => 1, 'ref_H' => 1], [], [], 'Requires Barracks L3. Unlocks armored heavy infantry.', ['unit_code' => 'dak_light_infantry', 'qty' => 2]],
                ['struct_barracks_l5', 'Barracks L5', ['cow' => 13, 'ref_M' => 1, 'ref_GBR' => 2, 'ref_H' => 1], [], [], 'Requires Barracks L4. Unlocks super heavy infantry.', ['unit_code' => 'dak_light_infantry', 'qty' => 1]],
                ['struct_factory_l2', 'Factory L2', ['cow' => 4, 'ore' => 1, 'food' => 1], [], [], 'Requires Factory L1. Unlocks heavy artillery.'],
                ['struct_factory_l3', 'Factory L3', ['cow' => 8, 'ref_M' => 1, 'food' => 1], [], [], 'Requires Factory L2. Unlocks tanks.'],
                ['struct_factory_l4', 'Factory L4', ['cow' => 10, 'ref_M' => 2, 'ref_H' => 1], [], [], 'Requires Factory L3. Unlocks super heavy artillery.'],
                ['struct_factory_l5', 'Factory L5', ['cow' => 15, 'ref_M' => 1, 'ref_RM' => 1, 'ref_H' => 1], [], [], 'Requires Factory L4. Unlocks armored tanks.'],
                ['struct_shipyard_l2', 'Shipyard L2', ['cow' => 6, 'ore' => 1, 'food' => 1, 'wood' => 2], [], [], 'Requires Shipyard L1. Unlocks ironclads and frigates.'],
                ['struct_shipyard_l3', 'Shipyard L3', ['cow' => 10, 'ref_M' => 1, 'food' => 1, 'ref_H' => 1], [], [], 'Requires Shipyard L2. Unlocks heavy cruisers.'],
                ['struct_shipyard_l4', 'Shipyard L4', ['cow' => 15, 'ref_M' => 1, 'ref_RM' => 1, 'ref_SR' => 1, 'ref_H' => 2], [], [], 'Requires Shipyard L3. Unlocks destroyers.'],
                ['struct_shipyard_l5', 'Shipyard L5', ['cow' => 20, 'ref_FS' => 1, 'food' => 2, 'ref_H' => 2, 'ref_CB' => 1], [], [], 'Requires Shipyard L4. Unlocks submarines.'],
                ['struct_shipyard_l6', 'Shipyard L6', ['cow' => 25, 'ref_FS' => 1, 'ref_CHB' => 1, 'ref_CFB' => 1], [], [], 'Requires Shipyard L5. Unlocks dreadnoughts.'],
                ['struct_shipyard_l7', 'Shipyard L7', ['cow' => 30, 'ref_AD' => 1, 'ref_SR' => 1, 'ref_CFB' => 1], [], [], 'Requires Shipyard L6. Unlocks aircraft carriers.'],
                ['struct_shipyard_l8', 'Shipyard L8', ['cow' => 35, 'food' => 5, 'ref_CB' => 1], [], [], 'Requires Shipyard L7. Unlocks nuclear submarines.'],
                ['struct_shipyard_l9', 'Shipyard L9', ['cow' => 40, 'ref_AM' => 1, 'ref_IC' => 1], [], [], 'Requires Shipyard L8. Unlocks floating fortresses.'],
                ['struct_shipyard_l10', 'Shipyard L10', ['cow' => 50, 'ref_DM' => 3], [], [], 'Requires Shipyard L9. Unlocks supersteel submarines.'],
                ['struct_airfield_l2', 'Airfield L2', ['cow' => 12, 'food' => 1, 'ref_H' => 1], [], [], 'Requires Airfield L1. Unlocks blimps.'],
                ['struct_airfield_l3', 'Airfield L3', ['cow' => 15, 'ref_M' => 1, 'food' => 1, 'ref_GBR' => 1], [], [], 'Requires Airfield L2. Unlocks fighters.'],
                ['struct_airfield_l4', 'Airfield L4', ['cow' => 20, 'ref_RM' => 1, 'ref_GBR' => 1, 'ref_CB' => 1], [], [], 'Requires Airfield L3. Unlocks bombers.'],
                ['struct_airfield_l5', 'Airfield L5', ['cow' => 25, 'ref_M' => 4, 'ref_SR' => 1], [], [], 'Requires Airfield L4. Unlocks fighter jets.'],
                ['struct_airfield_l6', 'Airfield L6', ['cow' => 30, 'ref_FS' => 1, 'ref_CHB' => 3, 'ref_CB' => 1], [], [], 'Requires Airfield L5. Unlocks airships.'],
                ['struct_airfield_l7', 'Airfield L7', ['cow' => 35, 'ref_URM' => 1, 'food' => 5, 'ref_CFB' => 1], [], [], 'Requires Airfield L6. Unlocks stealth fighters.'],
                ['struct_airfield_l8', 'Airfield L8', ['cow' => 40, 'ref_AD' => 1, 'ref_CB' => 3, 'ref_CFB' => 1], [], [], 'Requires Airfield L7. Unlocks stealth bombers.'],
                ['struct_airfield_l9', 'Airfield L9', ['cow' => 45, 'ref_AM' => 1], [], [], 'Requires Airfield L8. Unlocks heavy airships.'],
                ['struct_airfield_l10', 'Airfield L10', ['cow' => 50, 'ref_DM' => 1, 'ref_DE' => 1], [], [], 'Requires Airfield L9. Unlocks flying fortresses.'],
                ['struct_training_ground_l2', 'Training Ground L2', ['cow' => 12, 'ref_M' => 1, 'ref_GBR' => 3], [], [], 'Requires Training Ground L1. +0.3X training bonus, capacity 3.'],
                ['struct_training_ground_l3', 'Training Ground L3', ['cow' => 16, 'ref_FS' => 1, 'ref_CHB' => 1, 'ref_TW' => 1], [], [], 'Requires Training Ground L2. +0.4X training bonus, capacity 4.'],
                ['struct_training_ground_l4', 'Training Ground L4', ['cow' => 24, 'ref_RM' => 2, 'ref_SR' => 3, 'ref_CB' => 1], [], [], 'Requires Training Ground L3. +0.6X training bonus, capacity 5.'],
                ['struct_training_ground_l5', 'Training Ground L5', ['cow' => 32, 'ref_FS' => 1, 'ref_ZZ' => 3, 'ref_IC' => 1], [], [], 'Requires Training Ground L4. +1X training bonus, capacity 5.'],
                ['struct_trf_l2', 'TRF L2', ['cow' => 15, 'ref_AD' => 1, 'ref_PZA' => 1, 'ref_CFB' => 1, 'ref_BST' => 1], [], [], 'Requires TRF L1. 7-to-1 conversion and additional unlocks.'],
                ['struct_trf_l3', 'TRF L3', ['cow' => 20, 'ref_AM' => 1, 'ref_SR' => 3], [], [], 'Requires TRF L2. 6-to-1 conversion and additional unlocks.'],
                ['struct_trf_l4', 'TRF L4', ['cow' => 30, 'ref_DM' => 1, 'ref_IC' => 1], [], [], 'Requires TRF L3. 5-to-1 conversion and additional unlocks.'],
                ['struct_trf_l5', 'TRF L5', ['cow' => 40, 'ref_DE' => 1], [], [], 'Requires TRF L4. 4-to-1 conversion and additional unlocks.'],
                ['struct_teleporter_l2', 'Teleporter L2', ['cow' => 20, 'ref_CHB' => 1, 'ref_CFB' => 1], [], ['cow' => 10], 'Requires Teleporter L1. Teleport cost 10C.'],
                ['struct_teleporter_l3', 'Teleporter L3', ['cow' => 25, 'ref_WSH' => 1], [], ['cow' => 5], 'Requires Teleporter L2. Teleport radius increase.'],
                ['struct_teleporter_l4', 'Teleporter L4', ['cow' => 25, 'ref_DM' => 1], [], ['cow' => 2], 'Requires Teleporter L3. Teleport radius increase.'],
                ['struct_teleporter_l5', 'Teleporter L5', ['cow' => 25, 'ref_DE' => 1], [], [], 'Requires Teleporter L4. Teleport cost removed.'],
                ['struct_spore_factory_l2', 'Spore Factory L2', ['cur_SP' => 4000000, 'ref_RM' => 1, 'ref_TW' => 2, 'ref_MYC' => 2], ['cur_SP' => 3000000, 'ref_MYC' => 2], [], 'Requires Spore Factory L1. +3mSP and +2MYC yearly.'],
                ['struct_spore_factory_l3', 'Spore Factory L3', ['cur_SP' => 9000000, 'ref_URM' => 1, 'ref_TW' => 3, 'ref_SM' => 1], ['cur_SP' => 6000000, 'ref_SM' => 1], [], 'Requires Spore Factory L2. +6mSP and +1SM yearly.'],
                ['struct_spore_factory_l4', 'Spore Factory L4', ['cur_SP' => 15000000, 'ref_URM' => 1, 'ref_MYC' => 5, 'ref_TW' => 5], ['cur_SP' => 10000000, 'ref_MYC' => 1, 'ref_SM' => 1], [], 'Requires Spore Factory L3. +10mSP, +1MYC, +1SM yearly.'],
                ['struct_spore_factory_l5', 'Spore Factory L5', ['cur_SP' => 20000000, 'ref_URM' => 3, 'ref_TW' => 10], ['cur_SP' => 15000000, 'ref_MYC' => 3, 'ref_SM' => 1], [], 'Requires Spore Factory L4. +15mSP, +3MYC, +1SM yearly.'],
                ['struct_core_detonater_l2', 'Core Detonater L2', ['cur_GB' => 20000, 'ref_M' => 1, 'ref_GBR' => 1], [], [], 'Requires Core Detonater L1. Very deep hole.'],
                ['struct_core_detonater_l3', 'Core Detonater L3', ['cur_GB' => 50000, 'ref_FS' => 1, 'food' => 5], [], [], 'Requires Core Detonater L2. 1cm radius detonation effect.'],
                ['struct_core_detonater_l4', 'Core Detonater L4', ['cur_GB' => 75000, 'ref_AD' => 1, 'ref_CHB' => 2], [], [], 'Requires Core Detonater L3. 10cm radius detonation effect.'],
                ['struct_core_detonater_l5', 'Core Detonater L5', ['cur_GB' => 100000, 'ref_CB' => 3], [], [], 'Requires Core Detonater L4. 1m radius detonation effect.'],
                ['struct_temple_l2', 'Temple L2', ['cow' => 10, 'ore' => 1, 'ref_GBR' => 1, 'ref_H' => 1], [], [], 'Requires Temple L1. +2FTH and 2nd level faith unit unlock.'],
                ['struct_temple_l3', 'Temple L3', ['cow' => 15, 'ref_M' => 1, 'cur_G' => 1, 'ref_SR' => 1], [], [], 'Requires Temple L2. +3FTH and 3rd level faith unit unlock.'],
                ['struct_temple_l4', 'Temple L4', ['cow' => 25, 'ore' => 10, 'ref_CHB' => 3], [], [], 'Requires Temple L3. +4FTH and 4th level faith unit unlock.'],
                ['struct_temple_l5', 'Temple L5', ['cow' => 40, 'ref_AD' => 3, 'ref_PZA' => 3], [], [], 'Requires Temple L4. +5FTH and 5th level faith unit unlock.'],
                ['struct_heavy_mine_l2', 'Heavy Mine L2', ['cow' => 20], ['cur_G' => 1], [], 'Requires Heavy Mine L1. Additional mythril and special mineral chance.'],
                ['struct_heavy_mine_l3', 'Heavy Mine L3', ['cow' => 25], ['cur_G' => 1], [], 'Requires Heavy Mine L2. More mythril and special mineral chance.'],
                ['struct_heavy_mine_l4', 'Heavy Mine L4', ['cow' => 30], ['cur_G' => 1], [], 'Requires Heavy Mine L3. Higher advanced mineral yield.'],
                ['struct_heavy_mine_l5', 'Heavy Mine L5', ['cow' => 35], ['cur_G' => 2], [], 'Requires Heavy Mine L4. Peak advanced mineral yield.'],
                ['struct_grass_farm_l2', 'Grass Farm L2', ['cow' => 5, 'food' => 1, 'wood' => 1], ['ref_ZZ' => 3, 'cur_SP' => 750], [], 'Requires Grass Farm L1. +3ZZ and +750SP yearly.'],
                ['struct_grass_farm_l3', 'Grass Farm L3', ['cow' => 7, 'ref_M' => 1, 'food' => 2], ['ref_ZZ' => 6, 'cur_SP' => 2500], [], 'Requires Grass Farm L2. +6ZZ and +2.5kSP yearly.'],
                ['struct_grass_farm_l4', 'Grass Farm L4', ['cow' => 10, 'ref_GBR' => 1, 'ref_TW' => 2], ['ref_ZZ' => 10, 'cur_SP' => 15000], [], 'Requires Grass Farm L3. +10ZZ and +15kSP yearly.'],
                ['struct_grass_farm_l5', 'Grass Farm L5', ['cow' => 15, 'ref_RM' => 1, 'food' => 2, 'ref_TW' => 3], ['ref_ZZ' => 15, 'cur_SP' => 60000], [], 'Requires Grass Farm L4. +15ZZ and +60kSP yearly.'],
                ['struct_nuclear_refinery_l1', 'Nuclear Refinery L1', ['cow' => 15, 'ref_M' => 5, 'food' => 2, 'ref_GBR' => 3], [], [], 'Requires Refinery L3. Nuclear conversion 5-to-1.', ['requires_building_code' => 'refinery', 'requires_building_level' => 3]],
                ['struct_nuclear_refinery_l2', 'Nuclear Refinery L2', ['cow' => 20, 'ref_RM' => 1, 'ref_FS' => 1, 'ref_CHB' => 1, 'ref_TW' => 2, 'ref_CB' => 1], [], [], 'Requires Nuclear Refinery L1. Nuclear conversion 4-to-1.'],
                ['struct_nuclear_refinery_l3', 'Nuclear Refinery L3', ['cow' => 25, 'ref_FS' => 1, 'ref_URM' => 1, 'food' => 3, 'ref_SR' => 1], [], [], 'Requires Nuclear Refinery L2. Nuclear conversion 3-to-1.'],
                ['struct_nuclear_capital_l1', 'Nuclear Capital L1', ['cow' => 200, 'ref_PZA' => 10], ['cow' => 150, 'ref_M' => 5, 'wood' => 10], ['food' => 10], 'Requires Capital L10 and TRF L5. Nuclear capital tier unlocked.', ['requires_building_code' => 'capital', 'requires_building_level' => 10, 'requires_building_code_2' => 'trf', 'requires_building_level_2' => 5]],
                ['struct_nuclear_capital_l2', 'Nuclear Capital L2', ['cow' => 300, 'ref_IC' => 10], ['cow' => 200, 'ore' => 10, 'ref_M' => 5, 'ref_RM' => 5], ['food' => 5], 'Requires Nuclear Capital L1. Major strategic output increase.'],
                ['struct_nuclear_capital_l3', 'Nuclear Capital L3', ['cow' => 500, 'ref_WSH' => 10], ['cow' => 350, 'ore' => 20, 'ref_M' => 10, 'ref_RM' => 10], [], 'Requires Nuclear Capital L2. Capital becomes wartime movable unit.'],
                ['struct_nuclear_capital_l4', 'Nuclear Capital L4', ['cow' => 1000], ['cow' => 500, 'ore' => 30, 'ref_M' => 15, 'ref_RM' => 15], [], 'Requires Nuclear Capital L3. TRF refinement improves toward 3-to-1.'],
                ['struct_nuclear_capital_l5', 'Nuclear Capital L5', ['cow' => 2500], ['cow' => 1000, 'ore' => 50, 'ref_M' => 25, 'ref_RM' => 25], [], 'Requires Nuclear Capital L4. Dimensional control unlocked.'],
                ['struct_saf_l1', 'SAF L1', ['cow' => 15, 'ref_FS' => 1, 'ref_CHB' => 1, 'ref_SR' => 1], [], [], 'Requires Barracks L5 and Factory L5. SAF tier unlocked.', ['requires_building_code' => 'barracks', 'requires_building_level' => 5, 'requires_building_code_2' => 'factory', 'requires_building_level_2' => 5]],
                ['struct_saf_l2', 'SAF L2', ['cow' => 12, 'ref_FS' => 1, 'ref_CHB' => 1, 'ref_CB' => 1], [], [], 'Requires SAF L1.'],
                ['struct_saf_l3', 'SAF L3', ['cow' => 16, 'ref_URM' => 1, 'ref_SR' => 2], [], [], 'Requires SAF L2.'],
                ['struct_saf_l4', 'SAF L4', ['cow' => 20, 'ref_FS' => 2, 'ref_GBR' => 3, 'ref_TW' => 3], [], [], 'Requires SAF L3.'],
                ['struct_saf_l5', 'SAF L5', ['cow' => 25, 'ref_FS' => 2, 'ref_CHB' => 2, 'ref_CB' => 3], [], [], 'Requires SAF L4.'],
                ['struct_saf_l6', 'SAF L6', ['cow' => 30, 'ref_AM' => 1, 'ref_PZA' => 1, 'ref_CFB' => 1], [], [], 'Requires SAF L5.'],
                ['struct_saf_l7', 'SAF L7', ['cow' => 35, 'ref_AD' => 1, 'ref_DM' => 1, 'ref_IC' => 1], [], [], 'Requires SAF L6.'],
                ['struct_saf_l8', 'SAF L8', ['cow' => 40, 'ref_DE' => 1, 'ref_SR' => 3], [], [], 'Requires SAF L7.'],
                ['struct_saf_l9', 'SAF L9', ['cow' => 45, 'ref_WSH' => 1], [], [], 'Requires SAF L8.'],
                ['struct_saf_l10', 'SAF L10', ['cow' => 50], [], [], 'Requires SAF L9.'],
            ];

            $structuresCategoryId = DB::table('shop_categories')->where('code', 'structures')->value('id')
                ?? DB::table('shop_categories')->where('code', 'crafting')->value('id');
            $upgradesCategoryId = DB::table('shop_categories')->where('code', 'upgrades')->value('id');

            foreach ($structureItems as $structure) {
                [$code, $name, $cost, $yearlyEffect, $maintenance, $description] = array_slice($structure, 0, 6);
                $effect = $structure[6] ?? null;
                DB::table('shop_items')->updateOrInsert(
                    ['code' => $code],
                    [
                        'category_id' => $structuresCategoryId,
                        'display_name' => $name,
                        'description_text' => $description,
                        'cost_json' => json_encode($cost),
                        'maintenance_json' => json_encode($maintenance),
                        'yearly_effect_json' => json_encode($yearlyEffect),
                        'effect_json' => $effect ? json_encode($effect) : null,
                        'is_active' => 1,
                    ]
                );
            }

            foreach ($upgradeItems as $upgrade) {
                [$code, $name, $cost, $yearlyEffect, $maintenance, $description] = array_slice($upgrade, 0, 6);
                $effect = $upgrade[6] ?? null;
                DB::table('shop_items')->updateOrInsert(
                    ['code' => $code],
                    [
                        'category_id' => $upgradesCategoryId,
                        'display_name' => $name,
                        'description_text' => $description,
                        'cost_json' => json_encode($cost),
                        'maintenance_json' => json_encode($maintenance),
                        'yearly_effect_json' => json_encode($yearlyEffect),
                        'effect_json' => $effect ? json_encode($effect) : null,
                        'is_active' => 1,
                    ]
                );
            }

            $buildingFamilies = [
                'capital', 'refinery', 'fort', 'city', 'farm', 'lodging', 'mine', 'barracks', 'factory', 'shipyard',
                'airfield', 'training_ground', 'trf', 'teleporter', 'canal', 'spore_factory', 'core_detonater', 'temple',
                'heavy_mine', 'grass_farm'
            ];
            foreach ($buildingFamilies as $family) {
                DB::table('building_catalog')->updateOrInsert(
                    ['code' => $family],
                    ['display_name' => ucwords(str_replace('_', ' ', $family)), 'max_level' => 10]
                );
            }
        }

        // ── Currency exchange items ───────────────────────────────────────────────
        $currExchCategory = DB::table('shop_categories')->where('code', 'currency_exchange')->first();
        if ($currExchCategory) {
            $currencyItems = [
                ['exch_cow_to_gb',      'Convert Cow → Gobbo Bucks', ['cow' => 1],       ['currency' => ['GB'    => 2000]]],
                ['exch_cow_to_p',       'Convert Cow → Psycoin',     ['cow' => 1],       ['currency' => ['P'     => 30]]],
                ['exch_cow_to_gold',    'Convert Cow → Gold',        ['cow' => 1],       ['currency' => ['G'     => 3]]],
                ['exch_cow_to_silver',  'Convert Cow → Silver',      ['cow' => 10],      ['currency' => ['S'     => 3]]],
                ['exch_cow_to_bronze',  'Convert Cow → Bronze',      ['cow' => 100],     ['currency' => ['B'     => 3]]],
                ['exch_cow_to_xbux',    'Convert Cow → codebuX',     ['cow' => 10],      ['currency' => ['X'     => 1]]],
                ['exch_cow_to_credits', 'Convert Cow → Credits',     ['cow' => 20],      ['currency' => ['CD'    => 1]]],
                ['exch_cow_to_fd',      'Convert Cow → Fairy Dust',  ['cow' => 1],       ['currency' => ['FD'    => 40]]],
                ['exch_cow_to_cheese',  'Convert Cow → Cheese',      ['cow' => 70],      ['currency' => ['cheese'=> 1]]],
                ['exch_cow_to_spores',  'Convert Cow → SPores',      ['cow' => 1000000], ['currency' => ['SP'    => 1]]],
                ['exch_cow_to_rupees',  'Convert Cow → Rupees',      ['cow' => 1],       ['currency' => ['R'     => 10]]],
                ['exch_cow_to_marks',   'Convert Cow → MarKs',       ['cow' => 100],     ['currency' => ['MK'    => 1]]],
            ];
            foreach ($currencyItems as [$code, $name, $cost, $effect]) {
                DB::table('shop_items')->updateOrInsert(
                    ['code' => $code],
                    [
                        'category_id' => $currExchCategory->id,
                        'display_name' => $name,
                        'cost_json'    => json_encode($cost),
                        'effect_json'  => json_encode($effect),
                        'is_active'    => 1,
                    ]
                );
            }
        }

        // ── New unit catalog entries ──────────────────────────────────────────────
        $newUnitCatalog = [
            ['spy',              'Spy',                   'SPY' ],
            ['assassin',         'Assassin',              'ASN' ],
            ['saboteur',         'Saboteur',              'SBT' ],
            ['fine_coin',        'Fine Coin',             'FC'  ],
            ['ket_sushi',        'Ket Sushi',             'KS'  ],
            ['touch_grass',      'Touch Grass',           'TG'  ],
            ['fertilized_grass', 'Fertilized Grass',      'FG'  ],
            ['ntt',              'Ninja Turtle Takedown', 'NTT' ],
        ];
        foreach ($newUnitCatalog as [$code, $display, $class]) {
            DB::table('unit_catalog')->updateOrInsert(
                ['code' => $code],
                ['display_name' => $display, 'class_name' => $class, 'base_stats_json' => json_encode([])]
            );
        }

        // ── New recruitment shop items ────────────────────────────────────────────
        $recCat = DB::table('shop_categories')->where('code', 'recruitment')->first();
        if ($recCat) {
            $newRecruits = [
                ['recruit_spy',              'Recruit Spy',                   ['cow' => 3],                                        'spy'],
                ['recruit_assassin',         'Recruit Assassin',              ['cow' => 6],                                        'assassin'],
                ['recruit_saboteur',         'Recruit Saboteur',              ['cow' => 5],                                        'saboteur'],
                ['recruit_fine_coin',        'Mint Fine Coin',                ['cur_P' => 1],                                      'fine_coin'],
                ['recruit_ket_sushi',        'Prepare Ket Sushi',             ['ref_SR' => 1, 'ref_K' => 1, 'ref_RK' => 1],       'ket_sushi'],
                ['recruit_touch_grass',      'Grow Touch Grass',              ['ref_K' => 1, 'food' => 5],                        'touch_grass'],
                ['recruit_fertilized_grass', 'Fertilize Grass',               ['ref_K' => 1, 'food' => 5, 'ref_DP' => 1],         'fertilized_grass'],
                ['recruit_ntt',              'Recruit Ninja Turtle Takedown', ['cur_P' => 6],                                      'ntt'],
            ];
            foreach ($newRecruits as [$code, $name, $cost, $unitCode]) {
                DB::table('shop_items')->updateOrInsert(
                    ['code' => $code],
                    [
                        'category_id' => $recCat->id,
                        'display_name' => $name,
                        'cost_json'    => json_encode($cost),
                        'effect_json'  => json_encode(['unit_code' => $unitCode, 'qty' => 1]),
                        'is_active'    => 1,
                    ]
                );
            }
        }
    }
}
