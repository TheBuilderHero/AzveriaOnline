<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadMapLayerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MapController extends Controller
{
    private const EDITOR_STATE_PATH = 'maps/editor-state.json';

    public function index()
    {
        return response()->json(
            DB::table('map_layers')->orderBy('id')->get()
        );
    }

    public function editorState()
    {
        $disk = Storage::disk('public');
        if (!$disk->exists(self::EDITOR_STATE_PATH)) {
            return response()->json($this->defaultEditorState());
        }

        try {
            $raw = $disk->get(self::EDITOR_STATE_PATH);
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return response()->json($this->defaultEditorState());
            }

            return response()->json(array_merge($this->defaultEditorState(), $decoded));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to load map editor state.',
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
            'terrain_color_overrides.*' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'terrain_strokes' => ['nullable', 'array'],
            'terrain_strokes.*.tool' => ['nullable', 'string', 'in:brush,fill'],
            'terrain_strokes.*.terrain' => ['nullable', 'string', 'max:64'],
            'terrain_strokes.*.x' => ['nullable', 'numeric'],
            'terrain_strokes.*.y' => ['nullable', 'numeric'],
            'terrain_strokes.*.size' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'political_nations' => ['nullable', 'array'],
            'political_nations.*.id' => ['nullable'],
            'political_nations.*.name' => ['nullable', 'string', 'max:150'],
            'political_nations.*.alliance_name' => ['nullable', 'string', 'max:120'],
            'political_nations.*.races' => ['nullable', 'array'],
            'political_nations.*.races.*' => ['nullable', 'string', 'max:80'],
            'political_nations.*.pixels' => ['nullable', 'numeric', 'min:0'],
            'political_strokes' => ['nullable', 'array'],
            'political_strokes.*.nation_id' => ['nullable'],
            'political_strokes.*.tool' => ['nullable', 'string', 'in:brush,fill'],
            'political_strokes.*.remove' => ['nullable', 'boolean'],
            'political_strokes.*.x' => ['nullable', 'numeric'],
            'political_strokes.*.y' => ['nullable', 'numeric'],
            'political_strokes.*.size' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'editor_background_path' => ['nullable', 'string', 'max:2048'],
            'editor_background_opacity' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $payload = array_merge($this->defaultEditorState(), $data, [
            'saved_at' => now()->toIso8601String(),
            'saved_by_user_id' => $request->user()->id,
        ]);

        try {
            Storage::disk('public')->put(self::EDITOR_STATE_PATH, json_encode($payload, JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to save map editor state.',
                'detail' => $e->getMessage(),
            ], 500);
        }

        return response()->json(['message' => 'Map editor state saved']);
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

    public function uploadEditorReference(UploadMapLayerRequest $request)
    {
        $data = $request->validated();

        $imagePath = $data['image_path'] ?? null;
        if (!$imagePath && $request->hasFile('image_file')) {
            try {
                $imagePath = $request->file('image_file')->store('maps/editor-reference', 'public');
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Reference upload failed while storing the image file.',
                    'detail' => $e->getMessage(),
                ], 422);
            }
        }

        if (!$imagePath) {
            return response()->json(['message' => 'Reference upload failed: no file or image path was provided.'], 422);
        }

        return response()->json(['message' => 'Reference image updated', 'image_path' => $imagePath]);
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

            Storage::disk('public')->delete(self::EDITOR_STATE_PATH);
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
            'editor_background_path' => null,
            'editor_background_opacity' => 1,
        ];
    }
}
