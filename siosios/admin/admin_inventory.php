<?php
session_start();
include("admin_session_check.php");
include("../config.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$message = '';
$messageType = '';

// Handle stock update
if (isset($_POST['update_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $new_quantity = (int)$_POST['quantity'];
    
    $stmt = $con->prepare("UPDATE products SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_quantity, $product_id);
    
    if ($stmt->execute()) {
        $message = "Stock updated successfully!";
        $messageType = "success";
        logAuditTrail(
        $con,
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_role'],
        'inventory_update',
        "Updated inventory for product ID #{$product_id} to {$new_quantity}",
        'products',
        $product_id,
        null,
        ['quantity' => $new_quantity]
    );
    } else {
        $message = "Error updating stock.";
        $messageType = "danger";
    }
    $stmt->close();
}

// Fetch inventory data
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql = "SELECT * FROM products WHERE 1=1";

switch ($filter) {
    case 'low':
        $sql .= " AND quantity < 20";
        break;
    case 'out':
        $sql .= " AND quantity = 0";
        break;
    case 'siomai':
        $sql .= " AND category = 'siomai'";
        break;
    case 'siopao':
        $sql .= " AND category = 'siopao'";
        break;
    case 'bundle':
        $sql .= " AND category = 'bundle'";
        break;
}
$sql .= " ORDER BY quantity ASC";

$products = $con->query($sql);

// Statistics
$total_products = $con->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$low_stock = $con->query("SELECT COUNT(*) as total FROM products WHERE quantity < 20")->fetch_assoc()['total'];
$out_of_stock = $con->query("SELECT COUNT(*) as total FROM products WHERE quantity = 0")->fetch_assoc()['total'];
$total_value = $con->query("SELECT SUM(price * quantity) as total FROM products")->fetch_assoc()['total'] ?? 0;
$bundle_count = $con->query("SELECT COUNT(*) as total FROM products WHERE category = 'bundle'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - SioSio Admin</title>
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
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0;
            font-size: 2rem;
            color: var(--siosio-red);
        }
        
        .stat-card p {
            margin: 0.5rem 0 0 0;
            color: #6c757d;
        }
        
        .stock-critical {
            color: #dc3545;
            font-weight: bold;
        }
        
        .stock-low {
            color: #ffc107;
            font-weight: bold;
        }
        
        .stock-good {
            color: #198754;
        }
        
        .filter-btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .badge-category {
            padding: 0.35rem 0.65rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-siomai {
            background: #cfe2ff;
            color: #084298;
        }
        
        .badge-siopao {
            background: #ffeeba;
            color: #997404;
        }
        
        .badge-bundle {
            background: #d1ecf1;
            color: #0c5460;
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
                <li><a href="admin_inventory.php" class="active"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
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
                <h4 class="mb-0">Inventory Management</h4>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?= $total_products ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 class="text-warning"><?= $low_stock ?></h3>
                        <p>Low Stock Items</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 class="text-danger"><?= $out_of_stock ?></h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3 class="text-success">₱<?= number_format($total_value, 2) ?></h3>
                        <p>Inventory Value</p>
                    </div>
                </div>
            </div>
            
            <div class="content-card">
                <div class="mb-4">
                    <a href="admin_inventory.php?filter=all" 
                       class="btn btn-<?= $filter === 'all' ? 'danger' : 'outline-secondary' ?> filter-btn">
                        All Products (<?= $total_products ?>)
                    </a>
                    <a href="admin_inventory.php?filter=low" 
                       class="btn btn-<?= $filter === 'low' ? 'warning' : 'outline-secondary' ?> filter-btn">
                        <i class="bi bi-exclamation-triangle"></i> Low Stock (<?= $low_stock ?>)
                    </a>
                    <a href="admin_inventory.php?filter=out" 
                       class="btn btn-<?= $filter === 'out' ? 'danger' : 'outline-secondary' ?> filter-btn">
                        <i class="bi bi-x-circle"></i> Out of Stock (<?= $out_of_stock ?>)
                    </a>
                    <a href="admin_inventory.php?filter=siomai" 
                       class="btn btn-<?= $filter === 'siomai' ? 'info' : 'outline-secondary' ?> filter-btn">
                        Siomai
                    </a>
                    <a href="admin_inventory.php?filter=siopao" 
                       class="btn btn-<?= $filter === 'siopao' ? 'info' : 'outline-secondary' ?> filter-btn">
                        Siopao
                    </a>
                    <a href="admin_inventory.php?filter=bundle" 
                       class="btn btn-<?= $filter === 'bundle' ? 'info' : 'outline-secondary' ?> filter-btn">
                        <i class="bi bi-box"></i> Bundles (<?= $bundle_count ?>)
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Current Stock</th>
                                <th>Stock Value</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products->num_rows === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No products found for this filter</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($product = $products->fetch_assoc()): 
                                    $stock_value = $product['price'] * $product['quantity'];
                                    $stock_class = $product['quantity'] == 0 ? 'stock-critical' : 
                                                   ($product['quantity'] < 20 ? 'stock-low' : 'stock-good');
                                ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($product['name']) ?></strong></td>
                                    <td>
                                        <span class="badge badge-category badge-<?= $product['category'] ?>">
                                            <?= ucfirst($product['category']) ?>
                                        </span>
                                    </td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td class="<?= $stock_class ?>">
                                        <?= $product['quantity'] ?>
                                        <?php if ($product['quantity'] < 20 && $product['quantity'] > 0): ?>
                                            <i class="bi bi-exclamation-triangle text-warning"></i>
                                        <?php elseif ($product['quantity'] == 0): ?>
                                            <i class="bi bi-x-circle text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>₱<?= number_format($stock_value, 2) ?></td>
                                    <td>
                                        <?php if ($product['quantity'] == 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php elseif ($product['quantity'] < 20): ?>
                                            <span class="badge bg-warning">Low Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#stockModal<?= $product['id'] ?>">
                                            <i class="bi bi-pencil"></i> Update
                                        </button>
                                        
                                        <div class="modal fade" id="stockModal<?= $product['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Stock - <?= htmlspecialchars($product['name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Current Stock: <strong><?= $product['quantity'] ?></strong></label>
                                                                <small class="text-muted d-block">Category: <strong><?= ucfirst($product['category']) ?></strong></small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="quantity<?= $product['id'] ?>" class="form-label">New Stock Quantity</label>
                                                                <input type="number" class="form-control" 
                                                                       id="quantity<?= $product['id'] ?>" 
                                                                       name="quantity" 
                                                                       value="<?= $product['quantity'] ?>" 
                                                                       min="0" required>
                                                            </div>
                                                            <div class="alert alert-info">
                                                                <i class="bi bi-info-circle"></i> 
                                                                <?php if ($product['category'] === 'bundle'): ?>
                                                                    This is a bundle pack. Update quantity based on available combo packs.
                                                                <?php else: ?>
                                                                    Set stock to 0 for out of stock items.
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_stock" class="btn btn-danger">
                                                                <i class="bi bi-check-circle"></i> Update Stock
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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