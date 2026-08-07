<?php
session_start();
require_once("config/Database.php");

$db = new Database();
$conn = $db->connect();

/* LOGIN CHECK */
if(!isset($_SESSION['user']['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['user_id'];

/* WORKER CHECK */
if(!isset($_GET['worker_id'])){
    header("Location: search.php");
    exit();
}

$worker_id = intval($_GET['worker_id']);

/* GET WORKER */
$stmt = $conn->prepare("
    SELECT wp.*, u.name, u.address, u.profile_image 
    FROM worker_profiles wp
    JOIN users u ON wp.worker_id = u.user_id
    WHERE wp.worker_id = ? AND u.status = 'ACTIVE'
");
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();

if(!$worker){
    header("Location: search.php");
    exit();
}

$msg = "";
$msg_type = "";

/* BOOKING SUBMIT */
if(isset($_POST['book'])){

    $description = trim($_POST['description']);
    $job_date = $_POST['job_date'];
    $selected_slots = isset($_POST['time_slots']) ? $_POST['time_slots'] : [];
    $payment_option = $_POST['payment_method'] ?? 'CASH';

    // Validate slots
    if(empty($selected_slots)){
        $msg = "Please select at least one time slot.";
        $msg_type = "danger";
    } else {
        // Validate each slot is in range 6-17
        $valid_slots = [];
        foreach($selected_slots as $slot){
            $slot = intval($slot);
            if($slot >= 6 && $slot <= 17){
                $valid_slots[] = $slot;
            }
        }

        if(empty($valid_slots)){
            $msg = "Invalid time slots selected.";
            $msg_type = "danger";
        } else {
            // Check if any selected slots are already BOOKED for this worker on this date
            $placeholders = implode(',', array_fill(0, count($valid_slots), '?'));
            $check_sql = "SELECT slot_hour FROM booking_time_slots 
                          WHERE worker_id = ? AND slot_date = ? AND slot_hour IN ($placeholders) AND status = 'BOOKED'";
            $check_stmt = $conn->prepare($check_sql);
            
            $types = "is" . str_repeat("i", count($valid_slots));
            $params = array_merge([$worker_id, $job_date], $valid_slots);
            $check_stmt->bind_param($types, ...$params);
            $check_stmt->execute();
            $booked_result = $check_stmt->get_result();

            if($booked_result->num_rows > 0){
                $booked_hours = [];
                while($brow = $booked_result->fetch_assoc()){
                    $h = $brow['slot_hour'];
                    $booked_hours[] = ($h < 12) ? $h . ':00 AM' : (($h == 12) ? '12:00 PM' : ($h - 12) . ':00 PM');
                }
                $msg = "The following slots are already booked: " . implode(', ', $booked_hours) . ". Please choose different slots.";
                $msg_type = "danger";
            } else {
                // Calculate duration from slot count
                $duration = count($valid_slots);
                $duration_type = 'HOURS';

                // Get service_id from worker's skills
                $skills = $worker['skills'] ?? '';
                $service_id = null;
                
                $service_res = $conn->query("SELECT service_id, service_name FROM services");
                while($s_row = $service_res->fetch_assoc()) {
                    if(stripos($skills, $s_row['service_name']) !== false) {
                        $service_id = $s_row['service_id'];
                        break;
                    }
                }
                
                if(!$service_id) {
                    $first_s = $conn->query("SELECT service_id FROM services LIMIT 1")->fetch_assoc();
                    $service_id = $first_s ? $first_s['service_id'] : null;
                }

                if(!$service_id) {
                    die("System error: No services found in database. Please contact admin.");
                }

                // Insert job request
                $insert = $conn->prepare("
                    INSERT INTO job_requests
                    (customer_id, worker_id, service_id, description, job_date, duration, duration_type, status, payment_option, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, NOW())
                ");
                $insert->bind_param("iiississ",
                    $user_id,
                    $worker_id,
                    $service_id,
                    $description,
                    $job_date,
                    $duration,
                    $duration_type,
                    $payment_option
                );

                if($insert->execute()){
                    $job_id = $insert->insert_id;

                    // Insert time slots
                    $slot_insert = $conn->prepare("
                        INSERT INTO booking_time_slots (job_id, worker_id, slot_date, slot_hour, status)
                        VALUES (?, ?, ?, ?, 'PENDING')
                    ");
                    foreach($valid_slots as $slot_hour){
                        $slot_insert->bind_param("iisi", $job_id, $worker_id, $job_date, $slot_hour);
                        $slot_insert->execute();
                    }

                    // Build time slot display for notification
                    $slot_labels = [];
                    sort($valid_slots);
                    foreach($valid_slots as $h){
                        $slot_labels[] = ($h < 12) ? $h . ':00 AM' : (($h == 12) ? '12:00 PM' : ($h - 12) . ':00 PM');
                    }
                    $slots_text = implode(', ', $slot_labels);

                    $customer_name = $_SESSION['user']['name'];

                    // CREATE NOTIFICATION FOR WORKER
                    $notif = $conn->prepare("
                        INSERT INTO notifications (user_id, message, type, job_id, is_read, created_at)
                        VALUES (?, ?, 'booking_request', ?, 0, NOW())
                    ");
                    $notif_msg = "New booking request from $customer_name for " . date('M d, Y', strtotime($job_date)) . " — Time slots: $slots_text";
                    $notif->bind_param("isi", $worker_id, $notif_msg, $job_id);
                    $notif->execute();

                    header("Location: my_bookings.php?msg=Job request sent successfully! You will be notified when the worker accepts.");
                    exit();

                } else {
                    $msg = "Booking failed. Please try again.";
                    $msg_type = "danger";
                }
            }
        }
    }
}

$current_page = 'search';
$page_title = 'Book Worker';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Worker - QuickWorks</title>
    <style>
        /* Time Slot Picker Styles */
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 12px;
        }
        @media (max-width: 576px) {
            .slot-grid { grid-template-columns: repeat(2, 1fr); }
        }
        .slot-item {
            position: relative;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s ease;
            user-select: none;
            background: #fff;
        }
        .slot-item:hover:not(.slot-booked) {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        .slot-item.slot-selected {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-color: #2563eb;
            color: #fff;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);
        }
        .slot-item.slot-selected .slot-time {
            color: #fff;
        }
        .slot-item.slot-selected .slot-status {
            color: rgba(255,255,255,0.85);
        }
        .slot-item.slot-pending {
            background: #fffbeb;
            border-color: #fbbf24;
        }
        .slot-item.slot-pending .slot-status {
            color: #d97706;
        }
        .slot-item.slot-booked {
            background: #fef2f2;
            border-color: #fca5a5;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .slot-item.slot-booked .slot-status {
            color: #dc2626;
        }
        .slot-time {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .slot-status {
            font-size: 0.75rem;
            font-weight: 500;
            color: #64748b;
        }
        .slot-check {
            position: absolute;
            top: 6px;
            right: 8px;
            font-size: 1rem;
            display: none;
        }
        .slot-item.slot-selected .slot-check {
            display: block;
            color: #fff;
        }

        /* Summary Card */
        .booking-summary {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #bae6fd;
            border-radius: 14px;
            padding: 20px;
            margin-top: 16px;
            transition: all 0.3s ease;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
        }
        .summary-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        .summary-value {
            font-size: 0.95rem;
            color: #0f172a;
            font-weight: 600;
        }
        .summary-total {
            border-top: 2px solid #7dd3fc;
            margin-top: 8px;
            padding-top: 10px;
        }
        .summary-total .summary-value {
            font-size: 1.2rem;
            color: #0369a1;
        }

        /* Slot loading state */
        .slot-loading {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        .slot-loading .spinner {
            display: inline-block;
            width: 36px; height: 36px;
            border: 3px solid #e2e8f0;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        .slot-empty {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        /* Legend */
        .slot-legend {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 14px;
            padding: 12px 16px;
            background: #f8fafc;
            border-radius: 10px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: #475569;
        }
        .legend-dot {
            width: 12px; height: 12px;
            border-radius: 4px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<?php require_once("includes/customer_navbar.php"); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        
        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" style="border-radius: 12px;" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Worker Info Card -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 60px; height: 60px; border-radius: 14px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.5rem; overflow: hidden;">
                        <?php if(!empty($worker['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($worker['profile_image']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?php echo strtoupper(substr($worker['name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($worker['name']); ?></h5>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                            <?php echo htmlspecialchars($worker['address'] ?? 'N/A'); ?>
                            &nbsp;•&nbsp;
                            <i class="bi bi-star-fill me-1" style="color: #f59e0b;"></i>
                            <?php echo number_format($worker['rating'], 1); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <div style="background: #f0fdf4; padding: 8px 16px; border-radius: 10px;">
                            <span class="fw-bold text-success">Rs <?php echo number_format($worker['hourly_rate'] ?? 0, 0); ?>/hr</span>
                        </div>
                    </div>
                </div>
                <!-- Skills -->
                <div class="mt-3">
                    <?php
                    $skills = explode(',', $worker['skills'] ?? '');
                    foreach($skills as $s):
                        $s = trim($s);
                        if($s):
                    ?>
                        <span style="display: inline-block; padding: 4px 12px; background: #eff6ff; color: #3b82f6; border-radius: 6px; font-size: 0.8rem; font-weight: 500; margin: 2px;">
                            <?php echo htmlspecialchars($s); ?>
                        </span>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Booking Form -->
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-calendar-plus me-2 text-primary"></i>Booking Details</h5>

                <form method="POST" id="bookingForm">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Job Description</label>
                        <textarea name="description" class="form-control" rows="4" required
                            placeholder="Describe the work you need done..." 
                            style="border-radius: 10px; border-color: #e2e8f0;"></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Preferred Date</label>
                        <input type="date" name="job_date" id="jobDate" class="form-control" required
                            min="<?php echo date('Y-m-d'); ?>"
                            style="border-radius: 10px; border-color: #e2e8f0;">
                    </div>

                    <!-- Time Slot Picker -->
                    <div class="mb-4">
                        <label class="form-label fw-medium">
                            <i class="bi bi-clock me-1 text-primary"></i>Select Time Slots
                            <span class="text-muted" style="font-weight: 400; font-size: 0.85rem;"> — select one or more</span>
                        </label>
                        
                        <div id="slotContainer">
                            <div class="slot-empty">
                                <i class="bi bi-calendar3" style="font-size: 2rem; color: #cbd5e1;"></i>
                                <p class="mt-2 mb-0">Please select a date first to see available time slots</p>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="slot-legend" id="slotLegend" style="display: none;">
                            <div class="legend-item">
                                <div class="legend-dot" style="background: #dcfce7; border: 2px solid #22c55e;"></div>
                                Available
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background: #fef3c7; border: 2px solid #f59e0b;"></div>
                                Pending
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background: #fee2e2; border: 2px solid #ef4444;"></div>
                                Booked
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background: linear-gradient(135deg, #3b82f6, #2563eb); border: 2px solid #2563eb;"></div>
                                Selected
                            </div>
                        </div>

                        <!-- Hidden inputs for selected slots (dynamically generated) -->
                        <div id="hiddenSlots"></div>
                    </div>

                    <!-- Booking Summary -->
                    <div class="booking-summary" id="bookingSummary" style="display: none;">
                        <h6 class="fw-bold mb-3" style="color: #0369a1;">
                            <i class="bi bi-receipt me-2"></i>Booking Summary
                        </h6>
                        <div class="summary-row">
                            <span class="summary-label">Selected Slots</span>
                            <span class="summary-value" id="summarySlots">—</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Duration</span>
                            <span class="summary-value" id="summaryDuration">—</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Rate</span>
                            <span class="summary-value">Rs <?php echo number_format($worker['hourly_rate'] ?? 0, 0); ?>/hr</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span class="summary-label" style="font-weight: 600; color: #0f172a;">Estimated Total</span>
                            <span class="summary-value" id="summaryTotal">—</span>
                        </div>
                    </div>

                    <div class="mb-4 mt-4">
                        <label class="form-label fw-medium">Payment Method</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_cash" value="CASH" checked autocomplete="off">
                                <label class="btn btn-outline-primary w-100 py-3" for="pay_cash">
                                    <i class="bi bi-cash me-1"></i> Cash
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="pay_card" value="CARD" autocomplete="off">
                                <label class="btn btn-outline-primary w-100 py-3" for="pay_card">
                                    <i class="bi bi-credit-card me-1"></i> Card
                                </label>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">Choose how you wish to pay the worker after the job is done.</small>
                    </div>

                    <button type="submit" name="book" id="bookBtn" class="btn btn-primary btn-lg w-100" disabled 
                        style="border-radius: 12px; font-weight: 600; padding: 14px;">
                        <i class="bi bi-check-circle me-2"></i>Confirm Booking
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

    </div><!-- /qw-content -->
</div><!-- /qw-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const workerId = <?php echo $worker_id; ?>;
const hourlyRate = <?php echo $worker['hourly_rate'] ?? 0; ?>;
let selectedSlots = new Set();
let slotsData = [];

// Date change -> load slots
document.getElementById('jobDate').addEventListener('change', function(){
    const date = this.value;
    if(!date) return;
    loadSlots(date);
});

function loadSlots(date){
    const container = document.getElementById('slotContainer');
    container.innerHTML = `
        <div class="slot-loading">
            <div class="spinner"></div>
            <p class="mt-2 mb-0">Loading available slots...</p>
        </div>
    `;
    selectedSlots.clear();
    updateSummary();

    fetch(`api/get_available_slots.php?worker_id=${workerId}&date=${date}`)
    .then(r => r.json())
    .then(data => {
        if(data.error){
            container.innerHTML = `<div class="slot-empty"><p class="text-danger">${data.error}</p></div>`;
            return;
        }

        slotsData = data.slots;
        renderSlots();
        document.getElementById('slotLegend').style.display = 'flex';
    })
    .catch(err => {
        container.innerHTML = `<div class="slot-empty"><p class="text-danger">Failed to load slots. Please try again.</p></div>`;
    });
}

function renderSlots(){
    const container = document.getElementById('slotContainer');
    let html = '<div class="slot-grid">';

    slotsData.forEach(slot => {
        const isBooked = slot.status === 'booked';
        const isPending = slot.status === 'pending';
        const isSelected = selectedSlots.has(slot.hour);
        
        let cls = 'slot-item';
        if(isBooked) cls += ' slot-booked';
        else if(isSelected) cls += ' slot-selected';
        else if(isPending) cls += ' slot-pending';

        let statusText = '';
        if(isBooked) statusText = '<i class="bi bi-lock-fill me-1"></i>Booked';
        else if(isPending) statusText = '<i class="bi bi-hourglass-split me-1"></i>Pending';
        else if(isSelected) statusText = '<i class="bi bi-check-circle-fill me-1"></i>Selected';
        else statusText = '<i class="bi bi-check2 me-1"></i>Available';

        html += `
            <div class="${cls}" onclick="${isBooked ? '' : `toggleSlot(${slot.hour})`}" data-hour="${slot.hour}">
                <span class="slot-check">✓</span>
                <div class="slot-time">${slot.label}</div>
                <div class="slot-status">${statusText}</div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

function toggleSlot(hour){
    if(selectedSlots.has(hour)){
        selectedSlots.delete(hour);
    } else {
        selectedSlots.add(hour);
    }
    renderSlots();
    updateSummary();
}

function updateSummary(){
    const summary = document.getElementById('bookingSummary');
    const hiddenDiv = document.getElementById('hiddenSlots');
    const bookBtn = document.getElementById('bookBtn');

    if(selectedSlots.size === 0){
        summary.style.display = 'none';
        hiddenDiv.innerHTML = '';
        bookBtn.disabled = true;
        return;
    }

    // Show summary
    summary.style.display = 'block';
    bookBtn.disabled = false;

    // Build labels
    const sortedSlots = Array.from(selectedSlots).sort((a,b) => a - b);
    const labels = sortedSlots.map(h => {
        if(h < 12) return h + ':00 AM';
        if(h === 12) return '12:00 PM';
        return (h - 12) + ':00 PM';
    });

    document.getElementById('summarySlots').textContent = labels.join(', ');
    document.getElementById('summaryDuration').textContent = selectedSlots.size + ' hour' + (selectedSlots.size > 1 ? 's' : '');
    
    const total = selectedSlots.size * hourlyRate;
    document.getElementById('summaryTotal').textContent = 'Rs ' + total.toLocaleString();

    // Generate hidden inputs
    hiddenDiv.innerHTML = sortedSlots.map(h => 
        `<input type="hidden" name="time_slots[]" value="${h}">`
    ).join('');
}

// Form validation
document.getElementById('bookingForm').addEventListener('submit', function(e){
    if(selectedSlots.size === 0){
        e.preventDefault();
        alert('Please select at least one time slot.');
    }
});
</script>
</body>
</html>
