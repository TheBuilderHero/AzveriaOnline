<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateAboutRequest;
use App\Http\Requests\Api\UpdateSettingsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $structuredResources = [
            'base' => [
                'cow'  => (float) $resources->cow,
                'wood' => (float) $resources->wood,
                'ore'  => (float) $resources->ore,
                'food' => (float) $resources->food,
            ],
            'refined'    => $extra['refined']    ?? [],
            'currencies' => $extra['currencies'] ?? [],
        ];

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
        return response()->json([
            'base' => [
                'cow'  => (float) $row->cow,
                'wood' => (float) $row->wood,
                'ore'  => (float) $row->ore,
                'food' => (float) $row->food,
            ],
            'refined'    => $extra['refined']    ?? [],
            'currencies' => $extra['currencies'] ?? [],
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
            return response()->json($payload);
        }

        DB::table('user_settings')->insert([
            'user_id' => $request->user()->id,
            'theme' => 'light',
            'color_blind_mode' => 'none',
            'dog_bark_enabled' => 0,
            'extra_json' => json_encode(['font_mode' => 'normal']),
            'updated_at' => now(),
        ]);
        $created = DB::table('user_settings')->where('user_id', $request->user()->id)->first();
        $payload = (array) $created;
        $payload['font_mode'] = 'normal';
        return response()->json($payload);
    }

    public function updateSettings(UpdateSettingsRequest $request)
    {
        $data = $request->validated();

        $current = $this->settings($request)->getData(true);
        $extra = json_decode($current['extra_json'] ?? '{}', true) ?: [];
        $extra['font_mode'] = $data['font_mode'] ?? ($current['font_mode'] ?? 'normal');

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
            ->where('role', 'player')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
        return response()->json($rows);
    }

    private function findNation(int $userId): ?object
    {
        return DB::table('nations')->where('owner_user_id', $userId)->first();
    }
}
