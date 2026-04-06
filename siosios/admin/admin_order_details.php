<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: admin_orders.php");
    exit;
}

$order_id = (int)$_GET['id'];

// Fetch order details
$stmt = $con->prepare("SELECT o.*, u.name as customer_name, u.email, u.contact_num 
                       FROM orders o 
                       JOIN userss u ON o.user_id = u.user_id 
                       WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

logAuditTrail(
    $con,
    $_SESSION['admin_id'],
    $_SESSION['admin_name'],
    $_SESSION['admin_role'],
    'order_view',
    "Viewed order details for order #{$order_id}",
    'orders',
    $order_id
);

$stmt->close();

if (!$order) {
    header("Location: admin_orders.php");
    exit;
}

// Fetch order items
$stmt = $con->prepare("SELECT oi.*, p.image_url 
                       FROM order_items oi 
                       LEFT JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();

logAuditTrail(
    $con,
    $_SESSION['admin_id'],
    $_SESSION['admin_name'],
    $_SESSION['admin_role'],
    'order_view',
    "Viewed order details for order #{$order_id}",
    'orders',
    $order_id
);

$stmt->close();


// After updating order status, check if payment status changed to 'paid'
if (isset($_POST['update_payment_status']) && $_POST['pay_status'] === 'paid') {
    // Get order items and reduce stock
    $stmt = $con->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    $stmt_stock = $con->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    while ($item = $items->fetch_assoc()) {
        $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_stock->execute();
    }
    $stmt_stock->close();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order_id ?> - SioSio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <style>
        :root {
            --siosio-red: #dc3545;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
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
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            font-family: 'Joti One', cursive;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .sio-highlight {
            color: var(--siosio-red);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(220, 53, 69, 0.1);
            color: white;
            border-left: 3px solid var(--siosio-red);
        }
        
        .sidebar-menu i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
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
            margin-bottom: 1.5rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6c757d;
            font-weight: 600;
        }
        
        .item-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        @media print {
            .sidebar, .top-bar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 0;
            }
            .content-card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Admin</h3>
                <p class="mb-0 small text-muted">Management Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php" ><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php" class="active"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                 <li><a href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
                <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar d-flex justify-content-between align-items-center no-print">
                <div>
                    <h4 class="mb-0">Order Details #<?= $order_id ?></h4>
                    <small class="text-muted">
                        <a href="admin_orders.php" class="text-decoration-none">Orders</a> / Details
                    </small>
                </div>
                <div>
                    <a href="admin_orders.php" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button onclick="window.print()" class="btn btn-danger">
                        <i class="bi bi-printer"></i> Print Invoice
                    </button>
                </div>
            </div>
            
            <!-- Invoice Header -->
            <div class="content-card">
                <div class="row">
                    <div class="col-md-6">
                        <h2 class="mb-0" style="font-family: 'Joti One', cursive;">
                            <span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Store
                        </h2>
                        <p class="mb-0">Authentic Filipino Siomai & Siopao</p>
                        <p class="mb-0 small text-muted">contact@siosio.com | +63 123 456 7890</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <h5>INVOICE</h5>
                        <p class="mb-0"><strong>Order ID:</strong> #<?= $order_id ?></p>
                        <p class="mb-0"><strong>Tracking:</strong> <?= htmlspecialchars($order['tracking_number']) ?></p>
                        <p class="mb-0"><strong>Date:</strong> <?= date('F d, Y', strtotime($order['created_at'])) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Customer & Shipping Info -->
            <div class="row">
                <div class="col-md-6">
                    <div class="content-card">
                        <h5 class="mb-3"><i class="bi bi-person"></i> Customer Information</h5>
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span><?= htmlspecialchars($order['customer_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span><?= htmlspecialchars($order['email']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span><?= htmlspecialchars($order['contact_num'] ?: 'N/A') ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="content-card">
                        <h5 class="mb-3"><i class="bi bi-truck"></i> Shipping Information</h5>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span><?= htmlspecialchars($order['address_line1']) ?></span>
                        </div>
                        <?php if ($order['address_line2']): ?>
                        <div class="info-row">
                            <span class="info-label">Address Line 2:</span>
                            <span><?= htmlspecialchars($order['address_line2']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">City:</span>
                            <span><?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['barangay']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Province:</span>
                            <span><?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['postal_code']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Courier:</span>
                            <span><?= htmlspecialchars($order['Courier']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
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

            
            <!-- Order Summary -->
            <div class="row">
                <div class="col-md-6">
                    <div class="content-card">
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Order Status</h5>
                        <div class="info-row">
                            <span class="info-label">Order Status:</span>
                            <span class="badge bg-primary"><?= ucfirst($order['order_status']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Status:</span>
                            <span class="badge bg-<?= $order['pay_status'] === 'paid' ? 'success' : 'warning' ?>">
                                <?= ucfirst($order['pay_status']) ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Method:</span>
                            <span><?= strtoupper($order['pay_method']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="content-card">
                        <h5 class="mb-3"><i class="bi bi-calculator"></i> Order Summary</h5>
                        <div class="info-row">
                            <span class="info-label">Subtotal:</span>
                            <span>₱<?= number_format($order['subtotal'], 2) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">VAT (20%):</span>
                            <span>₱<?= number_format($order['vat'], 2) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Delivery Fee:</span>
                            <span>₱<?= number_format($order['delivery_fee'], 2) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><strong>TOTAL:</strong></span>
                            <span class="text-danger"><strong>₱<?= number_format($order['total'], 2) ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4 no-print">
                <p class="text-muted">Thank you for your business!</p>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function() {
            const timeoutInMilliseconds = 1800000; // 30 minutes (30 * 60 * 1000)
            let inactivityTimer;

            // Function to redirect to the logout page
            function logout() {
                // You can add a reason for a custom message on the login page
                window.location.href = 'admin_logout.php?reason=idle';
            }

            // Function to reset the timer
            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(logout, timeoutInMilliseconds);
            }

            // Reset the timer whenever the user interacts with the page
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