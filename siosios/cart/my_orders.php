<?php
session_start();
include("../config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header("Location: ../loginreg/logreg.php?error=notloggedin");
    exit;
}

// --- NEW SUSPENSION CHECK ---
$user_id_from_session = $_SESSION['user_id']; // Store user ID

// 2. Fetch the current user's status from the database
// Make sure $con (database connection) is available here
if (isset($con)) {
    $stmt_check_status = $con->prepare("SELECT status FROM userss WHERE user_id = ?");

    // Check if prepare() succeeded
    if ($stmt_check_status) {
        $stmt_check_status->bind_param("i", $user_id_from_session);
        $stmt_check_status->execute();
        $result_status = $stmt_check_status->get_result();

        if ($result_status->num_rows === 1) {
            $user_data = $result_status->fetch_assoc();
            
            // 3. Check if the user is suspended
            if ($user_data['status'] === 'suspended') {
                // User is suspended, log them out 
                session_unset();     // Unset $_SESSION variable
                session_destroy();   // Destroy session data
                
                // Redirect to login page with a specific error message
                header("Location: ../loginreg/logreg.php?error=suspended"); 
                exit; // Stop script execution
            }
            // If status is 'active' or something else, execution continues normally below...
        } else {
            // User ID from session not found in database (maybe deleted?) - Log them out.
            session_unset();
            session_destroy();
            header("Location: ../loginreg/logreg.php?error=notfound");
            exit;
        }
        $stmt_check_status->close();
    } else {
        // Database query failed - Log out as a safety measure
        error_log("Failed to prepare statement to check user status: " . $con->error); // Log error for admin
        session_unset();
        session_destroy();
        header("Location: ../loginreg/logreg.php?error=dberror");
        exit;
    }
} else {
    // Database connection ($con) not available - Log out as a safety measure
     error_log("Database connection variable not available for user status check."); 
     session_unset();
     session_destroy();
     header("Location: ../loginreg/logreg.php?error=dberror");
     exit;
}
// --- END OF NEW SUSPENSION CHECK ---

$user_id = (int)$_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Base SQL query with return request details
$sql = "SELECT o.*, rr.return_id, rr.status as return_status, rr.reason, rr.admin_notes
        FROM orders o
        LEFT JOIN return_requests rr ON o.order_id = rr.order_id
        WHERE o.user_id = ?";
$params = [$user_id];
$types = "i";

// Apply status filter
if (!empty($status_filter)) {
    $return_statuses = [
        'return_requested' => 'pending',
        'return_approved' => 'approved',
        'return_rejected' => 'rejected'
    ];
    
    if (array_key_exists($status_filter, $return_statuses)) {
        $sql .= " AND rr.status = ?";
        $params[] = $return_statuses[$status_filter];
        $types .= "s";
    } else {
        $sql .= " AND o.order_status = ? AND rr.status IS NULL";
        $params[] = $status_filter;
        $types .= "s";
    }
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $con->prepare($sql);
if (!empty($params)) {
    $bind_params = [&$types];
    for ($i = 0; $i < count($params); $i++) {
        $bind_params[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

function getStatusBadge($order_status, $return_status, $admin_notes = null) {
    if ($return_status === 'pending') {
        return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Return Requested</span>';
    } elseif ($return_status === 'approved') {
        return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Return Approved</span>';
    } elseif ($return_status === 'rejected') {
        return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Return Rejected</span>';
    } else {
        $badges = [
            'processing' => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Processing</span>',
            'shipped' => '<span class="badge bg-info"><i class="bi bi-box-seam"></i> Shipped</span>',
            'delivered' => '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Delivered</span>',
            'cancelled' => '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Cancelled</span>',
            'return_approved' => '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Return Approved</span>',
            'return_rejected' => '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Return Rejected</span>'
        ];
        return $badges[$order_status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Siosio Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .order-card {
        transition: all 0.3s ease;
        border-left: 4px solid #dc3545;
    }
    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .order-card.has-return {
        border-left-color: #0d6efd;
    }
    .tracking-timeline {
        position: relative;
        padding-left: 30px;
    }
    .tracking-step {
        position: relative;
        padding-bottom: 20px;
    }
    .tracking-step:before {
        content: '';
        position: absolute;
        left: -22px;
        top: 8px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #ddd;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px #ddd;
    }
    .tracking-step.active:before {
        background: #dc3545;
        box-shadow: 0 0 0 2px #dc3545;
    }
    .tracking-step.completed:before {
        background: #28a745;
        box-shadow: 0 0 0 2px #28a745;
    }
    .tracking-step:after {
        content: '';
        position: absolute;
        left: -17px;
        top: 20px;
        width: 2px;
        height: calc(100% - 10px);
        background: #ddd;
    }
    .tracking-step:last-child:after {
        display: none;
    }
    .order-item-img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 6px;
    }
    .return-status-card {
        background: #f0f7ff;
        border-left: 4px solid #0d6efd;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }
    .return-status-card.approved {
        background: #d1e7dd;
        border-left-color: #28a745;
    }
    .return-status-card.rejected {
        background: #f8d7da;
        border-left-color: #dc3545;
    }
</style>
</head>
<body class="bg-light">
<?php include("../headfoot/header.php"); ?>

<div class="container py-5" style="margin-top:100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-danger mb-0"><i class="bi bi-box-seam"></i> My Orders</h2>
        <a href="../products/product.php" class="btn btn-outline-danger">Continue Shopping</a>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="return_requested" <?= $status_filter === 'return_requested' ? 'selected' : '' ?>>Return Requested</option>
                        <option value="return_approved" <?= $status_filter === 'return_approved' ? 'selected' : '' ?>>Return Approved</option>
                        <option value="return_rejected" <?= $status_filter === 'return_rejected' ? 'selected' : '' ?>>Return Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-danger w-100">Filter Orders</button>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <a href="my_orders.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
                        
    <?php if ($orders->num_rows === 0): ?>
        <div class="text-center py-5">
            <div class="display-1 text-muted mb-3">📦</div>
            <h4 class="text-muted mb-3">No orders found</h4>
            <p class="text-muted mb-4">No orders match your filter criteria or you haven't placed an order yet.</p>
            <a href="../products/product.php" class="btn btn-danger btn-lg">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="col-12 mb-4">
                    <div class="card order-card shadow-sm <?= $order['return_status'] ? 'has-return' : '' ?>">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">
                                    Order #<?= $order['order_id'] ?>
                                    <?= getStatusBadge($order['order_status'], $order['return_status']) ?>
                                </h5>
                                <small class="text-muted">
                                    Placed on <?= date("M d, Y h:i A", strtotime($order['created_at'])) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="h5 mb-0 text-danger">₱<?= number_format($order['total'], 2) ?></div>
                                <small class="text-muted">Payment: 
                                    <span class="badge bg-<?= $order['pay_status'] === 'paid' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($order['pay_status']) ?>
                                    </span>
                                </small>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <?php if ($order['return_status']): ?>
                            <div class="return-status-card <?= $order['return_status'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-2">
                                            <i class="bi bi-arrow-counterclockwise"></i> Return Request Status
                                        </h6>
                                        <p class="mb-1">
                                            <strong>Status:</strong> 
                                            <span class="badge bg-<?= $order['return_status'] === 'pending' ? 'warning text-dark' : ($order['return_status'] === 'approved' ? 'success' : 'danger') ?>">
                                                <?= ucfirst($order['return_status']) ?>
                                            </span>
                                        </p>
                                        <p class="mb-1">
                                            <strong>Reason:</strong> <?= ucfirst(str_replace('_', ' ', $order['reason'])) ?>
                                        </p>
                                        <?php if ($order['admin_notes']): ?>
                                        <p class="mb-0">
                                            <strong>Admin Notes:</strong><br>
                                            <small><?= nl2br(htmlspecialchars($order['admin_notes'])) ?></small>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Order Items</h6>
                                    <?php
                                    $stmt_items = $con->prepare("SELECT oi.*, p.image_url 
                                                                 FROM order_items oi
                                                                 LEFT JOIN products p ON oi.product_id = p.id
                                                                 WHERE oi.order_id = ?");
                                    $stmt_items->bind_param("i", $order['order_id']);
                                    $stmt_items->execute();
                                    $items = $stmt_items->get_result();
                                    
                                    while ($item = $items->fetch_assoc()):
                                    ?>
                                        <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                     class="order-item-img me-3">
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                                <small class="text-muted">Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['price_at_time'], 2) ?></small>
                                            </div>
                                            <div class="fw-bold">₱<?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></div>
                                        </div>
                                    <?php 
                                    endwhile; 
                                    $stmt_items->close(); 
                                    ?>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="mb-3">Delivery Details</h6>
                                    <div class="mb-3">
                                        <strong>Tracking Number:</strong><br>
                                        <span class="badge bg-dark fs-6"><?= htmlspecialchars($order['tracking_number']) ?></span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>📍 Shipping Address:</strong><br>
                                        <small>
                                            <?= htmlspecialchars($order['address_line1']) ?><br>
                                            <?php if (!empty($order['address_line2'])): ?>
                                                <?= htmlspecialchars($order['address_line2']) ?><br>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($order['barangay']) ?>, 
                                            <?= htmlspecialchars($order['city']) ?><br>
                                            <?= htmlspecialchars($order['province']) ?> 
                                            <?= htmlspecialchars($order['postal_code']) ?>
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <strong>🚚 Courier:</strong> <?= htmlspecialchars($order['Courier']) ?><br>
                                        <strong>💳 Payment:</strong> <?= strtoupper($order['pay_method']) ?>
                                    </div>

                                    <h6 class="mb-3 mt-4">Order Tracking</h6>
                                    <div class="tracking-timeline">
                                        <div class="tracking-step completed">
                                            <strong>Order Placed</strong><br>
                                            <small class="text-muted"><?= date("M d, Y h:i A", strtotime($order['created_at'])) ?></small>
                                        </div>
                                        <div class="tracking-step <?= in_array($order['order_status'], ['processing', 'shipped', 'delivered', 'return_approved', 'return_rejected']) ? 'completed' : '' ?>">
                                            <strong>Processing</strong><br>
                                            <small class="text-muted">Your order is being prepared</small>
                                        </div>
                                        <div class="tracking-step <?= in_array($order['order_status'], ['shipped', 'delivered', 'return_approved', 'return_rejected']) ? 'completed' : '' ?>">
                                            <strong>Shipped</strong><br>
                                            <small class="text-muted">Out for delivery</small>
                                        </div>
                                        <div class="tracking-step <?= in_array($order['order_status'], ['delivered', 'return_approved', 'return_rejected']) ? 'completed' : '' ?>">
                                            <strong>Delivered</strong><br>
                                            <small class="text-muted">Order completed</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($order['order_status'] === 'delivered' && is_null($order['return_status'])): ?>
                                <div class="mt-3">
                                    <button type="button" 
                                            class="btn btn-danger btn-sm w-100" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#returnModal<?= $order['order_id'] ?>">
                                        <i class="bi bi-arrow-counterclockwise"></i> Request Return
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-white text-center">
                            <a href="order_details.php?order_id=<?= $order['order_id'] ?>" 
                               class="btn btn-sm btn-outline-danger">View Full Details</a>
                            
                            <?php if ($order['order_status'] === 'processing' && is_null($order['return_status'])): ?>
                                <button class="btn btn-sm btn-outline-secondary" 
                                        onclick="if(confirm('Are you sure you want to cancel this order?')) location.href='cancel_order.php?order_id=<?= $order['order_id'] ?>'">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($order['order_status'] === 'delivered' && is_null($order['return_status'])): ?>
                <div class="modal fade" id="returnModal<?= $order['order_id'] ?>" tabindex="-1" aria-labelledby="returnModalLabel<?= $order['order_id'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="request_return.php" method="POST" enctype="multipart/form-data" onsubmit="return validateReturnForm(this);">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title" id="returnModalLabel<?= $order['order_id'] ?>">
                                        Request Return for Order #<?= $order['order_id'] ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Reason for Return: <span class="text-danger">*</span></label>
                                        <select name="reason" class="form-select" required>
                                            <option value="">Select reason</option>
                                            <option value="defective">Defective Product</option>
                                            <option value="wrong_item">Wrong Item Received</option>
                                            <option value="not_as_described">Item Not As Described</option>
                                            <option value="damaged">Damaged During Shipping</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Additional Comments:</label>
                                        <textarea name="comments" class="form-control" rows="3" placeholder="Please provide detailed information about the issue..."></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Video Proof: <span class="text-danger">*</span></label>
                                        <input type="file" 
                                               name="video_proof" 
                                               class="form-control" 
                                               accept="video/mp4,video/quicktime,video/x-msvideo,video/*" 
                                               required>
                                        <small class="text-muted d-block mt-1">
                                            <i class="bi bi-info-circle"></i> Please upload a video (max 50MB) showing the product condition.
                                            Supported formats: MP4, MOV, AVI
                                        </small>
                                    </div>

                                    <div class="alert alert-info mb-0" role="alert">
                                        <strong>Note:</strong> Your return request will be reviewed within 1-2 business days. 
                                        Please ensure the video clearly shows the issue with the product.
                                    </div>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="submit_return" class="btn btn-danger">
                                        <i class="bi bi-send"></i> Submit Return Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<?php include("../headfoot/footer.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function validateReturnForm(form) {
    const videoInput = form.querySelector('input[name="video_proof"]');
    const reasonSelect = form.querySelector('select[name="reason"]');
    
    if (!reasonSelect.value) {
        alert('Please select a reason for return');
        reasonSelect.focus();
        return false;
    }
    
    if (!videoInput.files || videoInput.files.length === 0) {
        alert('Please upload a video proof');
        videoInput.focus();
        return false;
    }
    
    const file = videoInput.files[0];
    const maxSize = 50 * 1024 * 1024;
    
    if (file.size > maxSize) {
        alert('Video file is too large. Maximum size is 50MB.');
        videoInput.value = '';
        return false;
    }
    
    const allowedTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/mpeg'];
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(mp4|mov|avi|mpeg)$/i)) {
        alert('Invalid video format. Please upload MP4, MOV, or AVI files only.');
        videoInput.value = '';
        return false;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
    
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const videoInputs = document.querySelectorAll('input[name="video_proof"]');
    
    videoInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                
                const helpText = this.nextElementSibling;
                if (helpText) {
                    const sizeInfo = document.createElement('div');
                    sizeInfo.className = 'text-muted mt-1';
                    sizeInfo.innerHTML = `<strong>Selected:</strong> ${file.name} (${sizeMB} MB)`;
                    
                    const existingSizeInfo = helpText.nextElementSibling;
                    if (existingSizeInfo && existingSizeInfo.classList.contains('text-muted')) {
                        existingSizeInfo.remove();
                    }
                    
                    helpText.after(sizeInfo);
                }
            }
        });
    });
});
<?php include($_SERVER['DOCUMENT_ROOT'] . '/siosios/chat/chat_init.php'); ?>
</script>
</body>
</html>