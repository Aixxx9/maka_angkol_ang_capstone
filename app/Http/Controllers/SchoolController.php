<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\School;
use Illuminate\Support\Facades\Storage;

class SchoolController extends Controller
{
    /**
     * Display a listing of schools.
     */
    public function index()
    {
        $schools = School::select('id', 'name', 'slug', 'summary', 'logo_path')->get();

        // ✅ Always convert the logo path to a public URL
        $schools->transform(function ($school) {
            $school->logo_path = $school->logo_path
                ? Storage::url($school->logo_path)        // ✅ generates /storage/schools/file.jpg
                : asset('images/default-logo.png');       // fallback image
            return $school;
        });

        return Inertia::render('Schools/Index', [
            'schools' => $schools,
        ]);
    }

    /**
     * Store a newly created school in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'slug'    => 'required|string|max:255|unique:schools,slug',
            'summary' => 'nullable|string',
            'logo'    => 'nullable|image|max:20480', // 20MB
        ]);

        $logoPath = null;

        // ✅ Save uploaded logo inside storage/app/public/schools/
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('schools', 'public');
        }

        School::create([
            'name'      => $validated['name'],
            'slug'      => $validated['slug'],
            'summary'   => $validated['summary'] ?? null,
            'logo_path' => $logoPath,
        ]);

        return redirect()->back()->with('success', '✅ School added successfully!');
    }

    /**
     * Display the specified school.
     */
    public function show($slug){
        $school = School::where('slug', $slug)->firstOrFail();

        $school->logo_path = $school->logo_path
        ? Storage::url($school->logo_path)
        : asset('images/default-logo.png');

        return Inertia::render('Schools/Show', [
            'school' => $school,
            'achievements' => [],
        ]);
    }

    /**
     * Show the form for editing the specified school.
     */
    public function edit($slug)
    {
        $school = School::where('slug', $slug)->firstOrFail();
        $school->logo_path = $school->logo_path ? Storage::url($school->logo_path) : asset('images/default-logo.png');

        return Inertia::render('Schools/Edit', [
            'school' => $school,
        ]);
    }

    /**
     * Update the specified school in storage.
     */
    public function update(Request $request, $slug)
    {
        $school = School::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'logo' => 'nullable|image|max:20480', // 20MB
        ]);

        // ✅ Handle new logo upload if provided
        if ($request->hasFile('logo')) {
            if ($school->logo_path) {
                Storage::disk('public')->delete($school->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('schools', 'public');
        }

        $school->update($validated);

        $school->refresh();

        // ✅ Ensure proper logo URL is returned
        $school->logo_path = $school->logo_path ? Storage::url($school->logo_path) : asset('images/default-logo.png');

        return redirect()->back()->with([
            'success' => '✅ School updated successfully!',
            'updatedSchool' => $school,
        ]);
    }

    /**
     * Remove the specified school from storage.
     */
    public function destroy($slug)
    {
        $school = School::where('slug', $slug)->firstOrFail();

        if ($school->logo_path) {
            Storage::disk('public')->delete($school->logo_path);
        }

        $school->delete();

        return redirect('/schools')->with('success', '🗑️ School deleted successfully.');
    }
}
