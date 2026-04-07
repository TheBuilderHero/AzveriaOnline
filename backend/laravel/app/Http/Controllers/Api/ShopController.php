<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BuyShopItemRequest;
use App\Models\ShopItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function categories()
    {
        $this->authorize('viewAny', ShopItem::class);
        return response()->json(DB::table('shop_categories')->orderBy('id')->get());
    }

    public function items(Request $request)
    {
        $this->authorize('viewAny', ShopItem::class);
        $category = $request->query('category');
        $perPage = min((int) $request->query('per_page', 30), 100);

        $query = DB::table('shop_items as si')
            ->join('shop_categories as sc', 'si.category_id', '=', 'sc.id')
            ->where('si.is_active', 1)
            ->select('si.*', 'sc.code as category_code', 'sc.display_name as category_name');

        if ($category) {
            $query->where('sc.code', $category);
        }

        return response()->json($query->orderBy('si.id')->paginate($perPage));
    }

    public function buy(BuyShopItemRequest $request)
    {
        $data = $request->validated();

        $quantity = (int) ($data['quantity'] ?? 1);
        $item = DB::table('shop_items')->where('id', $data['item_id'])->first();
        if (!$item || !$item->is_active) {
            return response()->json(['message' => 'Item unavailable'], 422);
        }

        $this->authorize('buy', new ShopItem((array) $item));

        $nation = DB::table('nations')->where('owner_user_id', $request->user()->id)->first();
        if (!$nation) {
            return response()->json(['message' => 'No nation assigned'], 404);
        }

        $resourceRow = DB::table('nation_resources')->where('nation_id', $nation->id)->first();
        if (!$resourceRow) {
            return response()->json(['message' => 'No resources found'], 404);
        }

        $costs = json_decode($item->cost_json, true) ?: [];
        $allowedResourceColumns = ['cow', 'wood', 'ore', 'food'];

        foreach ($costs as $resource => $cost) {
            if (!in_array($resource, $allowedResourceColumns, true)) {
                continue;
            }

            $required = (float) $cost * $quantity;
            if ((float) $resourceRow->{$resource} < $required) {
                return response()->json(['message' => "Not enough {$resource}"], 422);
            }
        }

        $update = [];
        foreach ($costs as $resource => $cost) {
            if (!in_array($resource, $allowedResourceColumns, true)) {
                continue;
            }
            $update[$resource] = (float) $resourceRow->{$resource} - ((float) $cost * $quantity);
        }
        $update['updated_at'] = now();

        DB::table('nation_resources')->where('nation_id', $nation->id)->update($update);

        return response()->json([
            'message' => 'Purchase successful',
            'remaining' => DB::table('nation_resources')->where('nation_id', $nation->id)->first(),
        ]);
    }
}
