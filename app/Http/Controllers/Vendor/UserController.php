<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
 
    public function index()
    {
        $users = User::query()
            ->where('tenant_id', $this->tenantId())
            ->with(['branch', 'roles'])
            ->latest()
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'status' => (bool) $user->status,
                    'branch' => $user->branch?->name,
                    'role' => $user->roles->first()?->name,
                ];
            });

        return Inertia::render('VendorAdmin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        return Inertia::render('VendorAdmin/Users/CreateUpdate', [
            'userData' => null,
            'branches' => Branch::query()
                ->where('tenant_id', $this->tenantId())
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
            'roles' => Role::query()
                ->where('guard_name', 'vendor')
                ->where('tenant_id', $this->tenantId())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function getData(Request $request)
    {
        $users = User::with(['roles', 'branch'])
            ->where('tenant_id', $this->tenantId());

        return DataTables::of($users)

            ->filter(function ($query) use ($request) {
                $search = $request->input('search.value');

                if (filled($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhereHas('roles', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            });
                    });
                }
            })

            ->addColumn('name', fn($row) => $row->name ?? '—')
            ->addColumn('username', fn($row) => $row->username ?? '—')
            ->addColumn('email', fn($row) => $row->email ?? '—')

            ->addColumn('branch', fn($row) => $row->branch?->name ?? '—')

            ->addColumn(
                'role',
                fn($row) =>
                $row->roles->pluck('name')->implode(', ') ?: '—'
            )

            ->addColumn('status', fn($row) => $row->status)

            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $role = Role::query()
            ->where('id', $validated['role_id'])
            ->where('guard_name', 'vendor')
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $branch = Branch::query()
            ->where('id', $validated['branch_id'])
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        DB::transaction(function () use ($validated, $role, $branch) {
            $user = User::create([
                'tenant_id' => $this->tenantId(),
                'branch_id' => $branch->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'gender' => $validated['gender'],
                'status' => (bool) ($validated['status'] ?? false),
                'password' => Hash::make($validated['password']),
            ]);

            $user->syncRoles([$role->name]);
        });

        return redirect()
            ->route('vendor.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $user = User::query()
            ->where('tenant_id', $this->tenantId())
            ->with('roles')
            ->findOrFail($id);

        return Inertia::render('VendorAdmin/Users/CreateUpdate', [
            'userData' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'gender' => $user->gender,
                'status' => (bool) $user->status,
                'branch_id' => $user->branch_id,
                'role_id' => $user->roles->first()?->id,
            ],
            'branches' => Branch::query()
                ->where('tenant_id', $this->tenantId())
                ->where('is_active', 1)
                ->orderBy('name')
                ->get(['id', 'name']),
            'roles' => Role::query()
                ->where('guard_name', 'vendor')
                ->where('tenant_id', $this->tenantId())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::query()
            ->where('tenant_id', $this->tenantId())
            ->with('roles')
            ->findOrFail($id);

        $validated = $request->validate($this->rules($user->id), $this->messages());

        $role = Role::query()
            ->where('id', $validated['role_id'])
            ->where('guard_name', 'vendor')
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $branch = Branch::query()
            ->where('id', $validated['branch_id'])
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        DB::transaction(function () use ($validated, $role, $branch, $user) {
            $payload = [
                'branch_id' => $branch->id,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'gender' => $validated['gender'],
                'status' => (bool) ($validated['status'] ?? false),
            ];

            if (!empty($validated['password'])) {
                $payload['password'] = Hash::make($validated['password']);
            }

            $user->update($payload);
            $user->syncRoles([$role->name]);
        });

        return redirect()
            ->route('vendor.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        $user = User::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);

        if ((int) optional(auth('vendor')->user())->id === (int) $user->id) {
            return back()->withErrors([
                'general' => 'You cannot delete your own account.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $user->syncRoles([]);
            $user->delete();
        });

        return redirect()
            ->route('vendor.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'not_regex:/^[0-9]+$/'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($ignoreId),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $this->tenantId()))
                    ->ignore($ignoreId),
            ],
            'branch_id' => ['required', 'integer'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'role_id' => ['required', 'integer'],
            'status' => ['nullable', 'boolean'],
            'password' => [
                $ignoreId ? 'nullable' : 'required',
                'string',
                'min:6',
                'confirmed',
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.not_regex' => 'The name cannot contain only numbers.',
            'username.required' => 'Username is required.',
            'username.unique' => 'This username already exists.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email already exists.',
            'branch_id.required' => 'Branch is required.',
            'role_id.required' => 'Role is required.',
            'gender.required' => 'Gender is required.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    private function tenantId(): int
    {
        return (int) optional(auth('vendor')->user())->tenant_id;
    }
}