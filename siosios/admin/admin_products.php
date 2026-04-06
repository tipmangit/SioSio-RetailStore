<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$message = '';
$messageType = '';

// Check for session messages
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'info';
    
    // Clear the messages from session after displaying
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}


// Handle product status toggle
if (isset($_POST['toggle_status'])) {
    $product_id = (int)$_POST['product_id'];
    $current_status = $_POST['current_status'];
    $new_status = ($current_status === 'active') ? 'inactive' : 'active';
    
    $stmt = $con->prepare("UPDATE products SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $product_id);
    
    if ($stmt->execute()) {
        $message = "Product status updated successfully!";
        $messageType = "success";
        logAuditTrail(
            $con,
            $_SESSION['admin_id'],
            $_SESSION['admin_name'],
            $_SESSION['admin_role'],
            'product_status_change',
            "Product ID #{$product_id} status changed to '{$new_status}'",
            'products',
            $product_id,
            ['status' => $current_status],
            ['status' => $new_status]
        );
    }
    $stmt->close();
}

// Handle product deletion (PERMANENT)
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];

    if (empty($product_id)) {
        $message = "Invalid product ID.";
        $messageType = "danger";
    } else {
        // --- DANGEROUS ACTION: This will permanently delete the product and related data. ---
        
        // Start a transaction
        $con->begin_transaction();
        
        try {
            // 1. Get product details for logging before deletion
            $prod_stmt = $con->prepare("SELECT name FROM products WHERE id = ?");
            $prod_stmt->bind_param("i", $product_id);
            $prod_stmt->execute();
            $prod_result = $prod_stmt->get_result();
            $prod_row = $prod_result->fetch_assoc();
            $product_name = $prod_row ? $prod_row['name'] : "ID:{$product_id}";
            $prod_stmt->close();

            // 2. Delete related data in child tables FIRST
            
            // Delete from order_items (assuming this table references product_id)
            $delete_items_stmt = $con->prepare("DELETE FROM order_items WHERE product_id = ?");
            if ($delete_items_stmt) {
                 $delete_items_stmt->bind_param("i", $product_id);
                 $delete_items_stmt->execute();
                 $delete_items_stmt->close();
            } else {
                 throw new Exception("Failed to prepare statement for order_items: " . $con->error);
            }

            // Delete from notifications related to this product
             $delete_notif_stmt = $con->prepare("DELETE FROM notifications WHERE product_id = ?");
             if ($delete_notif_stmt) {
                 $delete_notif_stmt->bind_param("i", $product_id);
                 $delete_notif_stmt->execute();
                 $delete_notif_stmt->close();
             } else {
                  throw new Exception("Failed to prepare statement for notifications: " . $con->error);
             }

            // --- ADD DELETES FOR OTHER RELATED TABLES HERE ---
            // Example: $con->prepare("DELETE FROM cart_items WHERE product_id = ?")->execute([$product_id]);
            // Example: $con->prepare("DELETE FROM reviews WHERE product_id = ?")->execute([$product_id]);
            // --------------------------------------------------

            // 3. Finally, delete the product itself
            $delete_prod_stmt = $con->prepare("DELETE FROM products WHERE id = ?");
             if (!$delete_prod_stmt) {
                  throw new Exception("Failed to prepare statement for products: " . $con->error);
             }
            $delete_prod_stmt->bind_param("i", $product_id);
            $delete_prod_stmt->execute();
            
            if ($delete_prod_stmt->affected_rows > 0) {
                // All deletes were successful, commit the transaction
                $con->commit();
                
                $message = "Product '$product_name' (ID: #{$product_id}) and related data permanently deleted.";
                $messageType = "success";

                logAuditTrail(
                    $con,
                    $_SESSION['admin_id'],
                    $_SESSION['admin_name'],
                    $_SESSION['admin_role'],
                    'product_delete_permanent', // Distinct log type
                    "Permanently deleted product '$product_name' (ID: {$product_id}) and related data.",
                    'products',
                    $product_id
                );
                
            } else {
                // Product might have already been deleted or ID was invalid
                throw new Exception("Product not found or final delete failed. No changes were made.");
            }
            $delete_prod_stmt->close();

        } catch (Exception $e) {
            // Something went wrong, roll back all database changes
            $con->rollback();
            
            $message = "Failed to permanently delete product. Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
// Fetch all products
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

$sql = "SELECT * FROM products WHERE 1=1";
if ($search) {
    $sql .= " AND name LIKE '%" . $con->real_escape_string($search) . "%'";
}
if ($category_filter) {
    $sql .= " AND category = '" . $con->real_escape_string($category_filter) . "'";
}
$sql .= " ORDER BY id DESC";

$products = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - SioSio Admin</title>
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
        
        .sidebar-menu li {
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
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #842029;
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
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
        
        .btn-action {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
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
                <li><a href="admin_products.php" class="active"><i class="bi bi-box-seam"></i> Products</a></li>
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
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h4 class="mb-0">Products Management</h4>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            
            
            <div class="content-card">
                <!-- Filters & Add Button -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form method="GET" class="row g-2">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <option value="siomai" <?= $category_filter === 'siomai' ? 'selected' : '' ?>>Siomai</option>
                                    <option value="siopao" <?= $category_filter === 'siopao' ? 'selected' : '' ?>>Siopao</option>
                                    <option value="bundle" <?= $category_filter === 'bundle' ? 'selected' : '' ?>>Bundles</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="admin_product_add.php" class="btn btn-danger">
                            <i class="bi bi-plus-circle"></i> Add New Product
                        </a>
                    </div>
                </div>
                
                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products->num_rows === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No products found</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>" 
                                             class="product-img">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($product['name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 50)) ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-category badge-<?= $product['category'] ?>">
                                            <?= ucfirst($product['category']) ?>
                                        </span>
                                    </td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td>
                                        <?php if ($product['quantity'] < 20 && $product['quantity'] > 0): ?>
                                            <span class="text-warning fw-bold"><?= $product['quantity'] ?></span>
                                            <i class="bi bi-exclamation-triangle text-warning"></i>
                                        <?php elseif ($product['quantity'] == 0): ?>
                                            <span class="text-danger fw-bold"><?= $product['quantity'] ?></span>
                                            <i class="bi bi-x-circle text-danger"></i>
                                        <?php else: ?>
                                            <?= $product['quantity'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $product['status'] ?>">
                                            <button type="submit" name="toggle_status" 
                                                    class="badge-<?= $product['status'] ?> border-0">
                                                <?= ucfirst($product['status']) ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="admin_product_add.php?id=<?= $product['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary btn-action">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" name="delete_product" 
                                                    class="btn btn-sm btn-outline-danger btn-action">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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