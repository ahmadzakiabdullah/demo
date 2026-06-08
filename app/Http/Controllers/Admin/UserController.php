<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['systemRoles' => fn ($query) => $query->whereNull('organization_id')])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $role = $request->string('role')->toString();

                if ($role === 'member') {
                    $query->whereDoesntHave('systemRoles', function ($builder) {
                        $builder->whereNull('organization_id');
                    });
                } else {
                    $query->whereHas('systemRoles', function ($builder) use ($role) {
                        $builder->where('slug', $role)->whereNull('organization_id');
                    });
                }
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'system_role' => $user->primarySystemRole()?->only(['slug', 'name']),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at?->toDateString(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'role' => $request->string('role')->toString(),
            ],
            'roles' => collect([['slug' => 'member', 'name' => 'Member']])
                ->merge(
                    Role::query()
                        ->whereNull('organization_id')
                        ->where('slug', Role::SYSTEM_OWNER)
                        ->orderBy('name')
                        ->get(['slug', 'name']),
                )
                ->values()
                ->all(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Admin/Users/Create', [
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'email_verified_at' => now(),
        ]);

        $user->syncSystemRole($request->validated('system_role'));

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Admin/Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'system_role' => $user->primarySystemRole()?->slug,
            ],
            'roles' => $this->assignableRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = Hash::make($request->validated('password'));
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        $user->syncSystemRole($request->validated('system_role'));

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            abort(403);
        }

        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * @return list<array{slug: string, name: string}>
     */
    private function assignableRoles(): array
    {
        $roles = Role::query()
            ->whereNull('organization_id')
            ->where('slug', Role::SYSTEM_OWNER)
            ->orderBy('name')
            ->get(['slug', 'name'])
            ->map(fn (Role $role) => $role->only(['slug', 'name']))
            ->all();

        array_unshift($roles, ['slug' => '', 'name' => 'Member']);

        return $roles;
    }
}