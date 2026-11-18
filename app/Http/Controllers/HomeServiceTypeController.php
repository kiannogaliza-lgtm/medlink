<?php

namespace App\Http\Controllers;

use App\Models\HomeServiceType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomeServiceTypeController extends Controller
{
    /**
     * Get all active home service types (JSON).
     */
    public function index(Request $request)
    {
        $types = HomeServiceType::orderBy('name')->get();
        return response()->json($types);
    }

    /**
     * Store a newly created home service type.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:home_service_types,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $type = HomeServiceType::create([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Service type added successfully.',
                'data' => $type,
            ]);
        }

        return redirect()->back()->with('success', "Service type '{$type->name}' added successfully.");
    }

    /**
     * Update an existing home service type.
     */
    public function update(Request $request, HomeServiceType $homeServiceType)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('home_service_types')->ignore($homeServiceType->id)],
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $homeServiceType->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : $homeServiceType->is_active,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Service type updated successfully.',
                'data' => $homeServiceType,
            ]);
        }

        return redirect()->back()->with('success', "Service type '{$homeServiceType->name}' updated successfully.");
    }

    /**
     * Remove an existing home service type.
     */
    public function destroy(Request $request, HomeServiceType $homeServiceType)
    {
        $name = $homeServiceType->name;
        $homeServiceType->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Service type '{$name}' deleted successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Service type '{$name}' deleted successfully.");
    }
}
