<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');

require_once('../vendor/autoload.php');

// ===== STRIPE CONFIGURATION =====
define('STRIPE_SECRET_KEY', 'sk_test_51SDmtZ8CuwBmuHazluCT9JY8B8dJqHYDx7nM3dvnHAErAHhy58AUfW0nzomE9jFYJVMkMd7uuswFiGEztVERM80I00DRWjv9EO');

// Initialize Stripe
if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY)) {
    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    } catch (Exception $e) {
        error_log("Stripe initialization error: " . $e->getMessage());
    }
}

// Handle return status updates
if (isset($_POST['update_return'])) {
    $return_id = (int)$_POST['return_id'];
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);

    // Validate status
    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
        $_SESSION['message'] = "Invalid status provided.";
        $_SESSION['message_type'] = "danger";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Get order details for refund processing
    $stmt_order = $con->prepare("SELECT o.*, r.reason FROM orders o 
                                  LEFT JOIN return_requests r ON o.order_id = r.order_id
                                  WHERE o.order_id = ?");
    $stmt_order->bind_param("i", $order_id);
    $stmt_order->execute();
    $order = $stmt_order->get_result()->fetch_assoc();
    $stmt_order->close();

    if (!$order) {
        $_SESSION['message'] = "Order not found.";
        $_SESSION['message_type'] = "danger";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

$con->begin_transaction();
try {
    // Update return request status
    $stmt_return_update = $con->prepare("UPDATE return_requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE return_id = ?");
    
    if ($stmt_return_update === false) {
        throw new Exception("Database prepare error: " . $con->error);
    }
    
    $stmt_return_update->bind_param("ssi", $status, $admin_notes, $return_id);
    
    if (!$stmt_return_update->execute()) {
        throw new Exception("Failed to update return request: " . $stmt_return_update->error);
    }
    $stmt_return_update->close();

    // Update order status based on return status
    $new_order_status = ($status === 'approved') ? 'return_approved' : ($status === 'rejected' ? 'return_rejected' : 'return_requested');
    $stmt_order_update = $con->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");

    $action_type = ($status === 'approved') ? 'return_approve' : 'return_reject';
    logAuditTrail(
        $con,
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_role'],
        $action_type,
        "Return request #{$return_id} for order #{$order_id} was {$status}",
        'return_requests',
        $return_id
    );
    
    if ($stmt_order_update === false) {
        throw new Exception("Database prepare error: " . $con->error);
    }
    
    $stmt_order_update->bind_param("si", $new_order_status, $order_id);
    
    if (!$stmt_order_update->execute()) {
        throw new Exception("Failed to update order status: " . $stmt_order_update->error);
    }
    $stmt_order_update->close();

    // Log to order tracking
    $stmt_tracking = $con->prepare("INSERT INTO order_tracking (order_id, status, notes) VALUES (?, ?, ?)");
    
    if ($stmt_tracking === false) {
        throw new Exception("Database prepare error: " . $con->error);
    }
    
    $tracking_note = "Return " . ucfirst($status) . ": " . $admin_notes;
    $stmt_tracking->bind_param("iss", $order_id, $new_order_status, $tracking_note);
    
    if (!$stmt_tracking->execute()) {
        throw new Exception("Failed to log order tracking: " . $stmt_tracking->error);
    }
    $stmt_tracking->close();

    // Notify user of return status
    require_once(__DIR__ . '/../notification_functions.php');
    notifyUserReturnStatus($con, $return_id, $status);

    // Handle refund if approved
    $refund_message = "";
    if ($status === 'approved' && $order['pay_status'] === 'paid') {
        if ($order['pay_method'] === 'stripe' && !empty($order['stripe_payment_id'])) {
            // Process Stripe refund
            try {
                $refund = \Stripe\Refund::create([
                    'charge' => $order['stripe_payment_id'],
                    'reason' => 'requested_by_customer',
                    'metadata' => [
                        'order_id' => $order_id,
                        'return_id' => $return_id,
                        'reason' => $order['reason']
                    ]
                ]);

                // Log refund in database
                $stmt_refund = $con->prepare("INSERT INTO refunds (return_id, order_id, payment_method, amount, stripe_refund_id, status, notes) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt_refund === false) {
                    throw new Exception("Database prepare error: " . $con->error);
                }
                
                $refund_status = 'completed';
                $refund_notes = 'Stripe refund processed successfully';
                $stmt_refund->bind_param("iisdsss", $return_id, $order_id, $order['pay_method'], $order['total'], $refund->id, $refund_status, $refund_notes);
                $stmt_refund->execute();
                $stmt_refund->close();
                logAuditTrail(
                    $con,
                    $_SESSION['admin_id'],
                    $_SESSION['admin_name'],
                    $_SESSION['admin_role'],
                    'refund_process',
                    "Processed refund of ₱" . number_format($order['total'], 2) . " for order #{$order_id}",
                    'refunds',
                    null,
                    null,
                    ['order_id' => $order_id, 'amount' => $order['total'], 'method' => 'stripe']
                );

                // Notify user of refund
                notifyUserRefundProcessed($con, $order_id, $order['total']);

                $refund_message = " Stripe refund of ₱" . number_format($order['total'], 2) . " processed successfully.";
            } catch (\Stripe\Exception\ApiErrorException $e) {
                throw new Exception("Stripe refund failed: " . $e->getMessage());
            }
        } elseif ($order['pay_method'] === 'cod') {
            // For COD orders, log that refund needs manual processing
            $stmt_refund_cod = $con->prepare("INSERT INTO refunds (return_id, order_id, payment_method, amount, status, notes) 
                                               VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt_refund_cod === false) {
                throw new Exception("Database prepare error: " . $con->error);
            }
            
            $refund_status = 'pending';
            $refund_notes = 'Awaiting customer account details for COD refund';
            $stmt_refund_cod->bind_param("iisdss", $return_id, $order_id, $order['pay_method'], $order['total'], $refund_status, $refund_notes);
            $stmt_refund_cod->execute();
            $stmt_refund_cod->close();

            $refund_message = " COD refund pending - customer account details needed.";
        }
    }

    $con->commit();
    $_SESSION['message'] = "Return request #$return_id has been updated to '$status'.$refund_message";
    $_SESSION['message_type'] = "success";
} catch (Exception $e) {
    $con->rollback();
    $_SESSION['message'] = "Error updating return request: " . htmlspecialchars($e->getMessage());
    $_SESSION['message_type'] = "danger";
}
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch return requests with order and customer details
$sql = "SELECT r.*, o.tracking_number, o.order_status, o.pay_method, o.pay_status, o.stripe_payment_id, o.total, u.name as customer_name, u.email as customer_email
        FROM return_requests r 
        JOIN orders o ON r.order_id = o.order_id 
        JOIN userss u ON r.user_id = u.user_id 
        ORDER BY r.created_at DESC";
$returns = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Requests - SioSio Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">

    <style>
        :root {
            --siosio-red: #dc3545;
            --sidebar-width: 260px;
            --primary-blue: #0d6efd;
            --light-gray: #f8f9fa;
            --border-color: #dee2e6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            font-size: 0.95rem;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h3 { 
            font-family: 'Joti One', cursive;
            margin: 0; 
            font-size: 1.5rem; 
        }
        .sio-highlight { color: var(--siosio-red); }
        .sidebar-menu { list-style: none; padding: 1rem 0; margin: 0; }
        .sidebar-menu a { display: flex; align-items: center; padding: 0.875rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(220, 53, 69, 0.1); color: white; border-left: 3px solid var(--siosio-red); padding-left: calc(1.5rem - 3px); }
        .sidebar-menu i { margin-right: 0.75rem; font-size: 1.2rem; }

        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
        }

        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .content-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
        }
        
        .table thead {
            background-color: #f1f3f5;
        }

        .table th {
            font-weight: 600;
            color: #495057;
            border-bottom-width: 1px !important;
        }
        
        .table td, .table th {
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
            font-weight: 600;
            text-transform: capitalize;
            border-radius: 50px;
        }

        .bg-pending { background-color: #fff3cd !important; color: #856404 !important; }
        .bg-approved { background-color: #d1e7dd !important; color: #0f5132 !important; }
        .bg-rejected { background-color: #f8d7da !important; color: #842029 !important; }
        
        .modal-header {
            background-color: var(--siosio-red);
            color: white;
            border-bottom: none;
        }
        .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body h6 {
            font-weight: 700;
            color: #343a40;
            margin-bottom: 0.5rem;
            border-left: 3px solid var(--siosio-red);
            padding-left: 0.75rem;
        }
        
        .modal-body p {
            background-color: #f8f9fa;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            white-space: pre-wrap;
        }

        .modal-body video {
            border-radius: 6px;
            border: 1px solid var(--border-color);
            max-height: 400px;
        }
        
        .admin-form-section {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .form-label {
            font-weight: 600;
        }

        .status-info {
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .refund-section {
            background: #f0f7ff;
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .refund-section.cod {
            background: #fff3cd;
            border-left-color: #ff9800;
        }

        .payment-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .payment-info-item {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Admin</h3>
                <p class="mb-0 small text-muted">Management Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php" class="active"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                <li><a href="admin_cms.php"><i class="bi bi-file-text"></i> Content Management</a></li>
                <li><a href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
                <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <h4 class="mb-0">Manage Return Requests</h4>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>

            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Return ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($return = $returns->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $return['return_id'] ?></strong></td>
                                <td>#<?= $return['order_id'] ?></td>
                                <td>
                                    <?= htmlspecialchars($return['customer_name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($return['customer_email']) ?></small>
                                </td>
                                <td><strong>₱<?= number_format($return['total'], 2) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $return['pay_method'] === 'stripe' ? 'primary' : 'warning text-dark' ?>">
                                        <?= ucfirst($return['pay_method']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= htmlspecialchars($return['status']) ?>">
                                        <?= ucfirst($return['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($return['created_at'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#returnModal<?= $return['return_id'] ?>">
                                        <i class="bi bi-search me-1"></i> Review
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Modal for each return -->
                            <div class="modal fade" id="returnModal<?= $return['return_id'] ?>" tabindex="-1" aria-labelledby="modalTitle<?= $return['return_id'] ?>">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalTitle<?= $return['return_id'] ?>">
                                                Return Request #<?= $return['return_id'] ?> - Order #<?= $return['order_id'] ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="status-info bg-light border-start border-4 border-<?= $return['status'] ?>">
                                                <strong>Current Status:</strong> 
                                                <span class="badge bg-<?= $return['status'] ?>"><?= ucfirst($return['status']) ?></span>
                                                <br>
                                                <small class="text-muted">Requested on <?= date('M d, Y h:i A', strtotime($return['created_at'])) ?></small>
                                            </div>

                                            <!-- Payment Information -->
                                            <div class="payment-info">
                                                <div class="payment-info-item">
                                                    <strong>Payment Method:</strong>
                                                    <p class="mb-0"><?= ucfirst($return['pay_method']) ?></p>
                                                </div>
                                                <div class="payment-info-item">
                                                    <strong>Amount:</strong>
                                                    <p class="mb-0">₱<?= number_format($return['total'], 2) ?></p>
                                                </div>
                                            </div>

                                            <h6>Video Proof:</h6>
                                            <video controls width="100%" style="margin-bottom: 1.5rem;">
                                                <source src="<?= htmlspecialchars($return['video_proof_path']) ?>" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                            <hr class="my-4">
                                            
                                            <h6>Reason for Return:</h6>
                                            <p><?= htmlspecialchars($return['reason']) ?></p>
                                            
                                            <h6>Customer Comments:</h6>
                                            <p><?= !empty($return['comments']) ? nl2br(htmlspecialchars($return['comments'])) : '<em class="text-muted">No comments provided.</em>' ?></p>
                                            
                                            <div class="admin-form-section">
                                                <form method="POST" action="">
                                                    <input type="hidden" name="return_id" value="<?= $return['return_id'] ?>">
                                                    <input type="hidden" name="order_id" value="<?= $return['order_id'] ?>">
                                                    
                                                    <!-- Refund Information -->
                                                    <?php if ($return['pay_status'] === 'paid'): ?>
                                                        <div class="refund-section <?= $return['pay_method'] === 'cod' ? 'cod' : '' ?>">
                                                            <h6 class="mb-2">
                                                                <i class="bi bi-cash-coin"></i> Refund Information
                                                            </h6>
                                                            <?php if ($return['pay_method'] === 'stripe'): ?>
                                                                <p class="mb-0">
                                                                    <strong>Status:</strong> <?= $return['status'] === 'approved' ? 'Automatic refund will be processed' : 'Refund pending approval' ?><br>
                                                                    <small class="text-muted">Amount: ₱<?= number_format($return['total'], 2) ?> will be returned to customer's card</small>
                                                                </p>
                                                            <?php else: ?>
                                                                <p class="mb-0">
                                                                    <strong>Status:</strong> Manual refund required<br>
                                                                    <small class="text-muted">Customer needs to provide account details for refund of ₱<?= number_format($return['total'], 2) ?></small>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="mb-3">
                                                        <label for="statusSelect<?= $return['return_id'] ?>" class="form-label">Update Status:</label>
                                                        <select name="status" id="statusSelect<?= $return['return_id'] ?>" class="form-select" required>
                                                            <option value="pending" <?= $return['status'] == 'pending' ? 'selected' : '' ?>>Pending Review</option>
                                                            <option value="approved" <?= $return['status'] == 'approved' ? 'selected' : '' ?>>Approve Return</option>
                                                            <option value="rejected" <?= $return['status'] == 'rejected' ? 'selected' : '' ?>>Reject Return</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="adminNotes<?= $return['return_id'] ?>" class="form-label">Admin Notes:</label>
                                                        <textarea name="admin_notes" id="adminNotes<?= $return['return_id'] ?>" class="form-control" rows="4" placeholder="Explain the reason for approval or rejection..."><?= htmlspecialchars($return['admin_notes'] ?? '') ?></textarea>
                                                        <?php if ($return['pay_method'] === 'cod'): ?>
                                                            <small class="text-warning d-block mt-2">
                                                                <i class="bi bi-exclamation-triangle"></i> For COD refunds: Include instructions for the customer to provide their account details.
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <button type="submit" name="update_return" class="btn btn-danger w-100">
                                                        <i class="bi bi-check-circle-fill me-2"></i>Update Return Request
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                            <?php if ($returns->num_rows === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No return requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
         (function() {
            const timeoutInMilliseconds = 1800000;
            let inactivityTimer;

            function logout() {
                window.location.href = 'admin_logout.php?reason=idle';
            }

            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(logout, timeoutInMilliseconds);
            }

            window.addEventListener('load', resetTimer);
            document.addEventListener('mousemove', resetTimer);
            document.addEventListener('mousedown', resetTimer);
            document.addEventListener('keypress', resetTimer);
            document.addEventListener('touchmove', resetTimer);
            document.addEventListener('scroll', resetTimer);
        })();
    </script>
</body>
</html>