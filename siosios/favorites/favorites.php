<?php
session_start();
include("../config.php");

// Check login status
$logged_in = isset($_SESSION['user_id']);
$user_id = $logged_in ? (int)$_SESSION['user_id'] : null;

// Handle Add to Cart from Favorites
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart_favorite'])) {
    if (!$logged_in) {
        header("Location: ../loginreg/logreg.php");
        exit;
    }
    
    $product_name = $_POST['product_name'] ?? '';
    $product_price = $_POST['product_price'] ?? 0;
    $product_image = $_POST['product_image'] ?? '';
    $quantity = 1;
    
    // Get product ID from products table
    $stmt = $con->prepare("SELECT id FROM products WHERE name = ? AND price = ? LIMIT 1");
    $stmt->bind_param("sd", $product_name, $product_price);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $product_id = $product['id'];
        
        // Check if already in cart
        $check_stmt = $con->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND status = 'active'");
        $check_stmt->bind_param("ii", $user_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update quantity
            $cart_item = $check_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            $update_stmt = $con->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            $update_stmt->bind_param("ii", $new_quantity, $cart_item['cart_id']);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new cart item
            $insert_stmt = $con->prepare("INSERT INTO cart (user_id, product_id, product_name, quantity, price_at_time, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $insert_stmt->bind_param("iisid", $user_id, $product_id, $product_name, $quantity, $product_price);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        
        $check_stmt->close();
        $_SESSION['cart_message'] = "Product added to cart successfully!";
        header("Location: favorites.php");
        exit;
    } else {
        $_SESSION['cart_error'] = "Product not found!";
        header("Location: favorites.php");
        exit;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SioSio - Favorites</title>
    <link rel="stylesheet" href="favorites.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include("../headfoot/header.php"); ?>

    <main class="favorites-page container py-4" style="margin-top:100px;">
        <h2 class="favorites-title mb-4">Your Favorites</h2>

        <?php if (!$logged_in): ?>
            <div class="alert alert-danger text-center" role="alert">
                You must be <a href="../loginreg/logreg.php" class="alert-link">logged in</a> to access the favorites.
            </div>
        <?php else: ?>
            
            <?php if (isset($_SESSION['cart_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= $_SESSION['cart_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['cart_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['cart_error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-x-circle"></i> <?= $_SESSION['cart_error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['cart_error']); ?>
            <?php endif; ?>
            
            <div class="row g-4">
                <?php
                $stmt = $con->prepare("SELECT * FROM favorites WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $favorites = $stmt->get_result();

                if ($favorites->num_rows > 0):
                    while ($row = $favorites->fetch_assoc()): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 shadow-sm">
                                <img src="<?= htmlspecialchars($row['product_image']) ?>" 
                                    alt="<?= htmlspecialchars($row['product_name']) ?>" 
                                    class="card-img-top" style="object-fit:cover; height:200px;">

                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($row['product_name']) ?></h5>
                                    <p class="card-text text-danger fw-bold fs-5">
                                        ₱<?= number_format($row['product_price'], 2) ?>
                                    </p>

                                    <!-- Add to Cart Form -->
                                    <form method="POST" class="mb-2 mt-auto">
                                        <input type="hidden" name="product_name" value="<?= htmlspecialchars($row['product_name']) ?>">
                                        <input type="hidden" name="product_price" value="<?= $row['product_price'] ?>">
                                        <input type="hidden" name="product_image" value="<?= htmlspecialchars($row['product_image']) ?>">
                                        <button type="submit" name="add_to_cart_favorite" class="btn btn-danger w-100">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>

                                    <!-- Remove from Favorites -->
                                    <form method="POST" action="remove_favorite.php">
                                        <input type="hidden" name="favorite_id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger w-100">
                                            <i class="bi bi-heartbreak"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-heart" style="font-size: 4rem; color: #dc3545; opacity: 0.3;"></i>
                            <p class="mt-3 text-muted fs-5">You don't have any favorites yet.</p>
                            <a href="../products/product.php" class="btn btn-danger mt-2">
                                <i class="bi bi-shop"></i> Browse Products
                            </a>
                        </div>
                    </div>
                <?php endif;

                $stmt->close();
                ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include("../headfoot/footer.php"); ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/siosios/chat/chat_init.php'); ?>
</body>
</html>