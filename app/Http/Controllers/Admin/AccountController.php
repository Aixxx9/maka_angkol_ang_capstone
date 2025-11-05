<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index()
    {
        $mods = User::role('mod')
            ->with(['sports:id,name'])
            ->get(['id','name','email','is_disabled']);

        return Inertia::render('Admin/Accounts/Index', [
            'mods' => $mods->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'is_disabled' => (bool) $u->is_disabled,
                    'sports' => $u->sports->map(fn($s) => ['id' => $s->id, 'name' => $s->name]),
                ];
            }),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Accounts/Create', [
            'sports' => Sport::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'sports' => 'nullable|array',
            'sports.*' => 'integer|exists:sports,id',
            'is_disabled' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_disabled' => (bool)($data['is_disabled'] ?? false),
        ]);
        $user->assignRole('mod');
        $user->sports()->sync($data['sports'] ?? []);

        return redirect()->route('admin.accounts.index')->with('success', 'Moderator created.');
    }

    public function edit(User $user)
    {
        abort_if(!$user->hasRole('mod'), 404);
        $user->load('sports:id,name');
        return Inertia::render('Admin/Accounts/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_disabled' => (bool) $user->is_disabled,
                'sports' => $user->sports->map->only(['id','name']),
            ],
            'sports' => Sport::orderBy('name')->get(['id','name']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if(!$user->hasRole('mod'), 404);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'sports' => 'nullable|array',
            'sports.*' => 'integer|exists:sports,id',
            'is_disabled' => 'sometimes|boolean',
        ]);

        $payload = [
            'name' => $data['name'],
            'is_disabled' => (bool)($data['is_disabled'] ?? false),
        ];
        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);
        $user->sports()->sync($data['sports'] ?? []);

        return redirect()->route('admin.accounts.index')->with('success', 'Moderator updated.');
    }

    public function logout(User $user)
    {
        abort_if(!$user->hasRole('mod'), 404);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        return back()->with('success', 'Moderator logged out from all devices.');
    }

    public function destroy(User $user)
    {
        abort_if(!$user->hasRole('mod'), 404);
        $user->sports()->detach();
        $user->delete();
        return redirect()->route('admin.accounts.index')->with('success', 'Moderator deleted.');
    }
}

