<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResourceDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ResourceDefinitionController extends Controller
{
    // List all resources, grouped by type and group
    public function index()
    {
        $query = ResourceDefinition::query()->orderBy('type');
        if ($this->supportsGroupOrder()) {
            $query->orderBy('group_order');
        }
        $resources = $query->orderBy('group')->orderBy('order')->get();
        $grouped = $resources->groupBy(['type', 'group']);
        return response()->json($grouped);
    }

    // Create a new resource definition
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:resource_definitions,name',
            'display_name' => 'required|string',
            'type' => 'required|in:base,advanced',
            'group' => 'required|string',
            'group_order' => 'sometimes|integer|min:0',
            'order' => 'integer',
            'meta' => 'array',
        ]);
        if (!$this->supportsGroupOrder()) {
            unset($data['group_order']);
        }
        $resource = ResourceDefinition::create($data);
        return response()->json($resource, 201);
    }

    // Update a resource definition
    public function update(Request $request, $id)
    {
        $resource = ResourceDefinition::findOrFail($id);
        $data = $request->validate([
            'display_name' => 'sometimes|string',
            'type' => 'sometimes|in:base,advanced',
            'group' => 'sometimes|string',
            'group_order' => 'sometimes|integer|min:0',
            'order' => 'sometimes|integer',
            'meta' => 'sometimes|array',
        ]);
        if (!$this->supportsGroupOrder()) {
            unset($data['group_order']);
        }
        $resource->update($data);
        return response()->json($resource);
    }

    // Delete a resource definition
    public function destroy($id)
    {
        $resource = ResourceDefinition::findOrFail($id);
        $resource->delete();
        return response()->json(['message' => 'Resource deleted']);
    }

    private function supportsGroupOrder(): bool
    {
        return Schema::hasTable('resource_definitions')
            && Schema::hasColumn('resource_definitions', 'group_order');
    }
}
