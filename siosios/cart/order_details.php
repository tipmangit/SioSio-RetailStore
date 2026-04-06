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


$order_id = $_GET['order_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header("Location: my_orders.php");
    exit;
}

// Fetch order details with return request information
$stmt = $con->prepare("SELECT o.*, rr.return_id, rr.status as return_status, rr.reason, 
                              rr.comments as return_comments, rr.video_proof_path, 
                              rr.admin_notes, rr.created_at as return_created_at, rr.updated_at as return_updated_at
                       FROM orders o 
                       LEFT JOIN return_requests rr ON o.order_id = rr.order_id 
                       WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: my_orders.php");
    exit;
}

// Fetch order items
$stmt_items = $con->prepare("SELECT oi.*, p.image_url 
                             FROM order_items oi
                             LEFT JOIN products p ON oi.product_id = p.id
                             WHERE oi.order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();
$stmt_items->close();

function getReturnStatusBadge($status) {
    if ($status === 'pending') {
        return '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Under Review</span>';
    } elseif ($status === 'approved') {
        return '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>';
    } elseif ($status === 'rejected') {
        return '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>';
    }
}

// Fetch refund details if return exists
$refund = null;
if ($order['return_id']) {
    $stmt_refund = $con->prepare("SELECT * FROM refunds WHERE return_id = ?");
    $stmt_refund->bind_param("i", $order['return_id']);
    $stmt_refund->execute();
    $refund = $stmt_refund->get_result()->fetch_assoc();
    $stmt_refund->close();
}

// Function to display refund status badge
function getRefundStatusDisplay($refund, $payment_method, $return_status) {
    if (!$refund) {
        if ($return_status === 'approved') {
            if ($payment_method === 'stripe') {
                return [
                    'badge' => '<span class="badge bg-info"><i class="bi bi-hourglass-split"></i> Processing</span>',
                    'text' => 'Your Stripe refund is being processed and will arrive in 3-5 business days.',
                    'class' => 'info'
                ];
            } else {
                return [
                    'badge' => '<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Action Required</span>',
                    'text' => 'Please provide your payment details to receive your refund.',
                    'class' => 'warning'
                ];
            }
        }
        return null;
    }

    if ($refund['status'] === 'completed') {
        if ($payment_method === 'stripe') {
            return [
                'badge' => '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Refunded</span>',
                'text' => 'Refund of ₱' . number_format($refund['amount'], 2) . ' has been processed to your Stripe account/card.',
                'details' => 'Transaction ID: ' . $refund['stripe_refund_id'],
                'class' => 'success'
            ];
        } else {
            return [
                'badge' => '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Refunded</span>',
                'text' => 'Refund of ₱' . number_format($refund['amount'], 2) . ' has been processed.',
                'details' => 'Please allow 1-2 business days for the refund to appear in your account.',
                'class' => 'success'
            ];
        }
    } elseif ($refund['status'] === 'pending') {
        return [
            'badge' => '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>',
            'text' => 'Your refund is pending. ' . $refund['notes'],
            'class' => 'warning'
        ];
    } elseif ($refund['status'] === 'failed') {
        return [
            'badge' => '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Failed</span>',
            'text' => 'The refund could not be processed. Please contact support for assistance.',
            'details' => $refund['notes'] ?? 'Contact our support team for more details.',
            'class' => 'danger'
        ];
    }

    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Details - #<?= $order_id ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .status-badge { font-size: 1rem; padding: 8px 16px; }
    .detail-row { padding: 12px 0; border-bottom: 1px solid #eee; }
    .detail-row:last-child { border-bottom: none; }
    .print-btn { cursor: pointer; }
    .return-info-card {
        background: linear-gradient(135deg, #e7f5ff 0%, #d0ebff 100%);
        border-left: 4px solid #0d6efd;
        padding: 1.25rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .return-info-card.approved {
        background: linear-gradient(135deg, #d1e7dd 0%, #c3e6cb 100%);
        border-left-color: #28a745;
    }
    .return-info-card.rejected {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border-left-color: #dc3545;
    }
    .return-detail {
        margin: 0.5rem 0;
    }
    .return-detail strong {
        display: inline-block;
        min-width: 120px;
    }
      .timeline {
        position: relative;
        padding-left: 0;
    }

    .timeline-item {
        display: flex;
        margin-bottom: 2rem;
        position: relative;
    }

    .timeline-marker {
        width: 50px;
        height: 50px;
        background: #e9ecef;
        border: 3px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: #6c757d;
        font-size: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .timeline-marker.completed {
        background: #28a745;
        color: white;
    }

    .timeline-item:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 24px;
        top: 50px;
        width: 2px;
        height: calc(100% + 1rem);
        background: #dee2e6;
    }

    .timeline-item:not(:last-child).completed::after {
        background: #28a745;
    }

    .timeline-content {
        margin-left: 1.5rem;
        padding-top: 0.25rem;
    }
</style>
</head>
<body class="bg-light">
<div class="no-print">
    <?php include("../headfoot/header.php"); ?>
</div>

<div class="container py-5" style="margin-top:100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-danger mb-0">Order Details</h2>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary me-2 no-print">
                <i class="bi bi-printer"></i> Print Receipt
            </button>
            <a href="my_orders.php" class="btn btn-outline-danger no-print">Back to Orders</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Order #<?= $order['order_id'] ?></h5>
                    <span class="status-badge badge bg-light text-dark">
                         <?php
                            if ($order['return_status']) {
                                echo ucfirst($order['return_status']);
                            } else {
                                echo ucfirst(str_replace('_', ' ', $order['order_status']));
                            }
                        ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <strong>Order Date:</strong>
                        <span class="float-end"><?= date("F d, Y h:i A", strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Tracking Number:</strong>
                        <span class="float-end badge bg-dark"><?= htmlspecialchars($order['tracking_number']) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Payment Method:</strong>
                        <span class="float-end text-uppercase"><?= htmlspecialchars($order['pay_method']) ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Payment Status:</strong>
                        <span class="float-end">
                            <span class="badge bg-<?= $order['pay_status'] === 'paid' ? 'success' : 'warning' ?>">
                                <?= ucfirst($order['pay_status']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <strong>Courier:</strong>
                        <span class="float-end"><?= htmlspecialchars($order['Courier']) ?></span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Shipping Address</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <?= htmlspecialchars($order['address_line1']) ?><br>
                        <?php if (!empty($order['address_line2'])): ?>
                            <?= htmlspecialchars($order['address_line2']) ?><br>
                        <?php endif; ?>
                        <?= htmlspecialchars($order['barangay']) ?>, 
                        <?= htmlspecialchars($order['city']) ?><br>
                        <?= htmlspecialchars($order['province']) ?> 
                        <?= htmlspecialchars($order['postal_code']) ?>
                    </p>
                </div>
            </div>

            <?php if (!is_null($order['return_status'])): ?>
            <div class="return-info-card <?= $order['return_status'] ?>" id="return-details">
                <div class="d-flex justify-content-between align-items-start">
                    <div style="flex: 1;">
                        <h5 class="mb-3">
                            <i class="bi bi-arrow-counterclockwise"></i> Return Request Details
                        </h5>
                        
                        <div class="return-detail">
                            <strong>Return ID:</strong>
                            #<?= $order['return_id'] ?>
                        </div>

                        <div class="return-detail">
                            <strong>Status:</strong>
                            <?= getReturnStatusBadge($order['return_status']) ?>
                        </div>

                        <div class="return-detail">
                            <strong>Requested:</strong>
                            <?= date("M d, Y h:i A", strtotime($order['return_created_at'])) ?>
                        </div>

                        <div class="return-detail">
                            <strong>Last Updated:</strong>
                            <?= date("M d, Y h:i A", strtotime($order['return_updated_at'])) ?>
                        </div>

                        <div class="return-detail">
                            <strong>Reason:</strong>
                            <?= ucfirst(str_replace('_', ' ', $order['reason'])) ?>
                        </div>

                        <?php if (!empty($order['return_comments'])): ?>
                        <div class="return-detail">
                            <strong>Your Comments:</strong><br>
                            <div class="bg-white p-2 rounded mt-1" style="border-left: 3px solid #0d6efd;">
                                <?= nl2br(htmlspecialchars($order['return_comments'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="return-detail">
                            <strong>Video Proof:</strong><br>
                            <a href="../<?= htmlspecialchars($order['video_proof_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="bi bi-play-circle"></i> Watch Video
                            </a>
                        </div>

                        <?php if (!empty($order['admin_notes'])): ?>
                        <div class="return-detail">
                            <strong>Admin Response:</strong><br>
                            <div class="bg-white p-2 rounded mt-1" style="border-left: 3px solid #dc3545;">
                                <?= nl2br(htmlspecialchars($order['admin_notes'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                     alt="<?= htmlspecialchars($item['product_name']) ?>"
                                                     class="rounded me-3"
                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                            <?php endif; ?>
                                            <span><?= htmlspecialchars($item['product_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>₱<?= number_format($item['price_at_time'], 2) ?></td>
                                    <td>₱<?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 100px;">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (excl. VAT):</span>
                        <span>₱<?= number_format($order['subtotal'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>VAT (20%):</span>
                        <span>₱<?= number_format($order['vat'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery Fee:</span>
                        <span>₱<?= number_format($order['delivery_fee'], 2) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total:</span>
                        <span class="text-danger">₱<?= number_format($order['total'], 2) ?></span>
                    </div>
                </div>
                
                <div class="card-footer bg-white">
                    <?php if ($order['order_status'] === 'processing' && is_null($order['return_status'])): ?>
                        <a href="cancel_order.php?order_id=<?= $order['order_id'] ?>" 
                           class="btn btn-outline-danger w-100 no-print"
                           onclick="return confirm('Are you sure you want to cancel this order?')">
                            Cancel Order
                        </a>
                    <?php elseif ($order['order_status'] === 'delivered' && is_null($order['return_status'])): ?>
                        <button class="btn btn-success w-100 mb-2 no-print" disabled>
                            Order Completed
                        </button>
                        <a href="../products/product.php" class="btn btn-outline-danger w-100 no-print">
                            Order Again
                        </a>
                    <?php elseif (!is_null($order['return_status'])): ?>
                        <a href="#return-details" class="btn btn-outline-danger w-100 no-print" onclick="event.preventDefault(); document.getElementById('return-details').scrollIntoView({ behavior: 'smooth' });">
                            View Return Status
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mt-4 no-print">
                <div class="card-body text-center">
                    <h6>Need Help?</h6>
                    <p class="text-muted small">Contact our customer support</p>
                    <a href="#" class="btn btn-sm btn-outline-danger">
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </div>
    
</div>

<?php if ($order['return_status'] && ($order['return_status'] === 'approved' || $refund)): ?>
<div style="display: flex; justify-content: center; margin: 2rem 0;">
    <div style="width: 100%; max-width: 1300px;">
        <div class="return-info-card" id="refund-status" style="margin-bottom: 0;">
            <h5 class="mb-3">
                <i class="bi bi-cash-coin"></i> Refund Status
            </h5>
            
            <?php 
            $refund_display = getRefundStatusDisplay($refund, $order['pay_method'], $order['return_status']);
            if ($refund_display):
            ?>
                <div class="return-detail">
                    <strong>Status:</strong>
                    <?= $refund_display['badge'] ?>
                </div>

                <div class="return-detail">
                    <strong>Amount:</strong>
                    ₱<?= number_format($order['total'], 2) ?>
                </div>

                <div class="return-detail">
                    <strong>Payment Method:</strong>
                    <?= ucfirst($order['pay_method']) ?>
                </div>

                <?php if ($refund): ?>
                    <div class="return-detail">
                        <strong>Processed On:</strong>
                        <?= date('M d, Y h:i A', strtotime($refund['created_at'])) ?>
                    </div>

                    <?php if (!empty($refund['stripe_refund_id'])): ?>
                    <div class="return-detail">
                        <strong>Transaction ID:</strong><br>
                        <code style="font-size: 0.85rem; word-break: break-all;"><?= $refund['stripe_refund_id'] ?></code>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="return-detail">
                    <strong>Details:</strong><br>
                    <div class="bg-white p-2 rounded mt-1" style="border-left: 3px solid #0d6efd; font-size: 0.9rem;">
                        <p class="mb-0"><?= $refund_display['text'] ?></p>
                        <?php if (isset($refund_display['details'])): ?>
                            <small class="text-muted d-block mt-1"><?= $refund_display['details'] ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Timeline for Stripe Refunds -->
                <?php if ($order['pay_method'] === 'stripe' && $refund && $refund['status'] === 'completed'): ?>
                    <div class="return-detail mt-3">
                        <strong>Expected Timeline:</strong><br>
                                <ul class="small mb-0 mt-2">
                                            <li><strong>Initiated:</strong> <?= date('M d, Y', strtotime($refund['created_at'])) ?></li>
                                            <li><strong>Processing:</strong> 3-5 business days</li>
                                            <li><strong>Arrival:</strong> Amount will appear in your account</li>
                                        </ul>
                    </div>
                <?php elseif ($order['pay_method'] === 'cod' && $order['return_status'] === 'approved' && !$refund): ?>
                    <div class="return-detail">
                        <strong>Action Required:</strong><br>
                        <div class="bg-white p-2 rounded mt-1" style="border-left: 3px solid #ff9800;">
                            <p class="mb-2 small">To receive your refund of ₱<?= number_format($order['total'], 2) ?>, please provide your payment details by replying to the latest order update email or contacting our support team with one of the following:</p>
                            <ul class="mb-0 small">
                                <li>Stripe or PayPal account email</li>
                                <li>GCash, e-Wallet, or Mobile wallet number</li>
                                <li>Bank account details (Account number and Bank name)</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="no-print">
    <?php include("../headfoot/footer.php"); ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style media="print">
    .no-print { display: none !important; }
    body { background: white !important; margin-top: 0 !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
    .container { margin-top: 0 !important; padding-top: 0 !important; }
</style>
</body>
</html>