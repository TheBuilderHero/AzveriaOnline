<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadMapLayerRequest;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public function index()
    {
        return response()->json(
            DB::table('map_layers')->orderBy('id')->get()
        );
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
}
