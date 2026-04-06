<?php
session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');
require_once('../notification_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$is_edit = isset($_GET['id']);
$product = null;
$message = '';
$messageType = '';

// Fetch product data if editing
if ($is_edit) {
    $product_id = (int)$_GET['id'];
    $stmt = $con->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        header("Location: admin_products.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $status = $_POST['status'];
    
    // Get product ID from form (will be 0 if adding new)
    $product_id_from_form = (int)($_POST['product_id'] ?? 0); 
    $errors = [];

    // Server-side validation
    if (!is_numeric($price) || (float)$price < 0) {
        $errors[] = "Price must be a valid, non-negative number.";
    }
    if ($quantity > 500000) {
        $errors[] = "Stock quantity cannot exceed 500,000.";
    }
    if (empty($name)) {
        $errors[] = "Product name is required.";
    }
    if (!in_array($category, ['siomai', 'siopao', 'bundle'])) {
        $errors[] = "Invalid category selected.";
    }

    // Check for duplicate product name
    if (!empty($name)) {
        if ($is_edit) {
            // EDIT mode: Check for name duplication, excluding the current product
            $stmt_check = $con->prepare("SELECT id FROM products WHERE name = ? AND id != ?");
            $stmt_check->bind_param("si", $name, $product_id_from_form);
        } else {
            // ADD mode: Check if name already exists anywhere
            $stmt_check = $con->prepare("SELECT id FROM products WHERE name = ?");
            $stmt_check->bind_param("s", $name);
        }
        
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $errors[] = "Product name '" . htmlspecialchars($name) . "' already exists. Please use a different name.";
        }
        $stmt_check->close();
    }

    if (empty($errors)) {
        $price = (float)$price;
        
        // Image upload handling
        $image_path_to_db = $_POST['existing_image_path'] ?? '';
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['image_file']['tmp_name'];
            $new_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['image_file']['name']));
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($tmp_name, $destination)) {
                $image_path_to_db = $destination;
            } else {
                $errors[] = "Error: Could not move the uploaded file.";
            }
        }

        if (empty($errors)) {
            if ($is_edit) {
                // UPDATE EXISTING PRODUCT
                $stmt_update = $con->prepare("UPDATE products SET name = ?, category = ?, description = ?, price = ?, quantity = ?, status = ?, image_url = ? WHERE id = ?");
                
                $stmt_old = $con->prepare("SELECT name, category, price, quantity, status FROM products WHERE id = ?");
                $stmt_old->bind_param("i", $product_id_from_form);
                $stmt_old->execute();
                $old_data = $stmt_old->get_result()->fetch_assoc();
                $stmt_old->close();
                if ($stmt_update === false) {
                    $errors[] = "Database error: " . $con->error;
                } else {
                    $stmt_update->bind_param("sssdissi", $name, $category, $description, $price, $quantity, $status, $image_path_to_db, $product_id_from_form);
                    
                    if ($stmt_update->execute()) {
                        $action = "Updated Product ID #{$product_id_from_form}. Details: Name='{$name}', Category='{$category}', Price='{$price}', Quantity='{$quantity}', Status='{$status}'.";
                        // log_audit_trail($con, $_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role'], $action);

                        $_SESSION['message'] = "Product updated successfully!";
                        $_SESSION['message_type'] = "success";
                        header("Location: admin_products.php");
                        logAuditTrail(
                        $con,
                        $_SESSION['admin_id'],
                        $_SESSION['admin_name'],
                        $_SESSION['admin_role'],
                        'product_update',
                        "Updated product '{$name}' (ID: {$product_id_from_form})",
                        'products',
                        $product_id_from_form,
                        $old_data,
                        ['name' => $name, 'category' => $category, 'price' => $price, 'quantity' => $quantity, 'status' => $status]
                    );
                        exit;
                    } else {
                        $errors[] = "Error updating product: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                }
            } else {
                // ADD NEW PRODUCT
                $stmt_insert = $con->prepare("INSERT INTO products (name, category, description, price, quantity, status, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt_insert === false) {
                    $errors[] = "Database error: " . $con->error;
                } else {
                    $stmt_insert->bind_param("sssdiss", $name, $category, $description, $price, $quantity, $status, $image_path_to_db);
                    
                    if ($stmt_insert->execute()) {
                        $new_product_id = $con->insert_id;
                        
                        // Notify users about new product (only if active)
                        if ($status === 'active') {
                           
                            notifyUsersNewProduct($con, $new_product_id);
                        }
                        
                        $_SESSION['message'] = "Product added successfully!";
                        $_SESSION['message_type'] = "success";
                        header("Location: admin_products.php");
                        logAuditTrail(
                        $con,
                        $_SESSION['admin_id'],
                        $_SESSION['admin_name'],
                        $_SESSION['admin_role'],
                        'product_add',
                        "Added new product '{$name}' (ID: {$new_product_id})",
                        'products',
                        $new_product_id,
                        null,
                        ['name' => $name, 'category' => $category, 'price' => $price, 'quantity' => $quantity, 'status' => $status]
                    );
                        exit;
                    } else {
                        $errors[] = "Error adding product: " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                }
            }
        }
    }
    
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = "danger";
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Add' ?> Product - SioSio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <style>
        :root { --siosio-red: #dc3545; --sidebar-width: 260px; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%); color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h3 { font-family: 'Joti One', cursive; margin: 0; font-size: 1.5rem; }
        .sio-highlight { color: var(--siosio-red); }
        .sidebar-menu { list-style: none; padding: 1rem 0; margin: 0; }
        .sidebar-menu a { display: flex; align-items: center; padding: 0.875rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(220, 53, 69, 0.1); color: white; border-left: 3px solid var(--siosio-red); }
        .sidebar-menu i { margin-right: 0.75rem; font-size: 1.2rem; }
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 2rem; width: calc(100% - var(--sidebar-width)); }
        .top-bar { background: white; padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .content-card { background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .form-label { font-weight: 600; color: #333; }
        .product-preview { max-width: 300px; border-radius: 8px; border: 2px solid #e9ecef; padding: 10px; }
        .product-preview img { width: 100%; height: 250px; object-fit: cover; border-radius: 6px; }
        .category-info { font-size: 0.85rem; color: #6c757d; margin-top: 0.25rem; }
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
                <li><a href="admin_products.php" class="active"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
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
        </aside>
        
        <main class="main-content">
            <div class="top-bar d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0"><?= $is_edit ? 'Edit' : 'Add New' ?> Product</h4>
                    <small class="text-muted">
                        <a href="admin_products.php" class="text-decoration-none">Products</a> / 
                        <?= $is_edit ? 'Edit' : 'Add' ?>
                    </small>
                </div>
                <a href="admin_products.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Products
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= $message ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?? '' ?>">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= $product ? htmlspecialchars($product['name']) : '' ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required onchange="updateCategoryInfo()">
                                        <option value="">Select Category</option>
                                        <option value="siomai" <?= ($product && $product['category'] === 'siomai') ? 'selected' : '' ?>>Siomai</option>
                                        <option value="siopao" <?= ($product && $product['category'] === 'siopao') ? 'selected' : '' ?>>Siopao</option>
                                        <option value="bundle" <?= ($product && $product['category'] === 'bundle') ? 'selected' : '' ?>>Bundle Pack</option>
                                    </select>
                                    <div class="category-info" id="categoryInfo"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?= ($product && $product['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($product && $product['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="4" required><?= $product ? htmlspecialchars($product['description']) : '' ?></textarea>
                                <small class="text-muted">For bundles, mention what's included (e.g., "80 pieces of assorted siomai")</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price (₱) *</label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="0.01" min="0" value="<?= $product ? $product['price'] : '' ?>" required onchange="updatePreview()">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Stock Quantity *</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" 
                                           min="0" max="1000000" value="<?= $product ? $product['quantity'] : '' ?>" required onchange="updatePreview()">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image_file" class="form-label">Product Image *</label>
                                <input type="file" class="form-control" id="image_file" name="image_file" accept="image/jpeg, image/png, image/gif" onchange="previewImage(event)">
                                <small class="text-muted">
                                    <?= $is_edit ? 'Leave blank to keep the current image.' : 'An image is required.' ?>
                                </small>
                                <input type="hidden" name="existing_image_path" value="<?= $product ? htmlspecialchars($product['image_url']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Product Preview</label>
                            <div class="product-preview">
                                <img id="preview-image" src="<?= ($product && !empty($product['image_url'])) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/300x300?text=No+Image' ?>" alt="Preview" class="mb-2">
                                <h6 id="preview-name"><?= $product ? htmlspecialchars($product['name']) : 'Product Name' ?></h6>
                                <p id="preview-category" class="badge" style="background-color: #d1ecf1; color: #0c5460;"><?= $product ? ucfirst($product['category']) : 'Category' ?></p>
                                <p id="preview-price" class="text-danger fw-bold">₱<?= $product ? number_format($product['price'], 2) : '0.00' ?></p>
                                <p id="preview-stock" class="small text-muted">Stock: <?= $product ? $product['quantity'] : '0' ?></p>
                                <p id="preview-desc" class="small text-muted"><?= $product ? htmlspecialchars(substr($product['description'], 0, 80)) . '...' : 'Product description' ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="admin_products.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-check-circle"></i> <?= $is_edit ? 'Update' : 'Add' ?> Product
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCategoryInfo() {
            const category = document.getElementById('category').value;
            const categoryInfo = document.getElementById('categoryInfo');
            
            const info = {
                siomai: 'Individual dumpling pieces sold per unit or small packs',
                siopao: 'Individual steamed bun pieces sold per unit or small packs',
                bundle: 'Large quantity packs (e.g., 40pcs, 80pcs bundles for events)'
            };
            
            categoryInfo.textContent = info[category] || '';
        }
        
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }
        
        function updatePreview() {
            document.getElementById('preview-name').textContent = document.getElementById('name').value || 'Product Name';
            document.getElementById('preview-category').textContent = document.getElementById('category').value ? document.getElementById('category').value.charAt(0).toUpperCase() + document.getElementById('category').value.slice(1) : 'Category';
            const price = parseFloat(document.getElementById('price').value) || 0;
            document.getElementById('preview-price').textContent = '₱' + price.toFixed(2);
            document.getElementById('preview-stock').textContent = 'Stock: ' + (document.getElementById('quantity').value || '0');
            const desc = document.getElementById('description').value.substring(0, 80) + (document.getElementById('description').value.length > 80 ? '...' : '');
            document.getElementById('preview-desc').textContent = desc || 'Product description';
        }
        
        document.getElementById('name').addEventListener('input', updatePreview);
        document.getElementById('category').addEventListener('change', updatePreview);
        document.getElementById('description').addEventListener('input', updatePreview);
        
        // Initialize category info on page load
        updateCategoryInfo();
    </script>
</body>
</html>