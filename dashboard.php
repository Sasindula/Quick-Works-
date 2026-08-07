<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

/* AUTH CHECK */
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "WORKER"){
    header("Location: ../login.php");
    exit();
}

$worker_id = $_SESSION['user']['user_id'];
$worker_name = $_SESSION['user']['name'];

/* TOTAL JOBS */
$total = $conn->query("
    SELECT COUNT(*) AS c FROM job_requests WHERE worker_id = $worker_id
")->fetch_assoc()['c'];

/* PENDING JOBS */
$pending = $conn->query("
    SELECT COUNT(*) AS c FROM job_requests WHERE worker_id = $worker_id AND status = 'PENDING'
")->fetch_assoc()['c'];

/* COMPLETED JOBS */
$completed = $conn->query("
    SELECT COUNT(*) AS c FROM job_requests WHERE worker_id = $worker_id AND status = 'COMPLETED'
")->fetch_assoc()['c'];

/* EARNINGS */
$earnings = $conn->query("
    SELECT IFNULL(SUM(p.amount),0) AS total
    FROM payments p
    JOIN job_requests jr ON p.job_id = jr.job_id
    WHERE jr.worker_id = $worker_id AND p.payment_status = 'PAID'
")->fetch_assoc()['total'];

/* UNREAD NOTIFICATIONS */
$unread = $conn->query("
    SELECT COUNT(*) as c FROM notifications WHERE user_id=$worker_id AND is_read=0
")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - QuickWorks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f2f5; margin: 0; }

        .w-sidebar {
            position: fixed; top: 0; left: 0;
            width: 260px; height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff; z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        .w-sidebar .brand {
            padding: 28px 24px 20px; font-size: 1.5rem; font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .w-sidebar .brand i { color: #f59e0b; margin-right: 8px; }
        .w-sidebar .nav-links { padding: 16px 12px; list-style: none; margin: 0; }
        .w-sidebar .nav-links li { margin-bottom: 4px; }
        .w-sidebar .nav-links a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 10px;
            color: #94a3b8; text-decoration: none;
            font-size: 0.9rem; font-weight: 500; transition: all 0.2s;
        }
        .w-sidebar .nav-links a:hover { background: rgba(255,255,255,0.08); color: #e2e8f0; }
        .w-sidebar .nav-links a.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff; box-shadow: 0 4px 12px rgba(245,158,11,0.3);
        }
        .w-sidebar .nav-links a i { font-size: 1.1rem; width: 22px; text-align: center; }

        .w-main { margin-left: 260px; padding: 0; min-height: 100vh; }
        .w-topbar {
            background: #fff; padding: 16px 32px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;
        }
        .w-content { padding: 32px; }

        .notif-btn {
            position: relative; background: none; border: none;
            font-size: 1.3rem; color: #64748b; cursor: pointer;
            padding: 8px; border-radius: 8px; transition: all 0.2s;
        }
        .notif-btn:hover { background: #f1f5f9; }
        .notif-badge {
            position: absolute; top: 2px; right: 2px;
            background: #ef4444; color: #fff; border-radius: 50%;
            width: 20px; height: 20px; font-size: 0.7rem;
            display: flex; align-items: center; justify-content: center; font-weight: 700;
        }
        .notif-pulse { animation: pulse 2s infinite; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .slot-badge-sm {
            display: inline-flex; align-items: center;
            padding: 2px 8px; border-radius: 5px;
            font-size: 0.75rem; font-weight: 600; margin: 2px;
            background: #dbeafe; color: #1e40af;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="w-sidebar">
    <div class="brand"><i class="bi bi-wrench-adjustable-circle-fill"></i>Worker Panel</div>
    <ul class="nav-links">
        <li><a href="dashboard.php" class="active"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a></li>
        <li><a href="jobs.php"><i class="bi bi-list-check"></i><span>Jobs</span></a></li>
        <li><a href="worker_Payment.php"><i class="bi bi-wallet2"></i><span>Payments</span></a></li>
        <li><a href="worker_complaints.php"><i class="bi bi-exclamation-triangle"></i><span>Complaints</span></a></li>
        <li><a href="profiles.php"><i class="bi bi-person-fill"></i><span>Profile</span></a></li>
        <li style="position: absolute; bottom: 16px; width: calc(100% - 24px);">
            <a href="../logout.php" style="color: #f87171;"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
        </li>
    </ul>
</div>

<!-- MAIN -->
<div class="w-main">
    <div class="w-topbar">
        <h5 class="fw-bold mb-0">Dashboard</h5>
        <div class="d-flex align-items-center gap-3">
            <button class="notif-btn <?php echo $unread > 0 ? 'notif-pulse' : ''; ?>" data-bs-toggle="modal" data-bs-target="#notifModal">
                <i class="bi bi-bell-fill"></i>
                <?php if($unread > 0): ?>
                    <span class="notif-badge"><?php echo $unread; ?></span>
                <?php endif; ?>
            </button>
            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                <?php echo strtoupper(substr($worker_name, 0, 1)); ?>
            </div>
            <span class="fw-medium"><?php echo htmlspecialchars($worker_name); ?></span>
        </div>
    </div>

    <div class="w-content">
        <!-- Welcome -->
        <div class="mb-4" style="background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 16px; padding: 32px 40px; color: #fff; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -40px; right: -20px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <h2 style="font-weight: 700; margin-bottom: 8px;">Welcome back, <?php echo htmlspecialchars($worker_name); ?>! 🔧</h2>
            <p style="opacity: 0.9; margin-bottom: 0;">Manage your jobs and track your earnings</p>
        </div>

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">Total Jobs</p>
                                <h3 class="fw-bold mb-0"><?php echo $total; ?></h3>
                            </div>
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-briefcase-fill text-primary" style="font-size: 1.4rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">Pending</p>
                                <h3 class="fw-bold mb-0"><?php echo $pending; ?></h3>
                            </div>
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #fef3c7, #fde68a); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-clock-fill text-warning" style="font-size: 1.4rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">Completed</p>
                                <h3 class="fw-bold mb-0"><?php echo $completed; ?></h3>
                            </div>
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 1.4rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">Earnings</p>
                                <h3 class="fw-bold mb-0">Rs <?php echo number_format($earnings, 0); ?></h3>
                            </div>
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #ede9fe, #ddd6fe); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-wallet2" style="font-size: 1.4rem; color: #8b5cf6;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4">
            <div class="col-md-6">
                <a href="jobs.php" class="card border-0 shadow-sm text-decoration-none" style="border-radius: 14px; transition: all 0.3s;">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6, #2563eb); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-list-check text-white" style="font-size: 1.3rem;"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">View All Jobs</h6>
                            <small class="text-muted">Accept, reject or complete jobs</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="profiles.php" class="card border-0 shadow-sm text-decoration-none" style="border-radius: 14px; transition: all 0.3s;">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-person-gear text-white" style="font-size: 1.3rem;"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Edit Profile</h6>
                            <small class="text-muted">Update skills, rates & location</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- NOTIFICATION MODAL -->
<div class="modal fade" id="notifModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9;">
                <h5 class="modal-title fw-bold"><i class="bi bi-bell-fill me-2 text-warning"></i>Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="notifList">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9;">
                <button class="btn btn-sm btn-outline-primary" onclick="markAllRead()" style="border-radius: 8px;">Mark All Read</button>
            </div>
        </div>
    </div>
</div>

<!-- BOOKING RESPONSE MODAL -->
<div class="modal fade" id="respondModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9;">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar-check me-2 text-primary"></i>Booking Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3" style="background: #f8fafc; border-radius: 12px; padding: 16px;">
                    <p class="mb-1"><strong>Customer:</strong> <span id="rCustomer"></span></p>
                    <p class="mb-1"><strong>Date:</strong> <span id="rDate"></span></p>
                    <p class="mb-1"><strong>Description:</strong> <span id="rDesc"></span></p>
                    <p class="mb-1"><strong>Payment:</strong> <span id="rPayment" class="badge bg-info"></span></p>
                    <div class="mt-2">
                        <strong>Time Slots:</strong>
                        <div id="rTimeSlots" class="mt-1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9;">
                <button class="btn btn-danger" onclick="respondBooking('reject')" style="border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
                <button class="btn btn-success" onclick="respondBooking('accept')" style="border-radius: 10px; font-weight: 600;">
                    <i class="bi bi-check-circle me-1"></i>Accept
                </button>
            </div>
        </div>
    </div>
</div>

<!-- RESULT MODAL -->
<div class="modal fade" id="resultModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content text-center" style="border-radius: 16px; border: none;">
            <div class="modal-body p-4">
                <div id="resultIcon" style="font-size: 3rem;"></div>
                <h5 class="fw-bold mt-3" id="resultTitle"></h5>
                <p class="text-muted" id="resultMsg"></p>
                <button class="btn btn-primary" data-bs-dismiss="modal" style="border-radius: 10px;">OK</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentJobId = 0;
let currentNotifId = 0;

// Load notifications
function loadNotifications(){
    fetch('../api/notifications.php')
    .then(r => r.json())
    .then(data => {
        const list = document.getElementById('notifList');
        const badge = document.querySelector('.notif-badge');
        
        if(data.unread_count > 0){
            if(badge) badge.textContent = data.unread_count;
            else {
                const btn = document.querySelector('.notif-btn');
                const b = document.createElement('span');
                b.className = 'notif-badge';
                b.textContent = data.unread_count;
                btn.appendChild(b);
            }
        } else if(badge) badge.remove();

        if(data.notifications.length === 0){
            list.innerHTML = '<div class="text-center py-4"><i class="bi bi-bell-slash" style="font-size:2rem;color:#cbd5e1;"></i><p class="text-muted mt-2">No notifications</p></div>';
            return;
        }

        let html = '';
        data.notifications.forEach(n => {
            const isUnread = n.is_read == 0;
            const isBooking = n.type === 'booking_request';
            const slotsText = n.time_slots_text || '';
            const slotsJson = JSON.stringify(n.time_slots || []).replace(/'/g, "\\'");
            html += `
                <div class="px-4 py-3 ${isUnread ? 'bg-light' : ''}" style="border-bottom: 1px solid #f1f5f9; cursor: ${isBooking && isUnread ? 'pointer' : 'default'};"
                     ${isBooking && isUnread ? `onclick="openBookingResponse(${n.job_id}, ${n.notification_id}, '${(n.customer_name||'').replace(/'/g,"\\'")}', '${n.job_date||''}', '${(n.job_desc||'').replace(/'/g,"\\'")}', '${n.payment_option||'LATER'}', '${slotsText.replace(/'/g,"\\'")}')"` : ''}>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width:36px;height:36px;border-radius:50%;background:${isBooking?'linear-gradient(135deg,#3b82f6,#8b5cf6)':'#e2e8f0'};color:${isBooking?'#fff':'#64748b'};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi ${isBooking?'bi-calendar-plus':'bi-bell'}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1" style="font-size:0.9rem;${isUnread?'font-weight:600;':'color:#64748b;'}">${n.message}</p>
                            ${slotsText ? `<div class="mt-1">${(n.time_slots||[]).map(s => `<span class="slot-badge-sm">${s}</span>`).join('')}</div>` : ''}
                            <small class="text-muted">${new Date(n.created_at).toLocaleDateString('en-US', {month:'short',day:'numeric',hour:'numeric',minute:'2-digit'})}</small>
                            ${isBooking && isUnread ? '<span class="badge bg-primary ms-2" style="border-radius:6px;">Action Required</span>' : ''}
                        </div>
                    </div>
                </div>`;
        });
        list.innerHTML = html;
    });
}

function openBookingResponse(jobId, notifId, customer, date, desc, payment, slotsText){
    currentJobId = jobId;
    currentNotifId = notifId;
    document.getElementById('rCustomer').textContent = customer;
    document.getElementById('rDate').textContent = date;
    document.getElementById('rDesc').textContent = desc;
    document.getElementById('rPayment').textContent = payment === 'NOW' ? 'Pay Now (Upfront)' : 'Pay After Work';
    
    // Show time slots
    const slotsDiv = document.getElementById('rTimeSlots');
    if(slotsText){
        const slots = slotsText.split(', ');
        slotsDiv.innerHTML = slots.map(s => `<span class="slot-badge-sm">${s}</span>`).join('');
    } else {
        slotsDiv.innerHTML = '<span class="text-muted" style="font-size:0.85rem;">No slot info available</span>';
    }
    
    bootstrap.Modal.getInstance(document.getElementById('notifModal')).hide();
    new bootstrap.Modal(document.getElementById('respondModal')).show();
}

function respondBooking(action){
    fetch('../api/respond_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            job_id: currentJobId, 
            action: action,
            notification_id: currentNotifId 
        })
    })
    .then(r => r.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('respondModal')).hide();
        
        const icon = document.getElementById('resultIcon');
        const title = document.getElementById('resultTitle');
        const msg = document.getElementById('resultMsg');

        if(data.success){
            if(action === 'accept'){
                icon.innerHTML = '✅';
                title.textContent = 'Job Accepted!';
                msg.textContent = data.payment_option === 'NOW' 
                    ? 'Customer has paid upfront. Go complete the job!' 
                    : 'Customer will pay after work is done.';
            } else {
                icon.innerHTML = '❌';
                title.textContent = 'Job Rejected';
                msg.textContent = 'The customer has been notified.';
            }
        } else {
            icon.innerHTML = '⚠️';
            title.textContent = 'Error';
            msg.textContent = data.error || 'Something went wrong';
        }

        new bootstrap.Modal(document.getElementById('resultModal')).show();
        loadNotifications();
    });
}

function markAllRead(){
    fetch('../api/notifications.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({mark_all_read: true})
    }).then(() => loadNotifications());
}

// Load on modal open
document.getElementById('notifModal').addEventListener('show.bs.modal', loadNotifications);

// Poll every 10 seconds
setInterval(() => {
    fetch('../api/notifications.php')
    .then(r => r.json())
    .then(data => {
        const badge = document.querySelector('.notif-badge');
        const btn = document.querySelector('.notif-btn');
        if(data.unread_count > 0){
            if(badge) badge.textContent = data.unread_count;
            else {
                const b = document.createElement('span');
                b.className = 'notif-badge';
                b.textContent = data.unread_count;
                btn.appendChild(b);
            }
            btn.classList.add('notif-pulse');
        } else {
            if(badge) badge.remove();
            btn.classList.remove('notif-pulse');
        }
    });
}, 10000);
</script>
</body>
</html>
