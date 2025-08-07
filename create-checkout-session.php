<?php
session_start();
require 'stripe_config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    http_response_code(403);
    exit('Unauthorized access');
}

$user_id = $_SESSION['user_id'];
$email = $_SESSION['user_email'];

$plan = $_POST['plan'] ?? 'pro';
$price_id = ($plan === 'starter') ? $starter_price_id : $pro_price_id;

$checkout_session = \Stripe\Checkout\Session::create([
    'customer_email' => $email,
    'line_items' => [[
        'price' => $price_id,
        'quantity' => 1,
    ]],
    'mode' => 'subscription',
    'allow_promotion_codes' => true,
    'success_url' => $domain . '/upgrade-success.php?session_id={CHECKOUT_SESSION_ID}&plan='. $plan,
    'cancel_url' => $domain . '/upgrade.php',
    'metadata' => [
        'user_id' => $user_id,
        'selected_plan' => $plan
    ]
]);

header("Location: " . $checkout_session->url);
exit;
