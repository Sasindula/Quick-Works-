<?php
session_start();
require_once("config/Database.php");
require_once("config/Stripe.php");

if(!isset($_SESSION['user']) || !isset($_POST['job_id'])) {
    die("Unauthorized");
}

$job_id = intval($_POST['job_id']);
$amount = floatval($_POST['amount']);
$worker_name = $_POST['worker_name'];

// Stripe API Checkout Session creation using cURL
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);

$data = [
    'payment_method_types[]' => 'card',
    'line_items[0][price_data][currency]' => 'lkr',
    'line_items[0][price_data][product_data][name]' => 'Service by ' . $worker_name,
    'line_items[0][price_data][unit_amount]' => $amount * 100, // Stripe uses cents/cents equivalent
    'line_items[0][quantity]' => 1,
    'mode' => 'payment',
    'success_url' => STRIPE_SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}&job_id=' . $job_id . '&amount=' . $amount,
    'cancel_url' => STRIPE_CANCEL_URL . '?job_id=' . $job_id,
];

curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

$session = json_decode($response, true);

if(isset($session['url'])) {
    header("Location: " . $session['url']);
} else {
    echo "Stripe Error: " . ($session['error']['message'] ?? 'Unable to create session');
    echo "<br><br><b>Note:</b> You must put your real Stripe Test Keys in <code>config/Stripe.php</code> for this to work.";
}
?>
