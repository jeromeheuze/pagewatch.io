<?php
require 'stripe_config.php';
include './bin/dbconnect.php';

$payload = @file_get_contents("php://input");
file_put_contents('webhook_log.txt', $payload . PHP_EOL, FILE_APPEND);
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit('Invalid signature');
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $user_id = $session->metadata->user_id ?? null;
    $selected_plan = $session->metadata->selected_plan ?? null;

    if ($user_id && in_array($selected_plan, ['starter', 'pro'])) {
        $stmt = $DBcon->prepare("UPDATE users SET plan = ? WHERE id = ?");
        $stmt->bind_param("si", $selected_plan, $user_id);
        $stmt->execute();
    }
}

http_response_code(200);
