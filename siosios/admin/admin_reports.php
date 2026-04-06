<?php
// admin_reports.php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Date range filter
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Sales Summary
$sales_summary = $con->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total) as total_revenue,
        SUM(subtotal) as subtotal,
        SUM(vat) as total_vat,
        SUM(delivery_fee) as total_delivery,
        AVG(total) as avg_order_value
    FROM orders 
    WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
    AND pay_status != 'failed'
")->fetch_assoc();

// Top Selling Products
$top_products = $con->query("
    SELECT 
        p.name,
        p.category,
        p.price,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'
    AND o.pay_status != 'failed'
    GROUP BY oi.product_id
    ORDER BY total_sold DESC
    LIMIT 10
");

// Sales by Category
$category_sales = $con->query("
    SELECT 
        p.category,
        COUNT(DISTINCT oi.order_id) as orders,
        SUM(oi.quantity) as items_sold,
        SUM(oi.quantity * oi.price) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'
    AND o.pay_status != 'failed'
    GROUP BY p.category
");

// Daily Sales (for chart)
$daily_sales = $con->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders,
        SUM(total) as revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
    AND pay_status != 'failed'
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");

$chart_data = [];
while ($row = $daily_sales->fetch_assoc()) {
    $chart_data[] = $row;
}

// Customer insights
$customer_stats = $con->query("
    SELECT 
        COUNT(DISTINCT user_id) as total_customers,
        COUNT(*) / COUNT(DISTINCT user_id) as avg_orders_per_customer
    FROM orders
    WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
")->fetch_assoc();

// Top customers
$top_customers = $con->query("
    SELECT 
        u.name,
        u.email,
        COUNT(o.order_id) as order_count,
        SUM(o.total) as total_spent
    FROM orders o
    JOIN userss u ON o.user_id = u.user_id
    WHERE DATE(o.created_at) BETWEEN '$date_from' AND '$date_to'
    AND o.pay_status != 'failed'
    GROUP BY o.user_id
    ORDER BY total_spent DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - SioSio Admin</title>
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
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .metric-card {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border: 1px solid #e9ecef;
        }
        
        .metric-value {
            font-size: 1.75rem;
            font-weight: bold;
            color: var(--siosio-red);
            margin: 0.5rem 0;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        @media print {
            .sidebar, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
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
                <li><a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php" class="active"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                <li><a href="admin_cms.php"><i class="bi bi-file-text"></i> Content Management</a></li>
                <li><a href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
                <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar d-flex justify-content-between align-items-center no-print">
                <h4 class="mb-0">Sales Reports & Analytics</h4>
                <button onclick="window.print()" class="btn btn-danger">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
            
            <!-- Date Filter -->
            <div class="content-card no-print">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-filter"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Sales Summary -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="metric-card">
                        <i class="bi bi-currency-dollar text-danger" style="font-size: 2rem;"></i>
                        <div class="metric-value">₱<?= number_format($sales_summary['total_revenue'] ?? 0, 2) ?></div>
                        <div class="metric-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-card">
                        <i class="bi bi-cart-check text-primary" style="font-size: 2rem;"></i>
                        <div class="metric-value"><?= $sales_summary['total_orders'] ?? 0 ?></div>
                        <div class="metric-label">Total Orders</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-card">
                        <i class="bi bi-graph-up text-success" style="font-size: 2rem;"></i>
                        <div class="metric-value">₱<?= number_format($sales_summary['avg_order_value'] ?? 0, 2) ?></div>
                        <div class="metric-label">Average Order Value</div>
                    </div>
                </div>
            </div>
            
            <!-- Sales Chart -->
            <div class="content-card">
                <h5 class="mb-3"><i class="bi bi-bar-chart"></i> Daily Sales Trend</h5>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <!-- Two Columns -->
            <div class="row">
                <!-- Top Products -->
                <div class="col-md-6">
                    <div class="content-card">
                        <h5 class="mb-3"><i class="bi bi-trophy"></i> Top Selling Products</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $top_products->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                            <small class="text-muted"><?= ucfirst($product['category']) ?></small>
                                        </td>
                                        <td><?= $product['total_sold'] ?> pcs</td>
                                        <td><strong>₱<?= number_format($product['revenue'], 2) ?></strong></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top Customers -->
                <div class="col-md-6">
                    <div class="content-card">
                        <h5 class="mb-3"><i class="bi bi-people"></i> Top Customers</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($customer = $top_customers->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($customer['name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($customer['email']) ?></small>
                                        </td>
                                        <td><?= $customer['order_count'] ?></td>
                                        <td><strong>₱<?= number_format($customer['total_spent'], 2) ?></strong></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Category Performance -->
            <div class="content-card">
                <h5 class="mb-3"><i class="bi bi-pie-chart"></i> Sales by Category</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Orders</th>
                                <th>Items Sold</th>
                                <th>Revenue</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_category_revenue = 0;
                            $categories = [];
                            while ($cat = $category_sales->fetch_assoc()) {
                                $categories[] = $cat;
                                $total_category_revenue += $cat['revenue'];
                            }
                            
                            foreach ($categories as $cat):
                                $percentage = $total_category_revenue > 0 ? ($cat['revenue'] / $total_category_revenue) * 100 : 0;
                            ?>
                            <tr>
                                <td><strong><?= ucfirst($cat['category']) ?></strong></td>
                                <td><?= $cat['orders'] ?></td>
                                <td><?= $cat['items_sold'] ?></td>
                                <td><strong>₱<?= number_format($cat['revenue'], 2) ?></strong></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-danger" role="progressbar" 
                                             style="width: <?= $percentage ?>%">
                                            <?= number_format($percentage, 1) ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
        const salesData = <?= json_encode($chart_data) ?>;
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: salesData.map(d => d.date),
                datasets: [{
                    label: 'Daily Revenue (₱)',
                    data: salesData.map(d => d.revenue),
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: '#dc3545',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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