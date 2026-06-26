<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateAboutRequest;
use App\Http\Requests\Api\UpdateSettingsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MeController extends Controller
{
    public function dashboard(Request $request)
    {
        $nation = $this->findNation($request->user()->id);
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        $resources = DB::table('nation_resources')->where('nation_id', $nation->id)->first();
        $terrain = DB::table('nation_terrain_stats')->where('nation_id', $nation->id)->first();
        $unitsOwned = DB::table('nation_units as nu')
            ->leftJoin('unit_catalog as uc', 'nu.unit_catalog_id', '=', 'uc.id')
            ->where('nu.nation_id', $nation->id)
            ->where('nu.status', 'owned')
            ->select('nu.*', 'uc.display_name', 'uc.class_name')
            ->get();
        $unitsTraining = DB::table('nation_units as nu')
            ->leftJoin('unit_catalog as uc', 'nu.unit_catalog_id', '=', 'uc.id')
            ->where('nu.nation_id', $nation->id)
            ->where('nu.status', 'training')
            ->select('nu.*', 'uc.display_name', 'uc.class_name')
            ->get();
        $buildingsBuilt = DB::table('nation_buildings as nb')
            ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
            ->where('nb.nation_id', $nation->id)
            ->where('nb.status', 'built')
            ->select('nb.*', 'bc.display_name', 'bc.code')
            ->get();
        $buildingsInProgress = DB::table('nation_buildings as nb')
            ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
            ->where('nb.nation_id', $nation->id)
            ->whereIn('nb.status', ['constructing', 'upgrading'])
            ->select('nb.*', 'bc.display_name', 'bc.code')
            ->get();

        $extra = json_decode($resources->extra_json ?? '{}', true) ?: [];
        $resourceState = $this->readNationResourceState((object) $resources);
        $incomeMap = $this->normalizeIncomeMap($extra);
        $baseResources = $resourceState['base'];
        $advancedResources = $resourceState['advanced'];
        $currencyResources = $resourceState['currencies'];

        $structuredResources = [
            'base' => $baseResources,
            'advanced' => $advancedResources,
            'refined' => $advancedResources,
            'currencies' => $currencyResources,
        ];

        $projection = [
            'income' => [
                'base' => [],
                'advanced' => [],
                'refined' => [],
                'currencies' => [],
            ],
            'maintenance' => [
                'base' => [],
                'advanced' => [],
                'refined' => [],
                'currencies' => [],
            ],
            'net' => [
                'base' => [],
                'advanced' => [],
                'refined' => [],
                'currencies' => [],
            ],
            'income_breakdown' => [],
            'maintenance_breakdown' => [],
        ];

        foreach ($incomeMap as $incomeKey => $incomeValue) {
            $amount = (float) $incomeValue;
            $this->accumulateProjection($projection['income'], $incomeKey, $amount);
            $this->accumulateProjection($projection['net'], $incomeKey, $amount);
            $projection['income_breakdown'][] = [
                'asset' => 'Nation Base Income',
                'key' => $incomeKey,
                'amount' => $amount,
            ];
        }

        $assets = DB::table('nation_assets as na')
            ->join('shop_items as si', 'na.shop_item_id', '=', 'si.id')
            ->where('na.nation_id', $nation->id)
            ->select('na.qty', 'si.code', 'si.display_name', 'si.maintenance_json', 'si.yearly_effect_json')
            ->get();

        foreach ($assets as $asset) {
            $assetCode = strtolower(trim((string) ($asset->code ?? '')));
            if ($assetCode !== '' && str_starts_with($assetCode, 'struct_')) {
                // Structures are projected from nation_buildings below.
                continue;
            }

            $qty = (int) $asset->qty;
            $yearly = json_decode($asset->yearly_effect_json ?? 'null', true) ?: [];
            $maintenance = json_decode($asset->maintenance_json ?? 'null', true) ?: [];

            foreach ($yearly as $key => $value) {
                $amount = (float) $value * $qty;
                $this->accumulateProjection($projection['income'], $key, $amount);
                $this->accumulateProjection($projection['net'], $key, $amount);
                $projection['income_breakdown'][] = [
                    'asset' => $asset->display_name,
                    'key' => $key,
                    'amount' => $amount,
                ];
            }

            foreach ($maintenance as $key => $value) {
                $amount = (float) $value * $qty;
                $this->accumulateProjection($projection['maintenance'], $key, $amount);
                $this->accumulateProjection($projection['net'], $key, -$amount);
                $projection['maintenance_breakdown'][] = [
                    'asset' => $asset->display_name,
                    'key' => $key,
                    'amount' => $amount,
                ];
            }
        }

        if (Schema::hasColumn('building_catalog', 'yearly_production_json')) {
            $hasStructureYearlyMaintenance = Schema::hasColumn('building_catalog', 'yearly_maintenance_json');
            $shopHasYearlyEffectJson = Schema::hasColumn('shop_items', 'yearly_effect_json');
            $shopHasMaintenanceJson = Schema::hasColumn('shop_items', 'maintenance_json');
            $structureShopEffectCache = [];

            $structureQuery = DB::table('nation_buildings as nb')
                ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
                ->where('nb.nation_id', $nation->id)
                ->where('nb.status', 'built')
                ->select('nb.level', 'bc.code', 'bc.display_name', 'bc.yearly_production_json');
            if ($hasStructureYearlyMaintenance) {
                $structureQuery->addSelect('bc.yearly_maintenance_json');
            }
            $structureRows = $structureQuery->get();

            foreach ($structureRows as $row) {
                $level = max(1, (int) ($row->level ?? 1));
                $buildingCode = strtolower(trim((string) ($row->code ?? '')));
                $productionMap = json_decode((string) ($row->yearly_production_json ?? 'null'), true);
                $maintenanceMap = $hasStructureYearlyMaintenance
                    ? json_decode((string) ($row->yearly_maintenance_json ?? 'null'), true)
                    : [];

                $levelMap = is_array($productionMap) ? ($productionMap[(string) $level] ?? null) : null;
                if (!is_array($levelMap)) {
                    $levelMap = [];
                    foreach ((is_array($productionMap) ? $productionMap : []) as $key => $value) {
                        if (is_numeric($value)) {
                            $levelMap[(string) $key] = (float) $value;
                        }
                    }
                }

                $maintenanceLevelMap = [];
                if ($hasStructureYearlyMaintenance) {
                    $maintenanceLevelMap = is_array($maintenanceMap) ? ($maintenanceMap[(string) $level] ?? null) : null;
                    if (!is_array($maintenanceLevelMap)) {
                        $maintenanceLevelMap = [];
                        foreach ((is_array($maintenanceMap) ? $maintenanceMap : []) as $key => $value) {
                            if (is_numeric($value)) {
                                $maintenanceLevelMap[(string) $key] = (float) $value;
                            }
                        }
                    }
                }

                if (empty($levelMap) || ($hasStructureYearlyMaintenance && empty($maintenanceLevelMap))) {
                    $familyCode = $buildingCode;
                    if ($familyCode !== '' && preg_match('/^struct_(.+)_l\d+$/', $familyCode, $matches)) {
                        $familyCode = strtolower(trim((string) ($matches[1] ?? '')));
                    }

                    if ($familyCode !== '' && $shopHasYearlyEffectJson) {
                        $shopCode = 'struct_' . $familyCode . '_l' . $level;
                        if (!array_key_exists($shopCode, $structureShopEffectCache)) {
                            $shopQuery = DB::table('shop_items')->where('code', $shopCode)->select('yearly_effect_json');
                            if ($shopHasMaintenanceJson) {
                                $shopQuery->addSelect('maintenance_json');
                            }
                            $shopEffect = $shopQuery->first();
                            $structureShopEffectCache[$shopCode] = [
                                'yearly' => is_object($shopEffect)
                                    ? (json_decode((string) ($shopEffect->yearly_effect_json ?? 'null'), true) ?: [])
                                    : [],
                                'maintenance' => ($shopHasMaintenanceJson && is_object($shopEffect))
                                    ? (json_decode((string) ($shopEffect->maintenance_json ?? 'null'), true) ?: [])
                                    : [],
                            ];
                        }

                        $cached = $structureShopEffectCache[$shopCode] ?? ['yearly' => [], 'maintenance' => []];
                        if (empty($levelMap) && is_array($cached['yearly'] ?? null)) {
                            $levelMap = $cached['yearly'];
                        }
                        if ($hasStructureYearlyMaintenance && empty($maintenanceLevelMap) && is_array($cached['maintenance'] ?? null)) {
                            $maintenanceLevelMap = $cached['maintenance'];
                        }
                    }
                }

                foreach ($levelMap as $key => $value) {
                    $amount = (float) $value;
                    if ($amount == 0.0) {
                        continue;
                    }
                    $this->accumulateProjection($projection['income'], (string) $key, $amount);
                    $this->accumulateProjection($projection['net'], (string) $key, $amount);
                    $projection['income_breakdown'][] = [
                        'asset' => (string) ($row->display_name ?? 'Structure') . ' (L' . $level . ')',
                        'key' => (string) $key,
                        'amount' => $amount,
                    ];
                }

                if ($hasStructureYearlyMaintenance) {
                    foreach ($maintenanceLevelMap as $key => $value) {
                        $amount = (float) $value;
                        if ($amount == 0.0) {
                            continue;
                        }
                        $this->accumulateProjection($projection['maintenance'], (string) $key, $amount);
                        $this->accumulateProjection($projection['net'], (string) $key, -$amount);
                        $projection['maintenance_breakdown'][] = [
                            'asset' => (string) ($row->display_name ?? 'Structure') . ' (L' . $level . ')',
                            'key' => (string) $key,
                            'amount' => $amount,
                        ];
                    }
                }
            }
        }

        return response()->json([
            'user' => $request->user(),
            'nation' => $nation,
            'resources' => $structuredResources,
            'terrain' => $terrain,
            'units' => [
                'owned' => $unitsOwned,
                'training' => $unitsTraining,
            ],
            'buildings' => [
                'built' => $buildingsBuilt,
                'in_progress' => $buildingsInProgress,
            ],
            'yearly_projection' => $projection,
        ]);
    }

    public function resources(Request $request)
    {
        $nation = $this->findNation($request->user()->id);
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        $row = DB::table('nation_resources')->where('nation_id', $nation->id)->first();
        if (!$row) {
            return response()->json(['message' => 'No resources found'], 404);
        }

        $extra = json_decode($row->extra_json ?? '{}', true) ?: [];
        $resourceState = $this->readNationResourceState((object) $row);
        $base = $resourceState['base'];
        $advanced = $resourceState['advanced'];
        $currencies = $resourceState['currencies'];

        $displayNames = $this->resourceDisplayNames();
        $selection = $this->resolveTopbarSelectionForUser((int) $request->user()->id, $displayNames);
        $topbarDisplay = [];
        foreach ($selection as $item) {
            $type = (string) ($item['type'] ?? 'base');
            $name = (string) ($item['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $value = $this->resolveResourceValueByName(
                $type === 'advanced' ? $advanced : $base,
                $name
            );

            $label = (string) ($displayNames[$type][$name] ?? $name);
            $topbarDisplay[] = [
                'type' => $type,
                'name' => $name,
                'label' => $label,
                'value' => $value,
            ];
        }

        if (empty($topbarDisplay)) {
            $fallbackSelection = $this->defaultTopbarSelectionFromDisplayNames($displayNames);
            foreach ($fallbackSelection as $item) {
                $type = (string) ($item['type'] ?? 'base');
                $name = (string) ($item['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $value = $this->resolveResourceValueByName(
                    $type === 'advanced' ? $advanced : $base,
                    $name
                );
                $topbarDisplay[] = [
                    'type' => $type,
                    'name' => $name,
                    'label' => (string) ($displayNames[$type][$name] ?? $name),
                    'value' => $value,
                ];
            }
        }

        return response()->json([
            'base' => $base,
            'advanced' => $advanced,
            'refined' => $advanced,
            'currencies' => $currencies,
            'topbar_display' => $topbarDisplay,
        ]);
    }

    public function updateAbout(UpdateAboutRequest $request)
    {
        $nation = $this->findNation($request->user()->id);
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        $data = $request->validated();

        DB::table('nations')->where('id', $nation->id)->update([
            'about_text' => $data['about_text'] ?? null,
            'alliance_name' => $data['alliance_name'] ?? $nation->alliance_name,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'About text saved']);
    }

    public function settings(Request $request)
    {
        $settings = DB::table('user_settings')->where('user_id', $request->user()->id)->first();
        if ($settings) {
            $extra = json_decode($settings->extra_json ?? '{}', true) ?: [];
            $mapSettings = $this->loadGlobalMapSettingsRaw();
            $payload = (array) $settings;
            $payload['font_mode'] = $extra['font_mode'] ?? 'normal';
            $payload['map_zoom_sensitivity'] = max(0.25, min(3, (float) ($extra['map_zoom_sensitivity'] ?? 1)));
            $payload['map_max_zoom_pct'] = (int) ($mapSettings['map_max_zoom_pct'] ?? 180);
            $payload['map_show_nation_names'] = array_key_exists('map_show_nation_names', $extra)
                ? (bool) $extra['map_show_nation_names']
                : (bool) ($mapSettings['map_show_nation_names'] ?? true);
            $payload['map_split_water_colors'] = (bool) ($mapSettings['map_split_water_colors'] ?? false);
            $payload['map_popup_fields'] = $this->normalizeMapPopupFields($mapSettings['map_popup_fields'] ?? []);
            $payload['map_pixels_to_square_miles_formula'] = $this->normalizeMapSquareMilesFormula((string) ($mapSettings['map_pixels_to_square_miles_formula'] ?? 'PIXELS'));
            $payload['map_terrain_color_overrides'] = $this->normalizeTerrainColorOverrides($mapSettings['map_terrain_color_overrides'] ?? []);
            $payload['terrain_color_overrides'] = $this->normalizeTerrainColorOverrides($extra['terrain_color_overrides'] ?? []);
            $payload['show_unread_chat_badge'] = (bool) ($extra['show_unread_chat_badge'] ?? true);
            $payload['apply_year_change_effects'] = array_key_exists('apply_year_change_effects', $extra)
                ? (bool) $extra['apply_year_change_effects']
                : true;
            $payload['alliance_color_overrides'] = is_array($extra['alliance_color_overrides'] ?? null)
                ? $extra['alliance_color_overrides']
                : [];
            $payload['political_nation_color_overrides'] = is_array($extra['political_nation_color_overrides'] ?? null)
                ? $extra['political_nation_color_overrides']
                : [];
            return response()->json($payload);
        }

        DB::table('user_settings')->insert([
            'user_id' => $request->user()->id,
            'theme' => 'light',
            'color_blind_mode' => 'none',
            'dog_bark_enabled' => 0,
            'extra_json' => json_encode([
                'font_mode' => 'normal',
                'map_zoom_sensitivity' => 1,
                'map_show_nation_names' => (bool) ($this->loadGlobalMapSettingsRaw()['map_show_nation_names'] ?? true),
                'show_unread_chat_badge' => true,
                'apply_year_change_effects' => true,
                'terrain_color_overrides' => [],
                'alliance_color_overrides' => [],
                'political_nation_color_overrides' => [],
            ]),
            'updated_at' => now(),
        ]);
        $created = DB::table('user_settings')->where('user_id', $request->user()->id)->first();
        $payload = (array) $created;
        $payload['font_mode'] = 'normal';
        $payload['map_zoom_sensitivity'] = 1;
        $globalMapSettings = $this->loadGlobalMapSettingsRaw();
        $payload['map_max_zoom_pct'] = (int) ($globalMapSettings['map_max_zoom_pct'] ?? 180);
        $payload['map_show_nation_names'] = (bool) ($globalMapSettings['map_show_nation_names'] ?? true);
        $payload['map_split_water_colors'] = (bool) ($globalMapSettings['map_split_water_colors'] ?? false);
        $payload['map_popup_fields'] = $this->normalizeMapPopupFields($globalMapSettings['map_popup_fields'] ?? []);
        $payload['map_pixels_to_square_miles_formula'] = $this->normalizeMapSquareMilesFormula((string) ($globalMapSettings['map_pixels_to_square_miles_formula'] ?? 'PIXELS'));
        $payload['map_terrain_color_overrides'] = $this->normalizeTerrainColorOverrides($globalMapSettings['map_terrain_color_overrides'] ?? []);
        $payload['terrain_color_overrides'] = [];
        $payload['show_unread_chat_badge'] = true;
        $payload['apply_year_change_effects'] = true;
        $payload['alliance_color_overrides'] = [];
        $payload['political_nation_color_overrides'] = [];
        return response()->json($payload);
    }

    public function updateSettings(UpdateSettingsRequest $request)
    {
        $data = $request->validated();

        $current = $this->settings($request)->getData(true);
        $extra = json_decode($current['extra_json'] ?? '{}', true) ?: [];
        $extra['font_mode'] = $data['font_mode'] ?? ($current['font_mode'] ?? 'normal');
        $extra['map_zoom_sensitivity'] = array_key_exists('map_zoom_sensitivity', $data)
            ? max(0.25, min(3, (float) $data['map_zoom_sensitivity']))
            : max(0.25, min(3, (float) ($current['map_zoom_sensitivity'] ?? 1)));
        $extra['map_show_nation_names'] = array_key_exists('map_show_nation_names', $data)
            ? (bool) $data['map_show_nation_names']
            : (bool) ($current['map_show_nation_names'] ?? true);
        $extra['show_unread_chat_badge'] = array_key_exists('show_unread_chat_badge', $data)
            ? (bool) $data['show_unread_chat_badge']
            : (bool) ($current['show_unread_chat_badge'] ?? true);
        $extra['apply_year_change_effects'] = array_key_exists('apply_year_change_effects', $data)
            ? (bool) $data['apply_year_change_effects']
            : (bool) ($current['apply_year_change_effects'] ?? true);
        $extra['terrain_color_overrides'] = array_key_exists('terrain_color_overrides', $data)
            ? $this->normalizeTerrainColorOverrides($data['terrain_color_overrides'] ?? [])
            : $this->normalizeTerrainColorOverrides($extra['terrain_color_overrides'] ?? []);
        $extra['alliance_color_overrides'] = array_key_exists('alliance_color_overrides', $data)
            ? ($data['alliance_color_overrides'] ?? [])
            : (($extra['alliance_color_overrides'] ?? null) ?: []);
        $extra['political_nation_color_overrides'] = array_key_exists('political_nation_color_overrides', $data)
            ? ($data['political_nation_color_overrides'] ?? [])
            : (($extra['political_nation_color_overrides'] ?? null) ?: []);

        DB::table('user_settings')->where('user_id', $request->user()->id)->update([
            'theme' => $data['theme'] ?? $current['theme'],
            'color_blind_mode' => $data['color_blind_mode'] ?? $current['color_blind_mode'],
            'dog_bark_enabled' => array_key_exists('dog_bark_enabled', $data) ? (int) $data['dog_bark_enabled'] : (int) $current['dog_bark_enabled'],
            'extra_json' => json_encode($extra),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Settings saved']);
    }

    public function units(Request $request)
    {
        $nation = $this->findNation($request->user()->id);
        if (!$nation) {
            return response()->json([]);
        }

        $status = $request->query('status');
        $query = DB::table('nation_units as nu')
            ->leftJoin('unit_catalog as uc', 'nu.unit_catalog_id', '=', 'uc.id')
            ->where('nu.nation_id', $nation->id)
            ->select('nu.*', 'uc.display_name', 'uc.class_name');

        if (in_array($status, ['owned', 'training'], true)) {
            $query->where('nu.status', $status);
        }

        return response()->json($query->get());
    }

    public function updateUnitName(Request $request, int $unitId)
    {
        $nation = $this->findNation((int) $request->user()->id);
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        $data = $request->validate([
            'custom_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'instance_index' => ['sometimes', 'integer', 'min:1', 'max:100000'],
        ]);

        $unit = DB::table('nation_units')
            ->where('id', $unitId)
            ->where('nation_id', (int) $nation->id)
            ->first();

        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }

        $instanceIndex = (int) ($data['instance_index'] ?? 0);
        $useInstance = $instanceIndex > 0;

        $existingOverrides = json_decode((string) ($unit->stats_override_json ?? '{}'), true);
        $normalized = is_array($existingOverrides) ? $existingOverrides : [];
        $instances = is_array($normalized['_instances'] ?? null) ? $normalized['_instances'] : [];

        if ($useInstance && array_key_exists('custom_name', $data)) {
            $instanceKey = (string) $instanceIndex;
            $instanceOverride = is_array($instances[$instanceKey] ?? null) ? $instances[$instanceKey] : [];
            $value = trim((string) ($data['custom_name'] ?? ''));
            if ($value === '') {
                unset($instanceOverride['custom_name']);
            } else {
                $instanceOverride['custom_name'] = $value;
            }

            if (empty($instanceOverride)) {
                unset($instances[$instanceKey]);
            } else {
                $instances[$instanceKey] = $instanceOverride;
            }

            if (empty($instances)) {
                unset($normalized['_instances']);
            } else {
                $normalized['_instances'] = $instances;
            }
        }

        DB::table('nation_units')->where('id', $unitId)->update([
            'custom_name' => (!$useInstance && array_key_exists('custom_name', $data))
                ? trim((string) ($data['custom_name'] ?? ''))
                : $unit->custom_name,
            'stats_override_json' => json_encode($normalized),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Unit name saved']);
    }

    public function combatSnapshot(Request $request)
    {
        $nation = $this->findNation((int) $request->user()->id);
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        return response()->json($this->buildCombatSnapshotForNation((int) $nation->id));
    }

    public function combatOrders(Request $request)
    {
        $actorUserId = (int) $request->user()->id;

        $driver = DB::connection()->getDriverName();
        $actorUserFilterSql = $driver === 'sqlite'
            ? 'CAST(json_extract(meta_json, "$.actor_user_id") AS INTEGER) = ?'
            : 'CAST(JSON_UNQUOTE(JSON_EXTRACT(meta_json, "$.actor_user_id")) AS UNSIGNED) = ?';

        $rows = DB::table('admin_notifications')
            ->where('type', 'combat_order')
            ->whereRaw($actorUserFilterSql, [$actorUserId])
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return response()->json($rows);
    }

    public function storeCombatOrder(Request $request)
    {
        $nation = $this->findNation((int) $request->user()->id);
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $body = (string) $data['body'];
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = 'Combat Order: ' . (string) ($nation->name ?? ('Nation #' . $nation->id));
        }

        DB::table('admin_notifications')->insert([
            'type' => 'combat_order',
            'order_status' => 'pending',
            'title' => $title,
            'body' => $body,
            'meta_json' => json_encode([
                'actor_user_id' => (int) $request->user()->id,
                'actor_name' => (string) ($request->user()->name ?? ''),
                'nation_id' => (int) $nation->id,
            ]),
            'is_read' => 0,
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Order submitted']);
    }

    public function buildings(Request $request)
    {
        $nation = $this->findNation($request->user()->id);
        if (!$nation) {
            return response()->json([]);
        }

        $status = $request->query('status');
        $query = DB::table('nation_buildings as nb')
            ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
            ->where('nb.nation_id', $nation->id)
            ->select('nb.*', 'bc.display_name', 'bc.code', 'bc.max_level');

        if (in_array($status, ['built', 'constructing', 'upgrading'], true)) {
            $query->where('nb.status', $status);
        }

        return response()->json(
            $query
                ->orderBy('bc.display_name')
                ->orderBy('nb.level')
                ->orderBy('nb.id')
                ->get()
        );
    }

    public function terrainSquareMiles(Request $request)
    {
        $nation = $this->findNation($request->user()->id);
        if (!$nation) {
            return response()->json([]);
        }

        $terrain = DB::table('nation_terrain_stats')->where('nation_id', $nation->id)->first();
        return response()->json($terrain?->square_miles_json ? json_decode($terrain->square_miles_json, true) : []);
    }

    public function players(Request $request)
    {
        $rows = DB::table('users')
            ->leftJoin('nations', 'nations.owner_user_id', '=', 'users.id')
            ->where('role', 'player')
            ->select('users.id', 'users.name', 'users.email', 'nations.id as nation_id', 'nations.name as nation_name')
            ->orderBy('users.name')
            ->get();
        return response()->json($rows);
    }

    private function resourceDisplayNames(): array
    {
        $map = [
            'base' => [],
            'advanced' => [],
        ];

        $rows = DB::table('resource_definitions')
            ->select('type', 'name', 'display_name')
            ->whereIn('type', ['base', 'advanced'])
            ->get();

        foreach ($rows as $row) {
            $type = (string) ($row->type ?? '');
            $name = trim((string) ($row->name ?? ''));
            $displayName = trim((string) ($row->display_name ?? $name));
            if (!in_array($type, ['base', 'advanced'], true) || $name === '') {
                continue;
            }
            $map[$type][$name] = $displayName !== '' ? $displayName : $name;
        }

        return $map;
    }

    private function resolveTopbarSelectionForUser(int $userId, array $displayNames): array
    {
        $config = $this->loadResourceTopbarConfigRaw();
        $global = $this->normalizeTopbarSelection($config['global'] ?? [], $displayNames);
        if (empty($global)) {
            $global = $this->defaultTopbarSelectionFromDisplayNames($displayNames);
        }

        $override = collect($config['overrides'] ?? [])
            ->first(fn ($row) => (int) ($row['user_id'] ?? 0) === $userId);

        if (!is_array($override)) {
            return $global;
        }

        $overrideResources = $this->normalizeTopbarSelection($override['resources'] ?? [], $displayNames);
        if (empty($overrideResources)) {
            return $global;
        }

        $mode = (string) ($override['mode'] ?? 'replace');
        if ($mode === 'append') {
            return $this->normalizeTopbarSelection(array_merge($global, $overrideResources), $displayNames);
        }

        return $overrideResources;
    }

    private function normalizeTopbarSelection(array $selection, array $displayNames): array
    {
        $out = [];
        $seen = [];
        foreach ($selection as $item) {
            $type = (string) ($item['type'] ?? '');
            $name = trim((string) ($item['name'] ?? ''));
            if (!in_array($type, ['base', 'advanced'], true) || $name === '') {
                continue;
            }
            $canonicalName = $this->resolveTopbarName($type, $name, $displayNames);
            if ($canonicalName === null) {
                continue;
            }
            $key = $type . ':' . $canonicalName;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'type' => $type,
                'name' => $canonicalName,
            ];
        }

        return $out;
    }

    private function resolveTopbarName(string $type, string $name, array $displayNames): ?string
    {
        $map = $displayNames[$type] ?? [];
        if (!is_array($map) || $name === '') {
            return null;
        }

        if (array_key_exists($name, $map)) {
            return $name;
        }

        $needle = mb_strtolower(trim($name));
        if ($needle === '') {
            return null;
        }

        foreach ($map as $resourceName => $displayName) {
            $resourceNameLower = mb_strtolower(trim((string) $resourceName));
            $displayLower = mb_strtolower(trim((string) $displayName));
            if ($resourceNameLower === $needle || $displayLower === $needle) {
                return (string) $resourceName;
            }
        }

        return null;
    }

    private function resolveResourceValueByName(array $bucket, string $name): float
    {
        if (array_key_exists($name, $bucket)) {
            return (float) $bucket[$name];
        }

        $needle = mb_strtolower(trim($name));
        if ($needle === '') {
            return 0.0;
        }

        foreach ($bucket as $key => $value) {
            if (mb_strtolower(trim((string) $key)) === $needle) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    private function defaultTopbarSelectionFromDisplayNames(array $displayNames): array
    {
        $base = [];
        foreach (array_keys($displayNames['base'] ?? []) as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $base[] = ['type' => 'base', 'name' => $name];
        }
        if (!empty($base)) {
            return array_slice($base, 0, 4);
        }

        $advanced = [];
        foreach (array_keys($displayNames['advanced'] ?? []) as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $advanced[] = ['type' => 'advanced', 'name' => $name];
        }

        return array_slice($advanced, 0, 4);
    }

    private function legacyCoreBaseResourceValues(object $row): array
    {
        $out = [];
        foreach (get_object_vars($row) as $key => $value) {
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

    private function loadResourceTopbarConfigRaw(): array
    {
        $path = storage_path('app/resource_topbar_config.json');
        if (!File::exists($path)) {
            return [
                'global' => [],
                'overrides' => [],
            ];
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            return [
                'global' => [],
                'overrides' => [],
            ];
        }

        return [
            'global' => is_array($decoded['global'] ?? null) ? $decoded['global'] : [],
            'overrides' => is_array($decoded['overrides'] ?? null) ? $decoded['overrides'] : [],
        ];
    }

    private function loadGlobalMapSettingsRaw(): array
    {
        $path = storage_path('app/map_settings.json');
        if (!File::exists($path)) {
            return [
                'map_max_zoom_pct' => 180,
                'map_show_nation_names' => false,
                'map_split_water_colors' => false,
                'map_popup_fields' => $this->defaultMapPopupFields(),
                'map_pixels_to_square_miles_formula' => 'PIXELS',
                'map_terrain_color_overrides' => [],
            ];
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            return [
                'map_max_zoom_pct' => 180,
                'map_show_nation_names' => false,
                'map_split_water_colors' => false,
                'map_popup_fields' => $this->defaultMapPopupFields(),
                'map_pixels_to_square_miles_formula' => 'PIXELS',
                'map_terrain_color_overrides' => [],
            ];
        }

        $maxZoom = (int) ($decoded['map_max_zoom_pct'] ?? 180);
        if ($maxZoom < 100) {
            $maxZoom = 100;
        }
        if ($maxZoom > 300) {
            $maxZoom = 300;
        }

        return [
            'map_max_zoom_pct' => $maxZoom,
            'map_show_nation_names' => (bool) ($decoded['map_show_nation_names'] ?? false),
            'map_split_water_colors' => (bool) ($decoded['map_split_water_colors'] ?? false),
            'map_popup_fields' => array_key_exists('map_popup_fields', $decoded)
                ? $this->normalizeMapPopupFields($decoded['map_popup_fields'], false)
                : $this->defaultMapPopupFields(),
            'map_pixels_to_square_miles_formula' => $this->normalizeMapSquareMilesFormula((string) ($decoded['map_pixels_to_square_miles_formula'] ?? 'PIXELS')),
            'map_terrain_color_overrides' => $this->normalizeTerrainColorOverrides($decoded['map_terrain_color_overrides'] ?? []),
        ];
    }

    private function normalizeTerrainColorOverrides($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $allowedTerrainKeys = [
            'grassland',
            'forest',
            'mountain',
            'desert',
            'tundra',
            'magic_grassland',
            'water',
            'water_sea',
            'water_fresh',
        ];
        $allowed = array_fill_keys($allowedTerrainKeys, true);

        $out = [];
        foreach ($raw as $key => $value) {
            $terrainKey = strtolower(trim((string) $key));
            if ($terrainKey === '' || !isset($allowed[$terrainKey])) {
                continue;
            }
            $color = strtoupper(trim((string) $value));
            if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
                continue;
            }
            $out[$terrainKey] = $color;
        }

        return $out;
    }

    private function defaultMapPopupFields(): array
    {
        return ['alliance', 'races', 'color', 'owned_terrain_square_miles'];
    }

    private function availableMapPopupFields(): array
    {
        return [
            'alliance',
            'leader_name',
            'about_text',
            'color',
            'owned_terrain_square_miles',
            'total_army_rating',
            'units_count',
            'buildings_count',
            'races',
        ];
    }

    private function normalizeMapPopupFields($raw, bool $fallbackDefault = true): array
    {
        $allowed = array_fill_keys($this->availableMapPopupFields(), true);
        $list = is_array($raw) ? $raw : [];
        $out = [];
        foreach ($list as $item) {
            $key = strtolower(trim((string) $item));
            if ($key === 'owned_terrain_pixels') {
                $key = 'owned_terrain_square_miles';
            }
            if ($key === '' || !isset($allowed[$key]) || in_array($key, $out, true)) {
                continue;
            }
            $out[] = $key;
        }

        if (!empty($out)) {
            return $out;
        }

        return $fallbackDefault ? $this->defaultMapPopupFields() : [];
    }

    private function normalizeMapSquareMilesFormula(string $raw): string
    {
        $formula = strtoupper(trim($raw));
        if ($formula === '' || !preg_match('/^[0-9A-Z_+\-*\/().\s]+$/', $formula) || !preg_match('/\bPIXELS\b/', $formula)) {
            return 'PIXELS';
        }

        return preg_replace('/\s+/', ' ', $formula) ?: 'PIXELS';
    }

    private function findNation(int $userId): ?object
    {
        return DB::table('nations')->where('owner_user_id', $userId)->first();
    }

    private function buildCombatSnapshotForNation(int $nationId): array
    {
        $nation = DB::table('nations')
            ->where('id', $nationId)
            ->select('id', 'name', 'leader_name', 'alliance_name')
            ->first();

        $rows = DB::table('nation_units as nu')
            ->leftJoin('unit_catalog as uc', 'nu.unit_catalog_id', '=', 'uc.id')
            ->where('nu.nation_id', $nationId)
            ->select(
                'nu.id',
                'nu.nation_id',
                'nu.unit_catalog_id',
                'nu.custom_name',
                'nu.qty',
                'nu.status',
                'nu.training_ready_at',
                'nu.stats_override_json',
                'uc.code',
                'uc.display_name',
                'uc.class_name',
                'uc.is_commander',
                'uc.base_stats_json'
            )
            ->orderByDesc('nu.status')
            ->orderBy('uc.display_name')
            ->get();

        $commanders = [];
        $units = [];
        $totalArmyRating = 0.0;

        foreach ($rows as $row) {
            $baseStats = json_decode((string) ($row->base_stats_json ?? '{}'), true);
            $baseStats = is_array($baseStats) ? $baseStats : [];

            $rawOverrideStats = json_decode((string) ($row->stats_override_json ?? '{}'), true);
            $rawOverrideStats = is_array($rawOverrideStats) ? $rawOverrideStats : [];

            $instanceMap = [];
            if (is_array($rawOverrideStats['_instances'] ?? null)) {
                foreach ($rawOverrideStats['_instances'] as $idx => $payload) {
                    $instanceIdx = (int) $idx;
                    if ($instanceIdx <= 0 || !is_array($payload)) {
                        continue;
                    }
                    $instanceMap[$instanceIdx] = $payload;
                }
            }

            $sharedOverride = $rawOverrideStats;
            unset($sharedOverride['_instances']);

            $qty = max(1, (int) ($row->qty ?? 1));
            for ($instanceIndex = 1; $instanceIndex <= $qty; $instanceIndex++) {
                $instanceOverride = is_array($instanceMap[$instanceIndex] ?? null)
                    ? $instanceMap[$instanceIndex]
                    : [];
                $effectiveOverride = array_merge($sharedOverride, $instanceOverride);
                $effectiveCustomName = trim((string) ($effectiveOverride['custom_name'] ?? $row->custom_name ?? ''));

                $effectiveStats = array_merge($baseStats, $effectiveOverride);
                $ratingBreakdown = $this->buildCombatRatingBreakdown($effectiveStats);
                $rating = (float) ($ratingBreakdown['rating'] ?? 0);
                $effectiveClassName = trim((string) ($effectiveOverride['class_name'] ?? $row->class_name ?? ''));
                $effectiveStatus = trim((string) ($effectiveOverride['status'] ?? $row->status ?? 'owned'));
                $effectiveRace = trim((string) ($effectiveOverride['race'] ?? ''));
                $effectiveTerrain = trim((string) ($effectiveOverride['terrain'] ?? ''));

                $unit = [
                    'id' => (int) $row->id,
                    'instance_index' => $instanceIndex,
                    'instance_label' => 'Unit #' . $instanceIndex,
                    'source_qty' => $qty,
                    'nation_id' => (int) $row->nation_id,
                    'unit_catalog_id' => $row->unit_catalog_id !== null ? (int) $row->unit_catalog_id : null,
                    'code' => (string) ($row->code ?? ''),
                    'display_name' => (string) ($row->display_name ?? 'Unit'),
                    'custom_name' => $effectiveCustomName,
                    'class_name' => (string) ($row->class_name ?? ''),
                    'effective_class_name' => $effectiveClassName,
                    'is_commander' => (bool) ($row->is_commander ?? false),
                    'qty' => 1,
                    'status' => (string) ($row->status ?? 'owned'),
                    'effective_status' => $effectiveStatus,
                    'race' => $effectiveRace,
                    'terrain' => $effectiveTerrain,
                    'training_ready_at' => $row->training_ready_at,
                    'base_stats' => $baseStats,
                    'stats_override' => $effectiveOverride,
                    'effective_stats' => $effectiveStats,
                    'rating' => $rating,
                    'rating_breakdown' => $ratingBreakdown,
                ];

                $totalArmyRating += $rating;

                if ($this->isCommanderUnit($unit)) {
                    $commanders[] = $unit;
                } else {
                    $units[] = $unit;
                }
            }
        }

        return [
            'nation' => $nation,
            'commanders' => $commanders,
            'units' => $units,
            'total_army_rating' => round($totalArmyRating, 2),
        ];
    }

    private function isCommanderUnit(array $unit): bool
    {
        if (!empty($unit['is_commander'])) {
            return true;
        }

        $className = strtolower(trim((string) ($unit['class_name'] ?? '')));
        $displayName = strtolower(trim((string) ($unit['display_name'] ?? '')));
        $customName = strtolower(trim((string) ($unit['custom_name'] ?? '')));

        return str_contains($className, 'commander')
            || str_contains($displayName, 'commander')
            || str_contains($customName, 'commander');
    }

    private function calculateCombatRating(array $stats): float
    {
        return (float) ($this->buildCombatRatingBreakdown($stats)['rating'] ?? 0);
    }

    private function buildCombatRatingBreakdown(array $stats, ?string $formulaExpressionOverride = null): array
    {
        $cfg = $this->loadCombatRatingConfigRaw();

        $atk = is_numeric($stats['ATK'] ?? null) ? (float) $stats['ATK'] : 0.0;
        $def = is_numeric($stats['DEF'] ?? null) ? (float) $stats['DEF'] : 0.0;
        $dmg = is_numeric($stats['DMG'] ?? null) ? (float) $stats['DMG'] : 0.0;
        $hp = is_numeric($stats['HP'] ?? null) ? (float) $stats['HP'] : 0.0;
        $mvt = is_numeric($stats['MVT'] ?? null) ? (float) $stats['MVT'] : 0.0;
        $rng = is_numeric($stats['RNG'] ?? null) ? (float) $stats['RNG'] : 0.0;
        $act = is_numeric($stats['ACT'] ?? null) ? (float) $stats['ACT'] : 0.0;

        $formulaExpression = trim((string) ($formulaExpressionOverride ?? ($cfg['formula_expression'] ?? $this->defaultCombatRatingFormulaExpression())));
        if ($formulaExpression === '') {
            $formulaExpression = $this->defaultCombatRatingFormulaExpression();
        }

        $inputs = [
            'ATK' => $atk,
            'DEF' => $def,
            'DMG' => $dmg,
            'HP' => $hp,
            'MVT' => $mvt,
            'RNG' => $rng,
            'ACT' => $act,
        ];

        $eval = $this->evaluateCombatFormulaExpression($formulaExpression, $inputs);
        $rawResult = (float) ($eval['value'] ?? 0.0);
        $formulaRating = (float) floor($rawResult);

        $overrideRating = null;
        foreach (['rating', 'RATING', 'Rating'] as $key) {
            if (array_key_exists($key, $stats) && is_numeric($stats[$key])) {
                $overrideRating = round((float) $stats[$key], 2);
                break;
            }
        }

        return [
            'source' => $overrideRating !== null ? 'override' : 'formula',
            'inputs' => $inputs,
            'formula_expression' => $formulaExpression,
            'normalized_expression' => (string) ($eval['normalized_expression'] ?? $formulaExpression),
            'evaluated_expression' => (string) ($eval['evaluated_expression'] ?? ''),
            'raw_result' => round($rawResult, 4),
            'rounding_mode' => 'floor',
            'formula_rating' => $formulaRating,
            'rating' => $overrideRating ?? $formulaRating,
        ];
    }

    private function loadCombatRatingConfigRaw(): array
    {
        $defaults = [
            'formula_expression' => $this->defaultCombatRatingFormulaExpression(),
            'rounding_mode' => 'floor',
        ];

        $path = storage_path('app/combat_rating_config.json');
        if (!File::exists($path)) {
            return $defaults;
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        $out = $defaults;
        if (is_string($decoded['formula_expression'] ?? null) && trim((string) $decoded['formula_expression']) !== '') {
            $out['formula_expression'] = trim((string) $decoded['formula_expression']);
        }
        if (is_string($decoded['rounding_mode'] ?? null) && strtolower((string) $decoded['rounding_mode']) === 'floor') {
            $out['rounding_mode'] = 'floor';
        }

        if (!isset($decoded['formula_expression']) && isset($decoded['atk'], $decoded['def'], $decoded['dmg'], $decoded['hp'], $decoded['mvt'], $decoded['rng'], $decoded['act'], $decoded['divisor'])) {
            $atk = (float) $decoded['atk'];
            $def = (float) $decoded['def'];
            $dmg = (float) $decoded['dmg'];
            $hp = (float) $decoded['hp'];
            $mvt = (float) $decoded['mvt'];
            $rng = (float) $decoded['rng'];
            $act = (float) $decoded['act'];
            $divisor = max(0.01, (float) $decoded['divisor']);
            $out['formula_expression'] = "((ATK*{$atk}) + (DEF*{$def}) + (DMG*{$dmg}) + (HP*{$hp}) + (MVT*{$mvt}) + (RNG*{$rng}) + (ACT*{$act})) / {$divisor}";
        }

        return $out;
    }

    private function defaultCombatRatingFormulaExpression(): string
    {
        return 'HP*DEF + (ATK+DEF)(MVT+RNG) + ACT(ATK*DMG)';
    }

    private function evaluateCombatFormulaExpression(string $expression, array $variables): array
    {
        $allowedVariables = ['ATK', 'DEF', 'DMG', 'HP', 'MVT', 'RNG', 'ACT'];
        $tokenized = $this->tokenizeCombatFormula($expression);
        $normalizedTokens = [];
        $prevType = null;

        foreach ($tokenized as $token) {
            $type = $token['type'];
            if (($prevType === 'number' || $prevType === 'var' || $prevType === 'rparen')
                && ($type === 'number' || $type === 'var' || $type === 'lparen')) {
                $normalizedTokens[] = ['type' => 'op', 'value' => '*'];
            }

            if ($type === 'op' && $token['value'] === '-' && ($prevType === null || $prevType === 'op' || $prevType === 'lparen')) {
                $normalizedTokens[] = ['type' => 'number', 'value' => 0.0];
            }

            $normalizedTokens[] = $token;
            $prevType = $type;
        }

        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2, '^' => 3];
        $rightAssociative = ['^' => true];
        $output = [];
        $ops = [];

        foreach ($normalizedTokens as $token) {
            if ($token['type'] === 'number' || $token['type'] === 'var') {
                $output[] = $token;
                continue;
            }

            if ($token['type'] === 'op') {
                while (!empty($ops)) {
                    $top = end($ops);
                    if (($top['type'] ?? '') !== 'op') {
                        break;
                    }
                    $topOp = (string) $top['value'];
                    $curOp = (string) $token['value'];
                    $curPrec = $precedence[$curOp] ?? 0;
                    $topPrec = $precedence[$topOp] ?? 0;
                    $isRight = (bool) ($rightAssociative[$curOp] ?? false);
                    if (($isRight && $curPrec < $topPrec) || (!$isRight && $curPrec <= $topPrec)) {
                        $output[] = array_pop($ops);
                        continue;
                    }
                    break;
                }
                $ops[] = $token;
                continue;
            }

            if ($token['type'] === 'lparen') {
                $ops[] = $token;
                continue;
            }

            if ($token['type'] === 'rparen') {
                $matched = false;
                while (!empty($ops)) {
                    $top = array_pop($ops);
                    if (($top['type'] ?? '') === 'lparen') {
                        $matched = true;
                        break;
                    }
                    $output[] = $top;
                }
                if (!$matched) {
                    throw new \InvalidArgumentException('Unmatched closing parenthesis.');
                }
            }
        }

        while (!empty($ops)) {
            $top = array_pop($ops);
            if (($top['type'] ?? '') === 'lparen' || ($top['type'] ?? '') === 'rparen') {
                throw new \InvalidArgumentException('Unmatched opening parenthesis.');
            }
            $output[] = $top;
        }

        $stack = [];
        foreach ($output as $token) {
            if ($token['type'] === 'number') {
                $stack[] = (float) $token['value'];
                continue;
            }
            if ($token['type'] === 'var') {
                $name = strtoupper((string) $token['value']);
                if (!in_array($name, $allowedVariables, true)) {
                    throw new \InvalidArgumentException('Unsupported variable: ' . $name);
                }
                $stack[] = is_numeric($variables[$name] ?? null) ? (float) $variables[$name] : 0.0;
                continue;
            }
            if ($token['type'] !== 'op') {
                continue;
            }
            if (count($stack) < 2) {
                throw new \InvalidArgumentException('Invalid expression structure.');
            }
            $b = (float) array_pop($stack);
            $a = (float) array_pop($stack);
            $op = (string) $token['value'];
            if ($op === '+') {
                $stack[] = $a + $b;
            } elseif ($op === '-') {
                $stack[] = $a - $b;
            } elseif ($op === '*') {
                $stack[] = $a * $b;
            } elseif ($op === '/') {
                if (abs($b) < 0.0000001) {
                    throw new \InvalidArgumentException('Division by zero is not allowed.');
                }
                $stack[] = $a / $b;
            } elseif ($op === '^') {
                $stack[] = pow($a, $b);
            } else {
                throw new \InvalidArgumentException('Unsupported operator: ' . $op);
            }
        }

        if (count($stack) !== 1) {
            throw new \InvalidArgumentException('Invalid formula evaluation result.');
        }

        $formatNum = static fn (float $n): string => rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
        $normalizedExpression = '';
        $evaluatedExpression = '';
        foreach ($normalizedTokens as $token) {
            if ($token['type'] === 'number') {
                $part = $formatNum((float) $token['value']);
                $normalizedExpression .= $part;
                $evaluatedExpression .= $part;
                continue;
            }
            if ($token['type'] === 'var') {
                $name = strtoupper((string) $token['value']);
                $normalizedExpression .= $name;
                $evaluatedExpression .= $formatNum(is_numeric($variables[$name] ?? null) ? (float) $variables[$name] : 0.0);
                continue;
            }
            if ($token['type'] === 'op' || $token['type'] === 'lparen' || $token['type'] === 'rparen') {
                $normalizedExpression .= (string) $token['value'];
                $evaluatedExpression .= (string) $token['value'];
            }
        }

        return [
            'value' => (float) $stack[0],
            'normalized_expression' => $normalizedExpression,
            'evaluated_expression' => $evaluatedExpression,
        ];
    }

    private function tokenizeCombatFormula(string $expression): array
    {
        $src = trim($expression);
        if ($src === '') {
            throw new \InvalidArgumentException('Formula is empty.');
        }

        $tokens = [];
        $len = strlen($src);
        $i = 0;
        while ($i < $len) {
            $ch = $src[$i];
            if (ctype_space($ch)) {
                $i++;
                continue;
            }
            if (ctype_digit($ch) || $ch === '.') {
                $start = $i;
                $dotCount = $ch === '.' ? 1 : 0;
                $i++;
                while ($i < $len) {
                    $c = $src[$i];
                    if ($c === '.') {
                        $dotCount++;
                        if ($dotCount > 1) {
                            break;
                        }
                        $i++;
                        continue;
                    }
                    if (!ctype_digit($c)) {
                        break;
                    }
                    $i++;
                }
                $numRaw = substr($src, $start, $i - $start);
                if (!is_numeric($numRaw)) {
                    throw new \InvalidArgumentException('Invalid number token: ' . $numRaw);
                }
                $tokens[] = ['type' => 'number', 'value' => (float) $numRaw];
                continue;
            }
            if (ctype_alpha($ch) || $ch === '_') {
                $start = $i;
                $i++;
                while ($i < $len) {
                    $c = $src[$i];
                    if (!(ctype_alnum($c) || $c === '_')) {
                        break;
                    }
                    $i++;
                }
                $tokens[] = ['type' => 'var', 'value' => strtoupper(substr($src, $start, $i - $start))];
                continue;
            }
            if (in_array($ch, ['+', '-', '*', '/', '^'], true)) {
                $tokens[] = ['type' => 'op', 'value' => $ch];
                $i++;
                continue;
            }
            if ($ch === '(') {
                $tokens[] = ['type' => 'lparen', 'value' => '('];
                $i++;
                continue;
            }
            if ($ch === ')') {
                $tokens[] = ['type' => 'rparen', 'value' => ')'];
                $i++;
                continue;
            }

            throw new \InvalidArgumentException('Invalid character in formula: ' . $ch);
        }

        if (empty($tokens)) {
            throw new \InvalidArgumentException('Formula is empty.');
        }

        return $tokens;
    }

    private function accumulateProjection(array &$bucket, string $key, float $value): void
    {
        $token = $this->parseResourceToken($key);
        if (!$token) {
            return;
        }

        $name = $token['name'];
        if ($token['bucket'] === 'base') {
            $bucket['base'][$name] = (float) ($bucket['base'][$name] ?? 0) + $value;
            return;
        }

        if ($token['bucket'] === 'advanced') {
            $bucket['advanced'][$name] = (float) ($bucket['advanced'][$name] ?? 0) + $value;
            $bucket['refined'][$name] = (float) ($bucket['refined'][$name] ?? 0) + $value;
            return;
        }

        $bucket['currencies'][$name] = (float) ($bucket['currencies'][$name] ?? 0) + $value;
    }

    private function parseResourceToken(string $rawKey): ?array
    {
        $key = trim($rawKey);
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

    private function readNationResourceState(object $row): array
    {
        $extra = json_decode((string) ($row->extra_json ?? '{}'), true) ?: [];
        $base = [];
        foreach ((is_array($extra['base'] ?? null) ? $extra['base'] : []) as $key => $value) {
            $base[(string) $key] = (float) $value;
        }

        foreach ($this->legacyCoreBaseResourceValues($row) as $key => $value) {
            if (!array_key_exists($key, $base)) {
                $base[$key] = (float) $value;
            }
        }

        $advanced = is_array($extra['advanced'] ?? null) ? $extra['advanced'] : [];
        $currencies = is_array($extra['currencies'] ?? null) ? $extra['currencies'] : [];

        return [
            'base' => array_map(static fn ($v) => (float) $v, $base),
            'advanced' => array_map(static fn ($v) => (float) $v, $advanced),
            'currencies' => array_map(static fn ($v) => (float) $v, $currencies),
        ];
    }

    private function normalizeIncomeMap(array $extra): array
    {
        if (is_array($extra['income_resources'] ?? null)) {
            $out = [];
            foreach ($extra['income_resources'] as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $rawType = strtolower(trim((string) ($entry['type'] ?? 'base')));
                $type = match ($rawType) {
                    'advanced' => 'advanced',
                    'currencies' => 'currencies',
                    default => 'base',
                };
                $name = trim((string) ($entry['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $out[$type . ':' . $name] = (float) ($entry['amount'] ?? 0);
            }
            return $out;
        }

        $income = is_array($extra['income'] ?? null) ? $extra['income'] : [];
        if (empty($income)) {
            return [];
        }

        $out = [];
        foreach ($income as $key => $value) {
            $token = $this->parseResourceToken((string) $key);
            if (!$token) {
                continue;
            }
            $out[$token['bucket'] . ':' . $token['name']] = (float) $value;
        }

        return $out;
    }
}
