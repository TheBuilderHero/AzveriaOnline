<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Nation::class);
        $search = trim((string) $request->query('search', ''));
        $perPage = min((int) $request->query('per_page', 30), 100);

        $query = DB::table('nations as n')
            ->leftJoin('users as u', 'n.owner_user_id', '=', 'u.id')
            ->select('n.id', 'n.name', 'n.is_placeholder', 'u.name as player_name', 'n.leader_name', 'n.alliance_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('n.name', 'like', "%{$search}%")
                  ->orWhere('u.name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(n.name, ' (', COALESCE(u.name, 'Unassigned'), ')') LIKE ?", ["%{$search}%"]);
            });
        }

        return response()->json($query->orderBy('n.name')->paginate($perPage));
    }

    public function show(Request $request, int $nationId)
    {
        $nationModel = Nation::findOrFail($nationId);
        $this->authorize('view', $nationModel);

        $nation = DB::table('nations as n')
            ->leftJoin('users as u', 'n.owner_user_id', '=', 'u.id')
            ->where('n.id', $nationId)
            ->select('n.*', 'u.name as player_name')
            ->first();

        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        $resources = DB::table('nation_resources')->where('nation_id', $nationId)->first();
        $terrain = DB::table('nation_terrain_stats')->where('nation_id', $nationId)->first();
        $units = DB::table('nation_units as nu')
            ->leftJoin('unit_catalog as uc', 'nu.unit_catalog_id', '=', 'uc.id')
            ->where('nu.nation_id', $nationId)
            ->select('nu.*', 'uc.display_name', 'uc.class_name')
            ->get();
        $buildings = DB::table('nation_buildings as nb')
            ->join('building_catalog as bc', 'nb.building_catalog_id', '=', 'bc.id')
            ->where('nb.nation_id', $nationId)
            ->select('nb.*', 'bc.display_name', 'bc.code')
            ->get();

        $viewer = $request->user();
        $visibility = [
            'leader_name' => true,
            'alliance_name' => true,
            'about_text' => true,
            'resources_base' => true,
            'resources_refined' => true,
            'resources_currencies' => true,
            'terrain' => true,
            'units' => true,
            'buildings' => true,
        ];

        if ($viewer && $viewer->role !== 'admin') {
            $viewerUserId = (int) $viewer->id;
            $subjectUserId = (int) ($nation->owner_user_id ?? 0);
            if ($subjectUserId > 0 && $viewerUserId !== $subjectUserId) {
                $ruleRows = DB::table('player_visibility_rules')
                    ->where('viewer_user_id', $viewerUserId)
                    ->where('subject_user_id', $subjectUserId)
                    ->get();
                foreach ($ruleRows as $rule) {
                    $visibility[(string) $rule->field_key] = (bool) $rule->is_allowed;
                }

                if (!$visibility['leader_name']) {
                    $nation->leader_name = null;
                }
                if (!$visibility['alliance_name']) {
                    $nation->alliance_name = null;
                }
                if (!$visibility['about_text']) {
                    $nation->about_text = null;
                }

                if ($resources) {
                    $extra = json_decode($resources->extra_json ?? '{}', true) ?: [];
                    if (!$visibility['resources_base']) {
                        $resources->cow = null;
                        $resources->wood = null;
                        $resources->ore = null;
                        $resources->food = null;
                    }
                    if (!$visibility['resources_refined']) {
                        $extra['refined'] = [];
                    }
                    if (!$visibility['resources_currencies']) {
                        $extra['currencies'] = [];
                    }
                    $resources->extra_json = json_encode($extra);
                }

                if (!$visibility['terrain']) {
                    $terrain = null;
                }
                if (!$visibility['units']) {
                    $units = collect();
                }
                if (!$visibility['buildings']) {
                    $buildings = collect();
                }
            }
        }

        return response()->json([
            'nation' => $nation,
            'resources' => $resources,
            'terrain' => $terrain,
            'units' => $units,
            'buildings' => $buildings,
            'visibility' => $visibility,
        ]);
    }
}
