<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

// Suspension check
$user_id_from_session = $_SESSION['user_id'];

if (isset($con)) {
    $stmt_check_status = $con->prepare("SELECT status FROM userss WHERE user_id = ?");
    if ($stmt_check_status) {
        $stmt_check_status->bind_param("i", $user_id_from_session);
        $stmt_check_status->execute();
        $result_status = $stmt_check_status->get_result();

        if ($result_status->num_rows === 1) {
            $user_data = $result_status->fetch_assoc();
            
            if ($user_data['status'] === 'suspended') {
                session_unset();
                session_destroy();
                header("Location: ../loginreg/logreg.php?error=suspended"); 
                exit;
            }
        } else {
            session_unset();
            session_destroy();
            header("Location: ../loginreg/logreg.php?error=notfound");
            exit;
        }
        $stmt_check_status->close();
    }
}

$user_id = $_SESSION['user_id'];

// Fetch user's saved address
$stmt_user = $con->prepare("SELECT address_line1, address_line2, barangay, city, postal_code FROM userss WHERE user_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_address = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// Ensure items are selected
if (!isset($_POST['selected']) || empty($_POST['selected'])) {
    header("Location: cart.php");
    exit;
}

$selected_ids = $_POST['selected'];
$placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
$types = str_repeat('i', count($selected_ids));

// Fetch ONLY selected cart items
$sql = "SELECT c.cart_id, 
               p.id,
               p.name, 
               p.category,
               p.description,
               p.image_url, 
               c.quantity, 
               c.price_at_time
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ? 
        AND c.status = 'active'
        AND c.cart_id IN ($placeholders)";

$stmt = $con->prepare($sql);
$stmt->bind_param("i" . $types, $user_id, ...$selected_ids);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
$subtotal_gross = 0;

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal_gross += $row['price_at_time'] * $row['quantity'];
}
$stmt->close();

// Calculate totals
$vat_rate = 0.20;
$subtotal_net = $subtotal_gross / (1 + $vat_rate);
$vat = $subtotal_gross - $subtotal_net;
$delivery_fee = ($subtotal_gross > 0) ? 50.00 : 0;
$voucher_discount = 0;
$applied_voucher = null;

// Handle voucher application via AJAX or form submission
if (isset($_POST['apply_voucher']) && !empty($_POST['voucher_code'])) {
    $voucher_code = strtoupper(trim($_POST['voucher_code']));
    
    // Validate voucher
    $stmt_voucher = $con->prepare("SELECT * FROM vouchers WHERE code = ? AND status = 'active' AND start_date <= NOW() AND end_date >= NOW()");
    $stmt_voucher->bind_param("s", $voucher_code);
    $stmt_voucher->execute();
    $voucher_result = $stmt_voucher->get_result();
    
    if ($voucher_result->num_rows > 0) {
        $voucher = $voucher_result->fetch_assoc();
        
        // Check usage limits
        $can_use = true;
        $error_message = '';
        
        // Check total usage limit
        if ($voucher['usage_limit'] !== null && $voucher['usage_count'] >= $voucher['usage_limit']) {
            $can_use = false;
            $error_message = "This voucher has reached its usage limit.";
        }
        
        // Check per-user limit
        if ($can_use) {
            $stmt_user_usage = $con->prepare("SELECT COUNT(*) as usage_count FROM voucher_usage WHERE voucher_id = ? AND user_id = ?");
            $stmt_user_usage->bind_param("ii", $voucher['voucher_id'], $user_id);
            $stmt_user_usage->execute();
            $user_usage = $stmt_user_usage->get_result()->fetch_assoc();
            $stmt_user_usage->close();
            
            if ($voucher['per_user_limit'] !== null && $user_usage['usage_count'] >= $voucher['per_user_limit']) {
                $can_use = false;
                $error_message = "You have already used this voucher the maximum number of times.";
            }
        }
        
        // Check minimum purchase
        if ($can_use && $subtotal_gross < $voucher['min_purchase']) {
            $can_use = false;
            $error_message = "Minimum purchase of ₱" . number_format($voucher['min_purchase'], 2) . " required.";
        }
        
        // Check applicable products
        if ($can_use && $voucher['applicable_to'] !== 'all') {
            $applicable_total = 0;
            foreach ($cart_items as $item) {
                if ($item['category'] === $voucher['applicable_to']) {
                    $applicable_total += $item['price_at_time'] * $item['quantity'];
                }
            }
            
            if ($applicable_total == 0) {
                $can_use = false;
                $error_message = "This voucher is only applicable to " . ucfirst($voucher['applicable_to']) . " products.";
            } else {
                // Calculate discount on applicable items only
                if ($voucher['discount_type'] === 'percentage') {
                    $voucher_discount = ($applicable_total * $voucher['discount_value']) / 100;
                } else {
                    $voucher_discount = $voucher['discount_value'];
                }
            }
        } else if ($can_use) {
            // Calculate discount on full amount
            if ($voucher['discount_type'] === 'percentage') {
                $voucher_discount = ($subtotal_gross * $voucher['discount_value']) / 100;
            } else {
                $voucher_discount = $voucher['discount_value'];
            }
        }
        
        // Apply max discount cap
        if ($can_use && $voucher['max_discount'] !== null && $voucher_discount > $voucher['max_discount']) {
            $voucher_discount = $voucher['max_discount'];
        }
        
        // Ensure discount doesn't exceed subtotal
        if ($voucher_discount > $subtotal_gross) {
            $voucher_discount = $subtotal_gross;
        }
        
        if ($can_use) {
            $applied_voucher = $voucher;
            $_SESSION['applied_voucher'] = $voucher;
            $_SESSION['voucher_discount'] = $voucher_discount;
            $success_message = "Voucher applied successfully! You saved ₱" . number_format($voucher_discount, 2);
        } else {
            $voucher_error = $error_message;
        }
    } else {
        $voucher_error = "Invalid or expired voucher code.";
    }
    $stmt_voucher->close();
}

// Check for saved voucher in session
if (isset($_SESSION['applied_voucher']) && isset($_SESSION['voucher_discount'])) {
    $applied_voucher = $_SESSION['applied_voucher'];
    $voucher_discount = $_SESSION['voucher_discount'];
}

// Handle voucher removal
if (isset($_POST['remove_voucher'])) {
    unset($_SESSION['applied_voucher']);
    unset($_SESSION['voucher_discount']);
    $applied_voucher = null;
    $voucher_discount = 0;
    header("Location: checkout.php?" . http_build_query(['selected' => $selected_ids]));
    exit;
}

$grand_total = $subtotal_gross - $voucher_discount + $delivery_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Siosio Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            background-color: #f8f9fa;
            margin-top: 100px;
        }
        
        .product-card { 
            display: flex; 
            align-items: center; 
            margin-bottom: 10px; 
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .product-card img { 
            width: 60px; 
            height: 60px; 
            object-fit: cover; 
            margin-right: 15px; 
            border-radius: 8px; 
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .payment-method-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .payment-method-card.selected {
            border: 2px solid #dc3545;
            background: #fff5f5;
        }
        
        .saved-address-banner {
            background: linear-gradient(135deg, #d1e7dd 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .postal-info {
            background: #e7f5ff;
            border-left: 4px solid #0d6efd;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
            display: none;
        }
        
        .voucher-section {
            background: #fff;
            border: 2px dashed #dc3545;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .voucher-applied {
            background: linear-gradient(135deg, #d1e7dd 0%, #c3e6cb 100%);
            border: 2px solid #28a745;
        }
        
        .discount-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include("../headfoot/header.php"); ?>

<main class="container py-4">
    <h2 class="mb-4 text-center text-danger"><i class="bi bi-credit-card"></i> Checkout</h2>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($voucher_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-x-circle"></i> <?= $voucher_error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Shipping & Payment Info -->
        <div class="col-lg-7">
            <!-- Voucher Section -->
            <div class="voucher-section <?= $applied_voucher ? 'voucher-applied' : '' ?>">
                <h5 class="mb-3">
                    <i class="bi bi-ticket-perforated"></i> 
                    <?= $applied_voucher ? 'Voucher Applied!' : 'Have a Voucher Code?' ?>
                </h5>
                
                <?php if ($applied_voucher): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-success">
                                <i class="bi bi-check-circle-fill"></i> 
                                Code: <?= htmlspecialchars($applied_voucher['code']) ?>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($applied_voucher['description']) ?></small>
                            <div class="mt-2">
                                <span class="discount-badge">
                                    You saved ₱<?= number_format($voucher_discount, 2) ?>
                                </span>
                            </div>
                        </div>
                        <form method="POST" style="display: inline;">
                            <?php foreach ($selected_ids as $id): ?>
                                <input type="hidden" name="selected[]" value="<?= $id ?>">
                            <?php endforeach; ?>
                            <button type="submit" name="remove_voucher" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-x-circle"></i> Remove
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="POST" class="row g-2">
                        <?php foreach ($selected_ids as $id): ?>
                            <input type="hidden" name="selected[]" value="<?= $id ?>">
                        <?php endforeach; ?>
                        <div class="col-md-8">
                            <input type="text" 
                                   name="voucher_code" 
                                   class="form-control" 
                                   placeholder="Enter voucher code (e.g., WELCOME20)"
                                   style="text-transform: uppercase;">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="apply_voucher" class="btn btn-danger w-100">
                                <i class="bi bi-check-circle"></i> Apply
                            </button>
                        </div>
                    </form>
                    <small class="text-muted mt-2 d-block">
                        <i class="bi bi-info-circle"></i> Enter your voucher code to get instant discounts!
                    </small>
                <?php endif; ?>
            </div>
            
            <form action="checkout_process.php" method="post" id="checkout-form">
                <!-- Hidden input for selected items -->
                <?php foreach ($selected_ids as $cart_id): ?>
                    <input type="hidden" name="selected[]" value="<?= $cart_id ?>">
                <?php endforeach; ?>

                <!-- Shipping Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-box-seam"></i> Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($user_address['address_line1'])): ?>
                        <div class="saved-address-banner">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong><i class="bi bi-check-circle"></i> Saved Address Found</strong>
                                <button type="button" class="btn btn-sm btn-success" onclick="loadSavedAddress()">
                                    <i class="bi bi-download"></i> Use Saved Address
                                </button>
                            </div>
                            <small class="text-muted">
                                <?= htmlspecialchars($user_address['address_line1']) ?>
                                <?php if (!empty($user_address['address_line2'])): ?>
                                    , <?= htmlspecialchars($user_address['address_line2']) ?>
                                <?php endif; ?>
                                <br>
                                <?= htmlspecialchars($user_address['barangay']) ?>, 
                                <?= htmlspecialchars($user_address['city']) ?>, 
                                <?= htmlspecialchars($user_address['postal_code']) ?>
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="address_line1" class="form-label">Address Line 1 *</label>
                                <input type="text" class="form-control" id="address_line1" 
                                       name="address_line1" placeholder="House/Unit No., Street Name" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="address_line2" class="form-label">Address Line 2 (Optional)</label>
                                <input type="text" class="form-control" id="address_line2" 
                                       name="address_line2" placeholder="Building, Subdivision, Landmark">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="barangay" class="form-label">Barangay *</label>
                                <input type="text" class="form-control" id="barangay" 
                                       name="barangay" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <select class="form-select" id="city" name="city" required>
                                    <option value="" disabled selected>Select city</option>
                                    <option value="Caloocan">Caloocan</option>
                                    <option value="Las Piñas">Las Piñas</option>
                                    <option value="Makati">Makati</option>
                                    <option value="Malabon">Malabon</option>
                                    <option value="Mandaluyong">Mandaluyong</option>
                                    <option value="Manila">Manila</option>
                                    <option value="Marikina">Marikina</option>
                                    <option value="Muntinlupa">Muntinlupa</option>
                                    <option value="Navotas">Navotas</option>
                                    <option value="Parañaque">Parañaque</option>
                                    <option value="Pasay">Pasay</option>
                                    <option value="Pasig">Pasig</option>
                                    <option value="Quezon City">Quezon City</option>
                                    <option value="San Juan">San Juan</option>
                                    <option value="Taguig">Taguig</option>
                                    <option value="Valenzuela">Valenzuela</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label">Postal Code *</label>
                                <input type="text" class="form-control" id="postal_code" 
                                       name="postal_code" pattern="[0-9]{4}" maxlength="4" 
                                       placeholder="Enter 4-digit postal code" required>
                                <div id="postal-info" class="postal-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong id="detected-city"></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="payment-method-card card h-100" data-method="stripe">
                                    <div class="card-body text-center">
                                        <h6><i class="bi bi-credit-card"></i> Credit/Debit Card</h6>
                                        <small class="text-muted">Powered by Stripe</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-method-card card h-100" data-method="cod">
                                    <div class="card-body text-center">
                                        <h6><i class="bi bi-cash-coin"></i> Cash on Delivery</h6>
                                        <small class="text-muted">Pay when you receive</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="pay_method" id="pay_method" required>
                        
                        <!-- Stripe Card Element -->
                        <div id="stripe-section" class="mt-4" style="display: none;">
                            <div id="card-element" class="form-control" style="height: 40px; padding: 10px;"></div>
                            <div id="card-errors" class="text-danger mt-2"></div>
                        </div>
                    </div>
                </div>

                <!-- Courier Selection -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-truck"></i> Delivery Courier</h5>
                    </div>
                    <div class="card-body">
                        <select class="form-select" id="courier" name="courier" required>
                            <option value="" disabled selected>Select courier</option>
                            <option value="J&T Express">J&T Express</option>
                            <option value="LBC Express">LBC Express</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger btn-lg w-100" id="submit-btn">
                    Place Order - ₱<span id="submitTotal"><?= number_format($grand_total, 2) ?></span>
                </button>
                
                <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                    Back to Cart
                </a>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-5 mt-4 mt-lg-0">
            <div class="card shadow-sm sticky-top" style="top: 110px;">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($cart_items)): ?>
                        <p class="text-muted">No items selected.</p>
                    <?php else: ?>
                        <!-- Products -->
                        <div class="mb-3">
                            <h6 class="mb-3">Items (<?= count($cart_items) ?>)</h6>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="product-card">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                        <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                                        <div class="fw-bold text-danger">₱<?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <hr>
                        
                        <!-- Price Breakdown -->
                        <div class="summary-row">
                            <span>Subtotal (incl. VAT):</span>
                            <strong id="summarySubtotal">₱<?= number_format($subtotal_gross, 2) ?></strong>
                        </div>
                        
                        <?php if ($voucher_discount > 0): ?>
                        <div class="summary-row text-success">
                            <span><i class="bi bi-ticket-perforated"></i> Voucher Discount:</span>
                            <strong id="summaryDiscount">-₱<?= number_format($voucher_discount, 2) ?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row">
                            <span>Delivery Fee:</span>
                            <strong id="summaryDelivery">₱<?= number_format($delivery_fee, 2) ?></strong>
                        </div>
                        <hr>
                        <div class="summary-row">
                            <span class="fs-5 fw-bold">Total:</span>
                            <strong class="fs-4 text-danger" id="summaryTotal">₱<?= number_format($grand_total, 2) ?></strong>
                        </div>
                        
                        <?php if ($voucher_discount > 0): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <small><i class="bi bi-check-circle"></i> You're saving ₱<?= number_format($voucher_discount, 2) ?> with this voucher!</small>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include("../headfoot/footer.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// NCR Postal Code Database
const ncrPostalCodes = {
    '1000': 'Manila', '1001': 'Manila', '1002': 'Manila', '1003': 'Manila', '1004': 'Manila',
    '1005': 'Manila', '1006': 'Manila', '1007': 'Manila', '1008': 'Manila', '1009': 'Manila',
    '1010': 'Manila', '1011': 'Manila', '1012': 'Manila', '1013': 'Manila', '1014': 'Manila',
    '1015': 'Manila', '1016': 'Manila', '1017': 'Manila', '1018': 'Manila',
    '1100': 'Quezon City', '1101': 'Quezon City', '1102': 'Quezon City', '1103': 'Quezon City',
    '1104': 'Quezon City', '1105': 'Quezon City', '1106': 'Quezon City', '1107': 'Quezon City',
    '1108': 'Quezon City', '1109': 'Quezon City', '1110': 'Quezon City', '1111': 'Quezon City',
    '1112': 'Quezon City', '1113': 'Quezon City', '1114': 'Quezon City', '1115': 'Quezon City',
    '1116': 'Quezon City', '1117': 'Quezon City', '1118': 'Quezon City', '1119': 'Quezon City',
    '1120': 'Quezon City', '1121': 'Quezon City', '1122': 'Quezon City', '1123': 'Quezon City',
    '1124': 'Quezon City', '1125': 'Quezon City', '1126': 'Quezon City', '1127': 'Quezon City',
    '1400': 'Caloocan', '1401': 'Caloocan', '1402': 'Caloocan', '1403': 'Caloocan',
    '1404': 'Caloocan', '1405': 'Caloocan', '1406': 'Caloocan', '1407': 'Caloocan',
    '1408': 'Caloocan', '1409': 'Caloocan', '1410': 'Caloocan', '1411': 'Caloocan',
    '1412': 'Caloocan', '1413': 'Caloocan', '1414': 'Caloocan', '1415': 'Caloocan',
    '1416': 'Caloocan', '1417': 'Caloocan', '1418': 'Caloocan', '1419': 'Caloocan',
    '1420': 'Caloocan', '1421': 'Caloocan', '1422': 'Caloocan', '1423': 'Caloocan',
    '1424': 'Caloocan', '1425': 'Caloocan', '1426': 'Caloocan', '1427': 'Caloocan',
    '1428': 'Caloocan',
    '1740': 'Las Piñas', '1741': 'Las Piñas', '1742': 'Las Piñas', '1743': 'Las Piñas',
    '1744': 'Las Piñas', '1745': 'Las Piñas', '1746': 'Las Piñas',
    '1200': 'Makati', '1201': 'Makati', '1202': 'Makati', '1203': 'Makati', '1204': 'Makati',
    '1205': 'Makati', '1206': 'Makati', '1207': 'Makati', '1208': 'Makati', '1209': 'Makati',
    '1210': 'Makati', '1211': 'Makati', '1212': 'Makati', '1213': 'Makati', '1214': 'Makati',
    '1215': 'Makati', '1216': 'Makati', '1217': 'Makati', '1218': 'Makati', '1219': 'Makati',
    '1220': 'Makati', '1221': 'Makati', '1222': 'Makati', '1223': 'Makati', '1224': 'Makati',
    '1225': 'Makati', '1226': 'Makati', '1227': 'Makati', '1228': 'Makati', '1229': 'Makati',
    '1230': 'Makati', '1231': 'Makati', '1232': 'Makati', '1233': 'Makati', '1234': 'Makati',
    '1235': 'Makati', '1236': 'Makati',
    '1470': 'Malabon', '1471': 'Malabon', '1472': 'Malabon', '1473': 'Malabon', '1474': 'Malabon',
    '1550': 'Mandaluyong', '1551': 'Mandaluyong', '1552': 'Mandaluyong', '1553': 'Mandaluyong',
    '1554': 'Mandaluyong', '1555': 'Mandaluyong', '1556': 'Mandaluyong',
    '1800': 'Marikina', '1801': 'Marikina', '1802': 'Marikina', '1803': 'Marikina',
    '1804': 'Marikina', '1805': 'Marikina', '1806': 'Marikina', '1807': 'Marikina',
    '1808': 'Marikina', '1809': 'Marikina', '1810': 'Marikina',
    '1770': 'Muntinlupa', '1771': 'Muntinlupa', '1772': 'Muntinlupa', '1773': 'Muntinlupa',
    '1774': 'Muntinlupa', '1775': 'Muntinlupa', '1776': 'Muntinlupa', '1777': 'Muntinlupa',
    '1778': 'Muntinlupa', '1779': 'Muntinlupa', '1780': 'Muntinlupa', '1781': 'Muntinlupa',
    '1485': 'Navotas', '1486': 'Navotas', '1487': 'Navotas', '1488': 'Navotas',
    '1700': 'Parañaque', '1701': 'Parañaque', '1702': 'Parañaque', '1703': 'Parañaque',
    '1704': 'Parañaque', '1705': 'Parañaque', '1706': 'Parañaque', '1707': 'Parañaque',
    '1708': 'Parañaque', '1709': 'Parañaque', '1710': 'Parañaque', '1711': 'Parañaque',
    '1712': 'Parañaque', '1713': 'Parañaque', '1714': 'Parañaque', '1715': 'Parañaque',
    '1716': 'Parañaque', '1717': 'Parañaque', '1718': 'Parañaque', '1719': 'Parañaque',
    '1300': 'Pasay', '1301': 'Pasay', '1302': 'Pasay', '1303': 'Pasay', '1304': 'Pasay',
    '1305': 'Pasay', '1306': 'Pasay', '1307': 'Pasay', '1308': 'Pasay', '1309': 'Pasay',
    '1600': 'Pasig', '1601': 'Pasig', '1602': 'Pasig', '1603': 'Pasig', '1604': 'Pasig',
    '1605': 'Pasig', '1606': 'Pasig', '1607': 'Pasig', '1608': 'Pasig', '1609': 'Pasig',
    '1610': 'Pasig', '1611': 'Pasig', '1612': 'Pasig', '1613': 'Pasig', '1614': 'Pasig',
    '1615': 'Pasig', '1616': 'Pasig', '1617': 'Pasig', '1618': 'Pasig', '1619': 'Pasig',
    '1620': 'Pasig', '1621': 'Pasig', '1622': 'Pasig', '1623': 'Pasig', '1624': 'Pasig',
    '1625': 'Pasig', '1626': 'Pasig', '1627': 'Pasig', '1628': 'Pasig', '1629': 'Pasig',
    '1630': 'Pasig', '1631': 'Pasig', '1632': 'Pasig', '1633': 'Pasig', '1634': 'Pasig',
    '1635': 'Pasig', '1636': 'Pasig', '1637': 'Pasig', '1638': 'Pasig', '1639': 'Pasig',
    '1640': 'Pasig', '1641': 'Pasig',
    '1500': 'San Juan', '1501': 'San Juan', '1502': 'San Juan', '1503': 'San Juan',
    '1504': 'San Juan', '1505': 'San Juan', '1506': 'San Juan', '1507': 'San Juan',
    '1630': 'Taguig', '1631': 'Taguig', '1632': 'Taguig', '1633': 'Taguig',
    '1634': 'Taguig', '1635': 'Taguig', '1636': 'Taguig', '1637': 'Taguig',
    '1638': 'Taguig', '1639': 'Taguig', '1640': 'Taguig', '1641': 'Taguig',
    '1440': 'Valenzuela', '1441': 'Valenzuela', '1442': 'Valenzuela', '1443': 'Valenzuela',
    '1444': 'Valenzuela', '1445': 'Valenzuela', '1446': 'Valenzuela', '1447': 'Valenzuela',
    '1448': 'Valenzuela', '1449': 'Valenzuela', '1450': 'Valenzuela', '1451': 'Valenzuela',
    '1452': 'Valenzuela', '1453': 'Valenzuela', '1454': 'Valenzuela', '1455': 'Valenzuela',
    '1456': 'Valenzuela', '1457': 'Valenzuela', '1458': 'Valenzuela'
};

// Saved address data
const savedAddress = {
    address_line1: <?= json_encode($user_address['address_line1'] ?? '') ?>,
    address_line2: <?= json_encode($user_address['address_line2'] ?? '') ?>,
    barangay: <?= json_encode($user_address['barangay'] ?? '') ?>,
    city: <?= json_encode($user_address['city'] ?? '') ?>,
    postal_code: <?= json_encode($user_address['postal_code'] ?? '') ?>
};

// Load saved address function
function loadSavedAddress() {
    if (savedAddress.address_line1) {
        document.getElementById('address_line1').value = savedAddress.address_line1;
        document.getElementById('address_line2').value = savedAddress.address_line2 || '';
        document.getElementById('barangay').value = savedAddress.barangay;
        document.getElementById('postal_code').value = savedAddress.postal_code;
        document.getElementById('city').value = savedAddress.city;
        
        if (savedAddress.postal_code) {
            lookupPostalCode(savedAddress.postal_code);
        }
        
        const banner = document.querySelector('.saved-address-banner');
        if (banner) {
            const tempMessage = document.createElement('div');
            tempMessage.className = 'alert alert-success mt-2 mb-0';
            tempMessage.innerHTML = '<i class="bi bi-check-circle"></i> Address loaded successfully!';
            banner.appendChild(tempMessage);
            
            setTimeout(() => {
                tempMessage.remove();
            }, 3000);
        }
    }
}

// Postal code lookup
document.getElementById('postal_code').addEventListener('input', function() {
    const postalCode = this.value.trim();
    
    if (postalCode.length === 4) {
        lookupPostalCode(postalCode);
    } else {
        document.getElementById('postal-info').style.display = 'none';
        document.getElementById('city').value = '';
    }
});
</script>
</body>
</html>
function lookupPostalCode(postalCode) {
    const postalInfo = document.getElementById('postal-info');
    const citySelect = document.getElementById('city');
    const detectedCity = document.getElementById('detected-city');
    
    if (ncrPostalCodes[postalCode]) {
        const city = ncrPostalCodes[postalCode];
        detectedCity.textContent = `Detected: ${city}`;
        postalInfo.style.display = 'block';
        citySelect.value = city;
        citySelect.style.borderColor = '#28a745';
    } else {
        detectedCity.textContent = 'Invalid NCR postal code';
        postalInfo.style.display = 'block';
        postalInfo.style.background = '#f8d7da';
        postalInfo.style.borderLeftColor = '#dc3545';
        citySelect.value = '';
        citySelect.style.borderColor = '#dc3545';
    }
}

// Payment method selection
const paymentCards = document.querySelectorAll('.payment-method-card');
const payMethodInput = document.getElementById('pay_method');
const stripeSection = document.getElementById('stripe-section');

paymentCards.forEach(card => {
    card.addEventListener('click', function() {
        paymentCards.forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        const method = this.dataset.method;
        payMethodInput.value = method;
        
        if (method === 'stripe') {
            stripeSection.style.display = 'block';
            initializeStripe();
        } else {
            stripeSection.style.display = 'none';
        }
    });
});

// Stripe initialization
let stripe, elements, cardElement;

function initializeStripe() {
    if (!stripe) {
        stripe = Stripe('pk_test_51SDmtZ8CuwBmuHaz2gy1DqJvpadHIVQb0jghNXi7MZ7Bhq4fdlqMPGaLWn3OpQHIB2zM456eCp1R0mZIBY2enK7J00HTwc8oFu');
        elements = stripe.elements();
        
        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                }
            }
        });
        
        cardElement.mount('#card-element');
        
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
    }
}

// Form submission
document.getElementById('checkout-form').addEventListener('submit', async function(e) {
    const payMethod = payMethodInput.value;
    
    if (!payMethod) {
        e.preventDefault();
        alert('Please select a payment method');
        return;
    }
    
    const postalCode = document.getElementById('postal_code').value;
    if (!ncrPostalCodes[postalCode]) {
        e.preventDefault();
        alert('Please enter a valid NCR postal code');
        return;
    }
    
    if (payMethod === 'stripe') {
        e.preventDefault();
        
        const {token, error} = await stripe.createToken(cardElement);
        
        if (error) {
            document.getElementById('card-errors').textContent = error.message;
        } else {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'stripe_token';
            tokenInput.value = token.id;
            this.appendChild(tokenInput);
            this.submit();
        }
    }
});