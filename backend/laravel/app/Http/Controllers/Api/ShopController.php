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
        $baseColumns = ['cow', 'wood', 'ore', 'food'];
        $extra = json_decode($resourceRow->extra_json ?? '{}', true) ?: [];
        $refined = $extra['refined'] ?? [];
        $currencies = $extra['currencies'] ?? [];

        // Validate all costs
        foreach ($costs as $resource => $cost) {
            $required = (float) $cost * $quantity;
            if (in_array($resource, $baseColumns, true)) {
                if ((float) $resourceRow->{$resource} < $required) {
                    return response()->json(['message' => "Not enough {$resource}"], 422);
                }
            } elseif (str_starts_with($resource, 'ref_')) {
                $key = substr($resource, 4);
                if (($refined[$key] ?? 0) < $required) {
                    return response()->json(['message' => "Not enough refined resource: {$key}"], 422);
                }
            } elseif (str_starts_with($resource, 'cur_')) {
                $key = substr($resource, 4);
                if (($currencies[$key] ?? 0) < $required) {
                    return response()->json(['message' => "Not enough currency: {$key}"], 422);
                }
            }
        }

        // Apply cost deductions
        $baseUpdate = [];
        foreach ($costs as $resource => $cost) {
            $amount = (float) $cost * $quantity;
            if (in_array($resource, $baseColumns, true)) {
                $baseUpdate[$resource] = (float) $resourceRow->{$resource} - $amount;
            } elseif (str_starts_with($resource, 'ref_')) {
                $key = substr($resource, 4);
                $refined[$key] = ($refined[$key] ?? 0) - $amount;
            } elseif (str_starts_with($resource, 'cur_')) {
                $key = substr($resource, 4);
                $currencies[$key] = ($currencies[$key] ?? 0) - $amount;
            }
        }

        // Apply effect gains
        $effects = json_decode($item->effect_json, true) ?: [];
        if (isset($effects['refined']) && is_array($effects['refined'])) {
            foreach ($effects['refined'] as $key => $gain) {
                $refined[$key] = ($refined[$key] ?? 0) + ((float) $gain * $quantity);
            }
        }
        if (isset($effects['currency']) && is_array($effects['currency'])) {
            foreach ($effects['currency'] as $key => $gain) {
                $currencies[$key] = ($currencies[$key] ?? 0) + ((float) $gain * $quantity);
            }
        }

        // Backward-compatible effect handling: direct keys in effect_json
        foreach ($effects as $effectKey => $effectValue) {
            if (!is_numeric($effectValue)) {
                continue;
            }
            $gain = (float) $effectValue * $quantity;
            if (in_array($effectKey, $baseColumns, true)) {
                $baseUpdate[$effectKey] = ((float) ($baseUpdate[$effectKey] ?? $resourceRow->{$effectKey})) + $gain;
            } elseif (str_starts_with($effectKey, 'ref_')) {
                $rKey = substr($effectKey, 4);
                $refined[$rKey] = ($refined[$rKey] ?? 0) + $gain;
            } elseif (str_starts_with($effectKey, 'cur_')) {
                $cKey = substr($effectKey, 4);
                $currencies[$cKey] = ($currencies[$cKey] ?? 0) + $gain;
            }
        }

        $extra['refined'] = $refined;
        $extra['currencies'] = $currencies;
        $baseUpdate['extra_json'] = json_encode($extra);
        $baseUpdate['updated_at'] = now();
        DB::table('nation_resources')->where('nation_id', $nation->id)->update($baseUpdate);

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

        $hasTrackedAsset = !empty(json_decode($item->maintenance_json ?? 'null', true) ?: [])
            || !empty(json_decode($item->yearly_effect_json ?? 'null', true) ?: []);
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

        DB::table('admin_notifications')->insert([
            'type' => 'shop_purchase',
            'title' => 'Shop purchase: ' . $item->display_name,
            'body' => 'User #' . $request->user()->id . ' (' . $request->user()->name . ') purchased ' . $quantity . 'x ' . $item->display_name
                . ' for nation #' . $nation->id . ' (' . $nation->name . ').'
                . ' Purchase cost: ' . json_encode($costs)
                . '. Purchase effect: ' . json_encode($effects)
                . '. Remaining balances after purchase: ' . json_encode([
                    'base' => ['cow' => (float) $updatedRow->cow, 'wood' => (float) $updatedRow->wood, 'ore' => (float) $updatedRow->ore, 'food' => (float) $updatedRow->food],
                    'refined' => $updatedExtra['refined'] ?? [],
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
                'base'       => ['cow' => (float)$updatedRow->cow, 'wood' => (float)$updatedRow->wood, 'ore' => (float)$updatedRow->ore, 'food' => (float)$updatedRow->food],
                'refined'    => $updatedExtra['refined']    ?? [],
                'currencies' => $updatedExtra['currencies'] ?? [],
            ],
        ]);
    }
}
