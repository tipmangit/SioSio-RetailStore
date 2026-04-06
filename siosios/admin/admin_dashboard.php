<?php
session_start();
include("admin_session_check.php");
include("../config.php");
require_once(__DIR__ . '/../notification_functions.php');
require_once('audit_trail_functions.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch dashboard statistics
$stats = [];

// Total Products
$result = $con->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $result->fetch_assoc()['total'];

// Total Orders
$result = $con->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $result->fetch_assoc()['total'];

// Total Users
$result = $con->query("SELECT COUNT(*) as total FROM userss");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total Revenue
$result = $con->query("SELECT SUM(total) as revenue FROM orders WHERE pay_status != 'failed'");
$stats['total_revenue'] = $result->fetch_assoc()['revenue'] ?? 0;

// Low Stock Products
$result = $con->query("SELECT COUNT(*) as total FROM products WHERE quantity < 20");
$stats['low_stock'] = $result->fetch_assoc()['total'];

// Pending Orders
$result = $con->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'processing'");
$stats['pending_orders'] = $result->fetch_assoc()['total'];

// Recent Orders
$recent_orders = $con->query("SELECT o.*, u.name as customer_name FROM orders o JOIN userss u ON o.user_id = u.user_id ORDER BY o.created_at DESC LIMIT 5");

// Sales data for chart (last 7 days)
$sales_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $result = $con->query("SELECT COALESCE(SUM(total), 0) as daily_sales FROM orders WHERE DATE(created_at) = '$date' AND pay_status != 'failed'");
    $sales_data[] = [
        'date' => date('M d', strtotime($date)),
        'sales' => $result->fetch_assoc()['daily_sales']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SioSio Store</title>
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
            z-index: 1000;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.red { background: rgba(220, 53, 69, 0.1); color: var(--siosio-red); }
        .stat-icon.blue { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
        .stat-icon.green { background: rgba(25, 135, 84, 0.1); color: #198754; }
        .stat-icon.orange { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: #1a1a1a;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            margin: 0;
        }
        
        .content-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .content-card h5 {
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .badge-status {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-processing { background: #fff3cd; color: #856404; }
        .badge-shipped { background: #cfe2ff; color: #084298; }
        .badge-delivered { background: #d1e7dd; color: #0f5132; }
        .badge-cancelled { background: #f8d7da; color: #842029; }
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
                <li><a href="admin_dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
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
                <div>
                    <h4 class="mb-0">Dashboard</h4>
                    <small class="text-muted">Welcome back, Admin!</small>
                </div>
                <div class="top-bar-right">
                    <!-- Notification Bell -->
                    <?php include(__DIR__ . '/../notification_dropdown.php'); ?>
                    
                    <div>
                        <span class="badge bg-danger"><?= $stats['pending_orders'] ?> Pending Orders</span>
                        <span class="badge bg-warning text-dark ms-2"><?= $stats['low_stock'] ?> Low Stock</span>
                    </div>
                </div>
            </div>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <h3 class="stat-value">₱<?= number_format($stats['total_revenue'], 2) ?></h3>
                        <p class="stat-label">Total Revenue</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <h3 class="stat-value"><?= $stats['total_orders'] ?></h3>
                        <p class="stat-label">Total Orders</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h3 class="stat-value"><?= $stats['total_products'] ?></h3>
                        <p class="stat-label">Products</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="bi bi-people"></i>
                        </div>
                        <h3 class="stat-value"><?= $stats['total_users'] ?></h3>
                        <p class="stat-label">Registered Users</p>
                    </div>
                </div>
            </div>
            
            <div class="content-card">
                <h5><i class="bi bi-graph-up"></i> Sales Overview (Last 7 Days)</h5>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <div class="content-card">
                <h5><i class="bi bi-cart-check"></i> Recent Orders</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Tracking</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $recent_orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= htmlspecialchars($order['tracking_number']) ?></td>
                                <td>₱<?= number_format($order['total'], 2) ?></td>
                                <td>
                                    <span class="badge-status badge-<?= $order['order_status'] ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="admin_order_details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Sales Chart
        const salesData = <?= json_encode($sales_data) ?>;
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: salesData.map(d => d.date),
                datasets: [{
                    label: 'Sales (₱)',
                    data: salesData.map(d => d.sales),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

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