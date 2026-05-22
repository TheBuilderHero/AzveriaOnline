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
            $payload = (array) $settings;
            $payload['font_mode'] = $extra['font_mode'] ?? 'normal';
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

    private function findNation(int $userId): ?object
    {
        return DB::table('nations')->where('owner_user_id', $userId)->first();
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
