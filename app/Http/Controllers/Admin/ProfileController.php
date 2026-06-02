<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCommissionEntry;
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

        $isAgentRoute = request()->routeIs('agent.*');
        $commissionSummary = null;
        $recentCommissions = collect();

        if ($isAgentRoute && $user) {
            $commissionQuery = AgentCommissionEntry::query()->where('agent_id', $user->id);
            $commissionSummary = [
                'total' => round((float) (clone $commissionQuery)
                    ->whereNotIn('commission_status', [AgentCommissionEntry::STATUS_CANCELLED, AgentCommissionEntry::STATUS_REVERSED])
                    ->sum('commission_total'), 2),
                'month_total' => round((float) (clone $commissionQuery)
                    ->whereBetween('calculated_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->whereNotIn('commission_status', [AgentCommissionEntry::STATUS_CANCELLED, AgentCommissionEntry::STATUS_REVERSED])
                    ->sum('commission_total'), 2),
                'payable_total' => round((float) (clone $commissionQuery)->where('commission_status', AgentCommissionEntry::STATUS_PAYABLE)->sum('commission_total'), 2),
                'paid_total' => round((float) (clone $commissionQuery)->where('commission_status', AgentCommissionEntry::STATUS_PAID)->sum('commission_total'), 2),
                'count' => (int) (clone $commissionQuery)->count(),
            ];
            $recentCommissions = (clone $commissionQuery)
                ->with(['voyage:id,name', 'reservation.departure:id,start_date', 'travelDate:id,date'])
                ->latest('calculated_at')
                ->latest('id')
                ->limit(5)
                ->get();
        }

        return view($isAgentRoute ? 'agent.profile.edit' : 'admin.profile.edit', [
            'user' => $user,
            'managerTeamPreview' => $managerTeamPreview,
            'commissionSummary' => $commissionSummary,
            'recentCommissions' => $recentCommissions,
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
