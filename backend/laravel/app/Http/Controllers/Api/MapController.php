<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadMapLayerRequest;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MapController extends Controller
{
    private const ACTIVE_EDITOR_STATE_PATH = 'maps/editor-state-active.json';
    private const DRAFT_EDITOR_STATE_PATH = 'maps/editor-state-draft.json';

    public function __construct(private AccountService $accounts)
    {
    }

    public function index()
    {
        return response()->json(
            DB::table('map_layers')->orderBy('id')->get()
        );
    }

    public function editorState()
    {
        try {
            return response()->json($this->loadActiveEditorState());
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to load map editor state.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function adminEditorState()
    {
        try {
            $active = $this->loadActiveEditorState();
            $draft = $this->loadDraftEditorState($active);

            return response()->json([
                'active_state' => $active,
                'draft_state' => $draft,
                'status' => [
                    'active_saved_at' => $active['saved_at'] ?? null,
                    'active_saved_by_user_id' => $active['saved_by_user_id'] ?? null,
                    'draft_saved_at' => $draft['saved_at'] ?? null,
                    'draft_saved_by_user_id' => $draft['saved_by_user_id'] ?? null,
                    'has_unpublished_changes' => $this->stateHash($active) !== $this->stateHash($draft),
                    'published_at' => $active['published_at'] ?? null,
                    'published_by_user_id' => $active['published_by_user_id'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to load admin map editor state.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveEditorState(Request $request)
    {
        $data = $request->validate([
            'width' => ['required', 'integer', 'min:100', 'max:5000'],
            'height' => ['required', 'integer', 'min:100', 'max:5000'],
            'terrain_color_overrides' => ['nullable', 'array'],
            'terrain_strokes' => ['nullable', 'array'],
            'political_nations' => ['nullable', 'array'],
            'political_strokes' => ['nullable', 'array'],
        ]);

        $data['terrain_color_overrides'] = $this->normalizeTerrainColorOverrides($data['terrain_color_overrides'] ?? []);
        $data['terrain_strokes'] = $this->sanitizeTerrainStrokes($data['terrain_strokes'] ?? []);
        $data['political_strokes'] = $this->sanitizePoliticalStrokes($data['political_strokes'] ?? []);
        $data['political_nations'] = $this->sanitizePoliticalNations($data['political_nations'] ?? []);

        $payload = array_merge($this->defaultEditorState(), $data, [
            'saved_at' => now()->toIso8601String(),
            'saved_by_user_id' => $request->user()->id,
        ]);

        try {
            Storage::disk('public')->put(self::DRAFT_EDITOR_STATE_PATH, json_encode($payload, JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to save map editor state.',
                'detail' => $e->getMessage(),
            ], 500);
        }

        return response()->json(['message' => 'Draft map editor state saved']);
    }

    public function activateEditorState(Request $request)
    {
        try {
            $active = $this->loadActiveEditorState();
            $draft = $this->loadDraftEditorState($active);
            $nowIso = now()->toIso8601String();

            $payload = array_merge($this->defaultEditorState(), $draft, [
                'published_at' => $nowIso,
                'published_by_user_id' => $request->user()->id,
            ]);

            Storage::disk('public')->put(self::ACTIVE_EDITOR_STATE_PATH, json_encode($payload, JSON_UNESCAPED_SLASHES));

            return response()->json([
                'message' => 'Draft map is now active.',
                'published_at' => $nowIso,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to activate draft map.',
                'detail' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncTerrainStatsBulk(Request $request)
    {
        $data = $request->validate([
            'nation_stats' => ['required', 'array', 'max:4000'],
            'nation_stats.*.nation_id' => ['required', 'integer', 'min:1'],
            'nation_stats.*.terrain_square_miles' => ['required', 'array'],
        ]);

        $rows = is_array($data['nation_stats'] ?? null) ? $data['nation_stats'] : [];
        if (empty($rows)) {
            return response()->json([
                'updated_count' => 0,
                'failed_count' => 0,
                'skipped_unknown_nations' => 0,
                'failed_details' => [],
            ]);
        }

        $nationIds = array_values(array_unique(array_map(static fn ($row) => (int) ($row['nation_id'] ?? 0), $rows)));
        $validNationSet = array_fill_keys(
            DB::table('nations')->whereIn('id', $nationIds)->pluck('id')->map(static fn ($id) => (int) $id)->all(),
            true
        );

        $updatedCount = 0;
        $failedCount = 0;
        $skippedUnknown = 0;
        $failedDetails = [];

        DB::transaction(function () use ($rows, $validNationSet, &$updatedCount, &$failedCount, &$skippedUnknown, &$failedDetails) {
            foreach ($rows as $row) {
                $nationId = (int) ($row['nation_id'] ?? 0);
                if ($nationId <= 0 || !isset($validNationSet[$nationId])) {
                    $skippedUnknown++;
                    continue;
                }

                try {
                    $squareMiles = $this->sanitizeTerrainSquareMilesInput($row['terrain_square_miles'] ?? []);
                    DB::table('nation_terrain_stats')->updateOrInsert(
                        ['nation_id' => $nationId],
                        array_merge(
                            ['nation_id' => $nationId],
                            $this->accounts->buildTerrainStatsPayload($squareMiles),
                            ['updated_at' => now()]
                        )
                    );
                    $updatedCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    if (count($failedDetails) < 50) {
                        $failedDetails[] = [
                            'nation_id' => $nationId,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }
        });

        return response()->json([
            'updated_count' => $updatedCount,
            'failed_count' => $failedCount,
            'skipped_unknown_nations' => $skippedUnknown,
            'failed_details' => $failedDetails,
        ]);
    }

    public function uploadLayer(UploadMapLayerRequest $request, string $layerType)
    {
        if (!in_array($layerType, ['main', 'terrain', 'political'], true)) {
            return response()->json(['message' => 'Invalid layer type'], 422);
        }

        $data = $request->validated();

        $imagePath = $data['image_path'] ?? null;
        if (!$imagePath && $request->hasFile('image_file')) {
            try {
                $imagePath = $request->file('image_file')->store('maps', 'public');
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Map upload failed while storing the image file.',
                    'detail' => $e->getMessage(),
                ], 422);
            }
        }

        if (!$imagePath) {
            return response()->json(['message' => 'Map upload failed: no file or image path was provided.'], 422);
        }

        DB::table('map_layers')->updateOrInsert(
            ['layer_type' => $layerType],
            [
                'image_path' => $imagePath,
                'uploaded_by_user_id' => $request->user()->id,
                'updated_at' => now(),
            ]
        );

        return response()->json(['message' => 'Map layer updated', 'image_path' => $imagePath]);
    }

    public function resetMap(Request $request)
    {
        try {
            DB::transaction(function () {
                DB::table('map_layers')->delete();

                DB::table('nation_terrain_stats')->update([
                    'grassland_pct' => 0,
                    'mountain_pct' => 0,
                    'freshwater_pct' => 0,
                    'hills_pct' => 0,
                    'desert_pct' => 0,
                    'seafront_pct' => 0,
                    'square_miles_json' => json_encode([
                        'grassland' => 0,
                        'forest' => 0,
                        'mountain' => 0,
                        'desert' => 0,
                        'tundra' => 0,
                        'magic_grassland' => 0,
                        'water' => 0,
                        'freshwater' => 0,
                        'hills' => 0,
                        'seafront' => 0,
                    ]),
                    'updated_at' => now(),
                ]);
            });

            Storage::disk('public')->delete([
                self::ACTIVE_EDITOR_STATE_PATH,
                self::DRAFT_EDITOR_STATE_PATH,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to reset map.',
                'detail' => $e->getMessage(),
            ], 500);
        }

        return response()->json(['message' => 'Map reset completed']);
    }

    private function defaultEditorState(): array
    {
        return [
            'width' => 1200,
            'height' => 700,
            'terrain_color_overrides' => [],
            'terrain_strokes' => [
                ['tool' => 'fill', 'terrain' => 'water', 'x' => 0, 'y' => 0],
            ],
            'political_strokes' => [],
            'political_nations' => [],
        ];
    }

    private function normalizeTerrainColorOverrides($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $key => $value) {
            $terrainKey = trim((string) $key);
            if ($terrainKey === '' || mb_strlen($terrainKey) > 64) {
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

    private function sanitizeTerrainStrokes($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $stroke) {
            if (!is_array($stroke)) {
                continue;
            }

            $tool = in_array($stroke['tool'] ?? null, ['brush', 'fill'], true)
                ? (string) $stroke['tool']
                : 'brush';
            $terrain = trim((string) ($stroke['terrain'] ?? ''));
            if ($terrain === '' || mb_strlen($terrain) > 64) {
                continue;
            }

            $x = $this->sanitizeNumeric($stroke['x'] ?? null);
            $y = $this->sanitizeNumeric($stroke['y'] ?? null);
            $size = $this->sanitizeNumeric($stroke['size'] ?? null, 1, 200);

            $entry = [
                'tool' => $tool,
                'terrain' => $terrain,
            ];
            if ($x !== null) {
                $entry['x'] = $x;
            }
            if ($y !== null) {
                $entry['y'] = $y;
            }
            if ($size !== null) {
                $entry['size'] = $size;
            }

            $out[] = $entry;
        }

        return $out;
    }

    private function sanitizePoliticalStrokes($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $stroke) {
            if (!is_array($stroke)) {
                continue;
            }

            $tool = in_array($stroke['tool'] ?? null, ['brush', 'fill'], true)
                ? (string) $stroke['tool']
                : 'brush';
            $x = $this->sanitizeNumeric($stroke['x'] ?? null);
            $y = $this->sanitizeNumeric($stroke['y'] ?? null);
            $size = $this->sanitizeNumeric($stroke['size'] ?? null, 1, 200);
            $nationId = $this->sanitizeInteger($stroke['nation_id'] ?? null);

            $entry = [
                'tool' => $tool,
                'remove' => (bool) ($stroke['remove'] ?? false),
            ];
            if ($nationId !== null) {
                $entry['nation_id'] = $nationId;
            }
            if ($x !== null) {
                $entry['x'] = $x;
            }
            if ($y !== null) {
                $entry['y'] = $y;
            }
            if ($size !== null) {
                $entry['size'] = $size;
            }

            $out[] = $entry;
        }

        return $out;
    }

    private function sanitizePoliticalNations($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $nation) {
            if (!is_array($nation)) {
                continue;
            }

            $name = trim((string) ($nation['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $entry = [
                'name' => mb_substr($name, 0, 150),
                'alliance_name' => mb_substr(trim((string) ($nation['alliance_name'] ?? '')), 0, 120),
                'pixels' => max(0, (int) ($nation['pixels'] ?? 0)),
                'races' => [],
            ];

            $id = $this->sanitizeInteger($nation['id'] ?? null);
            if ($id !== null) {
                $entry['id'] = $id;
            }

            $races = $nation['races'] ?? [];
            if (is_array($races)) {
                foreach ($races as $race) {
                    $raceName = trim((string) $race);
                    if ($raceName === '') {
                        continue;
                    }
                    $entry['races'][] = mb_substr($raceName, 0, 80);
                }
            }

            $out[] = $entry;
        }

        return $out;
    }

    private function sanitizeNumeric($value, ?float $min = null, ?float $max = null): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $num = (float) $value;
        if ($min !== null && $num < $min) {
            return null;
        }
        if ($max !== null && $num > $max) {
            return null;
        }

        return $num;
    }

    private function sanitizeInteger($value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function loadActiveEditorState(): array
    {
        $state = $this->readStateFromDisk(self::ACTIVE_EDITOR_STATE_PATH);
        if (is_array($state)) {
            return array_merge($this->defaultEditorState(), $state);
        }

        return $this->defaultEditorState();
    }

    private function loadDraftEditorState(?array $activeState = null): array
    {
        $state = $this->readStateFromDisk(self::DRAFT_EDITOR_STATE_PATH);
        if (is_array($state)) {
            return array_merge($this->defaultEditorState(), $state);
        }

        $fallback = is_array($activeState) ? $activeState : $this->loadActiveEditorState();
        return array_merge($this->defaultEditorState(), $fallback);
    }

    private function readStateFromDisk(string $path): ?array
    {
        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            return null;
        }

        $raw = $disk->get($path);
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function stateHash(array $state): string
    {
        $payload = [
            'width' => (int) ($state['width'] ?? 0),
            'height' => (int) ($state['height'] ?? 0),
            'terrain_color_overrides' => $state['terrain_color_overrides'] ?? [],
            'terrain_strokes' => $state['terrain_strokes'] ?? [],
            'political_strokes' => $state['political_strokes'] ?? [],
            'political_nations' => $state['political_nations'] ?? [],
        ];

        return sha1((string) json_encode($payload));
    }

    private function sanitizeTerrainSquareMilesInput($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $key => $value) {
            $terrainKey = trim((string) $key);
            if ($terrainKey === '') {
                continue;
            }
            if (!is_numeric($value)) {
                continue;
            }
            $out[$terrainKey] = max(0, (float) $value);
        }

        return $out;
    }
}
