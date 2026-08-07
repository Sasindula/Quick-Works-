<?php
session_start();
require_once("config/Database.php");

$db = new Database();
$conn = $db->connect();

/* LOGIN CHECK */
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

/* JOB ID CHECK */
if(!isset($_GET['job_id'])){
    header("Location: my_bookings.php");
    exit();
}

$job_id = intval($_GET['job_id']);
$user_id = $_SESSION['user']['user_id'];

/* GET JOB DETAILS */
$stmt = $conn->prepare("
    SELECT jr.*, u.name as worker_name, u.address, wp.skills, wp.hourly_rate
    FROM job_requests jr
    JOIN users u ON jr.worker_id = u.user_id
    JOIN worker_profiles wp ON jr.worker_id = wp.worker_id
    WHERE jr.job_id = ? AND jr.customer_id = ?
");
$stmt->bind_param("ii", $job_id, $user_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if(!$job){
    header("Location: my_bookings.php");
    exit();
}

if(!in_array($job['status'], ['ACCEPTED', 'COMPLETED'])){
    header("Location: my_bookings.php?msg=" . urlencode("You can only pay for jobs that have been accepted."));
    exit();
}

$msg = "";

$rate = $job['hourly_rate'];
$calculated_total = $job['duration'] * $rate;

/* PAYMENT INSERT (Simulated) */
if(isset($_POST['pay_process'])){

    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $status = "PAID"; // Mockup: always successful

    $pay_stmt = $conn->prepare("
        INSERT INTO payments (job_id, amount, payment_method, payment_status, payment_date)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $pay_stmt->bind_param("idss", $job_id, $amount, $method, $status);
    
    if($pay_stmt->execute()){
        // Notify worker about payment
        $notif = $conn->prepare("
            INSERT INTO notifications (user_id, message, type, job_id, is_read, created_at)
            VALUES (?, ?, 'payment_received', ?, 0, NOW())
        ");
        $notif_msg = "Payment of Rs " . number_format($amount, 0) . " received for job #$job_id";
        $notif->bind_param("isi", $job['worker_id'], $notif_msg, $job_id);
        $notif->execute();

        header("Location: my_bookings.php?msg=Payment Successful!");
        exit();
    } else {
        $msg = "Payment failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - QuickWorks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .checkout-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .payment-method-btn {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }
        .payment-method-btn.active {
            border-color: #0d6efd;
            background-color: #f0f7ff;
            color: #0d6efd;
        }
        .payment-method-btn input {
            display: none;
        }
        .stripe-input {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            background: #f9fafb;
            font-size: 1rem;
            width: 100%;
            margin-bottom: 15px;
        }
        .stripe-input:focus {
            outline: none;
            border-color: #0d6efd;
            background: white;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #4b5563;
        }
        .summary-total {
            border-top: 2px dashed #e5e7eb;
            margin-top: 20px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            font-weight: 700;
            font-size: 1.25rem;
            color: #111827;
        }
        .processing-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.9);
            z-index: 1000;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .spinner-grow {
            width: 3rem;
            height: 3rem;
            color: #0d6efd;
        }
    </style>
</head>
<body>

<div class="processing-overlay" id="processingOverlay">
    <div class="spinner-grow mb-3" role="status"></div>
    <h4 class="fw-bold text-dark">Processing Payment...</h4>
    <p class="text-muted">Please do not refresh the page.</p>
</div>

<div class="checkout-container">
    <div class="mb-4">
        <a href="my_bookings.php" class="text-decoration-none text-muted fw-medium">
            <i class="bi bi-arrow-left me-1"></i> Back to My Bookings
        </a>
    </div>
    <div class="row g-4">
        <!-- Left: Payment Details -->
        <div class="col-lg-7">
            <h4 class="fw-bold mb-4">Secure Checkout</h4>
            
            <div class="card p-4">
                <form id="paymentForm" method="POST">
                    <input type="hidden" name="amount" value="<?php echo $calculated_total; ?>">
                    <input type="hidden" name="pay_process" value="1">
                    
                    <!-- Payment Method Toggle -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">PAYMENT METHOD</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="method" id="method_card" value="STRIPE" checked onclick="togglePayment('card')">
                                <label class="btn btn-outline-primary w-100 py-3" for="method_card">
                                    <i class="bi bi-credit-card me-1"></i> Card
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="method" id="method_cash" value="CASH" onclick="togglePayment('cash')">
                                <label class="btn btn-outline-success w-100 py-3" for="method_cash">
                                    <i class="bi bi-cash-stack me-1"></i> Cash
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Mockup Card Section -->
                    <div id="stripeFields">
                        <h6 class="fw-semibold mb-3">Card Details (Simulated Stripe)</h6>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">CARD NUMBER</label>
                            <input type="text" id="cardNum" class="stripe-input" placeholder="4242 4242 4242 4242" maxlength="19">
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">EXPIRY DATE</label>
                                <input type="text" id="cardExp" class="stripe-input" placeholder="MM / YY" maxlength="7">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold text-muted">CVC</label>
                                <input type="text" id="cardCvc" class="stripe-input" placeholder="123" maxlength="3">
                            </div>
                        </div>
                    </div>

                    <!-- Cash Section -->
                    <div id="cashFields" style="display:none;">
                        <div class="alert alert-info border-0" style="border-radius:12px;">
                            <i class="bi bi-info-circle me-2"></i> You have chosen to pay with <strong>Cash</strong>. 
                            Please hand over the amount to the worker after the service is completed.
                        </div>
                    </div>



                    <button type="submit" id="payBtn" class="btn btn-primary w-100 py-3 fw-bold mt-3" style="border-radius: 12px; font-size: 1.1rem;">
                        <i class="bi bi-lock-fill me-2"></i>Pay Rs <?php echo number_format($calculated_total, 0); ?>
                    </button>

                    <p class="text-center text-muted small mt-3">
                        <i class="bi bi-shield-check me-1"></i> Your payment is secured with 256-bit encryption.
                    </p>
                </form>
            </div>
        </div>

        <!-- Right: Order Summary -->
        <div class="col-lg-5">
            <h4 class="fw-bold mb-4">Order Summary</h4>
            <div class="card p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width: 50px; height: 50px; background: #0d6efd; color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        <?php echo substr($job['worker_name'], 0, 1); ?>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($job['worker_name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($job['skills']); ?></small>
                    </div>
                </div>

                <div class="summary-item">
                    <span>Base Rate</span>
                    <span>Rs <?php echo number_format($rate, 0); ?> / <?php echo strtolower(rtrim($job['duration_type'], 'S')); ?></span>
                </div>
                <div class="summary-item">
                    <span>Requested Duration</span>
                    <span><?php echo $job['duration'] . ' ' . ucfirst(strtolower($job['duration_type'])); ?></span>
                </div>
                <div class="summary-item">
                    <span>Platform Fee</span>
                    <span class="text-success">Free</span>
                </div>
                <div class="summary-item">
                    <span>Taxes</span>
                    <span>Included</span>
                </div>

                <div class="summary-total">
                    <span>Total</span>
                    <span>Rs <?php echo number_format($calculated_total, 0); ?></span>
                </div>

                <div class="mt-4 p-3 bg-light rounded-3">
                    <div class="d-flex gap-2">
                        <i class="bi bi-info-circle text-primary"></i>
                        <small class="text-muted">
                            By paying, you agree to QuickWorks terms and conditions regarding service delivery and refunds.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePayment(type) {
    const stripeFields = document.getElementById('stripeFields');
    const cashFields = document.getElementById('cashFields');
    const payBtn = document.getElementById('payBtn');
    const cardInputs = [document.getElementById('cardNum'), document.getElementById('cardExp'), document.getElementById('cardCvc')];

    if(type === 'card') {
        stripeFields.style.display = 'block';
        cashFields.style.display = 'none';
        payBtn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Pay Rs <?php echo number_format($calculated_total, 0); ?>';
        payBtn.className = 'btn btn-primary w-100 py-3 fw-bold mt-3';
        cardInputs.forEach(input => input.setAttribute('required', 'required'));
    } else {
        stripeFields.style.display = 'none';
        cashFields.style.display = 'block';
        payBtn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Confirm Cash Payment';
        payBtn.className = 'btn btn-success w-100 py-3 fw-bold mt-3';
        cardInputs.forEach(input => input.removeAttribute('required'));
    }
}

// Mockup Payment Simulation
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const overlay = document.getElementById('processingOverlay');
    overlay.style.display = 'flex';
    
    // Simulate secure processing delay
    setTimeout(() => {
        this.submit();
    }, 2000);
});

// Card number formatting
document.getElementById('cardNum').addEventListener('input', function(e) {
    let target = e.target;
    let position = target.selectionEnd;
    let length = target.value.length;
    target.value = target.value.replace(/[^\d]/g, '').replace(/(.{4})/g, '$1 ').trim();
    target.selectionEnd = position + (target.value.length - length);
});

// Expiry date formatting
document.getElementById('cardExp').addEventListener('input', function(e) {
    let target = e.target;
    if (target.value.length === 2 && !target.value.includes('/')) {
        target.value += ' / ';
    }
});

// Initialize with Card
togglePayment('card');
</script>

</body>
</html>
