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
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            'starting_resources' => ['sometimes', 'array'],
            'starting_resources.*.type' => ['required_with:starting_resources', 'in:base,advanced'],
            'starting_resources.*.name' => ['required_with:starting_resources', 'string', 'max:120'],
            'starting_resources.*.amount' => ['required_with:starting_resources', 'numeric'],
            'income_resources' => ['sometimes', 'array'],
            'income_resources.*.type' => ['required_with:income_resources', 'in:base,advanced'],
            'income_resources.*.name' => ['required_with:income_resources', 'string', 'max:120'],
            'income_resources.*.amount' => ['required_with:income_resources', 'numeric'],
        ]);

        if (array_key_exists('starting_resources', $data)) {
            $data['starting_resources'] = $this->normalizeDefaultResourceRows($data['starting_resources'], 'starting_resources');
        }
        if (array_key_exists('income_resources', $data)) {
            $data['income_resources'] = $this->normalizeDefaultResourceRows($data['income_resources'], 'income_resources');
        }

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

    private function normalizeDefaultResourceRows(array $rows, string $field): array
    {
        $seen = [];
        $normalized = [];

        foreach ($rows as $index => $row) {
            $type = ($row['type'] ?? '') === 'advanced' ? 'advanced' : 'base';
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    $field . '.' . $index . '.name' => 'Resource name is required.',
                ]);
            }

            $key = $type . ':' . $name;
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    $field => 'Duplicate resources are not allowed. Duplicate: ' . $key,
                ]);
            }
            $seen[$key] = true;

            $normalized[] = [
                'type' => $type,
                'name' => $name,
                'amount' => (float) ($row['amount'] ?? 0),
            ];
        }

        return $normalized;
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

        if (isset($data['resources']) || isset($data['currencies']) || isset($data['income'])) {
            $res = DB::table('nation_resources')->where('nation_id', $nationId)->first();
            $extra = json_decode($res->extra_json ?? '{}', true) ?: [];

            $resourcePayload = is_array($data['resources'] ?? null) ? $data['resources'] : [];
            $basePayload = is_array($resourcePayload['base'] ?? null) ? $resourcePayload['base'] : $resourcePayload;
            $advancedPayload = is_array($resourcePayload['advanced'] ?? null) ? $resourcePayload['advanced'] : [];

            $extraBase = is_array($extra['base'] ?? null) ? $extra['base'] : [];
            $extraAdvanced = is_array($extra['advanced'] ?? null) ? $extra['advanced'] : [];

            if (!empty($basePayload)) {
                foreach ($basePayload as $key => $value) {
                    if (in_array($key, ['cow', 'wood', 'ore', 'food'], true)) {
                        continue;
                    }
                    $extraBase[(string) $key] = (float) $value;
                }
            }

            if (!empty($advancedPayload)) {
                foreach ($advancedPayload as $key => $value) {
                    $extraAdvanced[(string) $key] = (float) $value;
                }
            }

            if (isset($data['currencies']) && is_array($data['currencies'])) {
                $currencies = is_array($extra['currencies'] ?? null) ? $extra['currencies'] : [];
                foreach ($data['currencies'] as $key => $value) {
                    $currencies[(string) $key] = (float) $value;
                }
                $extra['currencies'] = $currencies;
            }
            if (isset($data['income']) && is_array($data['income'])) {
                $extra['income'] = $this->normalizeIncomeMapInput($data['income']);
            }

            $extra['base'] = $extraBase;
            $extra['advanced'] = $extraAdvanced;

            DB::table('nation_resources')->where('nation_id', $nationId)->update([
                'cow' => array_key_exists('cow', $basePayload) ? (float) $basePayload['cow'] : ($res->cow ?? 0),
                'wood' => array_key_exists('wood', $basePayload) ? (float) $basePayload['wood'] : ($res->wood ?? 0),
                'ore' => array_key_exists('ore', $basePayload) ? (float) $basePayload['ore'] : ($res->ore ?? 0),
                'food' => array_key_exists('food', $basePayload) ? (float) $basePayload['food'] : ($res->food ?? 0),
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

    public function removeUnitFromNation(Request $request, int $nationId, int $nationUnitId)
    {
        $data = $request->validate([
            'qty' => ['sometimes', 'integer', 'min:1'],
        ]);

        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        $unitRow = DB::table('nation_units')
            ->where('id', $nationUnitId)
            ->where('nation_id', $nationId)
            ->first();
        if (!$unitRow) {
            return response()->json(['message' => 'Nation unit not found'], 404);
        }

        $removeQty = (int) ($data['qty'] ?? 1);
        if ($removeQty >= (int) $unitRow->qty) {
            DB::table('nation_units')->where('id', $nationUnitId)->delete();
        } else {
            DB::table('nation_units')->where('id', $nationUnitId)->update([
                'qty' => max(0, (int) $unitRow->qty - $removeQty),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Unit removed']);
    }

    public function addBuildingToNation(Request $request, int $nationId)
    {
        $data = $request->validate([
            'building_catalog_id' => ['required', 'integer', 'exists:building_catalog,id'],
            'level' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:built,constructing,upgrading'],
            'qty' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ]);

        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        $qty = (int) ($data['qty'] ?? 1);
        $payload = [];
        for ($i = 0; $i < $qty; $i++) {
            $payload[] = [
                'nation_id' => $nationId,
                'building_catalog_id' => (int) $data['building_catalog_id'],
                'level' => (int) ($data['level'] ?? 1),
                'status' => (string) ($data['status'] ?? 'built'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('nation_buildings')->insert($payload);

        return response()->json(['message' => 'Building added']);
    }

    public function removeBuildingFromNation(int $nationId, int $nationBuildingId)
    {
        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        $deleted = DB::table('nation_buildings')
            ->where('id', $nationBuildingId)
            ->where('nation_id', $nationId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Nation building not found'], 404);
        }

        return response()->json(['message' => 'Building removed']);
    }

    public function unitCatalog()
    {
        $rows = DB::table('unit_catalog')
            ->select('id', 'code', 'display_name', 'class_name', 'unlocked_by_structure')
            ->orderBy('display_name')
            ->get();

        return response()->json($rows);
    }

    public function buildingCatalog()
    {
        $rows = DB::table('building_catalog')
            ->select('id', 'code', 'display_name', 'max_level')
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
            ['key' => 'resources_advanced', 'label' => 'Advanced Resources'],
            ['key' => 'resources_currencies', 'label' => 'Currencies'],
            ['key' => 'terrain', 'label' => 'Terrain'],
            ['key' => 'army_rating', 'label' => 'Army Rating'],
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

    public function gameDocuments(Request $request)
    {
        $viewer = $request->user();
        $isAdmin = $viewer && $viewer->role === 'admin';

        $dbRows = collect();
        if (Schema::hasTable('game_documents')) {
            $dbRows = DB::table('game_documents')
                ->select('code', 'title', 'updated_at')
                ->get()
                ->keyBy('code');
        }

        $builtInCodes = collect($this->gameDocumentDefinitions())
            ->pluck('code')
            ->values();

        $rows = collect($this->gameDocumentDefinitions())
            ->map(function (array $document) use ($dbRows) {
                $path = $this->gameDocumentPath($document['filename']);
                $updatedAt = File::exists($path) ? date('c', File::lastModified($path)) : null;

                $dbRow = $dbRows->get($document['code']);
                if ($updatedAt === null && $dbRow) {
                    $updatedAt = $dbRow->updated_at;
                }

                return [
                    'code' => $document['code'],
                    'title' => ($dbRow && is_string($dbRow->title) && trim($dbRow->title) !== '') ? $dbRow->title : $document['title'],
                    'updated_at' => $updatedAt,
                ];
            });

        if ($dbRows->isNotEmpty()) {
            $customRows = $dbRows
                ->filter(fn ($row, $code) => !$builtInCodes->contains((string) $code))
                ->map(function ($row, $code) {
                    $fallbackTitle = Str::headline(str_replace('_', ' ', (string) $code));
                    return [
                        'code' => (string) $code,
                        'title' => (is_string($row->title) && trim($row->title) !== '') ? $row->title : $fallbackTitle,
                        'updated_at' => $row->updated_at,
                    ];
                })
                ->values();

            $rows = $rows->merge($customRows);
        }

        // Keep DB defaults explicit for admin-managed documents when visibility storage exists.
        if ($isAdmin && Schema::hasTable('game_document_visibility')) {
            $allCodes = $rows->pluck('code')->filter(fn ($code) => is_string($code) && trim($code) !== '')->values()->all();
            $this->seedMissingGameDocumentVisibilityDefaults($allCodes);
        }

        if (!$isAdmin) {
            $viewerUserId = (int) ($viewer->id ?? 0);
            $viewerRole = (string) ($viewer->role ?? '');
            $rows = $rows
                ->filter(function (array $row) use ($viewerUserId, $viewerRole) {
                    return $this->isDocumentVisibleToViewer((string) ($row['code'] ?? ''), $viewerUserId, $viewerRole);
                })
                ->values();
        }

        $rows = $rows
            ->sortBy('title')
            ->values();

        return response()->json($rows);
    }

    public function createGameDocument(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content_text' => ['nullable', 'string'],
            'code' => ['sometimes', 'nullable', 'string', 'max:80'],
        ]);

        if (!Schema::hasTable('game_documents')) {
            return response()->json(['message' => 'Game document storage is not available'], 500);
        }

        $rawCode = trim((string) ($data['code'] ?? ''));
        $codeSource = $rawCode !== '' ? $rawCode : (string) $data['title'];
        $code = Str::slug($codeSource, '_');
        if ($code === '' || strlen($code) > 80) {
            throw ValidationException::withMessages([
                'code' => ['Please provide a valid document code (letters, numbers, spaces, dashes, underscores).'],
            ]);
        }

        if ($this->findGameDocument($code) !== null) {
            throw ValidationException::withMessages([
                'code' => ['A built-in game document already uses this code.'],
            ]);
        }

        $exists = DB::table('game_documents')->where('code', $code)->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'code' => ['A game document with this code already exists.'],
            ]);
        }

        DB::table('game_documents')->insert([
            'code' => $code,
            'title' => trim((string) $data['title']),
            'content_text' => (string) ($data['content_text'] ?? ''),
            'updated_by_user_id' => $request->user()->id,
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('game_document_visibility')) {
            DB::table('game_document_visibility')->updateOrInsert(
                ['document_code' => $code],
                [
                    'visibility_type' => 'admin',
                    'role_name' => null,
                    'player_ids' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json([
            'message' => 'Game information document created',
            'code' => $code,
            'title' => trim((string) $data['title']),
        ], 201);
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

        if (Schema::hasTable('game_documents')) {
            $builtInCodes = collect($this->gameDocumentDefinitions())->pluck('code')->all();
            $customRows = DB::table('game_documents')
                ->select('code', 'content_text')
                ->whereNotIn('code', $builtInCodes)
                ->get();

            foreach ($customRows as $row) {
                $safeBase = preg_replace('/[^a-z0-9_\-]+/i', '_', (string) $row->code);
                $safeBase = trim((string) $safeBase, '_-');
                if ($safeBase === '') {
                    $safeBase = 'document_' . Str::random(6);
                }
                $zip->addFromString($safeBase . '.md', (string) ($row->content_text ?? ''));
            }
        }

        $zip->close();

        return response()->download($tmpFile, 'game-information.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function gameDocument(Request $request, string $code)
    {
        $viewer = $request->user();
        $isAdmin = $viewer && $viewer->role === 'admin';
        if (!$isAdmin) {
            $viewerUserId = (int) ($viewer->id ?? 0);
            $viewerRole = (string) ($viewer->role ?? '');
            if (!$this->isDocumentVisibleToViewer($code, $viewerUserId, $viewerRole)) {
                return response()->json(['message' => 'Game information document not found'], 404);
            }
        }

        $document = $this->findGameDocument($code);
        $dbRow = null;
        if (Schema::hasTable('game_documents')) {
            $dbRow = DB::table('game_documents')->where('code', $code)->first();
        }
        if ($document === null && !$dbRow) {
            return response()->json(['message' => 'Game information document not found'], 404);
        }

        if ($document === null && $dbRow) {
            $resolvedTitle = (is_string($dbRow->title) && trim($dbRow->title) !== '')
                ? $dbRow->title
                : Str::headline(str_replace('_', ' ', $code));

            return response()->json([
                'code' => (string) $code,
                'title' => $resolvedTitle,
                'content_text' => (string) ($dbRow->content_text ?? ''),
                'updated_at' => $dbRow->updated_at,
            ]);
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

        $resolvedTitle = $document['title'];
        if (Schema::hasTable('game_documents')) {
            $dbTitle = DB::table('game_documents')->where('code', $code)->value('title');
            if (is_string($dbTitle) && trim($dbTitle) !== '') {
                $resolvedTitle = $dbTitle;
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
            'title' => $resolvedTitle,
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
        $dbExisting = Schema::hasTable('game_documents')
            ? DB::table('game_documents')->where('code', $code)->first()
            : null;
        if ($document === null && !$dbExisting) {
            return response()->json(['message' => 'Game information document not found'], 404);
        }

        $defaultTitle = $document['title']
            ?? ((is_string($dbExisting?->title) && trim((string) $dbExisting->title) !== '')
                ? $dbExisting->title
                : Str::headline(str_replace('_', ' ', $code)));

        // Persist edits to the DB so they survive container restarts.
        // The resource file is baked into the image and serves as the factory default;
        // writing to it inside a container would be lost on restart.
        if (Schema::hasTable('game_documents')) {
            DB::table('game_documents')->updateOrInsert(
                ['code' => $code],
                [
                    'title' => $data['title'] ?? $defaultTitle,
                    'content_text' => $data['content_text'],
                    'updated_by_user_id' => $request->user()->id,
                    'updated_at' => now(),
                ]
            );
        }

        return response()->json(['message' => 'Game information document saved']);
    }

    public function getGameDocumentVisibility(Request $request, string $code)
    {
        try {
            $this->seedMissingGameDocumentVisibilityDefaults([$code]);
        } catch (\Throwable $e) {
            return response()->json([
                'document_code' => $code,
                'visibility_type' => 'admin',
                'role_name' => null,
                'player_ids' => [],
            ]);
        }

        $row = $this->readGameDocumentVisibilityRecord($code);

        if (!$row) {
            return response()->json([
                'document_code' => $code,
                'visibility_type' => 'admin',
                'role_name' => null,
                'player_ids' => [],
            ]);
        }

        return response()->json([
            'document_code' => (string) ($row['document_code'] ?? $code),
            'visibility_type' => (string) ($row['visibility_type'] ?? 'admin'),
            'role_name' => $row['role_name'] ?? null,
            'player_ids' => is_array($row['player_ids'] ?? null) ? $row['player_ids'] : [],
        ]);
    }

    public function updateGameDocumentVisibility(Request $request, string $code)
    {
        $data = $request->validate([
            'visibility_type' => ['required', 'in:admin,role,all,custom'],
            'role_name' => ['nullable', 'string', 'max:80'],
            'player_ids' => ['nullable', 'array'],
            'player_ids.*' => ['integer', 'exists:users,id'],
        ]);

        try {
            $saved = $this->writeGameDocumentVisibilityRecord($code, [
                'visibility_type' => (string) $data['visibility_type'],
                'role_name' => $data['visibility_type'] === 'role' ? ($data['role_name'] ?? null) : null,
                'player_ids' => $data['visibility_type'] === 'custom'
                    ? array_values(array_unique(array_map('intval', $data['player_ids'] ?? [])))
                    : [],
            ]);

            if (!$saved) {
                return response()->json([
                    'message' => 'Document visibility storage is not available. Check database or storage permissions.',
                ], 500);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Document visibility storage is currently unavailable.',
            ], 500);
        }

        return response()->json(['message' => 'Visibility updated']);
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

    public function combatSnapshot(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $nation = DB::table('nations')
            ->where('owner_user_id', (int) $data['user_id'])
            ->first();

        if (!$nation) {
            return response()->json(['message' => 'Player has no nation assigned'], 404);
        }

        return response()->json($this->buildCombatSnapshotForNation((int) $nation->id));
    }

    public function combatOrders(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $rows = DB::table('admin_notifications')
            ->where('type', 'combat_order')
            ->whereRaw('CAST(JSON_UNQUOTE(JSON_EXTRACT(meta_json, "$.actor_user_id")) AS UNSIGNED) = ?', [(int) $data['user_id']])
            ->orderByDesc('created_at')
            ->limit(300)
            ->get();

        return response()->json($rows);
    }

    public function combatRatingConfig()
    {
        return response()->json($this->loadCombatRatingConfigRaw());
    }

    public function updateCombatRatingConfig(Request $request)
    {
        $data = $request->validate([
            'atk' => ['required', 'numeric', 'min:0', 'max:100'],
            'def' => ['required', 'numeric', 'min:0', 'max:100'],
            'dmg' => ['required', 'numeric', 'min:0', 'max:100'],
            'hp' => ['required', 'numeric', 'min:0', 'max:100'],
            'mvt' => ['required', 'numeric', 'min:0', 'max:100'],
            'rng' => ['required', 'numeric', 'min:0', 'max:100'],
            'act' => ['required', 'numeric', 'min:0', 'max:100'],
            'divisor' => ['required', 'numeric', 'min:0.01', 'max:1000'],
        ]);

        $payload = [
            'atk' => (float) $data['atk'],
            'def' => (float) $data['def'],
            'dmg' => (float) $data['dmg'],
            'hp' => (float) $data['hp'],
            'mvt' => (float) $data['mvt'],
            'rng' => (float) $data['rng'],
            'act' => (float) $data['act'],
            'divisor' => (float) $data['divisor'],
            'updated_at' => now()->toIso8601String(),
            'updated_by_user_id' => (int) $request->user()->id,
        ];

        if (!$this->saveCombatRatingConfigRaw($payload)) {
            return response()->json(['message' => 'Combat rating config storage is unavailable.'], 500);
        }

        return response()->json(['message' => 'Combat rating config saved.']);
    }

    public function updateCombatOrderStatus(Request $request, int $notificationId)
    {
        $data = $request->validate([
            'order_status' => ['required', 'in:pending,approved,denied'],
            'review_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $row = DB::table('admin_notifications')
            ->where('id', $notificationId)
            ->where('type', 'combat_order')
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Combat order not found'], 404);
        }

        DB::table('admin_notifications')->where('id', $notificationId)->update([
            'order_status' => (string) $data['order_status'],
            'review_note' => array_key_exists('review_note', $data) ? (string) ($data['review_note'] ?? '') : null,
            'reviewed_by_user_id' => (int) $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['message' => 'Combat order status updated']);
    }

    public function updateCombatUnitStats(Request $request, int $nationUnitId)
    {
        $data = $request->validate([
            'stats_override_json' => ['sometimes', 'array'],
            'custom_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'instance_index' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            'class_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'nullable', 'string', 'max:120'],
            'race' => ['sometimes', 'nullable', 'string', 'max:120'],
            'terrain' => ['sometimes', 'nullable', 'string', 'max:120'],
            'admin_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'rating' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $unit = DB::table('nation_units')->where('id', $nationUnitId)->first();
        if (!$unit) {
            return response()->json(['message' => 'Nation unit not found'], 404);
        }

        $existingOverrides = json_decode((string) ($unit->stats_override_json ?? '{}'), true);
        $normalized = is_array($existingOverrides) ? $existingOverrides : [];
        $instanceIndex = (int) ($data['instance_index'] ?? 0);
        $useInstance = $instanceIndex > 0;

        $instances = [];
        if (is_array($normalized['_instances'] ?? null)) {
            $instances = $normalized['_instances'];
        }

        $instanceKey = (string) $instanceIndex;
        $instanceOverride = [];
        if ($useInstance && is_array($instances[$instanceKey] ?? null)) {
            $instanceOverride = $instances[$instanceKey];
        }

        foreach (($data['stats_override_json'] ?? []) as $key => $value) {
            $statKey = trim((string) $key);
            if ($statKey === '' || mb_strlen($statKey) > 40) {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                if ($useInstance) {
                    $instanceOverride[$statKey] = $value;
                } else {
                    $normalized[$statKey] = $value;
                }
            }
        }

        foreach (['class_name', 'status', 'race', 'terrain'] as $metaField) {
            if (!array_key_exists($metaField, $data)) {
                continue;
            }
            $value = trim((string) ($data[$metaField] ?? ''));
            if ($value === '') {
                if ($useInstance) {
                    unset($instanceOverride[$metaField]);
                } else {
                    unset($normalized[$metaField]);
                }
            } else {
                if ($useInstance) {
                    $instanceOverride[$metaField] = $value;
                } else {
                    $normalized[$metaField] = $value;
                }
            }
        }

        if (array_key_exists('rating', $data)) {
            if ($data['rating'] === null || $data['rating'] === '') {
                if ($useInstance) {
                    unset($instanceOverride['rating']);
                } else {
                    unset($normalized['rating']);
                }
            } else {
                if ($useInstance) {
                    $instanceOverride['rating'] = round((float) $data['rating'], 2);
                } else {
                    $normalized['rating'] = round((float) $data['rating'], 2);
                }
            }
        }

        if (array_key_exists('admin_note', $data)) {
            $value = trim((string) ($data['admin_note'] ?? ''));
            if ($value === '') {
                if ($useInstance) {
                    unset($instanceOverride['admin_note']);
                } else {
                    unset($normalized['admin_note']);
                }
            } else {
                if ($useInstance) {
                    $instanceOverride['admin_note'] = $value;
                } else {
                    $normalized['admin_note'] = $value;
                }
            }
        }

        if ($useInstance) {
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

        DB::table('nation_units')->where('id', $nationUnitId)->update([
            'custom_name' => array_key_exists('custom_name', $data)
                ? trim((string) ($data['custom_name'] ?? ''))
                : $unit->custom_name,
            'stats_override_json' => json_encode($normalized),
            'updated_at' => now(),
        ]);

        $nation = DB::table('nations')->where('id', (int) $unit->nation_id)->first();
        $this->createNotification(
            'combat_unit_update',
            'Combat Unit Stats Updated',
            'Admin updated unit stats for nation "' . (string) ($nation->name ?? ('#' . $unit->nation_id)) . '" (unit #' . $nationUnitId . ($useInstance ? ', instance #' . $instanceIndex : '') . ').',
            [
                'nation_id' => (int) $unit->nation_id,
                'nation_unit_id' => $nationUnitId,
                'instance_index' => $useInstance ? $instanceIndex : null,
            ]
        );

        return response()->json(['message' => 'Unit stats updated']);
    }

    public function resourceTopbarConfig()
    {
        $available = $this->availableTopbarResources();
        $config = $this->loadResourceTopbarConfigRaw();

        $normalizedGlobal = $this->normalizeResourceTopbarSelections($config['global'] ?? [], $available);
        if (empty($normalizedGlobal)) {
            $normalizedGlobal = $this->defaultTopbarSelections($available);
        }

        $overrides = [];
        foreach (($config['overrides'] ?? []) as $override) {
            $userId = (int) ($override['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $mode = (string) ($override['mode'] ?? 'replace');
            if (!in_array($mode, ['replace', 'append'], true)) {
                $mode = 'replace';
            }

            $resources = $this->normalizeResourceTopbarSelections($override['resources'] ?? [], $available);
            if (empty($resources)) {
                continue;
            }

            $overrides[] = [
                'user_id' => $userId,
                'mode' => $mode,
                'resources' => $resources,
            ];
        }

        return response()->json([
            'global' => $normalizedGlobal,
            'overrides' => $overrides,
            'available' => [
                'base' => array_values($available['base'] ?? []),
                'advanced' => array_values($available['advanced'] ?? []),
            ],
        ]);
    }

    public function updateResourceTopbarConfig(Request $request)
    {
        $data = $request->validate([
            'global' => ['required', 'array', 'min:1'],
            'global.*.type' => ['required', 'in:base,advanced'],
            'global.*.name' => ['required', 'string', 'max:120'],
            'overrides' => ['sometimes', 'array'],
            'overrides.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'overrides.*.mode' => ['sometimes', 'in:replace,append'],
            'overrides.*.resources' => ['required', 'array', 'min:1'],
            'overrides.*.resources.*.type' => ['required', 'in:base,advanced'],
            'overrides.*.resources.*.name' => ['required', 'string', 'max:120'],
        ]);

        $available = $this->availableTopbarResources();

        $global = $this->normalizeResourceTopbarSelections($data['global'] ?? [], $available);
        if (empty($global)) {
            throw ValidationException::withMessages([
                'global' => 'Select at least one valid resource for the global topbar configuration.',
            ]);
        }

        $overrideByUser = [];
        foreach (($data['overrides'] ?? []) as $override) {
            $userId = (int) ($override['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }

            $resources = $this->normalizeResourceTopbarSelections($override['resources'] ?? [], $available);
            if (empty($resources)) {
                continue;
            }

            $mode = (string) ($override['mode'] ?? 'replace');
            if (!in_array($mode, ['replace', 'append'], true)) {
                $mode = 'replace';
            }

            $overrideByUser[$userId] = [
                'user_id' => $userId,
                'mode' => $mode,
                'resources' => $resources,
            ];
        }

        $payload = [
            'global' => $global,
            'overrides' => array_values($overrideByUser),
            'updated_at' => now()->toIso8601String(),
            'updated_by_user_id' => (int) $request->user()->id,
        ];

        if (!$this->saveResourceTopbarConfigRaw($payload)) {
            return response()->json(['message' => 'Topbar configuration storage is unavailable.'], 500);
        }

        return response()->json(['message' => 'Topbar resource configuration saved.']);
    }

    public function mapSettings()
    {
        return response()->json($this->loadMapSettingsRaw());
    }

    public function updateMapSettings(Request $request)
    {
        $data = $request->validate([
            'map_max_zoom_pct' => ['required', 'integer', 'min:100', 'max:300'],
        ]);

        $payload = [
            'map_max_zoom_pct' => (int) $data['map_max_zoom_pct'],
            'updated_at' => now()->toIso8601String(),
            'updated_by_user_id' => (int) $request->user()->id,
        ];

        if (!$this->saveMapSettingsRaw($payload)) {
            return response()->json(['message' => 'Map settings storage is unavailable.'], 500);
        }

        return response()->json(['message' => 'Map settings saved.']);
    }

    public function deleteManagedAccount(Request $request, int $userId)
    {
        $data = $request->validate([
            'confirmation_name' => ['required', 'string'],
            'purge_player_data' => ['sometimes', 'boolean'],
            'purge_confirmation' => ['sometimes', 'nullable', 'string'],
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

        $purgePlayerData = (bool) ($data['purge_player_data'] ?? false);
        if ($purgePlayerData) {
            $phrase = strtoupper(trim((string) ($data['purge_confirmation'] ?? '')));
            if ($phrase !== 'PURGE PLAYER DATA') {
                throw ValidationException::withMessages([
                    'purge_confirmation' => 'Type PURGE PLAYER DATA to confirm data purge mode.',
                ]);
            }
        }
        $this->accounts->deleteAccount($user, false, $purgePlayerData);

        return response()->json([
            'message' => $purgePlayerData
                ? 'Player account and related map/player data deleted permanently.'
                : 'Player account deleted permanently.',
        ]);
    }

    public function cleanupDeveloperZombieData(Request $request)
    {
        $data = $request->validate([
            'dry_run' => ['sometimes', 'boolean'],
            'confirmation_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $dryRun = (bool) ($data['dry_run'] ?? false);
        if (!$dryRun) {
            if (strtoupper(trim((string) ($data['confirmation_text'] ?? ''))) !== 'PURGE ZOMBIE DATA') {
                throw ValidationException::withMessages([
                    'confirmation_text' => 'Type PURGE ZOMBIE DATA to continue.',
                ]);
            }
        }

        $validNationIds = DB::table('nations')->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $validNationSet = array_fill_keys($validNationIds, true);
        $validUserIds = DB::table('users')->pluck('id')->map(static fn ($id) => (int) $id)->all();
        $validUserSet = array_fill_keys($validUserIds, true);
        $exampleLimit = 25;

        $results = [
            'map_editor_political_nations_removed' => 0,
            'map_editor_political_strokes_removed' => 0,
            'resource_topbar_overrides_removed' => 0,
            'developer_logs_removed' => 0,
        ];
        $previewDetails = [
            'map_editor_political_nations' => [
                'label' => 'Map editor political nation entries',
                'reason' => 'Nation IDs do not exist anymore (or are invalid).',
                'count' => 0,
                'examples' => [],
            ],
            'map_editor_political_strokes' => [
                'label' => 'Map editor political strokes',
                'reason' => 'Strokes reference deleted nation IDs or have invalid shape.',
                'count' => 0,
                'examples' => [],
            ],
            'resource_topbar_overrides' => [
                'label' => 'Resource topbar user overrides',
                'reason' => 'Overrides reference deleted user IDs.',
                'count' => 0,
                'examples' => [],
            ],
            'developer_logs' => [
                'label' => 'Developer log entries',
                'reason' => 'Log actor_user_id references deleted users or invalid log shape.',
                'count' => 0,
                'examples' => [],
            ],
        ];

        $mapPaths = [
            'maps/editor-state-active.json',
            'maps/editor-state-draft.json',
            'maps/editor-state.json',
        ];
        $publicDisk = Storage::disk('public');
        foreach ($mapPaths as $mapPath) {
            if (!$publicDisk->exists($mapPath)) {
                continue;
            }

            try {
                $decoded = json_decode((string) $publicDisk->get($mapPath), true);
                if (!is_array($decoded)) {
                    continue;
                }

                $politicalNations = is_array($decoded['political_nations'] ?? null) ? $decoded['political_nations'] : [];
                $beforeNationCount = count($politicalNations);
                $removedPoliticalNations = [];
                foreach ($politicalNations as $row) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id > 0 && isset($validNationSet[$id])) {
                        continue;
                    }
                    if (count($removedPoliticalNations) < $exampleLimit) {
                        $removedPoliticalNations[] = [
                            'id' => $id,
                            'name' => (string) ($row['name'] ?? ''),
                            'path' => $mapPath,
                        ];
                    }
                }
                $filteredPoliticalNations = array_values(array_filter($politicalNations, static function ($row) use ($validNationSet) {
                    $id = (int) ($row['id'] ?? 0);
                    if ($id <= 0) return false;
                    return isset($validNationSet[$id]);
                }));
                $results['map_editor_political_nations_removed'] += max(0, $beforeNationCount - count($filteredPoliticalNations));

                $politicalStrokes = is_array($decoded['political_strokes'] ?? null) ? $decoded['political_strokes'] : [];
                $beforeStrokeCount = count($politicalStrokes);
                $removedPoliticalStrokes = [];
                foreach ($politicalStrokes as $index => $row) {
                    if (!is_array($row)) {
                        if (count($removedPoliticalStrokes) < $exampleLimit) {
                            $removedPoliticalStrokes[] = ['index' => $index, 'reason' => 'invalid_row_shape', 'path' => $mapPath];
                        }
                        continue;
                    }
                    $nationId = (int) ($row['nation_id'] ?? 0);
                    if ($nationId > 0 && !isset($validNationSet[$nationId])) {
                        if (count($removedPoliticalStrokes) < $exampleLimit) {
                            $removedPoliticalStrokes[] = [
                                'index' => $index,
                                'nation_id' => $nationId,
                                'tool' => (string) ($row['tool'] ?? ''),
                                'x' => $row['x'] ?? null,
                                'y' => $row['y'] ?? null,
                                'path' => $mapPath,
                            ];
                        }
                    }
                }
                $filteredPoliticalStrokes = array_values(array_filter($politicalStrokes, static function ($row) use ($validNationSet) {
                    if (!is_array($row)) return false;
                    $nationId = (int) ($row['nation_id'] ?? 0);
                    if ($nationId <= 0) return true;
                    return isset($validNationSet[$nationId]);
                }));
                $results['map_editor_political_strokes_removed'] += max(0, $beforeStrokeCount - count($filteredPoliticalStrokes));

                if (!$dryRun) {
                    $decoded['political_nations'] = $filteredPoliticalNations;
                    $decoded['political_strokes'] = $filteredPoliticalStrokes;
                    $publicDisk->put($mapPath, json_encode($decoded, JSON_UNESCAPED_SLASHES));
                }

                $availableNationExampleSlots = max(0, $exampleLimit - count($previewDetails['map_editor_political_nations']['examples']));
                if ($availableNationExampleSlots > 0 && !empty($removedPoliticalNations)) {
                    $previewDetails['map_editor_political_nations']['examples'] = array_merge(
                        $previewDetails['map_editor_political_nations']['examples'],
                        array_slice($removedPoliticalNations, 0, $availableNationExampleSlots)
                    );
                }

                $availableStrokeExampleSlots = max(0, $exampleLimit - count($previewDetails['map_editor_political_strokes']['examples']));
                if ($availableStrokeExampleSlots > 0 && !empty($removedPoliticalStrokes)) {
                    $previewDetails['map_editor_political_strokes']['examples'] = array_merge(
                        $previewDetails['map_editor_political_strokes']['examples'],
                        array_slice($removedPoliticalStrokes, 0, $availableStrokeExampleSlots)
                    );
                }
            } catch (\Throwable $e) {
            }
        }

        $previewDetails['map_editor_political_nations']['count'] = $results['map_editor_political_nations_removed'];
        $previewDetails['map_editor_political_strokes']['count'] = $results['map_editor_political_strokes_removed'];

        $topbarPath = storage_path('app/resource_topbar_config.json');
        if (File::exists($topbarPath)) {
            try {
                $decoded = json_decode((string) File::get($topbarPath), true);
                if (is_array($decoded)) {
                    $overrides = is_array($decoded['overrides'] ?? null) ? $decoded['overrides'] : [];
                    $before = count($overrides);
                    $removedOverrides = [];
                    foreach ($overrides as $row) {
                        $userId = (int) ($row['user_id'] ?? 0);
                        if ($userId > 0 && isset($validUserSet[$userId])) {
                            continue;
                        }
                        if (count($removedOverrides) < $exampleLimit) {
                            $removedOverrides[] = [
                                'user_id' => $userId,
                                'mode' => (string) ($row['mode'] ?? ''),
                                'resource_count' => is_array($row['resources'] ?? null) ? count($row['resources']) : 0,
                            ];
                        }
                    }
                    $filteredOverrides = array_values(array_filter($overrides, static function ($row) use ($validUserSet) {
                        $userId = (int) ($row['user_id'] ?? 0);
                        if ($userId <= 0) return false;
                        return isset($validUserSet[$userId]);
                    }));
                    $results['resource_topbar_overrides_removed'] = max(0, $before - count($filteredOverrides));
                    $previewDetails['resource_topbar_overrides']['count'] = $results['resource_topbar_overrides_removed'];
                    $previewDetails['resource_topbar_overrides']['examples'] = $removedOverrides;
                    if (!$dryRun) {
                        $decoded['overrides'] = $filteredOverrides;
                        File::put($topbarPath, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $developerLogPath = storage_path('app/developer_logs.json');
        if (File::exists($developerLogPath)) {
            try {
                $decoded = json_decode((string) File::get($developerLogPath), true);
                if (is_array($decoded)) {
                    $before = count($decoded);
                    $removedLogs = [];
                    foreach ($decoded as $row) {
                        if (!is_array($row)) {
                            if (count($removedLogs) < $exampleLimit) {
                                $removedLogs[] = ['id' => null, 'reason' => 'invalid_row_shape'];
                            }
                            continue;
                        }
                        $actorId = (int) ($row['actor_user_id'] ?? 0);
                        if ($actorId > 0 && isset($validUserSet[$actorId])) {
                            continue;
                        }
                        if ($actorId <= 0 && array_key_exists('actor_user_id', $row) === false) {
                            continue;
                        }
                        if (count($removedLogs) < $exampleLimit) {
                            $removedLogs[] = [
                                'id' => (string) ($row['id'] ?? ''),
                                'actor_user_id' => $actorId,
                                'level' => (string) ($row['level'] ?? ''),
                                'summary' => (string) ($row['summary'] ?? ''),
                            ];
                        }
                    }
                    $filtered = array_values(array_filter($decoded, static function ($row) use ($validUserSet) {
                        if (!is_array($row)) return false;
                        $actorId = (int) ($row['actor_user_id'] ?? 0);
                        if ($actorId <= 0) return true;
                        return isset($validUserSet[$actorId]);
                    }));
                    $results['developer_logs_removed'] = max(0, $before - count($filtered));
                    $previewDetails['developer_logs']['count'] = $results['developer_logs_removed'];
                    $previewDetails['developer_logs']['examples'] = $removedLogs;
                    if (!$dryRun) {
                        File::put($developerLogPath, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    }
                }
            } catch (\Throwable $e) {
            }
        }

        $totalRemoved = array_sum($results);
        return response()->json([
            'message' => $dryRun ? 'Zombie-data cleanup preview generated.' : 'Zombie-data cleanup complete.',
            'dry_run' => $dryRun,
            'total_removed' => $totalRemoved,
            'details' => $results,
            'preview_details' => $previewDetails,
        ]);
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
            'requirement_json' => array_key_exists('requirement_json', $data) ? json_encode($data['requirement_json']) : $item->requirement_json,
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
            'requirement_json' => ['sometimes', 'nullable', 'array'],
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
            'requirement_json' => array_key_exists('requirement_json', $data) ? json_encode($data['requirement_json']) : null,
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

    public function nationResearchUnlocks(int $nationId)
    {
        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        if (!Schema::hasTable('nation_research')) {
            return response()->json([
                'nation_id' => (int) $nationId,
                'nation_name' => (string) $nation->name,
                'unlocks' => [],
                'message' => 'Research unlock storage is not initialized yet.',
            ]);
        }

        $rows = DB::table('nation_research as nr')
            ->leftJoin('shop_items as si', 'nr.shop_item_id', '=', 'si.id')
            ->where('nr.nation_id', $nationId)
            ->select(
                'nr.id',
                'nr.research_code',
                'nr.shop_item_id',
                'nr.researched_at',
                'nr.created_at',
                'si.display_name as source_item_name'
            )
            ->orderByDesc('nr.researched_at')
            ->orderBy('nr.id')
            ->get();

        return response()->json([
            'nation_id' => (int) $nationId,
            'nation_name' => (string) $nation->name,
            'unlocks' => $rows,
            'count' => $rows->count(),
        ]);
    }

    public function resetNationResearchUnlocks(int $nationId)
    {
        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        if (!Schema::hasTable('nation_research')) {
            return response()->json(['message' => 'Research unlock storage is not initialized yet.'], 422);
        }

        $deleted = DB::table('nation_research')->where('nation_id', $nationId)->delete();

        return response()->json([
            'message' => 'Research unlocks reset.',
            'nation_id' => (int) $nationId,
            'nation_name' => (string) $nation->name,
            'deleted_count' => (int) $deleted,
        ]);
    }

    public function deleteNationResearchUnlock(int $nationId, int $unlockId)
    {
        $nation = DB::table('nations')->where('id', $nationId)->first();
        if (!$nation) {
            return response()->json(['message' => 'Nation not found'], 404);
        }

        if (!Schema::hasTable('nation_research')) {
            return response()->json(['message' => 'Research unlock storage is not initialized yet.'], 422);
        }

        $row = DB::table('nation_research')
            ->where('id', $unlockId)
            ->where('nation_id', $nationId)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Research unlock not found for this nation.'], 404);
        }

        DB::table('nation_research')->where('id', $unlockId)->delete();

        return response()->json([
            'message' => 'Research unlock removed.',
            'nation_id' => (int) $nationId,
            'unlock_id' => (int) $unlockId,
            'research_code' => (string) ($row->research_code ?? ''),
        ]);
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

    private function availableTopbarResources(): array
    {
        $rows = DB::table('resource_definitions')
            ->select('type', 'name', 'display_name')
            ->orderBy('type')
            ->orderBy('group')
            ->orderBy('order')
            ->get();

        $available = [
            'base' => [],
            'advanced' => [],
        ];

        foreach ($rows as $row) {
            $type = (string) ($row->type ?? '');
            $name = trim((string) ($row->name ?? ''));
            $displayName = trim((string) ($row->display_name ?? $name));
            if (!in_array($type, ['base', 'advanced'], true) || $name === '') {
                continue;
            }

            $available[$type][$name] = [
                'type' => $type,
                'name' => $name,
                'display_name' => $displayName !== '' ? $displayName : $name,
            ];
        }

        return $available;
    }

    private function defaultTopbarSelections(array $available): array
    {
        $fallback = array_values($available['base'] ?? []);
        if (empty($fallback)) {
            $fallback = array_values($available['advanced'] ?? []);
        }

        return array_map(static fn ($item) => [
            'type' => (string) ($item['type'] ?? 'base'),
            'name' => (string) ($item['name'] ?? ''),
        ], array_slice($fallback, 0, 4));
    }

    private function normalizeResourceTopbarSelections(array $selections, array $available): array
    {
        $out = [];
        $seen = [];

        foreach ($selections as $row) {
            $type = (string) ($row['type'] ?? '');
            $name = trim((string) ($row['name'] ?? ''));
            if (!in_array($type, ['base', 'advanced'], true) || $name === '') {
                continue;
            }
            if (!isset($available[$type][$name])) {
                continue;
            }

            $key = $type . ':' . $name;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $out[] = [
                'type' => $type,
                'name' => $name,
            ];
        }

        return $out;
    }

    private function resourceTopbarConfigPath(): string
    {
        return storage_path('app/resource_topbar_config.json');
    }

    private function loadResourceTopbarConfigRaw(): array
    {
        $path = $this->resourceTopbarConfigPath();
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

    private function saveResourceTopbarConfigRaw(array $payload): bool
    {
        try {
            $path = $this->resourceTopbarConfigPath();
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($payload, JSON_PRETTY_PRINT));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function mapSettingsPath(): string
    {
        return storage_path('app/map_settings.json');
    }

    private function loadMapSettingsRaw(): array
    {
        $path = $this->mapSettingsPath();
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

    private function saveMapSettingsRaw(array $payload): bool
    {
        try {
            $path = $this->mapSettingsPath();
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($payload, JSON_PRETTY_PRINT));
            return true;
        } catch (\Throwable $e) {
            return false;
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

    private function isDocumentVisibleToViewer(string $code, int $viewerUserId, string $viewerRole): bool
    {
        if ($code === '') {
            return false;
        }

        $isAdminViewer = strtolower($viewerRole) === 'admin';

        try {
            // Default is admin-only if no visibility has been explicitly configured.
            $row = $this->readGameDocumentVisibilityRecord($code);
            if (!$row) {
                return $isAdminViewer;
            }

            $visibilityType = (string) ($row['visibility_type'] ?? 'admin');
            if ($visibilityType === 'all') {
                return true;
            }

            if ($visibilityType === 'admin') {
                return $isAdminViewer;
            }

            if ($visibilityType === 'role') {
                return strtolower((string) ($row['role_name'] ?? '')) === strtolower($viewerRole);
            }

            if ($visibilityType === 'custom') {
                $ids = $row['player_ids'] ?? [];
                if (!is_array($ids)) {
                    $ids = [];
                }
                return in_array($viewerUserId, array_map('intval', $ids), true);
            }

            return false;
        } catch (\Throwable $e) {
            return $isAdminViewer;
        }
    }

    private function seedMissingGameDocumentVisibilityDefaults(array $codes): void
    {
        $normalizedCodes = collect($codes)
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $code !== '')
            ->unique()
            ->values();

        if ($normalizedCodes->isEmpty()) {
            return;
        }

        foreach ($normalizedCodes as $code) {
            $existing = $this->readGameDocumentVisibilityRecord($code);
            if ($existing) {
                continue;
            }

            $this->writeGameDocumentVisibilityRecord($code, [
                'visibility_type' => 'admin',
                'role_name' => null,
                'player_ids' => [],
            ]);
        }
    }

    private function readGameDocumentVisibilityRecord(string $code): ?array
    {
        if ($code === '') {
            return null;
        }

        if ($this->ensureGameDocumentVisibilityStorage()) {
            $row = DB::table('game_document_visibility')->where('document_code', $code)->first();
            if ($row) {
                return [
                    'document_code' => (string) $row->document_code,
                    'visibility_type' => (string) ($row->visibility_type ?? 'admin'),
                    'role_name' => $row->role_name,
                    'player_ids' => $row->player_ids ? (json_decode($row->player_ids, true) ?: []) : [],
                ];
            }
        }

        $fallback = $this->loadGameDocumentVisibilityFallback();
        $row = $fallback[$code] ?? null;
        if (!is_array($row)) {
            return null;
        }

        return [
            'document_code' => $code,
            'visibility_type' => (string) ($row['visibility_type'] ?? 'admin'),
            'role_name' => $row['role_name'] ?? null,
            'player_ids' => is_array($row['player_ids'] ?? null) ? $row['player_ids'] : [],
        ];
    }

    private function writeGameDocumentVisibilityRecord(string $code, array $record): bool
    {
        if ($code === '') {
            return false;
        }

        if ($this->ensureGameDocumentVisibilityStorage()) {
            DB::table('game_document_visibility')->updateOrInsert(
                ['document_code' => $code],
                [
                    'visibility_type' => (string) ($record['visibility_type'] ?? 'admin'),
                    'role_name' => $record['visibility_type'] === 'role' ? ($record['role_name'] ?? null) : null,
                    'player_ids' => ($record['visibility_type'] ?? 'admin') === 'custom'
                        ? json_encode(array_values(array_unique(array_map('intval', $record['player_ids'] ?? []))))
                        : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            return true;
        }

        $fallback = $this->loadGameDocumentVisibilityFallback();
        $fallback[$code] = [
            'visibility_type' => (string) ($record['visibility_type'] ?? 'admin'),
            'role_name' => ($record['visibility_type'] ?? 'admin') === 'role' ? ($record['role_name'] ?? null) : null,
            'player_ids' => ($record['visibility_type'] ?? 'admin') === 'custom'
                ? array_values(array_unique(array_map('intval', $record['player_ids'] ?? [])))
                : [],
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveGameDocumentVisibilityFallback($fallback);
    }

    private function gameDocumentVisibilityFallbackPath(): string
    {
        return storage_path('app/game_document_visibility.json');
    }

    private function loadGameDocumentVisibilityFallback(): array
    {
        $path = $this->gameDocumentVisibilityFallbackPath();
        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function saveGameDocumentVisibilityFallback(array $rows): bool
    {
        try {
            $path = $this->gameDocumentVisibilityFallbackPath();
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($rows, JSON_PRETTY_PRINT));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ensureGameDocumentVisibilityStorage(): bool
    {
        try {
            if (Schema::hasTable('game_document_visibility')) {
                return true;
            }

            Schema::create('game_document_visibility', function (Blueprint $table) {
                $table->id();
                $table->string('document_code', 80)->unique();
                $table->enum('visibility_type', ['admin', 'role', 'all', 'custom'])->default('admin');
                $table->string('role_name')->nullable();
                $table->json('player_ids')->nullable();
                $table->timestamps();
            });

            return true;
        } catch (\Throwable $e) {
            return Schema::hasTable('game_document_visibility');
        }
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
            'is_paused' => (bool) ($state->is_paused ?? 0),
            'paused_at' => $state->paused_at,
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
            'is_paused' => (int) ($state->is_paused ?? 0),
            'paused_at' => $state->paused_at,
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

    public function pauseTimeTracker(Request $request)
    {
        $data = $request->validate([
            'pause_note' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $this->syncTimeProgress();
        $state = $this->getGameTimeState();
        if ((bool) ($state->is_paused ?? false)) {
            return response()->json(['message' => 'Time tracker is already paused']);
        }

        $now = now();
        DB::table('game_time')->where('id', 1)->update([
            'is_paused' => 1,
            'paused_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('game_time_pause_history')->insert([
            'paused_at' => $now,
            'resumed_at' => null,
            'paused_by_user_id' => (int) $request->user()->id,
            'resumed_by_user_id' => null,
            'pause_note' => array_key_exists('pause_note', $data) ? (string) ($data['pause_note'] ?? '') : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->timeTracker();
    }

    public function resumeTimeTracker(Request $request)
    {
        $state = $this->getGameTimeState();
        if (!(bool) ($state->is_paused ?? false)) {
            return response()->json(['message' => 'Time tracker is not paused']);
        }

        $now = now();
        DB::table('game_time')->where('id', 1)->update([
            'is_paused' => 0,
            'paused_at' => null,
            'year_started_at' => $now->subSeconds((int) round((float) ($state->elapsed_hours_in_year ?? 0) * 3600)),
            'updated_at' => now(),
        ]);

        $openPause = DB::table('game_time_pause_history')
            ->whereNull('resumed_at')
            ->orderByDesc('id')
            ->first();
        if ($openPause) {
            DB::table('game_time_pause_history')->where('id', $openPause->id)->update([
                'resumed_at' => now(),
                'resumed_by_user_id' => (int) $request->user()->id,
                'updated_at' => now(),
            ]);
        }

        return $this->timeTracker();
    }

    public function timeTrackerPauseHistory()
    {
        $rows = DB::table('game_time_pause_history as h')
            ->leftJoin('users as up', 'up.id', '=', 'h.paused_by_user_id')
            ->leftJoin('users as ur', 'ur.id', '=', 'h.resumed_by_user_id')
            ->select(
                'h.id',
                'h.paused_at',
                'h.resumed_at',
                'h.pause_note',
                'h.paused_by_user_id',
                'h.resumed_by_user_id',
                'up.name as paused_by_name',
                'ur.name as resumed_by_name'
            )
            ->orderByDesc('h.paused_at')
            ->limit(200)
            ->get();

        return response()->json($rows);
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
            'is_paused' => 0,
            'paused_at' => null,
            'year_label_offset' => 0,
            'updated_at' => now(),
        ]);

        return DB::table('game_time')->where('id', 1)->first();
    }

    private function syncTimeProgress(): int
    {
        $state = $this->getGameTimeState();
        if ((bool) ($state->is_paused ?? false)) {
            return 0;
        }
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
                $incomeMap = $this->normalizeIncomeMap($extra);

                $delta = [
                    'base' => [],
                    'advanced' => [],
                    'currencies' => [],
                ];
                foreach ($incomeMap as $incomeKey => $incomeValue) {
                    $this->applyDeltaValue($delta, $incomeKey, (float) $incomeValue);
                }

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
                foreach (($updated['base'] ?? []) as $key => $value) {
                    if ((float) $value < 0) {
                        $negativeKeys[] = $key . '=' . $value;
                    }
                }
                foreach (($updated['currencies'] ?? []) as $key => $value) {
                    if ($value < 0) {
                        $negativeKeys[] = $key . '=' . $value;
                    }
                }
                foreach (($updated['advanced'] ?? []) as $key => $value) {
                    if ($value < 0) {
                        $negativeKeys[] = $key . '=' . $value;
                    }
                }

                if ($maintenanceDetails || $negativeKeys) {
                    $this->createNotification(
                        'yearly_maintenance',
                        'Year ' . $yearNumber . ' processing for ' . $nation->name,
                        'System processed yearly income and maintenance for nation "' . $nation->name . '".'
                        . ' Income applied: ' . json_encode(['base' => $delta['base'], 'advanced' => $delta['advanced']])
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
        $baseExtra = is_array($extra['base'] ?? null) ? $extra['base'] : [];
        $advanced = is_array($extra['advanced'] ?? null) ? $extra['advanced'] : [];
        $currencies = $extra['currencies'] ?? [];

        $updatedBase = [
            'cow' => (float) $resourceRow->cow,
            'wood' => (float) $resourceRow->wood,
            'ore' => (float) $resourceRow->ore,
            'food' => (float) $resourceRow->food,
        ];

        foreach (($delta['base'] ?? []) as $key => $value) {
            $resourceKey = (string) $key;
            if (array_key_exists($resourceKey, $updatedBase)) {
                $updatedBase[$resourceKey] = (float) $updatedBase[$resourceKey] + (float) $value;
            } else {
                $baseExtra[$resourceKey] = (float) ($baseExtra[$resourceKey] ?? 0) + (float) $value;
            }
        }

        foreach (($delta['advanced'] ?? []) as $key => $value) {
            $advanced[$key] = ($advanced[$key] ?? 0) + (float) $value;
        }

        foreach (($delta['currencies'] ?? []) as $key => $value) {
            $currencies[$key] = ($currencies[$key] ?? 0) + (float) $value;
        }

        $extra['base'] = $baseExtra;
        $extra['advanced'] = $advanced;
        $extra['currencies'] = $currencies;

        DB::table('nation_resources')->where('nation_id', $nationId)->update([
            'cow' => $updatedBase['cow'],
            'wood' => $updatedBase['wood'],
            'ore' => $updatedBase['ore'],
            'food' => $updatedBase['food'],
            'extra_json' => json_encode($extra),
            'updated_at' => now(),
        ]);

        return [
            'base' => array_merge($baseExtra, $updatedBase),
            'advanced' => $advanced,
            'currencies' => $currencies,
        ];
    }

    private function applyDeltaValue(array &$delta, string $key, float $value): void
    {
        if (str_starts_with($key, 'base:')) {
            $baseKey = substr($key, 5);
            if ($baseKey !== '') {
                $delta['base'][$baseKey] = ($delta['base'][$baseKey] ?? 0) + $value;
            }
            return;
        }

        if (str_starts_with($key, 'advanced:')) {
            $advancedKey = substr($key, 9);
            if ($advancedKey !== '') {
                $delta['advanced'][$advancedKey] = ($delta['advanced'][$advancedKey] ?? 0) + $value;
            }
            return;
        }

        if (in_array($key, ['cow', 'wood', 'ore', 'food'], true)) {
            $delta['base'][$key] = ($delta['base'][$key] ?? 0) + $value;
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
        return $this->normalizeIncomeMapInput($income);
    }

    private function normalizeIncomeMapInput(array $income): array
    {
        $out = [];
        foreach ($income as $key => $value) {
            $rawKey = (string) $key;
            $normalizedKey = '';

            if (str_contains($rawKey, ':')) {
                [$type, $name] = explode(':', $rawKey, 2);
                $type = trim(strtolower($type));
                $name = trim($name);
                if (($type === 'base' || $type === 'advanced') && $name !== '') {
                    $normalizedKey = $type . ':' . $name;
                }
            } else {
                $name = trim($rawKey);
                if ($name !== '') {
                    $normalizedKey = 'base:' . $name;
                }
            }

            if ($normalizedKey === '') {
                continue;
            }

            $out[$normalizedKey] = (float) $value;
        }

        return $out;
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
                $adminNote = trim((string) ($effectiveOverride['admin_note'] ?? ''));

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
                    'admin_note' => $adminNote,
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

    private function combatRatingConfigPath(): string
    {
        return storage_path('app/combat_rating_config.json');
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

        $path = $this->combatRatingConfigPath();
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

    private function saveCombatRatingConfigRaw(array $payload): bool
    {
        try {
            $path = $this->combatRatingConfigPath();
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($payload, JSON_PRETTY_PRINT));
            return true;
        } catch (\Throwable $e) {
            return false;
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

    public function developerLogs(Request $request)
    {
        $data = $request->validate([
            'level' => ['sometimes', 'in:error,warning,info,all'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'query' => ['sometimes', 'string', 'max:120'],
        ]);

        $level = (string) ($data['level'] ?? 'all');
        $limit = (int) ($data['limit'] ?? 200);
        $query = strtolower(trim((string) ($data['query'] ?? '')));

        $logs = $this->loadDeveloperLogsRaw();

        if ($level !== 'all') {
            $logs = array_values(array_filter($logs, static fn ($log) => (string) ($log['level'] ?? '') === $level));
        }

        if ($query !== '') {
            $logs = array_values(array_filter($logs, static function ($log) use ($query) {
                $haystack = strtolower(
                    (string) ($log['summary'] ?? '') . ' ' .
                    (string) ($log['source'] ?? '') . ' ' .
                    (string) ($log['section'] ?? '') . ' ' .
                    (string) json_encode($log['context'] ?? [])
                );
                return str_contains($haystack, $query);
            }));
        }

        usort($logs, static function ($a, $b) {
            return strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? ''));
        });

        return response()->json([
            'logs' => array_slice($logs, 0, $limit),
            'total' => count($logs),
        ]);
    }

    public function storeDeveloperLog(Request $request)
    {
        $data = $request->validate([
            'level' => ['required', 'in:error,warning,info'],
            'summary' => ['required', 'string', 'max:300'],
            'source' => ['sometimes', 'nullable', 'string', 'max:120'],
            'section' => ['sometimes', 'nullable', 'string', 'max:120'],
            'url' => ['sometimes', 'nullable', 'string', 'max:500'],
            'context' => ['sometimes', 'nullable', 'array'],
        ]);

        $logs = $this->loadDeveloperLogsRaw();
        $entry = [
            'id' => (string) Str::uuid(),
            'timestamp' => now()->toIso8601String(),
            'level' => (string) $data['level'],
            'summary' => trim((string) $data['summary']),
            'source' => trim((string) ($data['source'] ?? 'ui')),
            'section' => trim((string) ($data['section'] ?? '')),
            'url' => trim((string) ($data['url'] ?? '')),
            'context' => is_array($data['context'] ?? null) ? $data['context'] : [],
            'actor_user_id' => (int) $request->user()->id,
        ];

        $logs[] = $entry;
        $max = (int) ($this->loadDeveloperLogSettingsRaw()['max_entries'] ?? 2000);
        if ($max < 100) {
            $max = 100;
        }
        if (count($logs) > $max) {
            $logs = array_slice($logs, count($logs) - $max);
        }

        if (!$this->saveDeveloperLogsRaw($logs)) {
            return response()->json(['message' => 'Developer log storage is unavailable.'], 500);
        }

        return response()->json(['message' => 'Developer log captured.', 'entry' => $entry]);
    }

    public function clearDeveloperLogs()
    {
        if (!$this->saveDeveloperLogsRaw([])) {
            return response()->json(['message' => 'Developer log storage is unavailable.'], 500);
        }
        return response()->json(['message' => 'Developer logs cleared.']);
    }

    public function developerLogSettings()
    {
        return response()->json($this->loadDeveloperLogSettingsRaw());
    }

    public function updateDeveloperLogSettings(Request $request)
    {
        $data = $request->validate([
            'capture_error' => ['sometimes', 'boolean'],
            'capture_warning' => ['sometimes', 'boolean'],
            'capture_info' => ['sometimes', 'boolean'],
            'auto_capture_client' => ['sometimes', 'boolean'],
            'max_entries' => ['sometimes', 'integer', 'min:100', 'max:5000'],
        ]);

        $settings = array_merge($this->loadDeveloperLogSettingsRaw(), $data, [
            'updated_at' => now()->toIso8601String(),
            'updated_by_user_id' => (int) $request->user()->id,
        ]);

        if (!$this->saveDeveloperLogSettingsRaw($settings)) {
            return response()->json(['message' => 'Developer log settings storage is unavailable.'], 500);
        }

        return response()->json(['message' => 'Developer log settings saved.', 'settings' => $settings]);
    }

    private function developerLogsPath(): string
    {
        return storage_path('app/developer_logs.json');
    }

    private function developerLogSettingsPath(): string
    {
        return storage_path('app/developer_log_settings.json');
    }

    private function loadDeveloperLogsRaw(): array
    {
        $path = $this->developerLogsPath();
        if (!File::exists($path)) {
            return [];
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, static fn ($row) => is_array($row)));
    }

    private function saveDeveloperLogsRaw(array $logs): bool
    {
        try {
            $path = $this->developerLogsPath();
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode(array_values($logs), JSON_PRETTY_PRINT));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function loadDeveloperLogSettingsRaw(): array
    {
        $defaults = [
            'capture_error' => true,
            'capture_warning' => true,
            'capture_info' => true,
            'auto_capture_client' => true,
            'max_entries' => 2000,
        ];

        $path = $this->developerLogSettingsPath();
        if (!File::exists($path)) {
            return $defaults;
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return [
            'capture_error' => (bool) ($decoded['capture_error'] ?? $defaults['capture_error']),
            'capture_warning' => (bool) ($decoded['capture_warning'] ?? $defaults['capture_warning']),
            'capture_info' => (bool) ($decoded['capture_info'] ?? $defaults['capture_info']),
            'auto_capture_client' => (bool) ($decoded['auto_capture_client'] ?? $defaults['auto_capture_client']),
            'max_entries' => max(100, min(5000, (int) ($decoded['max_entries'] ?? $defaults['max_entries']))),
            'updated_at' => (string) ($decoded['updated_at'] ?? ''),
            'updated_by_user_id' => (int) ($decoded['updated_by_user_id'] ?? 0),
        ];
    }

    private function saveDeveloperLogSettingsRaw(array $settings): bool
    {
        try {
            $path = $this->developerLogSettingsPath();
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($settings, JSON_PRETTY_PRINT));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

}
