<?php

use Botble\Hotel\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class () extends Migration {
    public function up(): void
    {
        $customers = Customer::query()->whereNull('password')->get();

        foreach ($customers as $customer) {
            $customer->password = Hash::make(Str::random(32));
            $customer->save();
        }
    }
};
