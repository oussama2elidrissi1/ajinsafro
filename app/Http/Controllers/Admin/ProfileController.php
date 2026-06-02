<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BranchScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        $user = auth()->user();
        $managerTeamPreview = null;
        if ($user && $user->isManager()) {
            $direct = app(BranchScopeService::class)->portalDirectReports($user);
            $managerTeamPreview = [
                'members' => $direct,
                'count' => $direct->count(),
            ];
        }

        return view(request()->routeIs('agent.*') ? 'agent.profile.edit' : 'admin.profile.edit', [
            'user' => $user,
            'managerTeamPreview' => $managerTeamPreview,
        ]);
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ];

        if ($request->filled('new_password')) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['new_password'] = ['required', 'confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->address = $validated['address'] ?? null;

        if (! empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return redirect()
            ->route($request->routeIs('agent.*') ? 'agent.profile' : 'admin.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Upload and save the user's avatar.
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return redirect()
            ->route($request->routeIs('agent.*') ? 'agent.profile' : 'admin.profile.edit')
            ->with('success', 'Avatar updated successfully.');
    }
}
