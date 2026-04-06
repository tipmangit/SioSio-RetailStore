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


$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    header("Location: ../products/product.php");
    exit;
}

// Fetch order details
$stmt = $con->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header("Location: my_orders.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Success - Siosio Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
<style>
    body {
        background: linear-gradient(135deg, #ebebeeff 0%, #e8e7e9ff 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .success-container {
        max-width: 600px;
        width: 100%;
        padding: 20px;
    }
    
    .success-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow: hidden;
        animation: slideUp 0.5s ease;
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .success-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        padding: 40px 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .success-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 3s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }
    
    .success-icon {
        width: 100px;
        height: 100px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        animation: scaleIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        position: relative;
        z-index: 1;
    }
    
    @keyframes scaleIn {
        from { transform: scale(0) rotate(-180deg); }
        to { transform: scale(1) rotate(0); }
    }
    
    .checkmark {
        font-size: 50px;
        color: #28a745;
        font-weight: bold;
    }
    
    .brand-name {
        font-family: 'Joti One', cursive;
        font-size: 2rem;
        margin: 20px 0 10px;
        position: relative;
        z-index: 1;
    }
    
    .success-body {
        padding: 40px 30px;
    }
    
    .tracking-box {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 25px;
        margin: 30px 0;
        border: 2px dashed #dc3545;
    }
    
    .tracking-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: #dc3545;
        letter-spacing: 2px;
        font-family: 'Courier New', monospace;
    }
    
    .info-box {
        background: #fff5f5;
        border-left: 4px solid #dc3545;
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .info-row:last-child {
        border-bottom: none;
    }
    
    .action-buttons {
        display: grid;
        gap: 15px;
        margin-top: 30px;
    }
    
    .btn-siosio {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        color: white;
        padding: 15px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-siosio:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
        color: white;
    }
    
    .btn-outline-siosio {
        border: 2px solid #dc3545;
        color: #dc3545;
        background: white;
        padding: 15px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-outline-siosio:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.3);
    }
    
    .steps {
        margin-top: 30px;
        text-align: left;
    }
    
    .step-item {
        display: flex;
        align-items: start;
        margin-bottom: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .step-item:hover {
        background: #fff5f5;
        transform: translateX(5px);
    }
    
    .step-number {
        width: 35px;
        height: 35px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 15px;
        flex-shrink: 0;
    }
</style>
</head>
<body>

<div class="success-container">
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">
                <span class="checkmark">✓</span>
            </div>
            <h1 class="brand-name">SioSio Store</h1>
            <h3 class="mb-0">Order Placed Successfully!</h3>
        </div>
        
        <div class="success-body">
            <p class="text-center text-muted mb-4">
                Thank you for your order! We've received your order and will process it shortly.
            </p>
            
            <div class="tracking-box text-center">
                <small class="text-muted d-block mb-2">Your Tracking Number</small>
                <div class="tracking-number"><?= htmlspecialchars($order['tracking_number']) ?></div>
            </div>
            
            <div class="info-box">
                <h6 class="text-danger mb-3">Order Summary</h6>
                <div class="info-row">
                    <span>Order Number:</span>
                    <strong>#<?= $order['order_id'] ?></strong>
                </div>
                <div class="info-row">
                    <span>Total Amount:</span>
                    <strong class="text-danger">₱<?= number_format($order['total'], 2) ?></strong>
                </div>
                <div class="info-row">
                    <span>Payment Method:</span>
                    <strong><?= strtoupper($order['pay_method']) ?></strong>
                </div>
                <div class="info-row">
                    <span>Payment Status:</span>
                    <span class="badge bg-<?= $order['pay_status'] === 'paid' ? 'success' : 'warning' ?>">
                        <?= ucfirst($order['pay_status']) ?>
                    </span>
                </div>
                <div class="info-row">
                    <span>Courier:</span>
                    <strong><?= htmlspecialchars($order['Courier']) ?></strong>
                </div>
            </div>
            
            <div class="alert alert-info text-center">
                <strong>📧 Confirmation Sent!</strong><br>
                <small>A confirmation email has been sent to your registered email address.</small>
            </div>
            
            <div class="steps">
                <h6 class="text-danger mb-3">What happens next?</h6>
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Order Processing</strong><br>
                        <small class="text-muted">We'll process your order within 24 hours</small>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div>
                        <strong>Shipping Updates</strong><br>
                        <small class="text-muted">You'll receive shipping notifications as well as updates via email</small>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Track Your Order</strong><br>
                        <small class="text-muted">Use the tracking number anytime</small>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Delivery</strong><br>
                        <small class="text-muted">Estimated: <?= $order['Courier'] === 'Lalamove' ? 'Same day' : '2-5 business days' ?></small>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="my_orders.php" class="btn-siosio text-center">
                    View My Orders
                </a>
                <a href="order_details.php?order_id=<?= $order['order_id'] ?>" class="btn-outline-siosio text-center">
                    View Order Details
                </a>
                <a href="../products/product.php" class="btn-outline-siosio text-center">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>