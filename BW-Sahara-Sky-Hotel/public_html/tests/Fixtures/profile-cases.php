<?php

// Customer profile save (real controller and form request) on the fixture database.
// Regression: an empty optional date of birth was stored as today's date. The SafeContent cast stores blank as "".
// Run after seed-account-fixture.php: php tests/Fixtures/profile-cases.php
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Hotel\Http\Controllers\Front\Customers\PublicController;
use Botble\Hotel\Http\Requests\EditAccountRequest;
use Botble\Hotel\Models\Customer;

require __DIR__ . '/bootstrap.php';

$customer = Customer::query()->where('email', 'fixture.guest.b@example.invalid')->firstOrFail();
auth('customer')->setUser($customer);
$save = function (array $data) use ($customer): Customer {
    $request = EditAccountRequest::create('/customer/edit-account', 'POST', $data + ['first_name' => 'Sample', 'last_name' => 'Guest B']);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();
    (new PublicController())->postEditAccount($request, app(BaseHttpResponse::class));

    return $customer->fresh();
};

$failures = 0;
$checks = [
    'blank date of birth stays empty' => fn () => $save(['dob' => ''])->dob === null || $customer->fresh()->dob === '',
    'missing date of birth stays empty' => fn () => $save([])->dob === null || $customer->fresh()->dob === '',
    'entered date of birth is saved' => fn () => (string) $save(['dob' => '1990-05-17'])->dob === '1990-05-17' || str_starts_with((string) $save(['dob' => '1990-05-17'])->dob, '1990-05-17'),
    'clearing a saved date of birth empties it' => fn () => $save(['dob' => ''])->dob === null || $customer->fresh()->dob === '',
    'other fields are saved' => fn () => $save(['city' => 'Fixture City B'])->city === 'Fixture City B',
];
foreach ($checks as $name => $check) {
    $ok = (bool) $check();
    $failures += $ok ? 0 : 1;
    printf("%s %s\n", $ok ? 'PASS' : 'FAIL', $name);
}
exit($failures ? 1 : 0);
