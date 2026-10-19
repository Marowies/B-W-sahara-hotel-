<?php

// Synthetic customers and bookings for the account/booking browser tests. Idempotent: removes only
// rows belonging to fixture.guest.*@example.invalid before inserting them again.
//   A  upcoming (processing, bank transfer pending), past (completed, invoice), future cancelled
//   B  no bookings (empty state)
//   C  past stay + future cancelled stay (no upcoming; latest is the cancelled one)
//   D  three upcoming created out of date order (check-in today first) + a cancelled one
//   E  booking without a booking-room row
use Illuminate\Support\Facades\DB;

require __DIR__ . '/bootstrap.php';

$room = DB::table('ht_rooms')->orderBy('id')->first(['id', 'name', 'price']);
if (! $room) {
    fwrite(STDERR, "Seed rooms first (php artisan db:seed --class=RoomSeeder on the fixture database).\n");
    exit(1);
}
$day = fn (int $offset) => now()->addDays($offset)->toDateString();
$at = fn (int $minutesAgo) => now()->subMinutes($minutesAgo)->toDateTimeString();
$password = password_hash('fixture-only-password', PASSWORD_BCRYPT);

DB::transaction(function () use ($room, $day, $at, $password): void {
    $old = DB::table('ht_customers')->where('email', 'like', 'fixture.guest.%@example.invalid')->pluck('id');
    $bookings = DB::table('ht_bookings')->whereIn('customer_id', $old)->get(['id', 'payment_id']);
    DB::table('ht_booking_rooms')->whereIn('booking_id', $bookings->pluck('id'))->delete();
    DB::table('ht_booking_addresses')->whereIn('booking_id', $bookings->pluck('id'))->delete();
    DB::table('ht_invoices')->whereIn('reference_id', $bookings->pluck('id'))->where('reference_type', 'Botble\\Hotel\\Models\\Booking')->delete();
    DB::table('payments')->whereIn('id', $bookings->pluck('payment_id')->filter())->delete();
    DB::table('ht_bookings')->whereIn('id', $bookings->pluck('id'))->delete();
    DB::table('hotel_auth_tokens')->whereIn('customer_id', $old)->delete();
    DB::table('ht_customers')->whereIn('id', $old)->delete();

    $customer = fn (string $key, string $first, string $last, array $extra = []) => DB::table('ht_customers')->insertGetId($extra + [
        'first_name' => $first, 'last_name' => $last, 'email' => "fixture.guest.$key@example.invalid", 'password' => $password,
        'confirmed_at' => $at(0), 'created_at' => $at(0), 'updated_at' => $at(0),
    ]);
    $booking = function (int $customerId, string $number, string $status, ?int $start, int $createdMinutesAgo, ?array $payment = null) use ($room, $day, $at): int {
        $paymentId = $payment ? DB::table('payments')->insertGetId([
            'currency' => 'USD', 'charge_id' => 'FIXTURE-CHARGE-' . $number, 'payment_channel' => $payment[0], 'amount' => $room->price * 2,
            'status' => $payment[1], 'payment_type' => 'direct', 'customer_id' => $customerId, 'customer_type' => 'Botble\\Hotel\\Models\\Customer',
            'created_at' => $at($createdMinutesAgo), 'updated_at' => $at($createdMinutesAgo),
        ]) : null;
        $id = DB::table('ht_bookings')->insertGetId([
            'amount' => $room->price * 2, 'sub_total' => $room->price * 2, 'tax_amount' => 0, 'currency_id' => 1, 'payment_id' => $paymentId,
            'customer_id' => $customerId, 'transaction_id' => md5($number), 'status' => $status, 'number_of_guests' => 2, 'number_of_children' => 0,
            'booking_number' => $number, 'created_at' => $at($createdMinutesAgo), 'updated_at' => $at($createdMinutesAgo),
        ]);
        if ($start !== null) {
            DB::table('ht_booking_rooms')->insert(['booking_id' => $id, 'room_id' => $room->id, 'price' => $room->price, 'currency_id' => 1, 'number_of_rooms' => 1,
                'start_date' => $day($start), 'end_date' => $day($start + 2), 'room_name' => $room->name, 'created_at' => $at(0), 'updated_at' => $at(0)]);
        }
        DB::table('ht_booking_addresses')->insert(['booking_id' => $id, 'first_name' => 'Fixture', 'last_name' => 'Guest', 'phone' => '+00 0000 0000',
            'email' => 'fixture.guest@example.invalid', 'created_at' => $at(0), 'updated_at' => $at(0)]);
        if ($payment && $payment[1] === 'completed') {
            DB::table('ht_invoices')->insert(['customer_id' => $customerId, 'payment_id' => $paymentId, 'reference_type' => 'Botble\\Hotel\\Models\\Booking',
                'reference_id' => $id, 'code' => 'INV-' . $number, 'sub_total' => $room->price * 2, 'amount' => $room->price * 2, 'status' => 'completed',
                'customer_name' => 'Fixture Guest', 'customer_email' => 'fixture.guest@example.invalid', 'customer_phone' => '+00 0000 0000',
                'customer_address' => 'Fixture address', 'created_at' => $at(0), 'updated_at' => $at(0)]);
        }

        return $id;
    };

    $a = $customer('a', 'Fixture', 'Guest A', ['phone' => '+00 0000 0001', 'country' => 'Fixture Country', 'city' => 'Fixture City']);
    $booking($a, 'FIXTURE-1001', 'processing', 30, 3, ['bank_transfer', 'pending']);
    $booking($a, 'FIXTURE-1002', 'completed', -60, 2, ['cod', 'completed']);
    $booking($a, 'FIXTURE-1003', 'cancelled', 90, 1);
    $customer('b', 'Sample', 'Guest B');
    $c = $customer('c', 'Fixture', 'Guest C');
    $booking($c, 'FIXTURE-C-PAST', 'completed', -20, 50);
    $booking($c, 'FIXTURE-C-CANCELLED', 'cancelled', 15, 10);
    $d = $customer('d', 'Fixture', 'Guest D');
    $booking($d, 'FIXTURE-D-LATER', 'pending', 40, 5);
    $booking($d, 'FIXTURE-D-TODAY', 'processing', 0, 30);
    $booking($d, 'FIXTURE-D-PENDING', 'pending', 10, 20);
    $booking($d, 'FIXTURE-D-CANCELLED', 'cancelled', 0, 1);
    $e = $customer('e', 'Fixture', 'Guest E');
    $booking($e, 'FIXTURE-E-NOROOM', 'pending', null, 5);
});

echo json_encode(['customers' => DB::table('ht_customers')->where('email', 'like', 'fixture.guest.%@example.invalid')->pluck('email'), 'password' => 'fixture-only-password']), "\n";
