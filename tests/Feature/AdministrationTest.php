<?php

use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createAdministrationOrganization(string $name = 'Administration Office'): Organization
{
    return Organization::query()->create([
        'name' => $name,
        'slug' => str()->slug($name).'-'.str()->random(6),
    ]);
}

function assignAdministrationMembership(Organization $organization, User $user, array $membership = []): void
{
    $organization->users()->attach($user->id, [
        'status' => $membership['status'] ?? 'active',
        'department_id' => $membership['department_id'] ?? null,
        'joined_at' => now(),
    ]);
}

function createAdministrationAdmin(): array
{
    $organization = createAdministrationOrganization();
    $admin = User::factory()->create(['email' => 'admin@example.test']);
    assignAdministrationMembership($organization, $admin);
    config(['app.administrator_email' => $admin->email]);

    return [$organization, $admin];
}

it('denies administration pages to members without an administrator role', function () {
    $organization = createAdministrationOrganization();
    $user = User::factory()->create();
    assignAdministrationMembership($organization, $user);

    $this->actingAs($user)
        ->get(route('admin.departments.index'))
        ->assertForbidden();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.departments.index'));
});

it('lets the configured administrator update the organization profile and see its dynamic name', function () {
    [$organization, $admin] = createAdministrationAdmin();

    $this->actingAs($admin)
        ->put(route('admin.organization.update'), [
            'name' => 'Central Public Records Office',
            'description' => 'Official correspondence registry',
            'address' => '10 Civic Square',
            'phone' => '+1 555 0100',
            'email' => 'records@example.test',
        ])
        ->assertRedirect(route('admin.organization.edit'));

    $this->assertDatabaseHas('organizations', [
        'id' => $organization->id,
        'name' => 'Central Public Records Office',
        'description' => 'Official correspondence registry',
        'address' => '10 Civic Square',
    ]);
    $this->get(route('dashboard'))->assertOk()->assertSee('Central Public Records Office');
    $this->get(route('admin.organization.edit'))->assertOk()->assertSee('Central Public Records Office');
});

it('manages departments without deleting departments referenced by users or documents', function () {
    [$organization, $admin] = createAdministrationAdmin();
    $departmentPayload = [
        'name' => 'Registry Operations',
        'code' => 'REGOPS',
        'description' => 'Correspondence processing',
        'is_active' => '1',
    ];

    $this->actingAs($admin)
        ->post(route('admin.departments.store'), $departmentPayload)
        ->assertRedirect(route('admin.departments.index'));

    $department = Department::query()->where('code', 'REGOPS')->firstOrFail();
    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'organization_id' => $organization->id,
        'description' => 'Correspondence processing',
    ]);
    $this->get(route('documents.create', 'incoming'))->assertOk()->assertSee('Registry Operations');

    $this->patch(route('admin.departments.status', $department->id), ['is_active' => '0'])
        ->assertRedirect(route('admin.departments.index'));
    expect($department->fresh()->is_active)->toBeFalse();

    $user = User::factory()->create();
    $organization->users()->attach($user->id, ['status' => 'active', 'department_id' => $department->id]);
    DB::table('documents')->insert([
        'organization_id' => $organization->id,
        'sending_department_id' => $department->id,
        'created_by' => $admin->id,
        'reference_number' => 'IN-ADMIN-001',
        'direction' => 'incoming',
        'status' => 'registered',
        'priority' => 'normal',
        'subject' => 'Department reference safety',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->delete(route('admin.departments.destroy', $department->id))
        ->assertRedirect(route('admin.departments.index'))
        ->assertSessionHasErrors('department');
    $this->assertDatabaseHas('departments', ['id' => $department->id]);

    $unusedDepartment = $organization->departments()->create(['name' => 'Unused Office', 'is_active' => true]);
    $this->delete(route('admin.departments.destroy', $unusedDepartment->id))
        ->assertRedirect(route('admin.departments.index'));
    $this->assertDatabaseMissing('departments', ['id' => $unusedDepartment->id]);
});

it('creates users with active organization memberships, roles, and hashed passwords', function () {
    [$organization, $admin] = createAdministrationAdmin();
    $department = $organization->departments()->create(['name' => 'Records Unit', 'is_active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Records Administrator',
            'email' => 'records-admin@example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'department_id' => $department->id,
            'membership_status' => 'active',
            'is_active' => '1',
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.users.index'));

    $newUser = User::query()->where('email', 'records-admin@example.test')->firstOrFail();
    $membership = $organization->memberships()->where('user_id', $newUser->id)->firstOrFail();

    expect(Hash::check('correct-horse-battery', $newUser->password))->toBeTrue()
        ->and($membership->status)->toBe('active')
        ->and($membership->department_id)->toBe($department->id)
        ->and($newUser->isAdministrator())->toBeTrue();
    $this->assertDatabaseHas('organization_user_role', [
        'organization_user_id' => $membership->id,
        'role_id' => $membership->roles()->where('slug', 'admin')->value('roles.id'),
    ]);
});

it('requires new users to have an active organization membership', function () {
    [, $admin] = createAdministrationAdmin();

    $this->actingAs($admin)
        ->from(route('admin.users.create'))
        ->post(route('admin.users.store'), [
            'name' => 'Pending Officer',
            'email' => 'pending@example.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'department_id' => '',
            'membership_status' => 'inactive',
            'is_active' => '1',
            'role' => 'user',
        ])
        ->assertRedirect(route('admin.users.create'))
        ->assertSessionHasErrors('membership_status');

    $this->assertDatabaseMissing('users', ['email' => 'pending@example.test']);
});

it('deactivates users and refuses their subsequent login', function () {
    [$organization, $admin] = createAdministrationAdmin();
    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'department_id' => '',
            'membership_status' => 'inactive',
            'is_active' => '0',
            'role' => 'user',
        ])
        ->assertSessionHasErrors('role');
    $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);

    $user = User::factory()->create([
        'name' => 'Former Officer',
        'email' => 'former@example.test',
        'password' => 'secure-password',
    ]);
    assignAdministrationMembership($organization, $user);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'department_id' => '',
            'membership_status' => 'inactive',
            'is_active' => '0',
            'role' => 'user',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    $this->assertDatabaseHas('organization_user', [
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'status' => 'inactive',
    ]);

    auth()->logout();
    $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'secure-password',
    ])->assertRedirect(route('login'));
    $this->assertGuest();

    $this->actingAs($user->fresh())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
    $this->assertGuest();
});
