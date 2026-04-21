<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreatePlaceholderNationRequest;
use App\Http\Requests\Api\StoreChatRequest;
use App\Http\Requests\Api\UpdateNationRequest;
use App\Http\Requests\Api\UpdateShopItemRequest;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function __construct(private AccountService $accounts)
    {
    }

    public function newAccountDefaults()
    {
        return response()->json($this->accounts->getNewAccountDefaults());
    }

    public function updateNewAccountDefaults(Request $request)
    {
        $data = $request->validate([
            'nation_name_template' => ['sometimes', 'string', 'max:150'],
            'leader_name_template' => ['sometimes', 'string', 'max:120'],
            'alliance_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'about_text' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'default_temp_password' => ['sometimes', 'nullable', 'string', 'max:120'],
            'resources' => ['sometimes', 'array'],
            'resources.*' => ['numeric', 'min:0'],
            'refined_resources' => ['sometimes', 'array'],
            'refined_resources.*' => ['numeric', 'min:0'],
            'currencies' => ['sometimes', 'array'],
            'currencies.*' => ['numeric', 'min:0'],
            'terrain_square_miles' => ['sometimes', 'array'],
            'terrain_square_miles.*' => ['numeric', 'min:0'],
            'income_defaults' => ['sometimes', 'array'],
            'income_defaults.cow' => ['sometimes', 'numeric', 'min:0'],
            'income_defaults.wood' => ['sometimes', 'numeric', 'min:0'],
            'income_defaults.ore' => ['sometimes', 'numeric', 'min:0'],
            'income_defaults.food' => ['sometimes', 'numeric', 'min:0'],
            'income_randomize_resources' => ['sometimes', 'boolean'],
            'income_resource_min' => ['sometimes', 'numeric', 'min:0'],
            'income_resource_max' => ['sometimes', 'numeric', 'min:0'],
            'income_randomize_cow' => ['sometimes', 'boolean'],
            'income_cow_min' => ['sometimes', 'numeric', 'min:0'],
            'income_cow_max' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $effective = array_replace_recursive($this->accounts->getNewAccountDefaults(), $data);
        if ((float) ($effective['income_resource_min'] ?? 0) > (float) ($effective['income_resource_max'] ?? 0)) {
            throw ValidationException::withMessages([
                'income_resource_min' => 'Resource income min cannot be greater than resource income max.',
            ]);
        }
        if ((float) ($effective['income_cow_min'] ?? 0) > (float) ($effective['income_cow_max'] ?? 0)) {
            throw ValidationException::withMessages([
                'income_cow_min' => 'Cow income min cannot be greater than cow income max.',
            ]);
        }

        $merged = $this->accounts->saveNewAccountDefaults($data);

        return response()->json(['message' => 'New account defaults saved', 'defaults' => $merged]);
    }

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

        DB::table('nation_terrain_stats')->insert(array_merge(
            ['nation_id' => $nationId],
            $this->accounts->buildTerrainStatsPayload([]),
            ['updated_at' => now()]
        ));

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
            if (isset($data['income'])) {
                $extra['income'] = array_merge($extra['income'] ?? [], $data['income']);
            }
            DB::table('nation_resources')->where('nation_id', $nationId)->update([
                'cow' => $data['resources']['cow'] ?? ($res->cow ?? 0),
                'wood' => $data['resources']['wood'] ?? ($res->wood ?? 0),
                'ore' => $data['resources']['ore'] ?? ($res->ore ?? 0),
                'food' => $data['resources']['food'] ?? ($res->food ?? 0),
                'extra_json' => json_encode($extra),
                'updated_at' => now(),
            ]);
        } elseif (isset($data['refined_resources']) || isset($data['currencies']) || isset($data['income'])) {
            $res = DB::table('nation_resources')->where('nation_id', $nationId)->first();
            $extra = json_decode($res->extra_json ?? '{}', true) ?: [];
            if (isset($data['refined_resources'])) {
                $extra['refined'] = array_merge($extra['refined'] ?? [], $data['refined_resources']);
            }
            if (isset($data['currencies'])) {
                $extra['currencies'] = array_merge($extra['currencies'] ?? [], $data['currencies']);
            }
            if (isset($data['income'])) {
                $extra['income'] = array_merge($extra['income'] ?? [], $data['income']);
            }
            DB::table('nation_resources')->where('nation_id', $nationId)->update([
                'extra_json' => json_encode($extra),
                'updated_at' => now(),
            ]);
        }

        if (isset($data['terrain_square_miles'])) {
            $terrain = DB::table('nation_terrain_stats')->where('nation_id', $nationId)->first();
            $sqMiles = array_merge(
                $terrain?->square_miles_json ? (json_decode($terrain->square_miles_json, true) ?: []) : [],
                $data['terrain_square_miles']
            );
            DB::table('nation_terrain_stats')->updateOrInsert(
                ['nation_id' => $nationId],
                array_merge(
                    $this->accounts->buildTerrainStatsPayload($sqMiles),
                    ['updated_at' => now()]
                )
            );
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

    public function unitCatalog()
    {
        $rows = DB::table('unit_catalog')
            ->select('id', 'code', 'display_name', 'class_name', 'unlocked_by_structure')
            ->orderBy('display_name')
            ->get();

        return response()->json($rows);
    }

    public function visibilityFields()
    {
        return response()->json([
            ['key' => 'leader_name', 'label' => 'Leader Name'],
            ['key' => 'alliance_name', 'label' => 'Alliance Name'],
            ['key' => 'about_text', 'label' => 'About Text'],
            ['key' => 'resources_base', 'label' => 'Base Resources'],
            ['key' => 'resources_refined', 'label' => 'Refined Resources'],
            ['key' => 'resources_currencies', 'label' => 'Currencies'],
            ['key' => 'terrain', 'label' => 'Terrain'],
            ['key' => 'units', 'label' => 'Units'],
            ['key' => 'buildings', 'label' => 'Buildings'],
        ]);
    }

    public function visibilityRules(Request $request)
    {
        $data = $request->validate([
            'viewer_user_id' => ['required', 'integer', 'exists:users,id'],
            'subject_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $this->ensureDistinctVisibilityPair((int) $data['viewer_user_id'], (int) $data['subject_user_id']);

        $rows = DB::table('player_visibility_rules')
            ->where('viewer_user_id', (int) $data['viewer_user_id'])
            ->where('subject_user_id', (int) $data['subject_user_id'])
            ->get();

        return response()->json($rows);
    }

    public function updateVisibilityRules(Request $request)
    {
        $data = $request->validate([
            'viewer_user_id' => ['required', 'integer', 'exists:users,id'],
            'subject_user_id' => ['required', 'integer', 'exists:users,id'],
            'rules' => ['required', 'array'],
            'rules.*.field_key' => ['required', 'string', 'max:80'],
            'rules.*.is_allowed' => ['required', 'boolean'],
        ]);

        $viewer = (int) $data['viewer_user_id'];
        $subject = (int) $data['subject_user_id'];

        $this->ensureDistinctVisibilityPair($viewer, $subject);

        DB::table('player_visibility_rules')
            ->where('viewer_user_id', $viewer)
            ->where('subject_user_id', $subject)
            ->delete();

        foreach ($data['rules'] as $rule) {
            DB::table('player_visibility_rules')->insert([
                'viewer_user_id' => $viewer,
                'subject_user_id' => $subject,
                'field_key' => (string) $rule['field_key'],
                'is_allowed' => (int) ((bool) $rule['is_allowed']),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Visibility rules saved']);
    }

    public function gameDocuments()
    {
        $rows = collect($this->gameDocumentDefinitions())
            ->map(function (array $document) {
                $path = $this->gameDocumentPath($document['filename']);
                $updatedAt = File::exists($path) ? date('c', File::lastModified($path)) : null;

                if ($updatedAt === null && Schema::hasTable('game_documents')) {
                    $updatedAt = DB::table('game_documents')
                        ->where('code', $document['code'])
                        ->value('updated_at');
                }

                return [
                    'code' => $document['code'],
                    'title' => $document['title'],
                    'updated_at' => $updatedAt,
                ];
            })
            ->sortBy('title')
            ->values();

        return response()->json($rows);
    }

    public function downloadAllGameDocuments()
    {
        $zip = new \ZipArchive();
        $tmpFile = tempnam(sys_get_temp_dir(), 'gamerules_') . '.zip';
        $zip->open($tmpFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($this->gameDocumentDefinitions() as $document) {
            $code = $document['code'];

            $content = null;
            if (Schema::hasTable('game_documents')) {
                $row = DB::table('game_documents')->where('code', $code)->first();
                $isPlaceholder = $row && str_contains((string) $row->content_text, 'Use Edit to replace this placeholder');
                if ($row && $row->updated_by_user_id !== null && !$isPlaceholder) {
                    $content = $row->content_text;
                }
            }
            if ($content === null) {
                $path = $this->gameDocumentPath($document['filename']);
                if (File::exists($path)) {
                    $content = File::get($path);
                }
            }

            if ($content !== null) {
                $zip->addFromString($document['filename'], $content);
            }
        }

        $zip->close();

        return response()->download($tmpFile, 'game-information.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function gameDocument(string $code)
    {
        $document = $this->findGameDocument($code);
        if ($document === null) {
            return response()->json(['message' => 'Game information document not found'], 404);
        }

        // DB takes priority if an admin has explicitly saved edits (updated_by_user_id is set).
        // Otherwise fall back to the baked-in resource file which is the factory default.
        $content = null;
        $updatedAt = null;

        if (Schema::hasTable('game_documents')) {
            $row = DB::table('game_documents')->where('code', $code)->first();
            $isPlaceholder = $row && str_contains((string) $row->content_text, 'Use Edit to replace this placeholder');
            if ($row && $row->updated_by_user_id !== null && !$isPlaceholder) {
                $content = $row->content_text;
                $updatedAt = $row->updated_at;
            }
        }

        if ($content === null) {
            $path = $this->gameDocumentPath($document['filename']);
            if (File::exists($path)) {
                $content = File::get($path);
                $updatedAt = date('c', File::lastModified($path));
            } elseif (Schema::hasTable('game_documents')) {
                // Last resort: use whatever is in DB even if it is the seeded placeholder.
                $content = DB::table('game_documents')->where('code', $code)->value('content_text');
            }
        }

        if ($content === null) {
            return response()->json(['message' => 'Game information document not found'], 404);
        }

        return response()->json([
            'code' => $document['code'],
            'title' => $document['title'],
            'content_text' => $content,
            'updated_at' => $updatedAt,
        ]);
    }

    public function updateGameDocument(Request $request, string $code)
    {
        $data = $request->validate([
            'content_text' => ['required', 'string'],
            'title' => ['sometimes', 'string', 'max:200'],
        ]);

        $document = $this->findGameDocument($code);
        if ($document === null) {
            return response()->json(['message' => 'Game information document not found'], 404);
        }

        // Persist edits to the DB so they survive container restarts.
        // The resource file is baked into the image and serves as the factory default;
        // writing to it inside a container would be lost on restart.
        if (Schema::hasTable('game_documents')) {
            DB::table('game_documents')->updateOrInsert(
                ['code' => $code],
                [
                    'title' => $data['title'] ?? $document['title'],
                    'content_text' => $data['content_text'],
                    'updated_by_user_id' => $request->user()->id,
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json(['message' => 'Game information document saved']);
    }

    public function shopItemTemplates()
    {
        $templates = [];

        $existingItems = DB::table('shop_items')
            ->select('display_name', 'description_text', 'effect_json')
            ->orderBy('display_name')
            ->get();
        foreach ($existingItems as $item) {
            $templates[] = [
                'name' => $item->display_name,
                'description_text' => (string) ($item->description_text ?? ''),
                'effect_json' => json_decode($item->effect_json ?? '{}', true) ?: new \stdClass(),
                'source' => 'existing_shop_item',
            ];
        }

        $units = DB::table('unit_catalog')->select('code', 'display_name', 'class_name')->orderBy('display_name')->get();
        foreach ($units as $unit) {
            $templates[] = [
                'name' => 'Recruit ' . $unit->display_name,
                'description_text' => 'Recruit one ' . $unit->display_name . ' (' . ($unit->class_name ?: 'unit') . ').',
                'effect_json' => [
                    'unit_code' => $unit->code,
                    'qty' => 1,
                ],
                'source' => 'unit_catalog',
            ];
        }

        $buildings = DB::table('building_catalog')->select('code', 'display_name')->orderBy('display_name')->get();
        foreach ($buildings as $building) {
            $templates[] = [
                'name' => $building->display_name . ' (L1)',
                'description_text' => 'Adds one level 1 ' . $building->display_name . ' structure.',
                'effect_json' => new \stdClass(),
                'source' => 'building_catalog',
            ];
        }

        return response()->json($templates);
    }

    public function createManagedAccount(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120', "regex:/^[A-Za-z0-9][A-Za-z0-9 _'\\-]*$/"],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'role' => ['sometimes', 'in:admin,player'],
            'force_password_reset' => ['sometimes', 'boolean'],
            'create_nation' => ['sometimes', 'boolean'],
        ], [
            'name.regex' => 'Display names may use letters, numbers, spaces, apostrophes, hyphens, and underscores only.',
            'email.unique' => 'That email address already belongs to an existing account.',
        ]);

        $trimmedName = trim((string) ($data['name'] ?? ''));
        if (preg_match('/\s{2,}/', $trimmedName)) {
            throw ValidationException::withMessages([
                'name' => 'Display names cannot contain repeated spaces.',
            ]);
        }

        $role = (string) ($data['role'] ?? 'player');
        $user = $this->accounts->createAccount([
            'name' => $trimmedName,
            'email' => trim((string) $data['email']),
            'password' => $data['password'],
            'role' => $role,
            'create_nation' => array_key_exists('create_nation', $data) ? (bool) $data['create_nation'] : $role === 'player',
            'force_password_reset' => (bool) ($data['force_password_reset'] ?? true),
        ]);

        return response()->json(['message' => 'Account created', 'user' => $user], 201);
    }

    public function users(Request $request)
    {
        $role = trim((string) $request->query('role', ''));

        $query = DB::table('users as u')
            ->leftJoin('nations as n', 'n.owner_user_id', '=', 'u.id')
            ->select('u.id', 'u.name', 'u.email', 'u.role', 'u.created_at', 'n.id as nation_id', 'n.name as nation_name')
            ->orderBy('u.role')
            ->orderBy('u.name');

        if ($role !== '') {
            $query->where('u.role', $role);
        }

        return response()->json($query->get());
    }

    public function deleteManagedAccount(Request $request, int $userId)
    {
        $data = $request->validate([
            'confirmation_name' => ['required', 'string'],
        ], [
            'confirmation_name.required' => 'Enter the exact username to confirm account removal.',
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->name !== $data['confirmation_name']) {
            throw ValidationException::withMessages([
                'confirmation_name' => 'The confirmation name does not match the account you are trying to delete.',
            ]);
        }

        $this->accounts->deleteAccount($user, false);

        return response()->json(['message' => 'Player account deleted permanently.']);
    }

    public function createChat(StoreChatRequest $request)
    {
        $response = app(ChatController::class)->store($request);
        $payload = $response->getData(true);
        $chatId = (int) ($payload['id'] ?? 0);
        if ($chatId > 0) {
            $chatType = DB::table('chats')->where('id', $chatId)->value('type');
            if ($chatType === 'global') {
                $this->accounts->syncGlobalChatMembershipsForAllUsers($chatId);
            }
        }

        return $response;
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
                ['archived_at' => null, 'deleted_at' => null]
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
            'description_text' => $data['description_text'] ?? $item->description_text,
            'cost_json' => array_key_exists('cost_json', $data) ? json_encode($data['cost_json']) : $item->cost_json,
            'maintenance_json' => array_key_exists('maintenance_json', $data) ? json_encode($data['maintenance_json']) : $item->maintenance_json,
            'yearly_effect_json' => array_key_exists('yearly_effect_json', $data) ? json_encode($data['yearly_effect_json']) : $item->yearly_effect_json,
            'effect_json' => array_key_exists('effect_json', $data) ? json_encode($data['effect_json']) : $item->effect_json,
            'is_active' => array_key_exists('is_active', $data) ? (int) $data['is_active'] : $item->is_active,
            'visibility_json' => array_key_exists('visibility_json', $data)
                ? ($data['visibility_json'] === null ? null : json_encode(array_values(array_map('intval', $data['visibility_json']))))
                : $item->visibility_json,
        ];

        DB::table('shop_items')->where('id', $itemId)->update($updated);

        $fresh = DB::table('shop_items as si')
            ->join('shop_categories as sc', 'si.category_id', '=', 'sc.id')
            ->where('si.id', $itemId)
            ->select('si.*', 'sc.code as category_code', 'sc.display_name as category_name')
            ->first();

        return response()->json(['message' => 'Shop item updated', 'item' => $fresh]);
    }

    public function createShopItem(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:shop_categories,id'],
            'code' => ['sometimes', 'nullable', 'string', 'max:64', 'unique:shop_items,code'],
            'display_name' => ['required', 'string', 'max:160'],
            'description_text' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'cost_json' => ['sometimes', 'array'],
            'maintenance_json' => ['sometimes', 'nullable', 'array'],
            'yearly_effect_json' => ['sometimes', 'nullable', 'array'],
            'effect_json' => ['sometimes', 'nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
            'visibility_json' => ['sometimes', 'nullable', 'array'],
            'visibility_json.*' => ['integer', 'exists:users,id'],
        ]);

        $categoryCode = DB::table('shop_categories')->where('id', $data['category_id'])->value('code') ?? 'item';
        $baseCode = $data['code']
            ? Str::slug((string) $data['code'], '_')
            : Str::slug($categoryCode . '_' . $data['display_name'], '_');
        $baseCode = trim($baseCode, '_');
        if ($baseCode === '') {
            $baseCode = 'item';
        }

        $generatedCode = $baseCode;
        $suffix = 2;
        while (DB::table('shop_items')->where('code', $generatedCode)->exists()) {
            $generatedCode = $baseCode . '_' . $suffix;
            $suffix++;
        }

        $itemId = DB::table('shop_items')->insertGetId([
            'category_id' => $data['category_id'],
            'code' => $generatedCode,
            'display_name' => $data['display_name'],
            'description_text' => $data['description_text'] ?? null,
            'cost_json' => json_encode($data['cost_json'] ?? new \stdClass()),
            'maintenance_json' => array_key_exists('maintenance_json', $data) ? json_encode($data['maintenance_json']) : null,
            'yearly_effect_json' => array_key_exists('yearly_effect_json', $data) ? json_encode($data['yearly_effect_json']) : null,
            'effect_json' => array_key_exists('effect_json', $data) ? json_encode($data['effect_json']) : null,
            'is_active' => (int) ($data['is_active'] ?? 1),
            'visibility_json' => array_key_exists('visibility_json', $data)
                ? ($data['visibility_json'] === null ? null : json_encode(array_values(array_map('intval', $data['visibility_json']))))
                : null,
        ]);

        $fresh = DB::table('shop_items as si')
            ->join('shop_categories as sc', 'si.category_id', '=', 'sc.id')
            ->where('si.id', $itemId)
            ->select('si.*', 'sc.code as category_code', 'sc.display_name as category_name')
            ->first();

        return response()->json(['message' => 'Shop item created', 'id' => $itemId, 'item' => $fresh], 201);
    }

    public function deleteShopItem(int $itemId)
    {
        DB::table('nation_assets')->where('shop_item_id', $itemId)->delete();
        DB::table('shop_items')->where('id', $itemId)->delete();
        return response()->json(['message' => 'Shop item deleted']);
    }

    public function notifications()
    {
        $query = DB::table('admin_notifications')->orderByDesc('created_at');

        $type = request()->query('type');
        if (is_string($type) && $type !== '') {
            $query->where('type', $type);
        }

        $nationId = request()->query('nation_id');
        if ($nationId !== null && $nationId !== '') {
            $query->whereRaw('JSON_EXTRACT(meta_json, "$.nation_id") = ?', [(int) $nationId]);
        }

        $userId = request()->query('user_id');
        if ($userId !== null && $userId !== '') {
            $query->where(function ($q) use ($userId) {
                $q->whereRaw('JSON_EXTRACT(meta_json, "$.actor_user_id") = ?', [(int) $userId])
                  ->orWhereRaw('JSON_EXTRACT(meta_json, "$.target_user_id") = ?', [(int) $userId]);
            });
        }

        $rows = $query->get();
        DB::table('admin_notifications')->where('is_read', 0)->update(['is_read' => 1, 'read_at' => now()]);
        return response()->json($rows);
    }

    public function deleteNotification(int $notificationId)
    {
        DB::table('admin_notifications')->where('id', $notificationId)->delete();
        return response()->json(['message' => 'Notification deleted']);
    }

    private function ensureDistinctVisibilityPair(int $viewerUserId, int $subjectUserId): void
    {
        if ($viewerUserId === $subjectUserId) {
            throw ValidationException::withMessages([
                'subject_user_id' => ['Viewer and subject must be different players.'],
            ]);
        }
    }

    private function findGameDocument(string $code): ?array
    {
        foreach ($this->gameDocumentDefinitions() as $document) {
            if ($document['code'] === $code) {
                return $document;
            }
        }

        return null;
    }

    private function gameDocumentRoot(): string
    {
        // Files live inside backend/laravel/resources/game-information/ so they are
        // included in the Docker build context (context: ./backend) and copied into
        // the image at build time via `COPY laravel/ ./`.
        return resource_path('game-information');
    }

    private function gameDocumentPath(string $filename): string
    {
        return $this->gameDocumentRoot() . DIRECTORY_SEPARATOR . $filename;
    }

    private function gameDocumentDefinitions(): array
    {
        return [
            ['code' => 'reptonians', 'title' => 'Reptonians', 'filename' => 'reptonians.md'],
            ['code' => 'elves', 'title' => 'Elves', 'filename' => 'elves.md'],
            ['code' => 'kilonites', 'title' => 'Kilonites', 'filename' => 'kilonites.md'],
            ['code' => 'goblins', 'title' => 'Goblins', 'filename' => 'goblins.md'],
            ['code' => 'testudians', 'title' => 'Testudians', 'filename' => 'testudians.md'],
            ['code' => 'zeptins', 'title' => 'Zeptins', 'filename' => 'zeptins.md'],
            ['code' => 'centaurs', 'title' => 'Centaurs', 'filename' => 'centaurs.md'],
            ['code' => 'dwarves', 'title' => 'Dwarves', 'filename' => 'dwarves.md'],
            ['code' => 'humans', 'title' => 'Humans', 'filename' => 'humans.md'],
            ['code' => 'structures_and_terrain', 'title' => 'Structures and Terrain', 'filename' => 'structures_and_terrain.md'],
            ['code' => 'war_rules_and_such', 'title' => 'War Rules and Such', 'filename' => 'war_rules_and_such.md'],
            ['code' => 'rules_and_resources', 'title' => 'Rules and Resources', 'filename' => 'rules_and_resources.md'],
        ];
    }

    public function timeTracker()
    {
        $processed = $this->syncTimeProgress();
        $state = $this->getGameTimeState();
        $hoursPerYear = max(0.01, (float) $state->seconds_per_year / 3600);
        $elapsedHours = $state->auto_increment_enabled
            ? max(0, (now()->timestamp - strtotime($state->year_started_at ?? $state->started_at)) / 3600)
            : (float) $state->elapsed_hours_in_year;
        $currentGameYear = ((int) $state->processed_years + 1) + (int) ($state->year_label_offset ?? 0);

        return response()->json([
            'started_at' => $state->started_at,
            'year_started_at' => $state->year_started_at,
            'seconds_per_year' => (int) $state->seconds_per_year,
            'hours_per_year' => $hoursPerYear,
            'elapsed_hours_in_year' => round($elapsedHours, 2),
            'auto_increment_enabled' => (bool) $state->auto_increment_enabled,
            'year_label_offset' => (int) ($state->year_label_offset ?? 0),
            'processed_years' => (int) $state->processed_years,
            'current_game_year' => $currentGameYear,
            'processed_now' => $processed,
        ]);
    }

    public function updateTimeTracker(Request $request)
    {
        $data = $request->validate([
            'seconds_per_year' => ['sometimes', 'numeric', 'min:1'],
            'hours_per_year' => ['sometimes', 'numeric', 'min:0.01'],
            'elapsed_hours_in_year' => ['sometimes', 'numeric', 'min:0'],
            'auto_increment_enabled' => ['sometimes', 'boolean'],
            'current_game_year' => ['sometimes', 'integer', 'min:1'],
            'apply_year_change_effects' => ['sometimes', 'boolean'],
        ]);

        $this->syncTimeProgress();
        $state = $this->getGameTimeState();

        $secondsPerYear = (int) $state->seconds_per_year;
        if (array_key_exists('hours_per_year', $data)) {
            $secondsPerYear = max(1, (int) round(((float) $data['hours_per_year']) * 3600));
        }
        if (array_key_exists('seconds_per_year', $data)) {
            $secondsPerYear = max(1, (int) round((float) $data['seconds_per_year']));
        }

        $autoIncrementEnabled = array_key_exists('auto_increment_enabled', $data)
            ? (int) ((bool) $data['auto_increment_enabled'])
            : (int) $state->auto_increment_enabled;

        $elapsedHours = (float) $state->elapsed_hours_in_year;
        if ($state->auto_increment_enabled) {
            $elapsedHours = max(0, (now()->timestamp - strtotime($state->year_started_at ?? $state->started_at)) / 3600);
        }
        if (array_key_exists('elapsed_hours_in_year', $data)) {
            $elapsedHours = (float) $data['elapsed_hours_in_year'];
        }
        $hoursPerYear = max(0.01, $secondsPerYear / 3600);
        $elapsedHours = min($elapsedHours, $hoursPerYear);

        $yearOffset = (int) ($state->year_label_offset ?? 0);
        $currentDisplayYear = ((int) $state->processed_years + 1) + $yearOffset;
        if (array_key_exists('current_game_year', $data)) {
            $targetYear = (int) $data['current_game_year'];
            $delta = $targetYear - $currentDisplayYear;
            if ($delta > 0 && (bool) ($data['apply_year_change_effects'] ?? false)) {
                $this->processYears($delta);
                $state = $this->getGameTimeState();
                $yearOffset = (int) ($state->year_label_offset ?? 0);
            } else {
                $yearOffset += $delta;
            }
        }

        $yearStartedAt = now()->subSeconds((int) round($elapsedHours * 3600));

        DB::table('game_time')->where('id', 1)->update([
            'seconds_per_year' => $secondsPerYear,
            'elapsed_hours_in_year' => $elapsedHours,
            'auto_increment_enabled' => $autoIncrementEnabled,
            'year_started_at' => $yearStartedAt,
            'year_label_offset' => $yearOffset,
            'updated_at' => now(),
        ]);

        return $this->timeTracker();
    }

    public function advanceYear(Request $request)
    {
        $data = $request->validate([
            'apply_effects' => ['sometimes', 'boolean'],
        ]);

        $applyEffects = (bool) ($data['apply_effects'] ?? true);
        if ($applyEffects) {
            $this->processYears(1);
        } else {
            $state = $this->getGameTimeState();
            DB::table('game_time')->where('id', 1)->update([
                'year_label_offset' => (int) ($state->year_label_offset ?? 0) + 1,
                'elapsed_hours_in_year' => 0,
                'year_started_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Year advanced', 'apply_effects' => $applyEffects]);
    }

    private function getGameTimeState(): object
    {
        $state = DB::table('game_time')->where('id', 1)->first();
        if ($state) {
            return $state;
        }

        DB::table('game_time')->insert([
            'id' => 1,
            'started_at' => now(),
            'year_started_at' => now(),
            'seconds_per_year' => 48 * 3600,
            'processed_years' => 0,
            'elapsed_hours_in_year' => 0,
            'auto_increment_enabled' => 1,
            'year_label_offset' => 0,
            'updated_at' => now(),
        ]);

        return DB::table('game_time')->where('id', 1)->first();
    }

    private function syncTimeProgress(): int
    {
        $state = $this->getGameTimeState();
        if (!(int) $state->auto_increment_enabled) {
            return 0;
        }

        $hoursPerYear = max(0.01, (float) $state->seconds_per_year / 3600);
        $elapsedHours = max(0, (now()->timestamp - strtotime($state->year_started_at ?? $state->started_at)) / 3600);
        $pendingYears = (int) floor($elapsedHours / $hoursPerYear);
        $remainderHours = $elapsedHours - ($pendingYears * $hoursPerYear);

        $updatedYearStartedAt = now()->subSeconds((int) round($remainderHours * 3600));
        DB::table('game_time')->where('id', 1)->update([
            'elapsed_hours_in_year' => $remainderHours,
            'year_started_at' => $updatedYearStartedAt,
            'updated_at' => now(),
        ]);

        if ($pendingYears > 0) {
            $this->processYears($pendingYears);
        }

        return $pendingYears;
    }

    private function processYears(int $yearsToProcess): void
    {
        if ($yearsToProcess <= 0) {
            return;
        }

        $state = $this->getGameTimeState();

        for ($yearOffset = 1; $yearOffset <= $yearsToProcess; $yearOffset++) {
            $yearNumber = (int) $state->processed_years + $yearOffset;
            $nations = DB::table('nations')->where('is_placeholder', 0)->get();
            foreach ($nations as $nation) {
                $resourceRow = DB::table('nation_resources')->where('nation_id', $nation->id)->first();
                if (!$resourceRow) {
                    continue;
                }
                $extra = json_decode($resourceRow->extra_json ?? '{}', true) ?: [];
                $income = $extra['income'] ?? ['cow' => 30, 'wood' => 3, 'ore' => 3, 'food' => 3];

                $delta = [
                    'base' => [
                        'cow' => (float) ($income['cow'] ?? 30),
                        'wood' => (float) ($income['wood'] ?? 3),
                        'ore' => (float) ($income['ore'] ?? 3),
                        'food' => (float) ($income['food'] ?? 3),
                    ],
                    'refined' => [],
                    'currencies' => [],
                ];

                $assets = DB::table('nation_assets as na')
                    ->join('shop_items as si', 'na.shop_item_id', '=', 'si.id')
                    ->where('na.nation_id', $nation->id)
                    ->select('na.qty', 'si.display_name', 'si.maintenance_json', 'si.yearly_effect_json')
                    ->get();

                $maintenanceDetails = [];
                foreach ($assets as $asset) {
                    $yearlyEffect = json_decode($asset->yearly_effect_json ?? 'null', true) ?: [];
                    $maintenance = json_decode($asset->maintenance_json ?? 'null', true) ?: [];

                    foreach ($yearlyEffect as $key => $value) {
                        $this->applyDeltaValue($delta, $key, (float) $value * (int) $asset->qty);
                    }
                    foreach ($maintenance as $key => $value) {
                        $amount = (float) $value * (int) $asset->qty;
                        $this->applyDeltaValue($delta, $key, -$amount);
                        $maintenanceDetails[] = $asset->display_name . ': ' . $key . ' ' . $amount;
                    }
                }

                $updated = $this->applyNationDelta($nation->id, $delta);
                $negativeKeys = [];
                foreach (['cow', 'wood', 'ore', 'food'] as $key) {
                    if (($updated['base'][$key] ?? 0) < 0) {
                        $negativeKeys[] = $key . '=' . $updated['base'][$key];
                    }
                }
                foreach (($updated['currencies'] ?? []) as $key => $value) {
                    if ($value < 0) {
                        $negativeKeys[] = $key . '=' . $value;
                    }
                }
                foreach (($updated['refined'] ?? []) as $key => $value) {
                    if ($value < 0) {
                        $negativeKeys[] = $key . '=' . $value;
                    }
                }

                if ($maintenanceDetails || $negativeKeys) {
                    $this->createNotification(
                        'yearly_maintenance',
                        'Year ' . $yearNumber . ' processing for ' . $nation->name,
                        'System processed yearly income and maintenance for nation "' . $nation->name . '".'
                        . ' Income applied: ' . json_encode($delta['base'])
                        . '. Maintenance details: ' . ($maintenanceDetails ? implode('; ', $maintenanceDetails) : 'none')
                        . '. Negative balances: ' . ($negativeKeys ? implode(', ', $negativeKeys) : 'none') . '.',
                        ['nation_id' => $nation->id, 'year' => $yearNumber, 'negative_balances' => $negativeKeys]
                    );
                }
            }

            $authorId = DB::table('users')->where('role', 'admin')->value('id');
            if ($authorId) {
                DB::table('announcements')->insert([
                    'author_user_id' => $authorId,
                    'body' => 'Year ' . $yearNumber . ' has been processed. Nation income and maintenance were applied.',
                    'created_at' => now(),
                ]);
            }
        }

        DB::table('game_time')->where('id', 1)->update([
            'processed_years' => (int) $state->processed_years + $yearsToProcess,
            'elapsed_hours_in_year' => 0,
            'year_started_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function applyNationDelta(int $nationId, array $delta): array
    {
        $resourceRow = DB::table('nation_resources')->where('nation_id', $nationId)->first();
        $extra = json_decode($resourceRow->extra_json ?? '{}', true) ?: [];
        $refined = $extra['refined'] ?? [];
        $currencies = $extra['currencies'] ?? [];

        foreach (($delta['refined'] ?? []) as $key => $value) {
            $refined[$key] = ($refined[$key] ?? 0) + (float) $value;
        }
        foreach (($delta['currencies'] ?? []) as $key => $value) {
            $currencies[$key] = ($currencies[$key] ?? 0) + (float) $value;
        }

        $updatedBase = [
            'cow' => (float) $resourceRow->cow + (float) (($delta['base']['cow'] ?? 0)),
            'wood' => (float) $resourceRow->wood + (float) (($delta['base']['wood'] ?? 0)),
            'ore' => (float) $resourceRow->ore + (float) (($delta['base']['ore'] ?? 0)),
            'food' => (float) $resourceRow->food + (float) (($delta['base']['food'] ?? 0)),
        ];

        $extra['refined'] = $refined;
        $extra['currencies'] = $currencies;

        DB::table('nation_resources')->where('nation_id', $nationId)->update([
            'cow' => $updatedBase['cow'],
            'wood' => $updatedBase['wood'],
            'ore' => $updatedBase['ore'],
            'food' => $updatedBase['food'],
            'extra_json' => json_encode($extra),
            'updated_at' => now(),
        ]);

        return ['base' => $updatedBase, 'refined' => $refined, 'currencies' => $currencies];
    }

    private function applyDeltaValue(array &$delta, string $key, float $value): void
    {
        if (in_array($key, ['cow', 'wood', 'ore', 'food'], true)) {
            $delta['base'][$key] = ($delta['base'][$key] ?? 0) + $value;
        } elseif (str_starts_with($key, 'ref_')) {
            $rKey = substr($key, 4);
            $delta['refined'][$rKey] = ($delta['refined'][$rKey] ?? 0) + $value;
        } elseif (str_starts_with($key, 'cur_')) {
            $cKey = substr($key, 4);
            $delta['currencies'][$cKey] = ($delta['currencies'][$cKey] ?? 0) + $value;
        }
    }

    private function createNotification(string $type, string $title, string $body, array $meta = []): void
    {
        DB::table('admin_notifications')->insert([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'meta_json' => json_encode($meta),
            'is_read' => 0,
            'created_at' => now(),
        ]);
    }

}
