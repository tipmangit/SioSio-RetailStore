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

// Fetch cart items
$stmt = $con->prepare("SELECT c.*, p.id as product_id, p.name, p.description, p.image_url 
                       FROM cart c
                       JOIN products p ON c.product_id = p.id
                       WHERE c.user_id = ? AND c.status = 'active'
                       ORDER BY c.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart - Siosio Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html, body {
        height: 100%;
    }

    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        flex-direction: column;
    }

    main {
        flex: 1;
        overflow-y: auto;
    }

    footer {
        flex-shrink: 0;
    }
    
    .cart-container {
        margin-top: 100px;
        padding: 20px 0;
    }
    
    .cart-item {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        display: flex;
        gap: 15px;
        align-items: center;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .cart-item:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    
    .item-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    .item-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .item-details {
        flex: 1;
    }
    
    .item-details h6 {
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .item-price {
        color: #dc3545;
        font-weight: 700;
        font-size: 1.1rem;
    }
    
    .quantity-control {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f0f0f0;
        border-radius: 6px;
        padding: 5px 10px;
        width: fit-content;
    }
    
    .quantity-control button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #dc3545;
    }
    
    .quantity-control input {
        width: 40px;
        border: none;
        text-align: center;
        background: none;
        font-weight: 600;
        padding: 0;
    }
    
    .cart-summary {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        position: sticky;
        top: 110px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .summary-row:last-child {
        border-bottom: none;
        font-size: 1.2rem;
        font-weight: 700;
        color: #dc3545;
        padding-top: 15px;
    }
    

    .empty-cart {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-cart i {
        font-size: 80px;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .btn-checkout {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        color: white;
        padding: 15px;
        border-radius: 8px;
        font-weight: 600;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .btn-checkout:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
        color: white;
    }
    
    .btn-checkout:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .select-all-section {
        background: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>
</head>
<body>
<?php include("../headfoot/header.php"); ?>

<main>
<div class="cart-container container">
    <h2 class="mb-4 text-danger"><i class="bi bi-bag"></i> Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="bi bi-bag-x"></i>
            <h3 class="text-muted">Your cart is empty</h3>
            <p class="text-muted mb-4">Start shopping to add items to your cart</p>
            <a href="../products/product.php" class="btn btn-danger btn-lg">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Select All Section -->
                <div class="select-all-section">
                    <label class="mb-0">
                        <input type="checkbox" id="selectAll" class="item-checkbox"> 
                        <strong>Select All Items</strong>
                    </label>
                    <button class="btn btn-sm btn-outline-danger" id="deleteSelectedBtn" disabled>
                        Delete Selected
                    </button>
                </div>
                
                <!-- Cart Items -->
                <form id="cartForm">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <input type="checkbox" class="item-checkbox" name="selected[]" 
                                   value="<?= $item['cart_id'] ?>" data-item-id="<?= $item['cart_id'] ?>">
                            
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($item['name']) ?>" class="item-image">
                            
                            <div class="item-details">
                                <h6><?= htmlspecialchars($item['name']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 50)) ?>...</small>
                                <div class="item-price">₱<?= number_format($item['price_at_time'], 2) ?></div>
                            </div>
                            
                            <div class="quantity-control">
                                <button type="button" class="qty-btn-decrease" onclick="updateQty(<?= $item['cart_id'] ?>, 'decrease')">−</button>
                                <input type="number" value="<?= $item['quantity'] ?>" min="1" readonly>
                                <button type="button" class="qty-btn-increase" onclick="updateQty(<?= $item['cart_id'] ?>, 'increase')">+</button>
                            </div>
                            
                            <div style="text-align: right; min-width: 100px;">
                                <div class="fw-bold">₱<?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></div>
                                <button type="button" class="btn btn-sm btn-link text-danger" 
                                        onclick="removeItem(<?= $item['cart_id'] ?>)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
            
            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="mb-4">Order Summary</h5>
                    
                    <!-- Summary -->
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span id="deliveryFee">₱50.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Total:</span>
                        <span id="total">₱0.00</span>
                    </div>
                    
                    <button class="btn-checkout mt-4" onclick="proceedToCheckout()" id="checkoutBtn">
                        Proceed to Checkout
                    </button>
                    
                    <a href="../products/product.php" class="btn btn-outline-danger w-100 mt-2">
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
</main>

<?php include("../headfoot/footer.php"); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Initialize summary on page load
function updateSummary() {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
    let subtotal = 0;
    
    checkboxes.forEach(checkbox => {
        const item = checkbox.closest('.cart-item');
        const priceText = item.querySelector('.item-price').textContent;
        const price = parseFloat(priceText.replace('₱', '').replace(',', ''));
        const qty = parseInt(item.querySelector('.quantity-control input').value);
        subtotal += price * qty;
    });
    
    const deliveryFee = subtotal > 0 ? 50 : 0;
    const total = subtotal + deliveryFee;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('deliveryFee').textContent = '₱' + deliveryFee.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('total').textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Select All functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]');
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    checkboxes.forEach(cb => cb.checked = this.checked);
    deleteBtn.disabled = !this.checked;
    updateSummary();
});

// Individual checkbox change
document.querySelectorAll('input[name="selected[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const selectAll = document.getElementById('selectAll');
        const allChecked = document.querySelectorAll('input[name="selected[]"]:checked').length === 
                          document.querySelectorAll('input[name="selected[]"]').length;
        selectAll.checked = allChecked;
        
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        deleteBtn.disabled = document.querySelectorAll('input[name="selected[]"]:checked').length === 0;
        
        updateSummary();
    });
});

// Delete selected items
document.getElementById('deleteSelectedBtn').addEventListener('click', function() {
    const selected = document.querySelectorAll('input[name="selected[]"]:checked');
    if (selected.length === 0) {
        alert('Please select items to delete');
        return;
    }
    
    if (!confirm('Are you sure you want to delete the selected items?')) return;
    
    selected.forEach(checkbox => {
        const cartId = checkbox.value;
        fetch('removecart.php?cart_id=' + cartId).then(() => {
            location.reload();
        });
    });
});

function updateQty(cartId, action) {
    const form = new FormData();
    form.append('cart_id', cartId);
    form.append(action, '1');
    
    fetch('updatecart.php', {
        method: 'POST',
        body: form
    }).then(response => {
        if (response.ok) {
            location.reload();
        }
    });
}

function removeItem(cartId) {
    if (confirm('Remove this item from cart?')) {
        window.location.href = 'removecart.php?cart_id=' + cartId;
    }
}

function proceedToCheckout() {
    const selected = document.querySelectorAll('input[name="selected[]"]:checked');
    
    if (selected.length === 0) {
        alert('Please select at least one item');
        return;
    }
    
    // Submit to checkout
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'checkout.php';
    
    selected.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected[]';
        input.value = checkbox.value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
}

// Initialize summary on page load
function updateSummary() {
    const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
    let subtotal = 0;
    
    checkboxes.forEach(checkbox => {
        const item = checkbox.closest('.cart-item');
        const priceText = item.querySelector('.item-price').textContent;
        const price = parseFloat(priceText.replace('₱', '').replace(',', ''));
        const qty = parseInt(item.querySelector('.quantity-control input').value);
        subtotal += price * qty;
    });
    
    const deliveryFee = subtotal > 0 ? 50 : 0;
    const total = subtotal + deliveryFee;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('deliveryFee').textContent = '₱' + deliveryFee.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    document.getElementById('total').textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

document.addEventListener('DOMContentLoaded', updateSummary);
</script>
</body>
</html>