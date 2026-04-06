<?php
session_start();
require '../config.php';
require_once('../notification_functions.php');

// Stripe PHP library
require_once('../vendor/autoload.php');

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
    } else {
        error_log("Failed to prepare statement to check user status: " . $con->error);
        session_unset();
        session_destroy();
        header("Location: ../loginreg/logreg.php?error=dberror");
        exit;
    }
} else {
     error_log("Database connection variable not available for user status check."); 
     session_unset();
     session_destroy();
     header("Location: ../loginreg/logreg.php?error=dberror");
     exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $address_line1 = trim($_POST['address_line1']);
    $address_line2 = trim($_POST['address_line2'] ?? '');
    $barangay      = trim($_POST['barangay']);
    $city          = trim($_POST['city']);
    $province      = trim($_POST['province'] ?? '');
    $postal_code   = trim($_POST['postal_code']);
    $pay_method    = $_POST['pay_method'];
    $courier       = $_POST['courier'];
    $selected_ids  = $_POST['selected'] ?? [];

    // Validate selected items
    if (empty($selected_ids)) {
        $_SESSION['error'] = "No items selected for checkout.";
        header("Location: cart.php");
        exit;
    }

    // Fetch cart items
    $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
    $types = str_repeat('i', count($selected_ids));

    $sql = "SELECT c.cart_id, c.product_id, p.name, p.category, c.quantity, c.price_at_time, p.quantity as stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ? AND c.status = 'active' AND c.cart_id IN ($placeholders)";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("i" . $types, $user_id, ...$selected_ids);
    $stmt->execute();
    $cart_result = $stmt->get_result();

    $cart_items = [];
    $subtotal_gross = 0;

    while ($row = $cart_result->fetch_assoc()) {
        // Check if sufficient stock available
        if ($row['stock'] < $row['quantity']) {
            $_SESSION['error'] = "Insufficient stock for " . htmlspecialchars($row['name']) . ". Only " . $row['stock'] . " available.";
            header("Location: cart.php");
            exit;
        }
        $cart_items[] = $row;
        $subtotal_gross += $row['price_at_time'] * $row['quantity'];
    }
    $stmt->close();

    if (empty($cart_items)) {
        $_SESSION['error'] = "Selected items not found in cart.";
        header("Location: cart.php");
        exit;
    }

    // Calculate totals
    $vat_rate = 0.20;
    $subtotal_net = $subtotal_gross / (1 + $vat_rate);
    $vat = $subtotal_gross - $subtotal_net;
    $delivery_fee = 50.00;
    
    // Handle voucher discount
    $voucher_code = null;
    $voucher_discount = 0;
    $voucher_id = null;
    
    if (isset($_SESSION['applied_voucher']) && isset($_SESSION['voucher_discount'])) {
        $applied_voucher = $_SESSION['applied_voucher'];
        $voucher_discount = $_SESSION['voucher_discount'];
        $voucher_code = $applied_voucher['code'];
        $voucher_id = $applied_voucher['voucher_id'];
        
        // Re-validate voucher before processing
        $stmt_voucher = $con->prepare("SELECT * FROM vouchers WHERE voucher_id = ? AND status = 'active' AND start_date <= NOW() AND end_date >= NOW()");
        $stmt_voucher->bind_param("i", $voucher_id);
        $stmt_voucher->execute();
        $voucher_result = $stmt_voucher->get_result();
        
        if ($voucher_result->num_rows == 0) {
            // Voucher is no longer valid
            unset($_SESSION['applied_voucher']);
            unset($_SESSION['voucher_discount']);
            $voucher_code = null;
            $voucher_discount = 0;
            $voucher_id = null;
        } else {
            $voucher = $voucher_result->fetch_assoc();
            
            // Re-check usage limits
            if ($voucher['usage_limit'] !== null && $voucher['usage_count'] >= $voucher['usage_limit']) {
                unset($_SESSION['applied_voucher']);
                unset($_SESSION['voucher_discount']);
                $voucher_code = null;
                $voucher_discount = 0;
                $voucher_id = null;
            } else {
                // Check per-user limit
                $stmt_user_usage = $con->prepare("SELECT COUNT(*) as usage_count FROM voucher_usage WHERE voucher_id = ? AND user_id = ?");
                $stmt_user_usage->bind_param("ii", $voucher_id, $user_id);
                $stmt_user_usage->execute();
                $user_usage = $stmt_user_usage->get_result()->fetch_assoc();
                $stmt_user_usage->close();
                
                if ($voucher['per_user_limit'] !== null && $user_usage['usage_count'] >= $voucher['per_user_limit']) {
                    unset($_SESSION['applied_voucher']);
                    unset($_SESSION['voucher_discount']);
                    $voucher_code = null;
                    $voucher_discount = 0;
                    $voucher_id = null;
                }
            }
        }
        $stmt_voucher->close();
    }
    
    $total = $subtotal_gross - $voucher_discount + $delivery_fee;

    // Process payment based on method
   $pay_status = 'pending';
    $stripe_payment_id = null;

    if ($pay_method === 'stripe' && isset($_POST['stripe_token'])) {
        try {
            // Set your secret key
            \Stripe\Stripe::setApiKey('sk_test_51SDmtZ8CuwBmuHazluCT9JY8B8dJqHYDx7nM3dvnHAErAHhy58AUfW0nzomE9jFYJVMkMd7uuswFiGEztVERM80I00DRWjv9EO');

            // Create charge
            $charge = \Stripe\Charge::create([
                'amount' => round($total * 100), // Amount in cents
                'currency' => 'php',
                'description' => 'Siosio Store Order',
                'source' => $_POST['stripe_token'],
                'metadata' => [
                    'user_id' => $user_id,
                    'order_items' => count($cart_items)
                ]
            ]);

            if ($charge->status === 'succeeded') {
                $pay_status = 'paid';
                $stripe_payment_id = $charge->id;
            } else {
                throw new Exception('Payment was not successful');
            }
        } catch (\Stripe\Exception\CardException $e) {
            $_SESSION['error'] = 'Card error: ' . $e->getError()->message;
            header("Location: checkout.php");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Payment failed: ' . $e->getMessage();
            header("Location: checkout.php");
            exit;
        }
    }
    // Generate tracking number
    $tracking_number = "TRK" . strtoupper(uniqid());

    // Begin transaction
    $con->begin_transaction();

    try {
        // Insert into orders table
        $stmt = $con->prepare("INSERT INTO orders 
            (user_id, tracking_number, address_line1, address_line2, barangay, city, province, 
            postal_code, pay_method, courier, pay_status, order_status, subtotal, vat, 
            delivery_fee, total, voucher_code, voucher_discount, stripe_payment_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processing', ?, ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->bind_param("issssssssssddddss", 
            $user_id,
            $tracking_number,
            $address_line1,
            $address_line2,
            $barangay,
            $city,
            $province,
            $postal_code,
            $pay_method,
            $courier,
            $pay_status,
            $subtotal_net,
            $vat,
            $delivery_fee,
            $total,
            $voucher_code,
            $voucher_discount,
            $stripe_payment_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create order");
        }

        $order_id = $stmt->insert_id;
        $stmt->close();

        // Insert order items & reduce stock
        $stmt_items = $con->prepare("INSERT INTO order_items 
            (order_id, product_id, product_name, quantity, price_at_time) 
            VALUES (?, ?, ?, ?, ?)");

        $stmt_stock = $con->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");

        foreach ($cart_items as $item) {
            // Insert order item
            $stmt_items->bind_param("iisid",
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['quantity'],
                $item['price_at_time']
            );
            
            if (!$stmt_items->execute()) {
                throw new Exception("Failed to add order items");
            }

            // Reduce stock ONLY if payment is completed
            if ($pay_status === 'paid') {
                $stmt_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                if (!$stmt_stock->execute()) {
                    throw new Exception("Failed to update product stock");
                }
                
                // Check if stock went negative
                $check_stock = $con->prepare("SELECT quantity FROM products WHERE id = ?");
                $check_stock->bind_param("i", $item['product_id']);
                $check_stock->execute();
                $stock_result = $check_stock->get_result()->fetch_assoc();
                $check_stock->close();
                
                if ($stock_result['quantity'] < 0) {
                    throw new Exception("Stock became negative for product ID " . $item['product_id']);
                }
            }
        }
        $stmt_items->close();
        $stmt_stock->close();

        // Record voucher usage if applicable
        if ($voucher_id !== null && $voucher_discount > 0) {
            $stmt_voucher_usage = $con->prepare("INSERT INTO voucher_usage (voucher_id, user_id, order_id, discount_amount) VALUES (?, ?, ?, ?)");
            $stmt_voucher_usage->bind_param("iiid", $voucher_id, $user_id, $order_id, $voucher_discount);
            
            if (!$stmt_voucher_usage->execute()) {
                throw new Exception("Failed to record voucher usage");
            }
            $stmt_voucher_usage->close();
            
            // Update voucher usage count
            $stmt_update_voucher = $con->prepare("UPDATE vouchers SET usage_count = usage_count + 1 WHERE voucher_id = ?");
            $stmt_update_voucher->bind_param("i", $voucher_id);
            $stmt_update_voucher->execute();
            $stmt_update_voucher->close();
            
            // Notify admins about voucher usage
            $stmt_admins = $con->prepare("SELECT id FROM admins WHERE status = 'active'");
            $stmt_admins->execute();
            $admins_result = $stmt_admins->get_result();
            
            while ($admin = $admins_result->fetch_assoc()) {
                createNotification($con, [
                    'admin_id' => $admin['id'],
                    'recipient_type' => 'admin',
                    'order_id' => $order_id,
                    'type' => 'promotion',
                    'title' => 'Voucher Used in Order',
                    'message' => "Voucher '{$voucher_code}' was used in order #{$order_id}. Discount: ₱" . number_format($voucher_discount, 2),
                    'action_url' => '../admin/admin_order_details.php?id=' . $order_id,
                    'priority' => 'low'
                ]);
            }
            $stmt_admins->close();
            
            // Clear voucher from session
            unset($_SESSION['applied_voucher']);
            unset($_SESSION['voucher_discount']);
        }

        // Update cart status to 'ordered'
        $update_cart = $con->prepare("UPDATE cart SET status = 'ordered' WHERE cart_id = ?");
        foreach ($selected_ids as $cart_id) {
            $update_cart->bind_param("i", $cart_id);
            if (!$update_cart->execute()) {
                throw new Exception("Failed to update cart");
            }
        }
        $update_cart->close();

        // Commit transaction
        $con->commit();

        // Success
        $_SESSION['success'] = "Order placed successfully!";
        header("Location: success.php?order_id=" . $order_id);
        exit;

    } catch (Exception $e) {
        $con->rollback();
        $_SESSION['error'] = "Order failed: " . $e->getMessage();
        header("Location: checkout.php");
        exit;
    }
} else {
    header("Location: cart.php");
    exit;
}
?>