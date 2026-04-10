<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreatePlaceholderNationRequest;
use App\Http\Requests\Api\StoreChatRequest;
use App\Http\Requests\Api\UpdateNationRequest;
use App\Http\Requests\Api\UpdateShopItemRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function nations(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 30), 100);
        $rows = DB::table('nations as n')
            ->leftJoin('users as u', 'n.owner_user_id', '=', 'u.id')
            ->select('n.*', 'u.name as player_name')
            ->orderBy('n.name')
            ->paginate($perPage);

        return response()->json($rows);
    }

    public function createPlaceholderNation(CreatePlaceholderNationRequest $request)
    {
        $data = $request->validated();

        $nationId = DB::table('nations')->insertGetId([
            'name' => $data['name'],
            'is_placeholder' => 1,
            'leader_name' => $data['leader_name'] ?? null,
            'alliance_name' => $data['alliance_name'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nation_resources')->insert([
            'nation_id' => $nationId,
            'cow' => 0,
            'wood' => 0,
            'ore' => 0,
            'food' => 0,
            'updated_at' => now(),
        ]);

        DB::table('nation_terrain_stats')->insert([
            'nation_id' => $nationId,
            'grassland_pct' => 0,
            'mountain_pct' => 0,
            'freshwater_pct' => 0,
            'hills_pct' => 0,
            'desert_pct' => 0,
            'square_miles_json' => json_encode(new \stdClass()),
            'updated_at' => now(),
        ]);

        return response()->json(['id' => $nationId, 'message' => 'Placeholder nation created'], 201);
    }

    public function updateNation(UpdateNationRequest $request, int $nationId)
    {
        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        $data = $request->validated();

        DB::table('nations')->where('id', $nationId)->update([
            'name' => $data['name'] ?? $nation->name,
            'leader_name' => $data['leader_name'] ?? $nation->leader_name,
            'alliance_name' => $data['alliance_name'] ?? $nation->alliance_name,
            'about_text' => $data['about_text'] ?? $nation->about_text,
            'updated_at' => now(),
        ]);

        if (isset($data['resources'])) {
            $res = DB::table('nation_resources')->where('nation_id', $nationId)->first();
            $extra = json_decode($res->extra_json ?? '{}', true) ?: [];
            if (isset($data['refined_resources'])) {
                $extra['refined'] = array_merge($extra['refined'] ?? [], $data['refined_resources']);
            }
            if (isset($data['currencies'])) {
                $extra['currencies'] = array_merge($extra['currencies'] ?? [], $data['currencies']);
            }
            DB::table('nation_resources')->where('nation_id', $nationId)->update([
                'cow' => $data['resources']['cow'] ?? ($res->cow ?? 0),
                'wood' => $data['resources']['wood'] ?? ($res->wood ?? 0),
                'ore' => $data['resources']['ore'] ?? ($res->ore ?? 0),
                'food' => $data['resources']['food'] ?? ($res->food ?? 0),
                'extra_json' => json_encode($extra),
                'updated_at' => now(),
            ]);
        } elseif (isset($data['refined_resources']) || isset($data['currencies'])) {
            $res = DB::table('nation_resources')->where('nation_id', $nationId)->first();
            $extra = json_decode($res->extra_json ?? '{}', true) ?: [];
            if (isset($data['refined_resources'])) {
                $extra['refined'] = array_merge($extra['refined'] ?? [], $data['refined_resources']);
            }
            if (isset($data['currencies'])) {
                $extra['currencies'] = array_merge($extra['currencies'] ?? [], $data['currencies']);
            }
            DB::table('nation_resources')->where('nation_id', $nationId)->update([
                'extra_json' => json_encode($extra),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Nation updated']);
    }

    public function addUnitToNation(Request $request, int $nationId)
    {
        $data = $request->validate([
            'unit_catalog_id' => ['required', 'integer', 'exists:unit_catalog,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:owned,training'],
        ]);

        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        $existing = DB::table('nation_units')
            ->where('nation_id', $nationId)
            ->where('unit_catalog_id', $data['unit_catalog_id'])
            ->where('status', $data['status'] ?? 'owned')
            ->first();

        if ($existing) {
            DB::table('nation_units')->where('id', $existing->id)
                ->update(['qty' => $existing->qty + $data['qty'], 'updated_at' => now()]);
        } else {
            DB::table('nation_units')->insert([
                'nation_id' => $nationId,
                'unit_catalog_id' => $data['unit_catalog_id'],
                'qty' => $data['qty'],
                'status' => $data['status'] ?? 'owned',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Unit added']);
    }

    public function createChat(StoreChatRequest $request)
    {
        return app(ChatController::class)->store($request);
    }

    public function deleteChat(int $chatId)
    {
        DB::table('chats')->where('id', $chatId)->delete();
        return response()->json(['message' => 'Chat deleted']);
    }

    public function addMembers(Request $request, int $chatId)
    {
        $data = $request->validate([
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        foreach (array_unique($data['member_ids']) as $userId) {
            DB::table('chat_members')->updateOrInsert(
                ['chat_id' => $chatId, 'user_id' => $userId],
                []
            );
        }

        return response()->json(['message' => 'Members updated']);
    }

    public function removeMember(int $chatId, int $userId)
    {
        DB::table('chat_members')->where('chat_id', $chatId)->where('user_id', $userId)->delete();
        return response()->json(['message' => 'Member removed']);
    }

    public function updateShopItem(UpdateShopItemRequest $request, int $itemId)
    {
        $data = $request->validated();

        $item = DB::table('shop_items')->where('id', $itemId)->first();
        if (!$item) {
            return response()->json(['message' => 'Shop item not found'], 404);
        }

        $updated = [
            'display_name' => $data['display_name'] ?? $item->display_name,
            'cost_json' => array_key_exists('cost_json', $data) ? json_encode($data['cost_json']) : $item->cost_json,
            'effect_json' => array_key_exists('effect_json', $data) ? json_encode($data['effect_json']) : $item->effect_json,
            'is_active' => array_key_exists('is_active', $data) ? (int) $data['is_active'] : $item->is_active,
            'visibility_json' => array_key_exists('visibility_json', $data)
                ? ($data['visibility_json'] === null ? null : json_encode(array_values(array_map('intval', $data['visibility_json']))))
                : $item->visibility_json,
        ];

        DB::table('shop_items')->where('id', $itemId)->update($updated);

        return response()->json(['message' => 'Shop item updated']);
    }
}
