<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $organization = $this->currentOrganization($request);
        $memberships = $organization->memberships()
            ->with(['user', 'department', 'roles'])
            ->orderBy('id')
            ->paginate(15);

        return view('administration.users.index', compact('memberships', 'organization'));
    }

    public function create(Request $request): View
    {
        $organization = $this->currentOrganization($request);

        return view('administration.users.form', [
            'organization' => $organization,
            'membership' => new OrganizationMembership(['status' => 'active']),
            'user' => new User(['is_active' => true]),
            'departments' => $organization->departments()->where('is_active', true)->orderBy('name')->get(),
            'isAdmin' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $this->currentOrganization($request);
        $data = $this->validatedUser($request);
        $membershipData = $this->membershipData($data);
        unset($data['department_id'], $data['membership_status'], $data['role']);

        $user = User::query()->create($data);
        $membership = $organization->memberships()->create([
            ...$membershipData,
            'user_id' => $user->id,
            'joined_at' => $membershipData['status'] === 'active' ? now() : null,
        ]);
        $this->syncAdminRole($membership, $request->string('role')->toString(), $request->user()->id);

        return redirect()->route('admin.users.index')->with('status', 'User added to the organization.');
    }

    public function edit(Request $request, int $user): View
    {
        $organization = $this->currentOrganization($request);
        $membership = $organization->memberships()->with(['user', 'department', 'roles'])->where('user_id', $user)->firstOrFail();

        return view('administration.users.form', [
            'organization' => $organization,
            'membership' => $membership,
            'user' => $membership->user,
            'departments' => $organization->departments()->orderBy('name')->get(),
            'isAdmin' => $membership->roles->contains('slug', 'admin')
                || $membership->user->email === config('app.administrator_email'),
        ]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $organization = $this->currentOrganization($request);
        $membership = $organization->memberships()->with(['user', 'roles'])->where('user_id', $user)->firstOrFail();
        $data = $this->validatedUser($request, $membership->user);
        $roleSlug = $data['role'];
        $membershipData = $this->membershipData($data);
        unset($data['department_id'], $data['membership_status'], $data['role'], $data['password']);

        $isBootstrapAdmin = $membership->user->email === config('app.administrator_email');
        $isAdminRole = $membership->roles->contains('slug', 'admin');
        $willRemainAdmin = $roleSlug === 'admin' && $data['is_active'] && $membershipData['status'] === 'active';

        if ($isBootstrapAdmin && (! $willRemainAdmin || $request->input('email') !== $membership->user->email)) {
            return back()->withInput()->withErrors([
                'role' => 'The configured bootstrap administrator cannot be renamed, deactivated, or demoted.',
            ]);
        }

        if ($isAdminRole && ! $willRemainAdmin && ! $this->anotherActiveAdministratorExists($organization, $membership)) {
            return back()->withInput()->withErrors([
                'role' => 'At least one active administrator must remain in the organization.',
            ]);
        }

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $membership->user->update($data);
        $membership->update($membershipData);
        $this->syncAdminRole($membership, $roleSlug, $request->user()->id);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    private function currentOrganization(Request $request): Organization
    {
        return $request->user()->currentOrganization() ?? abort(404);
    }

    /** @return array<string, mixed> */
    private function validatedUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where(fn ($query) => $query
                    ->where('organization_id', $request->user()->currentOrganization()?->id)
                    ->where(fn ($departmentQuery) => $departmentQuery
                        ->where('is_active', true)
                        ->when($user?->memberships()->where('organization_id', $request->user()->currentOrganization()?->id)->value('department_id'), fn ($departmentQuery, $departmentId) => $departmentQuery
                            ->orWhere('id', $departmentId)))),
            ],
            'membership_status' => ['required', Rule::in($user ? ['active', 'inactive'] : ['active'])],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);
    }

    /** @param array<string, mixed> $data
     * @return array{department_id: int|null, status: string}
     */
    private function membershipData(array $data): array
    {
        return [
            'department_id' => $data['department_id'] ?? null,
            'status' => $data['membership_status'],
        ];
    }

    private function syncAdminRole(OrganizationMembership $membership, string $role, int $assignedBy): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrator', 'description' => 'Organization administration access', 'is_system' => true],
        );
        $hasRole = $membership->roles()->where('roles.id', $adminRole->id)->exists();

        if ($role === 'admin' && ! $hasRole) {
            $membership->roles()->attach($adminRole->id, ['assigned_by' => $assignedBy, 'assigned_at' => now()]);
        } elseif ($role !== 'admin' && $hasRole) {
            $membership->roles()->detach($adminRole->id);
        }
    }

    private function anotherActiveAdministratorExists(Organization $organization, OrganizationMembership $except): bool
    {
        $bootstrapAdminExists = $organization->memberships()
            ->where('status', 'active')
            ->whereHas('user', fn ($query) => $query
                ->where('email', config('app.administrator_email'))
                ->where('is_active', true))
            ->exists();

        return $bootstrapAdminExists || $organization->memberships()
            ->where('id', '!=', $except->id)
            ->where('status', 'active')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))
            ->exists();
    }
}
