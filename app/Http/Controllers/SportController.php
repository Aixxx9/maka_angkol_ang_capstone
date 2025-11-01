<?php

namespace App\Http\Controllers;

use App\Models\Sport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SportController extends Controller
{
    // Display all sports
    public function index()
    {
        $sports = Sport::latest()->get();

        return Inertia::render('Sports/Index', [
            'sports' => $sports,
        ]);
    }

    // Show the create form
    public function create()
    {
        return Inertia::render('Sports/Create');
    }

    // Store new sport
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:sports,slug',
            'type'        => 'required|in:team,individual',
            'description' => 'nullable|string',
            'icon'        => 'nullable|image|max:2048',
            'stat_fields' => 'nullable|array',
            'stat_fields.*.key'   => 'required_with:stat_fields|string|max:50',
            'stat_fields.*.label' => 'required_with:stat_fields|string|max:100',
            'stat_fields.*.type'  => 'nullable|in:number,percent',
            'stat_fields.*.agg'   => 'nullable|in:sum,avg',
        ]);

        // Generate slug if not provided
        $slug = $validated['slug'] ?? Str::slug($validated['name'], '-');

        // Handle file upload
        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('sports', 'public');
        }

        Sport::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'icon_path' => $iconPath,
            'stat_fields' => $validated['stat_fields'] ?? null,
        ]);

        return redirect()->route('sports.index')
            ->with('success', 'Sport added successfully!');
    }

    // Show single sport (optional)
    public function show(Sport $sport)
    {
        return Inertia::render('Sports/Show', [
            'sport' => $sport,
        ]);
    }

    public function destroy(Sport $sport)
{
    // delete icon if exists
    if ($sport->icon_path && \Storage::disk('public')->exists($sport->icon_path)) {
        \Storage::disk('public')->delete($sport->icon_path);
    }

    $sport->delete();

    return redirect()->route('sports.index')->with('success', 'Sport deleted successfully!');
}

public function edit(Sport $sport)
{
    return Inertia::render('Sports/Edit', [
        'sport' => $sport,
    ]);
}

    public function update(Request $request, Sport $sport)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:sports,slug,' . $sport->id,
            'type'        => 'required|in:team,individual',
            'description' => 'nullable|string',
            'icon'        => 'nullable|image|max:20480',
            'stat_fields' => 'nullable|array',
            'stat_fields.*.key'   => 'required_with:stat_fields|string|max:50',
            'stat_fields.*.label' => 'required_with:stat_fields|string|max:100',
            'stat_fields.*.type'  => 'nullable|in:number,percent',
            'stat_fields.*.agg'   => 'nullable|in:sum,avg',
        ]);

    $slug = $validated['slug'] ?? \Str::slug($validated['name'], '-');

    // handle icon upload
    if ($request->hasFile('icon')) {
        if ($sport->icon_path && \Storage::disk('public')->exists($sport->icon_path)) {
            \Storage::disk('public')->delete($sport->icon_path);
        }
        $sport->icon_path = $request->file('icon')->store('sports', 'public');
    }

    $sport->update([
        'name' => $validated['name'],
        'slug' => $slug,
        'type' => $validated['type'],
        'description' => $validated['description'] ?? null,
        'icon_path' => $sport->icon_path,
        'stat_fields' => $validated['stat_fields'] ?? $sport->stat_fields,
    ]);

    return redirect()->route('sports.index')->with('success', 'Sport updated successfully!');
}


}
