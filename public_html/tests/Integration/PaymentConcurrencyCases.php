<?php

test('Payment: concurrent Stripe deliveries commit one receipt and one fulfillment', function () use ($db, $autoload): void {
    if ($db->getDriverName() !== 'mysql') { throw new RuntimeException('This test requires InnoDB.'); }
    $db->statement('CREATE TABLE payment_test_completions (payment_identity VARCHAR(255)) ENGINE=InnoDB');
    $db->table('payments')->insert(['id' => 9000, 'charge_id' => 'pi_concurrent', 'status' => 'pending', 'order_id' => 1, 'amount' => 200, 'currency' => 'USD', 'payment_channel' => 'stripe']);
    $processes = [];$startAt = microtime(true) + 2;
    try {
        for ($index = 0; $index < 6; $index++) {
            $command = [PHP_BINARY, __DIR__ . '/run.php', $autoload, $GLOBALS['argv'][2], 'worker', (string) $index, (string) $startAt, 'stripe-webhook'];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            check(is_resource($process), 'Cannot start webhook worker.');fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $outcomes = [];
        foreach ($processes as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);$error = stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
            check(proc_close($process) === 0, 'Webhook worker failed: ' . $error);
            $outcomes[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR)['status'];
        }
        $fulfillments = $db->table('payment_test_completions')->count();
        $receipts = $db->table('payment_webhook_receipts')->where('event_id', 'evt_concurrent')->count();
        $GLOBALS['integrationEvidence']['stripe_concurrency'] = ['statuses' => $outcomes, 'fulfillments' => $fulfillments, 'receipts' => $receipts];
        check($outcomes === array_fill(0, 6, 204) && $fulfillments === 1 && $receipts === 1, 'Concurrent delivery duplicated fulfillment.');
    } finally {
        $db->table('payments')->where('id', 9000)->delete();
        $db->table('payment_webhook_receipts')->where('event_id', 'evt_concurrent')->delete();
        $db->statement('DROP TABLE payment_test_completions');
    }
});
