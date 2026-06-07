<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateAboutRequest;
use App\Http\Requests\Api\UpdateSettingsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
        $incomeMap = $this->normalizeIncomeMap($extra);
        // Only use canonical dynamic resources for base resources
        $baseResources = [];
        foreach (($extra['base'] ?? []) as $key => $value) {
            $baseResources[(string) $key] = (float) $value;
        }
        $advancedResources = is_array($extra['advanced'] ?? null)
            ? $extra['advanced']
            : (is_array($extra['refined'] ?? null) ? $extra['refined'] : []);

        $structuredResources = [
            'base' => $baseResources,
            'advanced' => $advancedResources,
            'refined' => $advancedResources,
            'currencies' => $extra['currencies'] ?? [],
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
            ->select('na.qty', 'si.display_name', 'si.maintenance_json', 'si.yearly_effect_json')
            ->get();

        foreach ($assets as $asset) {
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
        $base = [
            'cow'  => (float) $row->cow,
            'wood' => (float) $row->wood,
            'ore'  => (float) $row->ore,
            'food' => (float) $row->food,
        ];
        foreach (($extra['base'] ?? []) as $key => $value) {
            if (in_array($key, ['cow', 'wood', 'ore', 'food'], true)) {
                continue;
            }
            $base[(string) $key] = (float) $value;
        }
        $advanced = is_array($extra['advanced'] ?? null)
            ? $extra['advanced']
            : (is_array($extra['refined'] ?? null) ? $extra['refined'] : []);

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
            foreach (['cow', 'wood', 'ore', 'food'] as $name) {
                $topbarDisplay[] = [
                    'type' => 'base',
                    'name' => $name,
                    'label' => (string) ($displayNames['base'][$name] ?? ucfirst($name)),
                    'value' => (float) ($base[$name] ?? 0),
                ];
            }
        }

        return response()->json([
            'base' => $base,
            'advanced' => $advanced,
            'refined' => $advanced,
            'currencies' => $extra['currencies'] ?? [],
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
            $payload['show_unread_chat_badge'] = (bool) ($extra['show_unread_chat_badge'] ?? true);
            $payload['apply_year_change_effects'] = (bool) ($extra['apply_year_change_effects'] ?? false);
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
                'show_unread_chat_badge' => true,
                'apply_year_change_effects' => false,
                'alliance_color_overrides' => [],
                'political_nation_color_overrides' => [],
            ]),
            'updated_at' => now(),
        ]);
        $created = DB::table('user_settings')->where('user_id', $request->user()->id)->first();
        $payload = (array) $created;
        $payload['font_mode'] = 'normal';
        $payload['map_zoom_sensitivity'] = 1;
        $payload['map_max_zoom_pct'] = (int) ($this->loadGlobalMapSettingsRaw()['map_max_zoom_pct'] ?? 180);
        $payload['show_unread_chat_badge'] = true;
        $payload['apply_year_change_effects'] = false;
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
        $extra['show_unread_chat_badge'] = array_key_exists('show_unread_chat_badge', $data)
            ? (bool) $data['show_unread_chat_badge']
            : (bool) ($current['show_unread_chat_badge'] ?? true);
        $extra['apply_year_change_effects'] = array_key_exists('apply_year_change_effects', $data)
            ? (bool) $data['apply_year_change_effects']
            : (bool) ($current['apply_year_change_effects'] ?? false);
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
        ]);

        $unit = DB::table('nation_units')
            ->where('id', $unitId)
            ->where('nation_id', (int) $nation->id)
            ->first();

        if (!$unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }

        DB::table('nation_units')->where('id', $unitId)->update([
            'custom_name' => array_key_exists('custom_name', $data)
                ? trim((string) ($data['custom_name'] ?? ''))
                : $unit->custom_name,
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

        $rows = DB::table('admin_notifications')
            ->where('type', 'combat_order')
            ->whereRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(meta_json, "$.actor_user_id")) AS UNSIGNED) = ?', [$actorUserId])
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
            ->select('nb.*', 'bc.display_name', 'bc.code');

        if (in_array($status, ['built', 'constructing', 'upgrading'], true)) {
            $query->where('nb.status', $status);
        }

        return response()->json($query->get());
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
            ->orderBy('name')
            ->get();
        return response()->json($rows);
    }

    private function resourceDisplayNames(): array
    {
        $map = [
            'base' => [
                'cow' => 'Cow',
                'wood' => 'Wood',
                'ore' => 'Ore',
                'food' => 'Food',
            ],
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
            $global = [
                ['type' => 'base', 'name' => 'cow'],
                ['type' => 'base', 'name' => 'wood'],
                ['type' => 'base', 'name' => 'ore'],
                ['type' => 'base', 'name' => 'food'],
            ];
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
            return ['map_max_zoom_pct' => 180];
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            return ['map_max_zoom_pct' => 180];
        }

        $maxZoom = (int) ($decoded['map_max_zoom_pct'] ?? 180);
        if ($maxZoom < 100) {
            $maxZoom = 100;
        }
        if ($maxZoom > 300) {
            $maxZoom = 300;
        }

        return ['map_max_zoom_pct' => $maxZoom];
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
                    'custom_name' => $row->custom_name,
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

    private function buildCombatRatingBreakdown(array $stats): array
    {
        $cfg = $this->loadCombatRatingConfigRaw();

        $atk = is_numeric($stats['ATK'] ?? null) ? (float) $stats['ATK'] : 0.0;
        $def = is_numeric($stats['DEF'] ?? null) ? (float) $stats['DEF'] : 0.0;
        $dmg = is_numeric($stats['DMG'] ?? null) ? (float) $stats['DMG'] : 0.0;
        $hp = is_numeric($stats['HP'] ?? null) ? (float) $stats['HP'] : 0.0;
        $mvt = is_numeric($stats['MVT'] ?? null) ? (float) $stats['MVT'] : 0.0;
        $rng = is_numeric($stats['RNG'] ?? null) ? (float) $stats['RNG'] : 0.0;
        $act = is_numeric($stats['ACT'] ?? null) ? (float) $stats['ACT'] : 0.0;

        $components = [
            'ATK' => $atk * (float) $cfg['atk'],
            'DEF' => $def * (float) $cfg['def'],
            'DMG' => $dmg * (float) $cfg['dmg'],
            'HP' => $hp * (float) $cfg['hp'],
            'MVT' => $mvt * (float) $cfg['mvt'],
            'RNG' => $rng * (float) $cfg['rng'],
            'ACT' => $act * (float) $cfg['act'],
        ];
        $score = array_sum($components);
        $divisor = max(0.01, (float) ($cfg['divisor'] ?? 10.0));
        $formulaRating = round($score / $divisor, 2);

        $overrideRating = null;
        foreach (['rating', 'RATING', 'Rating'] as $key) {
            if (array_key_exists($key, $stats) && is_numeric($stats[$key])) {
                $overrideRating = round((float) $stats[$key], 2);
                break;
            }
        }

        return [
            'source' => $overrideRating !== null ? 'override' : 'formula',
            'inputs' => [
                'ATK' => $atk,
                'DEF' => $def,
                'DMG' => $dmg,
                'HP' => $hp,
                'MVT' => $mvt,
                'RNG' => $rng,
                'ACT' => $act,
            ],
            'weights' => [
                'ATK' => (float) $cfg['atk'],
                'DEF' => (float) $cfg['def'],
                'DMG' => (float) $cfg['dmg'],
                'HP' => (float) $cfg['hp'],
                'MVT' => (float) $cfg['mvt'],
                'RNG' => (float) $cfg['rng'],
                'ACT' => (float) $cfg['act'],
            ],
            'components' => array_map(static fn ($v) => round((float) $v, 2), $components),
            'score' => round($score, 2),
            'divisor' => $divisor,
            'formula_rating' => $formulaRating,
            'rating' => $overrideRating ?? $formulaRating,
        ];
    }

    private function loadCombatRatingConfigRaw(): array
    {
        $defaults = [
            'atk' => 2.0,
            'def' => 1.5,
            'dmg' => 3.0,
            'hp' => 2.0,
            'mvt' => 1.0,
            'rng' => 1.0,
            'act' => 1.0,
            'divisor' => 10.0,
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
        foreach (array_keys($defaults) as $key) {
            if (is_numeric($decoded[$key] ?? null)) {
                $out[$key] = (float) $decoded[$key];
            }
        }
        $out['divisor'] = max(0.01, (float) $out['divisor']);

        return $out;
    }

    private function accumulateProjection(array &$bucket, string $key, float $value): void
    {
        if (str_starts_with($key, 'base:')) {
            $baseKey = substr($key, 5);
            if ($baseKey !== '') {
                $bucket['base'][$baseKey] = (float) ($bucket['base'][$baseKey] ?? 0) + $value;
            }
            return;
        }

        if (str_starts_with($key, 'advanced:')) {
            $advancedKey = substr($key, 9);
            if ($advancedKey !== '') {
                $bucket['advanced'][$advancedKey] = (float) ($bucket['advanced'][$advancedKey] ?? 0) + $value;
                $bucket['refined'][$advancedKey] = (float) ($bucket['refined'][$advancedKey] ?? 0) + $value;
            }
            return;
        }

        if (in_array($key, ['cow', 'wood', 'ore', 'food'], true)) {
            $bucket['base'][$key] = (float) ($bucket['base'][$key] ?? 0) + $value;
            return;
        }
    }

    private function normalizeIncomeMap(array $extra): array
    {
        if (is_array($extra['income_resources'] ?? null)) {
            $out = [];
            foreach ($extra['income_resources'] as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $type = ($entry['type'] ?? '') === 'advanced' ? 'advanced' : 'base';
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
            return ['base:cow' => 30, 'base:wood' => 3, 'base:ore' => 3, 'base:food' => 3];
        }

        $out = [];
        foreach ($income as $key => $value) {
            $rawKey = (string) $key;
            if (str_contains($rawKey, ':')) {
                [$type, $name] = explode(':', $rawKey, 2);
                $type = trim(strtolower($type));
                $name = trim($name);
                if (($type === 'base' || $type === 'advanced') && $name !== '') {
                    $out[$type . ':' . $name] = (float) $value;
                }
                continue;
            }

            $name = trim($rawKey);
            if ($name !== '') {
                $out['base:' . $name] = (float) $value;
            }
        }

        return $out;
    }
}
