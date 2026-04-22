<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserGroupController extends Controller
{
    private function adminOnly(): void
    {
        abort_unless(Auth::user()?->isAdministrator(), 403);
    }

    public function index(Request $request)
    {
        $this->adminOnly();

        $groups = UserGroup::withCount('members')
            ->with('creator')
            ->when($request->filled('search'), fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.groups.index', compact('groups'));
    }

    public function create()
    {
        $this->adminOnly();
        $users = User::orderBy('name')->get();
        return view('users.groups.create', compact('users'));
    }

    public function store(Request $request)
    {
        $this->adminOnly();

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:user_groups,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'members'     => ['nullable', 'array'],
            'members.*'   => ['integer', 'exists:users,id'],
        ]);

        $group = UserGroup::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by'  => Auth::id(),
        ]);

        if (!empty($data['members'])) {
            $group->members()->sync($data['members']);
        }

        return redirect()->route('user-groups.show', $group)
            ->with('success', 'Group "' . $group->name . '" created.');
    }

    public function show(UserGroup $userGroup)
    {
        $this->adminOnly();
        $userGroup->load(['members', 'creator']);
        $group = $userGroup;
        return view('users.groups.show', compact('group'));
    }

    public function edit(UserGroup $userGroup)
    {
        $this->adminOnly();
        $users       = User::orderBy('name')->get();
        $memberIds   = $userGroup->members()->pluck('users.id')->toArray();
        return view('users.groups.edit', compact('userGroup', 'users', 'memberIds'));
    }

    public function update(Request $request, UserGroup $userGroup)
    {
        $this->adminOnly();

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:user_groups,name,' . $userGroup->id],
            'description' => ['nullable', 'string', 'max:500'],
            'members'     => ['nullable', 'array'],
            'members.*'   => ['integer', 'exists:users,id'],
        ]);

        $userGroup->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        $userGroup->members()->sync($data['members'] ?? []);

        return redirect()->route('user-groups.show', $userGroup)
            ->with('success', 'Group "' . $userGroup->name . '" updated.');
    }

    public function destroy(UserGroup $userGroup)
    {
        $this->adminOnly();
        $name = $userGroup->name;
        $userGroup->delete();
        return redirect()->route('user-groups.index')
            ->with('success', 'Group "' . $name . '" deleted.');
    }
}
