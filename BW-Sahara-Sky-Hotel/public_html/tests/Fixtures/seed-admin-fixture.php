<?php

// Two disposable admin users for local admin checks (fixture database only):
//   fixture.admin@example.invalid    super user, as `php artisan cms:user:create` creates one
//   fixture.limited@example.invalid  role with booking/customer read access only, to check permission denial
// Password for both: fixture-only-password. Re-running replaces only these two users and the fixture role.
use Botble\ACL\Models\Role;
use Botble\ACL\Models\User;
use Botble\ACL\Services\ActivateUserService;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/bootstrap.php';

User::query()->whereIn('email', ['fixture.admin@example.invalid', 'fixture.limited@example.invalid'])->get()->each->delete();
Role::query()->where('slug', 'fixture-limited')->delete();

$activate = app(ActivateUserService::class);
$admin = User::query()->forceCreate(['first_name' => 'Fixture', 'last_name' => 'Admin', 'email' => 'fixture.admin@example.invalid',
    'username' => 'fixture-admin', 'password' => Hash::make('fixture-only-password'), 'super_user' => true, 'manage_supers' => true]);
$activate->activate($admin);

$role = Role::query()->forceCreate(['name' => 'Fixture limited', 'slug' => 'fixture-limited', 'description' => 'Local fixture role',
    'permissions' => ['booking.index' => true, 'customer.index' => true], 'created_by' => $admin->getKey(), 'updated_by' => $admin->getKey()]);
$limited = User::query()->forceCreate(['first_name' => 'Fixture', 'last_name' => 'Limited', 'email' => 'fixture.limited@example.invalid',
    'username' => 'fixture-limited', 'password' => Hash::make('fixture-only-password'), 'super_user' => false, 'manage_supers' => false]);
$activate->activate($limited);
$limited->roles()->sync([$role->getKey()]);
$limited->forceFill(['permissions' => $role->permissions])->save();

echo json_encode(['admin' => $admin->email, 'limited' => $limited->email, 'login' => route('access.login')]), "\n";
