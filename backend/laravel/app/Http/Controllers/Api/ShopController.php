<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BuyShopItemRequest;
use App\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ShopController extends Controller
{
    public function categories()
    {
        $this->authorize('viewAny', ShopItem::class);
        $this->ensurePrimaryShopCategories();

        return response()->json(
            DB::table('shop_categories')
                ->whereIn('code', ['craft', 'build', 'recruit', 'research'])
                ->orderByRaw("FIELD(code, 'craft', 'build', 'recruit', 'research')")
                ->get()
        );
    }

    public function items(Request $request)
    {
        $this->authorize('viewAny', ShopItem::class);
        $this->ensurePrimaryShopCategories();

        $category = $this->normalizeCategoryCode((string) $request->query('category', ''));
        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';

        $query = DB::table('shop_items as si')
            ->join('shop_categories as sc', 'si.category_id', '=', 'sc.id')
            ->select('si.*', 'sc.code as category_code', 'sc.display_name as category_name');

        if (!$isAdmin) {
            $query->where('si.is_active', 1);
            $userId = $user->id;
            $query->where(function ($q) use ($userId) {
                $q->whereNull('si.visibility_json')
                  ->orWhereRaw('JSON_CONTAINS(si.visibility_json, JSON_QUOTE(CAST(? AS CHAR)), \'$\')', [$userId]);
            });
        }

        if ($category !== '') {
            $query->whereIn('sc.code', $this->categoryAliases($category));
        }

        $rows = $query->orderBy('si.id')->get()->map(function ($item) {
            $code = $this->normalizeCategoryCode((string) ($item->category_code ?? ''));
            $item->category_code = $code;
            if ($code !== '') {
                $item->category_name = ucfirst($code);
            }
            $item->terrain_requirement_for_level = $this->structureTerrainRequirementForShopItem($item);
            return $item;
        });

        if ($isAdmin) {
            return response()->json($rows);
        }

        $nation = DB::table('nations')->where('owner_user_id', $user->id)->first();
        if (!$nation) {
            return response()->json([]);
        }

        $resourceRow = DB::table('nation_resources')->where('nation_id', $nation->id)->first();
        $balances = $this->loadNationBalances($resourceRow);
        $buildingLevels = $this->loadNationBuildingLevels((int) $nation->id);
        $researchSet = $this->loadNationResearchSet((int) $nation->id);

        $visible = $rows->filter(function ($item) use ($buildingLevels, $researchSet, $balances) {
            $rawRequirement = json_decode((string) ($item->requirement_json ?? 'null'), true);
            return $this->nationMeetsRequirement($rawRequirement, $buildingLevels, $researchSet, $balances);
        })->values();

        return response()->json($visible);
    }

    public function buy(BuyShopItemRequest $request)
    {
        $data = $request->validated();

        $quantity = (int) ($data['quantity'] ?? 1);
        $item = DB::table('shop_items as si')
            ->join('shop_categories as sc', 'si.category_id', '=', 'sc.id')
            ->where('si.id', $data['item_id'])
            ->select('si.*', 'sc.code as category_code')
            ->first();
        if (!$item || !$item->is_active) {
            return response()->json(['message' => 'Item unavailable'], 422);
        }
        $categoryCode = $this->normalizeCategoryCode((string) ($item->category_code ?? ''));

        $structureMeta = $this->parseStructureCode((string) $item->code);
        $effects = json_decode($item->effect_json, true) ?: [];

        $this->authorize('buy', new ShopItem((array) $item));

        $nation = DB::table('nations')->where('owner_user_id', $request->user()->id)->first();
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        $resourceRow = DB::table('nation_resources')->where('nation_id', $nation->id)->first();
        if (!$resourceRow) {
            return response()->json(['message' => 'No resources found'], 404);
        }

        $buildingLevels = $this->loadNationBuildingLevels((int) $nation->id);
        $researchSet = $this->loadNationResearchSet((int) $nation->id);
        $balances = $this->loadNationBalances($resourceRow);
        $rawRequirement = json_decode((string) ($item->requirement_json ?? 'null'), true);
        if (!$this->nationMeetsRequirement($rawRequirement, $buildingLevels, $researchSet, $balances)) {
            return response()->json(['message' => 'Requirements not met for this item.'], 422);
        }

        if ($categoryCode === 'research' && Schema::hasTable('nation_research')) {
            $already = DB::table('nation_research')
                ->where('nation_id', $nation->id)
                ->where('research_code', (string) $item->code)
                ->exists();
            if ($already) {
                return response()->json(['message' => 'Research already completed.'], 422);
            }
            $quantity = 1;
        }

        if ($structureMeta && $structureMeta['level'] > 1) {
            $requiredLevel = $structureMeta['level'] - 1;
            $upgradableBuilding = DB::table('nation_buildings as nb')
                ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
                ->where('nb.nation_id', $nation->id)
                ->where('bc.code', $structureMeta['family'])
                ->where('nb.level', $requiredLevel)
                ->where('nb.status', 'built')
                ->orderBy('nb.id')
                ->first();
            if (!$upgradableBuilding) {
                return response()->json(['message' => 'Upgrade unavailable: you need an existing level ' . $requiredLevel . ' structure.'], 422);
            }
        }

        $plannedStructurePlacements = [];
        $plannedStructureUpgrades = [];
        $selectedTerrainType = strtolower(trim((string) ($data['terrain_type'] ?? '')));
        if ($selectedTerrainType !== '' && !in_array($selectedTerrainType, $this->terrainKeys(), true)) {
            return response()->json(['message' => 'Selected terrain type is invalid.'], 422);
        }
        $hasBuildingTerrainColumns = Schema::hasColumn('nation_buildings', 'terrain_type')
            && Schema::hasColumn('nation_buildings', 'terrain_allocated_square_miles');
        $hasCatalogTerrainRequirement = Schema::hasColumn('building_catalog', 'terrain_requirement_json');
        if ($structureMeta && $hasBuildingTerrainColumns && $hasCatalogTerrainRequirement) {
            $buildingCatalog = DB::table('building_catalog')
                ->where('code', (string) $structureMeta['family'])
                ->first();
            if (!$buildingCatalog) {
                return response()->json(['message' => 'Structure catalog entry is missing for this purchase.'], 422);
            }

            $terrainRequirementMap = $this->normalizeStructureTerrainRequirementMap(
                json_decode((string) ($buildingCatalog->terrain_requirement_json ?? 'null'), true) ?: []
            );
            $terrainAvailability = $this->computeNationTerrainAvailability((int) $nation->id);
            $structureLevel = max(1, (int) ($structureMeta['level'] ?? 1));
            $levelRule = $this->structureTerrainRequirementForLevel($terrainRequirementMap, $structureLevel);
            $levelAllowedTerrain = is_array($levelRule['allowed_terrain'] ?? null)
                ? array_values(array_filter(array_map(static fn ($v) => strtolower(trim((string) $v)), $levelRule['allowed_terrain'])))
                : [];
            $requiresTerrainSelection = count($levelAllowedTerrain) > 1;

            if ($requiresTerrainSelection && $selectedTerrainType === '') {
                return response()->json([
                    'message' => 'Select a terrain type before building this structure. Allowed: ' . implode(', ', $levelAllowedTerrain),
                ], 422);
            }
            if ($selectedTerrainType !== '' && !empty($levelAllowedTerrain) && !in_array($selectedTerrainType, $levelAllowedTerrain, true)) {
                return response()->json([
                    'message' => 'Selected terrain is not allowed for this structure level. Allowed: ' . implode(', ', $levelAllowedTerrain),
                ], 422);
            }

            if ((int) ($structureMeta['level'] ?? 1) <= 1) {
                for ($i = 0; $i < $quantity; $i++) {
                    $allocation = $this->allocateTerrainForStructurePlacement(
                        $terrainAvailability,
                        $terrainRequirementMap,
                        1,
                        $selectedTerrainType !== '' ? $selectedTerrainType : null
                    );
                    if ($allocation === null) {
                        return response()->json([
                            'message' => $selectedTerrainType !== ''
                                ? ('Not enough available ' . $selectedTerrainType . ' terrain to build this structure.')
                                : 'Not enough eligible terrain available to build this structure.',
                            'terrain_availability' => $terrainAvailability,
                        ], 422);
                    }
                    $plannedStructurePlacements[] = $allocation;
                }
            } else {
                $requiredLevel = max(1, (int) $structureMeta['level'] - 1);
                $upgradeRows = DB::table('nation_buildings')
                    ->where('nation_id', $nation->id)
                    ->where('building_catalog_id', (int) $buildingCatalog->id)
                    ->where('level', $requiredLevel)
                    ->where('status', 'built')
                    ->orderBy('id')
                    ->limit($quantity)
                    ->get();

                if ($upgradeRows->count() < $quantity) {
                    return response()->json(['message' => 'Upgrade unavailable: you need ' . $quantity . ' existing level ' . $requiredLevel . ' structures.'], 422);
                }

                foreach ($upgradeRows as $row) {
                    $oldType = strtolower(trim((string) ($row->terrain_type ?? '')));
                    $oldAmount = max(0.0, (float) ($row->terrain_allocated_square_miles ?? 0));
                    if ($oldType !== '' && array_key_exists($oldType, $terrainAvailability['available'])) {
                        $terrainAvailability['available'][$oldType] = (float) ($terrainAvailability['available'][$oldType] ?? 0) + $oldAmount;
                        $terrainAvailability['used'][$oldType] = max(0.0, (float) ($terrainAvailability['used'][$oldType] ?? 0) - $oldAmount);
                    }

                    $allocation = $this->allocateTerrainForStructurePlacement(
                        $terrainAvailability,
                        $terrainRequirementMap,
                        (int) $structureMeta['level'],
                        $selectedTerrainType !== '' ? $selectedTerrainType : null
                    );
                    if ($allocation === null) {
                        return response()->json([
                            'message' => $selectedTerrainType !== ''
                                ? ('Not enough available ' . $selectedTerrainType . ' terrain to upgrade this structure.')
                                : 'Not enough eligible terrain available to upgrade this structure.',
                            'terrain_availability' => $terrainAvailability,
                        ], 422);
                    }

                    $plannedStructureUpgrades[] = [
                        'row_id' => (int) $row->id,
                        'terrain_type' => $allocation['terrain_type'] ?? null,
                        'terrain_allocated_square_miles' => (float) ($allocation['terrain_allocated_square_miles'] ?? 0),
                    ];
                }
            }
        }

        if (is_array($effects) && isset($effects['requires_building_code'])) {
            $requiredCode = (string) $effects['requires_building_code'];
            $requiredLevel = (int) ($effects['requires_building_level'] ?? 1);
            $owned = DB::table('nation_buildings as nb')
                ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
                ->where('nb.nation_id', $nation->id)
                ->where('bc.code', $requiredCode)
                ->where('nb.level', '>=', max(1, $requiredLevel))
                ->where('nb.status', 'built')
                ->exists();
            if (!$owned) {
                return response()->json(['message' => 'Requires ' . $requiredCode . ' level ' . max(1, $requiredLevel) . ' or higher.'], 422);
            }

            if (isset($effects['requires_building_code_2'])) {
                $requiredCode2 = (string) $effects['requires_building_code_2'];
                $requiredLevel2 = (int) ($effects['requires_building_level_2'] ?? 1);
                $owned2 = DB::table('nation_buildings as nb')
                    ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
                    ->where('nb.nation_id', $nation->id)
                    ->where('bc.code', $requiredCode2)
                    ->where('nb.level', '>=', max(1, $requiredLevel2))
                    ->where('nb.status', 'built')
                    ->exists();
                if (!$owned2) {
                    return response()->json(['message' => 'Requires ' . $requiredCode2 . ' level ' . max(1, $requiredLevel2) . ' or higher.'], 422);
                }
            }
        }

        $costs = json_decode($item->cost_json, true) ?: [];
        $extra = json_decode($resourceRow->extra_json ?? '{}', true) ?: [];
        $base = $this->legacyCoreBaseResourceValues($resourceRow);
        foreach ((is_array($extra['base'] ?? null) ? $extra['base'] : []) as $key => $value) {
            $base[(string) $key] = (float) $value;
        }
        $advanced = is_array($extra['advanced'] ?? null)
            ? $extra['advanced']
            : (is_array($extra['refined'] ?? null) ? $extra['refined'] : []);
        $currencies = is_array($extra['currencies'] ?? null) ? $extra['currencies'] : [];

        // Validate all costs
        foreach ($costs as $resource => $cost) {
            $required = (float) $cost * $quantity;
            $token = $this->parseResourceToken((string) $resource);
            if (!$token) {
                continue;
            }

            if ($token['bucket'] === 'base') {
                if ((float) ($base[$token['name']] ?? 0) < $required) {
                    return response()->json(['message' => "Not enough base resource: {$token['name']}"], 422);
                }
                continue;
            }

            if ($token['bucket'] === 'advanced') {
                if ((float) ($advanced[$token['name']] ?? 0) < $required) {
                    return response()->json(['message' => "Not enough advanced resource: {$token['name']}"], 422);
                }
                continue;
            }

            if ((float) ($currencies[$token['name']] ?? 0) < $required) {
                return response()->json(['message' => "Not enough currency: {$token['name']}"], 422);
            }
        }

        // Apply cost deductions
        foreach ($costs as $resource => $cost) {
            $amount = (float) $cost * $quantity;
            $token = $this->parseResourceToken((string) $resource);
            if (!$token) {
                continue;
            }

            if ($token['bucket'] === 'base') {
                $base[$token['name']] = (float) ($base[$token['name']] ?? 0) - $amount;
                continue;
            }

            if ($token['bucket'] === 'advanced') {
                $advanced[$token['name']] = (float) ($advanced[$token['name']] ?? 0) - $amount;
                continue;
            }

            $currencies[$token['name']] = (float) ($currencies[$token['name']] ?? 0) - $amount;
        }

        // Apply effect gains
        if (isset($effects['advanced']) && is_array($effects['advanced'])) {
            foreach ($effects['advanced'] as $key => $gain) {
                $advanced[(string) $key] = (float) ($advanced[(string) $key] ?? 0) + ((float) $gain * $quantity);
            }
        }
        if (isset($effects['refined']) && is_array($effects['refined'])) {
            foreach ($effects['refined'] as $key => $gain) {
                $advanced[(string) $key] = (float) ($advanced[(string) $key] ?? 0) + ((float) $gain * $quantity);
            }
        }
        if (isset($effects['currencies']) && is_array($effects['currencies'])) {
            foreach ($effects['currencies'] as $key => $gain) {
                $currencies[(string) $key] = (float) ($currencies[(string) $key] ?? 0) + ((float) $gain * $quantity);
            }
        }
        if (isset($effects['currency']) && is_array($effects['currency'])) {
            foreach ($effects['currency'] as $key => $gain) {
                $currencies[(string) $key] = (float) ($currencies[(string) $key] ?? 0) + ((float) $gain * $quantity);
            }
        }

        // Legacy compatibility: support effect_json like {"gain":{"metal":1}}.
        if (isset($effects['gain']) && is_array($effects['gain'])) {
            foreach ($effects['gain'] as $key => $gain) {
                $normalizedKey = $this->normalizeRefinedKey((string) $key);
                if ($normalizedKey !== null) {
                    $advanced[$normalizedKey] = (float) ($advanced[$normalizedKey] ?? 0) + ((float) $gain * $quantity);
                }
            }
        }

        // Backward-compatible effect handling: direct keys in effect_json
        foreach ($effects as $effectKey => $effectValue) {
            if (!is_numeric($effectValue)) {
                continue;
            }
            $gain = (float) $effectValue * $quantity;
            $token = $this->parseResourceToken((string) $effectKey);
            if (!$token) {
                continue;
            }

            if ($token['bucket'] === 'base') {
                $base[$token['name']] = (float) ($base[$token['name']] ?? 0) + $gain;
                continue;
            }

            if ($token['bucket'] === 'advanced') {
                $advanced[$token['name']] = (float) ($advanced[$token['name']] ?? 0) + $gain;
                continue;
            }

            $currencies[$token['name']] = (float) ($currencies[$token['name']] ?? 0) + $gain;
        }

        $extraBase = [];
        foreach ($base as $key => $value) {
            if (in_array($key, $baseColumns, true)) {
                continue;
            }
            $extraBase[$key] = (float) $value;
        }

        $extra['base'] = $extraBase;
        $extra['advanced'] = $advanced;
        $extra['refined'] = $advanced;
        $extra['currencies'] = $currencies;
        DB::table('nation_resources')->where('nation_id', $nation->id)->update([
            'cow' => (float) ($base['cow'] ?? 0),
            'wood' => (float) ($base['wood'] ?? 0),
            'ore' => (float) ($base['ore'] ?? 0),
            'food' => (float) ($base['food'] ?? 0),
            'extra_json' => json_encode($extra),
            'updated_at' => now(),
        ]);

        // Handle unit recruitment effect
        if (isset($effects['unit_code'])) {
            $unitCatalog = DB::table('unit_catalog')->where('code', $effects['unit_code'])->first();
            if ($unitCatalog) {
                $qty = (int) ($effects['qty'] ?? 1) * $quantity;
                $existing = DB::table('nation_units')
                    ->where('nation_id', $nation->id)
                    ->where('unit_catalog_id', $unitCatalog->id)
                    ->where('status', 'owned')
                    ->first();
                if ($existing) {
                    DB::table('nation_units')->where('id', $existing->id)->update([
                        'qty' => $existing->qty + $qty,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('nation_units')->insert([
                        'nation_id' => $nation->id,
                        'unit_catalog_id' => $unitCatalog->id,
                        'qty' => $qty,
                        'status' => 'owned',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $updatedRow = DB::table('nation_resources')->where('nation_id', $nation->id)->first();
        $updatedExtra = json_decode($updatedRow->extra_json ?? '{}', true) ?: [];

        $hasTrackedAsset = $categoryCode !== 'research'
            && (!empty(json_decode($item->maintenance_json ?? 'null', true) ?: [])
            || !empty(json_decode($item->yearly_effect_json ?? 'null', true) ?: []));
        if ($hasTrackedAsset) {
            $existingAsset = DB::table('nation_assets')
                ->where('nation_id', $nation->id)
                ->where('shop_item_id', $item->id)
                ->first();
            if ($existingAsset) {
                DB::table('nation_assets')->where('id', $existingAsset->id)->update([
                    'qty' => $existingAsset->qty + $quantity,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('nation_assets')->insert([
                    'nation_id' => $nation->id,
                    'shop_item_id' => $item->id,
                    'qty' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($categoryCode === 'research' && Schema::hasTable('nation_research')) {
            DB::table('nation_research')->updateOrInsert(
                [
                    'nation_id' => $nation->id,
                    'research_code' => (string) $item->code,
                ],
                [
                    'shop_item_id' => $item->id,
                    'researched_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $researchSet[strtolower((string) $item->code)] = true;
        }

        if ($structureMeta) {
            $catalogId = $this->ensureBuildingCatalog($structureMeta['family']);
            $hasTerrainType = Schema::hasColumn('nation_buildings', 'terrain_type');
            $hasTerrainAllocated = Schema::hasColumn('nation_buildings', 'terrain_allocated_square_miles');
            if ($structureMeta['level'] === 1) {
                for ($i = 0; $i < $quantity; $i++) {
                    $insertPayload = [
                        'nation_id' => $nation->id,
                        'building_catalog_id' => $catalogId,
                        'level' => 1,
                        'status' => 'built',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    if ($hasTerrainType) {
                        $insertPayload['terrain_type'] = isset($plannedStructurePlacements[$i])
                            ? ($plannedStructurePlacements[$i]['terrain_type'] ?? null)
                            : null;
                    }
                    if ($hasTerrainAllocated) {
                        $insertPayload['terrain_allocated_square_miles'] = isset($plannedStructurePlacements[$i])
                            ? (float) ($plannedStructurePlacements[$i]['terrain_allocated_square_miles'] ?? 0)
                            : 0;
                    }
                    DB::table('nation_buildings')->insert($insertPayload);
                }
            } else {
                if (!empty($plannedStructureUpgrades)) {
                    foreach ($plannedStructureUpgrades as $upgradePlan) {
                        $rowId = (int) ($upgradePlan['row_id'] ?? 0);
                        if ($rowId <= 0) {
                            continue;
                        }
                        $upgradePayload = [
                            'level' => $structureMeta['level'],
                            'status' => 'built',
                            'updated_at' => now(),
                        ];
                        if ($hasTerrainType) {
                            $upgradePayload['terrain_type'] = $upgradePlan['terrain_type'] ?? null;
                        }
                        if ($hasTerrainAllocated) {
                            $upgradePayload['terrain_allocated_square_miles'] = (float) ($upgradePlan['terrain_allocated_square_miles'] ?? 0);
                        }
                        DB::table('nation_buildings')->where('id', $rowId)->update($upgradePayload);

                        $requiredLevel = $structureMeta['level'] - 1;
                        $lowerCode = 'struct_' . $structureMeta['family'] . '_l' . $requiredLevel;
                        $lowerItemId = DB::table('shop_items')->where('code', $lowerCode)->value('id');
                        if ($lowerItemId) {
                            $lowerAsset = DB::table('nation_assets')
                                ->where('nation_id', $nation->id)
                                ->where('shop_item_id', $lowerItemId)
                                ->first();
                            if ($lowerAsset) {
                                if ((int) $lowerAsset->qty <= 1) {
                                    DB::table('nation_assets')->where('id', $lowerAsset->id)->delete();
                                } else {
                                    DB::table('nation_assets')->where('id', $lowerAsset->id)->update([
                                        'qty' => (int) $lowerAsset->qty - 1,
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                } else {
                    for ($i = 0; $i < $quantity; $i++) {
                        $requiredLevel = $structureMeta['level'] - 1;
                        $row = DB::table('nation_buildings')
                            ->where('nation_id', $nation->id)
                            ->where('building_catalog_id', $catalogId)
                            ->where('level', $requiredLevel)
                            ->where('status', 'built')
                            ->orderBy('id')
                            ->first();
                        if (!$row) {
                            break;
                        }
                        DB::table('nation_buildings')->where('id', $row->id)->update([
                            'level' => $structureMeta['level'],
                            'status' => 'built',
                            'updated_at' => now(),
                        ]);

                        $lowerCode = 'struct_' . $structureMeta['family'] . '_l' . $requiredLevel;
                        $lowerItemId = DB::table('shop_items')->where('code', $lowerCode)->value('id');
                        if ($lowerItemId) {
                            $lowerAsset = DB::table('nation_assets')
                                ->where('nation_id', $nation->id)
                                ->where('shop_item_id', $lowerItemId)
                                ->first();
                            if ($lowerAsset) {
                                if ((int) $lowerAsset->qty <= 1) {
                                    DB::table('nation_assets')->where('id', $lowerAsset->id)->delete();
                                } else {
                                    DB::table('nation_assets')->where('id', $lowerAsset->id)->update([
                                        'qty' => (int) $lowerAsset->qty - 1,
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        DB::table('admin_notifications')->insert([
            'type' => 'shop_purchase',
            'title' => 'Shop purchase: ' . $item->display_name,
            'body' => 'User #' . $request->user()->id . ' (' . $request->user()->name . ') purchased ' . $quantity . 'x ' . $item->display_name
                . ' for nation #' . $nation->id . ' (' . $nation->name . ').'
                . ' Purchase cost: ' . json_encode($costs)
                . '. Purchase effect: ' . json_encode($effects)
                . '. Remaining balances after purchase: ' . json_encode([
                    'base' => $this->legacyCoreBaseResourceValues($updatedRow),
                    'advanced' => $updatedExtra['advanced'] ?? ($updatedExtra['refined'] ?? []),
                    'currencies' => $updatedExtra['currencies'] ?? [],
                ]),
            'meta_json' => json_encode([
                'actor_user_id' => $request->user()->id,
                'nation_id' => $nation->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'cost_json' => $costs,
                'effect_json' => $effects,
            ]),
            'is_read' => 0,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Purchase successful',
            'remaining' => [
                'base'       => $this->legacyCoreBaseResourceValues($updatedRow),
                'advanced'   => $updatedExtra['advanced']   ?? ($updatedExtra['refined'] ?? []),
                'refined'    => $updatedExtra['advanced']   ?? ($updatedExtra['refined'] ?? []),
                'currencies' => $updatedExtra['currencies'] ?? [],
            ],
        ]);
    }

    private function parseResourceToken(string $resource): ?array
    {
        $key = trim($resource);
        if ($key === '') {
            return null;
        }

        if (str_contains($key, ':')) {
            [$rawType, $rawName] = explode(':', $key, 2);
            $type = strtolower(trim($rawType));
            $name = trim($rawName);
            if ($name === '') {
                return null;
            }

            if ($type === 'base') {
                return ['bucket' => 'base', 'name' => $name];
            }
            if ($type === 'advanced') {
                return ['bucket' => 'advanced', 'name' => $name];
            }
            if ($type === 'currencies') {
                return ['bucket' => 'currencies', 'name' => $name];
            }

            return null;
        }

        return ['bucket' => 'base', 'name' => $key];
    }

    private function parseStructureCode(string $code): ?array
    {
        if (!preg_match('/^struct_([a-z0-9_]+)_l([0-9]+)$/', $code, $matches)) {
            return null;
        }

        return [
            'family' => $matches[1],
            'level' => (int) $matches[2],
        ];
    }

    private function structureTerrainRequirementForShopItem($item): ?array
    {
        if (!Schema::hasColumn('building_catalog', 'terrain_requirement_json')) {
            return null;
        }

        $meta = $this->parseStructureCode((string) ($item->code ?? ''));
        if (!$meta) {
            return null;
        }

        $raw = DB::table('building_catalog')
            ->where('code', (string) ($meta['family'] ?? ''))
            ->value('terrain_requirement_json');
        if ($raw === null) {
            return null;
        }

        $map = $this->normalizeStructureTerrainRequirementMap(
            json_decode((string) $raw, true) ?: []
        );
        $rule = $this->structureTerrainRequirementForLevel($map, (int) ($meta['level'] ?? 1));
        $required = (float) ($rule['required_square_miles'] ?? 0);
        $allowed = is_array($rule['allowed_terrain'] ?? null) ? $rule['allowed_terrain'] : [];
        if ($required <= 0 || empty($allowed)) {
            return null;
        }

        return [
            'required_square_miles' => $required,
            'allowed_terrain' => array_values(array_map(static fn ($v) => strtolower((string) $v), $allowed)),
        ];
    }

    private function terrainKeys(): array
    {
        return ['grassland', 'mountain', 'freshwater', 'hills', 'desert', 'seafront'];
    }

    private function normalizeStructureTerrainRequirementMap(array $requirements): array
    {
        $normalized = [];
        $terrainKeySet = array_fill_keys($this->terrainKeys(), true);

        foreach ($requirements as $levelKey => $rawRule) {
            $level = max(1, (int) $levelKey);
            if (!is_array($rawRule)) {
                continue;
            }

            $required = (float) ($rawRule['required_square_miles'] ?? $rawRule['required'] ?? $rawRule['amount'] ?? 0);
            if (!is_finite($required) || $required < 0) {
                $required = 0;
            }

            $allowedRaw = $rawRule['allowed_terrain'] ?? ($rawRule['terrain_types'] ?? []);
            $allowed = [];
            if (is_array($allowedRaw)) {
                foreach ($allowedRaw as $terrain) {
                    $key = strtolower(trim((string) $terrain));
                    if ($key === '' || !isset($terrainKeySet[$key])) {
                        continue;
                    }
                    $allowed[$key] = true;
                }
            }

            if ($required <= 0 || empty($allowed)) {
                continue;
            }

            $normalized[(string) $level] = [
                'required_square_miles' => round($required, 2),
                'allowed_terrain' => array_values(array_keys($allowed)),
            ];
        }

        uksort($normalized, static fn ($a, $b) => (int) $a <=> (int) $b);

        return $normalized;
    }

    private function structureTerrainRequirementForLevel(array $terrainRequirementMap, int $level): array
    {
        $rule = $terrainRequirementMap[(string) max(1, $level)] ?? null;
        if (!is_array($rule)) {
            return ['required_square_miles' => 0.0, 'allowed_terrain' => []];
        }

        return [
            'required_square_miles' => max(0.0, (float) ($rule['required_square_miles'] ?? 0)),
            'allowed_terrain' => is_array($rule['allowed_terrain'] ?? null) ? $rule['allowed_terrain'] : [],
        ];
    }

    private function computeNationTerrainAvailability(int $nationId): array
    {
        $totals = array_fill_keys($this->terrainKeys(), 0.0);
        $used = array_fill_keys($this->terrainKeys(), 0.0);

        $terrainRow = DB::table('nation_terrain_stats')->where('nation_id', $nationId)->first();
        $squareMiles = [];
        if ($terrainRow && isset($terrainRow->square_miles_json)) {
            $squareMiles = json_decode((string) $terrainRow->square_miles_json, true);
            $squareMiles = is_array($squareMiles) ? $squareMiles : [];
        }

        foreach ($totals as $terrain => $ignored) {
            $totals[$terrain] = max(0.0, (float) ($squareMiles[$terrain] ?? 0));
        }

        $available = [];
        foreach ($totals as $terrain => $total) {
            $available[$terrain] = max(0.0, $total);
        }

        $rows = DB::table('nation_buildings as nb')
            ->leftJoin('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
            ->where('nb.nation_id', $nationId)
            ->whereIn('nb.status', ['built', 'constructing', 'upgrading'])
            ->select('nb.level', 'nb.terrain_type', 'nb.terrain_allocated_square_miles', 'bc.terrain_requirement_json')
            ->get();

        $missingAllocationRows = [];
        foreach ($rows as $row) {
            $type = strtolower(trim((string) ($row->terrain_type ?? '')));
            $allocated = max(0.0, (float) ($row->terrain_allocated_square_miles ?? 0));
            if ($type !== '' && array_key_exists($type, $used) && $allocated > 0) {
                $used[$type] += $allocated;
                $available[$type] = max(0.0, (float) ($available[$type] ?? 0) - $allocated);
                continue;
            }
            $missingAllocationRows[] = $row;
        }

        foreach ($missingAllocationRows as $row) {
            $terrainRequirementMap = $this->normalizeStructureTerrainRequirementMap(
                json_decode((string) ($row->terrain_requirement_json ?? 'null'), true) ?: []
            );
            $rule = $this->structureTerrainRequirementForLevel($terrainRequirementMap, (int) ($row->level ?? 1));
            $required = max(0.0, (float) ($rule['required_square_miles'] ?? 0));
            $allowed = is_array($rule['allowed_terrain'] ?? null) ? $rule['allowed_terrain'] : [];
            if ($required <= 0 || empty($allowed)) {
                continue;
            }

            $pick = null;
            $bestAvailable = -1.0;
            foreach ($allowed as $terrainType) {
                $terrainKey = strtolower(trim((string) $terrainType));
                if (!array_key_exists($terrainKey, $available)) {
                    continue;
                }
                $candidate = (float) ($available[$terrainKey] ?? 0);
                if ($candidate > $bestAvailable) {
                    $bestAvailable = $candidate;
                    $pick = $terrainKey;
                }
            }

            if ($pick === null) {
                continue;
            }

            $used[$pick] += $required;
            $available[$pick] = max(0.0, (float) ($available[$pick] ?? 0) - $required);
        }

        $available = [];
        foreach ($totals as $terrain => $total) {
            $available[$terrain] = max(0.0, $total - (float) ($used[$terrain] ?? 0));
        }

        return [
            'total' => $totals,
            'used' => $used,
            'available' => $available,
        ];
    }

    private function allocateTerrainForStructurePlacement(array &$terrainAvailability, array $terrainRequirementMap, int $level, ?string $preferredTerrainType = null): ?array
    {
        $rule = $this->structureTerrainRequirementForLevel($terrainRequirementMap, $level);
        $required = (float) ($rule['required_square_miles'] ?? 0);
        $allowedTerrain = is_array($rule['allowed_terrain'] ?? null) ? $rule['allowed_terrain'] : [];

        if ($required <= 0 || empty($allowedTerrain)) {
            return [
                'terrain_type' => null,
                'terrain_allocated_square_miles' => 0.0,
            ];
        }

        $preferred = strtolower(trim((string) ($preferredTerrainType ?? '')));
        if ($preferred !== '') {
            if (!in_array($preferred, $allowedTerrain, true)) {
                return null;
            }
            $available = (float) ($terrainAvailability['available'][$preferred] ?? 0.0);
            if ($available < $required) {
                return null;
            }
            $terrainAvailability['available'][$preferred] = max(0.0, (float) ($terrainAvailability['available'][$preferred] ?? 0) - $required);
            $terrainAvailability['used'][$preferred] = (float) ($terrainAvailability['used'][$preferred] ?? 0) + $required;
            return [
                'terrain_type' => $preferred,
                'terrain_allocated_square_miles' => round($required, 2),
            ];
        }

        $bestType = null;
        $bestAvailable = -1.0;
        foreach ($allowedTerrain as $terrainType) {
            $terrainKey = strtolower(trim((string) $terrainType));
            $available = (float) ($terrainAvailability['available'][$terrainKey] ?? 0.0);
            if ($available < $required) {
                continue;
            }
            if ($available > $bestAvailable) {
                $bestAvailable = $available;
                $bestType = $terrainKey;
            }
        }

        if ($bestType === null) {
            return null;
        }

        $terrainAvailability['available'][$bestType] = max(0.0, (float) ($terrainAvailability['available'][$bestType] ?? 0) - $required);
        $terrainAvailability['used'][$bestType] = (float) ($terrainAvailability['used'][$bestType] ?? 0) + $required;

        return [
            'terrain_type' => $bestType,
            'terrain_allocated_square_miles' => round($required, 2),
        ];
    }

    private function ensureBuildingCatalog(string $familyCode): int
    {
        $code = $familyCode;
        $existing = DB::table('building_catalog')->where('code', $code)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $display = ucwords(str_replace('_', ' ', $familyCode));
        return (int) DB::table('building_catalog')->insertGetId([
            'code' => $code,
            'display_name' => $display,
            'max_level' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function normalizeRefinedKey(string $key): ?string
    {
        $trimmed = strtoupper(trim($key));
        if ($trimmed === '') {
            return null;
        }

        $aliases = [
            'METAL' => 'M',
            'M' => 'M',
            'RADIOACTIVE_METAL' => 'RM',
            'RADIOACTIVE METAL' => 'RM',
            'RM' => 'RM',
            'FOVIUM_STEEL' => 'FS',
            'FOVIUM STEEL' => 'FS',
            'FS' => 'FS',
            'URANIUM' => 'URM',
            'URM' => 'URM',
        ];

        return $aliases[$trimmed] ?? $trimmed;
    }

    private function loadNationBuildingLevels(int $nationId): array
    {
        $rows = DB::table('nation_buildings as nb')
            ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
            ->where('nb.nation_id', $nationId)
            ->where('nb.status', 'built')
            ->select('bc.code', 'nb.level')
            ->get();

        $levels = [];
        foreach ($rows as $row) {
            $code = strtolower((string) ($row->code ?? ''));
            if ($code === '') {
                continue;
            }
            $levels[$code] = max((int) ($levels[$code] ?? 0), (int) ($row->level ?? 0));
        }

        return $levels;
    }

    private function loadNationResearchSet(int $nationId): array
    {
        if (!Schema::hasTable('nation_research')) {
            return [];
        }

        $codes = DB::table('nation_research')
            ->where('nation_id', $nationId)
            ->pluck('research_code')
            ->all();

        $set = [];
        foreach ($codes as $code) {
            $key = strtolower(trim((string) $code));
            if ($key !== '') {
                $set[$key] = true;
            }
        }

        return $set;
    }

    private function loadNationBalances($resourceRow): array
    {
        $extra = json_decode((string) ($resourceRow->extra_json ?? '{}'), true) ?: [];

        $base = $this->legacyCoreBaseResourceValues($resourceRow);
        $coreKeys = array_fill_keys(array_keys($base), true);
        foreach ((is_array($extra['base'] ?? null) ? $extra['base'] : []) as $key => $value) {
            if (isset($coreKeys[(string) $key])) {
                continue;
            }
            $base[(string) $key] = (float) $value;
        }

        return [
            'base' => $base,
            'advanced' => is_array($extra['advanced'] ?? null)
                ? $extra['advanced']
                : (is_array($extra['refined'] ?? null) ? $extra['refined'] : []),
            'currencies' => is_array($extra['currencies'] ?? null) ? $extra['currencies'] : [],
        ];
    }

    private function legacyCoreBaseResourceValues(object $resourceRow): array
    {
        $out = [];
        foreach (get_object_vars($resourceRow) as $key => $value) {
            $name = (string) $key;
            if (in_array($name, ['nation_id', 'extra_json', 'updated_at', 'created_at'], true)) {
                continue;
            }
            if (!is_numeric($value)) {
                continue;
            }
            $out[$name] = (float) $value;
        }

        return $out;
    }

    private function nationMeetsRequirement($requirement, array $buildingLevels, array $researchSet, array $balances): bool
    {
        if (!is_array($requirement) || empty($requirement)) {
            return true;
        }

        if (isset($requirement['all']) && is_array($requirement['all'])) {
            foreach ($requirement['all'] as $child) {
                if (!$this->nationMeetsRequirement($child, $buildingLevels, $researchSet, $balances)) {
                    return false;
                }
            }
            return true;
        }

        if (isset($requirement['any']) && is_array($requirement['any'])) {
            foreach ($requirement['any'] as $child) {
                if ($this->nationMeetsRequirement($child, $buildingLevels, $researchSet, $balances)) {
                    return true;
                }
            }
            return false;
        }

        $type = strtolower((string) ($requirement['type'] ?? ''));
        if ($type === 'structure') {
            $code = strtolower(trim((string) ($requirement['building_code'] ?? $requirement['code'] ?? '')));
            $level = max(1, (int) ($requirement['level'] ?? 1));
            if ($code === '') {
                return true;
            }
            return (int) ($buildingLevels[$code] ?? 0) >= $level;
        }

        if ($type === 'research') {
            $single = strtolower(trim((string) ($requirement['code'] ?? '')));
            if ($single !== '') {
                return isset($researchSet[$single]);
            }
            $codes = is_array($requirement['codes'] ?? null) ? $requirement['codes'] : [];
            $mode = strtolower((string) ($requirement['mode'] ?? 'all'));
            if (empty($codes)) {
                return true;
            }
            if ($mode === 'any') {
                foreach ($codes as $code) {
                    if (isset($researchSet[strtolower(trim((string) $code))])) {
                        return true;
                    }
                }
                return false;
            }
            foreach ($codes as $code) {
                if (!isset($researchSet[strtolower(trim((string) $code))])) {
                    return false;
                }
            }
            return true;
        }

        if ($type === 'resource') {
            $required = is_array($requirement['cost'] ?? null)
                ? $requirement['cost']
                : (is_array($requirement['resources'] ?? null) ? $requirement['resources'] : []);
            foreach ($required as $resource => $value) {
                $token = $this->parseResourceToken((string) $resource);
                if (!$token) {
                    continue;
                }
                $needed = (float) $value;
                if ($token['bucket'] === 'base') {
                    if ((float) ($balances['base'][$token['name']] ?? 0) < $needed) {
                        return false;
                    }
                    continue;
                }
                if ($token['bucket'] === 'advanced') {
                    if ((float) ($balances['advanced'][$token['name']] ?? 0) < $needed) {
                        return false;
                    }
                    continue;
                }
                if ((float) ($balances['currencies'][$token['name']] ?? 0) < $needed) {
                    return false;
                }
            }
            return true;
        }

        return true;
    }

    private function ensurePrimaryShopCategories(): void
    {
        DB::table('shop_categories')->updateOrInsert(['code' => 'craft'], ['display_name' => 'Craft']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'build'], ['display_name' => 'Build']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'recruit'], ['display_name' => 'Recruit']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'research'], ['display_name' => 'Research']);
    }

    private function normalizeCategoryCode(string $code): string
    {
        $value = strtolower(trim($code));

        return match ($value) {
            'refinement', 'crafting', 'currency_exchange', 'craft' => 'craft',
            'structures', 'upgrades', 'build' => 'build',
            'recruitment', 'recruit' => 'recruit',
            'research' => 'research',
            default => $value,
        };
    }

    private function categoryAliases(string $normalized): array
    {
        return match ($normalized) {
            'craft' => ['craft', 'refinement', 'crafting', 'currency_exchange'],
            'build' => ['build', 'structures', 'upgrades'],
            'recruit' => ['recruit', 'recruitment'],
            'research' => ['research'],
            default => [$normalized],
        };
    }
}
