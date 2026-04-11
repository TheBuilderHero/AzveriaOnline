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

            foreach ($structureItems as $structure) {
                [$code, $name, $cost, $yearlyEffect, $maintenance, $description] = array_slice($structure, 0, 6);
                $effect = $structure[6] ?? null;
                DB::table('shop_items')->updateOrInsert(
                    ['code' => $code],
                    [
                        'category_id' => DB::table('shop_categories')->where('code', 'crafting')->value('id'),
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
