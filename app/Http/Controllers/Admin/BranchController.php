<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('settings.view');
        $user = $request->user();
        $query = Branch::query()->orderBy('type')->orderBy('name');
        if (! $this->branchScope->canSeeAllBranches($user)) {
            $query->whereIn('id', $this->branchScope->visibleBranchIds($user) ?? []);
        }
        $branches = $query->withCount('users')->paginate(15);
        $canCreateBranch = $this->branchScope->canSeeAllBranches($user);
        return view('admin.branches.index', compact('branches', 'canCreateBranch'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('settings.view');
        if (! $this->branchScope->canSeeAllBranches($request->user())) {
            return redirect()->route('admin.branches.index')->with('error', 'Seuls le siège et les super admins peuvent créer une agence.');
        }
        $branch = new Branch();
        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'email', 'branch_id']);
        return view('admin.branches.form', ['branch' => $branch, 'users' => $users, 'isEdit' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.view');
        if (! $this->branchScope->canSeeAllBranches($request->user())) {
            return redirect()->route('admin.branches.index')->with('error', 'Seuls le siège et les super admins peuvent créer une agence.');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'],
            'type' => ['required', Rule::in([Branch::TYPE_HEAD_OFFICE, Branch::TYPE_BRANCH])],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        Branch::create($data);
        return redirect()->route('admin.branches.index')->with('success', 'Agence créée avec succès.');
    }

    public function edit(Branch $branch): View|RedirectResponse
    {
        $this->authorize('settings.view');
        $user = request()->user();
        if (! $this->branchScope->canSeeAllBranches($user) && $branch->id !== $user->branch_id) {
            return redirect()->route('admin.branches.index')->with('error', 'Accès non autorisé.');
        }
        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'email', 'branch_id']);
        return view('admin.branches.form', ['branch' => $branch, 'users' => $users, 'isEdit' => true]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('settings.view');
        $user = $request->user();
        if (! $this->branchScope->canSeeAllBranches($user) && $branch->id !== $user->branch_id) {
            return redirect()->route('admin.branches.index')->with('error', 'Accès non autorisé.');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'code' => ['required', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($branch->id)],
            'type' => ['required', Rule::in([Branch::TYPE_HEAD_OFFICE, Branch::TYPE_BRANCH])],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $branch->update($data);
        return redirect()->route('admin.branches.index')->with('success', 'Agence mise à jour.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('settings.view');
        $user = request()->user();
        if (! $this->branchScope->canSeeAllBranches($user) && $branch->id !== $user->branch_id) {
            return redirect()->route('admin.branches.index')->with('error', 'Accès non autorisé.');
        }
        if ($branch->users()->exists()) {
            return redirect()->route('admin.branches.index')->with('error', 'Impossible de supprimer une agence ayant des utilisateurs.');
        }
        $branch->delete();
        return redirect()->route('admin.branches.index')->with('success', 'Agence supprimée.');
    }
}
