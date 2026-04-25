<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /** Deny non-admins with a friendly redirect */
    private function denyIfNotAdmin(string $ability, mixed $arg = null): void
    {
        $check = $arg ? Gate::allows($ability, $arg) : Gate::allows($ability, User::class);
        if (! $check) {
            abort(redirect()->route('dashboard')
                ->with('error', 'Access denied. Administrator privileges required.'));
        }
    }

    public function index(Request $request)
    {
        $this->denyIfNotAdmin('viewAny');

        $query = User::latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', "%$s%")
                                      ->orWhere('email', 'like', "%$s%"));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(15)->withQueryString();

        $stats = [
            'total'                => User::count(),
            'administrators'       => User::where('role', 'administrator')->count(),
            'assessors'            => User::where('role', 'assessor')->count(),
            'patch_administrators' => User::where('role', 'patch_administrator')->count(),
        ];

        return view('users.index', compact('users', 'stats'));
    }

    public function create()
    {
        $this->denyIfNotAdmin('create');
        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->denyIfNotAdmin('create');

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'role'     => ['required', 'in:administrator,assessor,patch_administrator'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);
        AuditLog::record('user.created', $user, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $this->denyIfNotAdmin('view', $user);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->denyIfNotAdmin('update', $user);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->denyIfNotAdmin('update', $user);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'        => ['required', 'in:administrator,assessor,patch_administrator'],
            'mfa_enabled' => ['nullable', 'boolean'],
            'password'    => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $data['mfa_enabled'] = $request->boolean('mfa_enabled');

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);
        AuditLog::record('user.updated', $user, ['role' => $user->role]);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->denyIfNotAdmin('delete', $user);

        AuditLog::record('user.deleted', null, ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted.');
    }
}
