<?php
session_start();
include("../config.php");
require_once('../notification_functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginreg/logreg.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preferences = [
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'push_notifications' => isset($_POST['push_notifications']) ? 1 : 0,
        'order_updates' => isset($_POST['order_updates']) ? 1 : 0,
        'new_products' => isset($_POST['new_products']) ? 1 : 0,
        'promotions' => isset($_POST['promotions']) ? 1 : 0,
        'low_stock_alerts' => isset($_POST['low_stock_alerts']) ? 1 : 0
    ];
    
    if (updateNotificationPreferences($con, $user_id, $preferences, 'user')) {
        $message = "Notification preferences updated successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to update preferences. Please try again.";
        $messageType = "danger";
    }
}

// Get current preferences
$prefs = getNotificationPreferences($con, $user_id, 'user');
if (!$prefs) {
    // Create default preferences if not exist
    $stmt = $con->prepare("INSERT INTO notification_preferences (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    $prefs = getNotificationPreferences($con, $user_id, 'user');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notification Preferences - SioSio Store</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body {
        background: #f8f9fa;
    }
    
    .preferences-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .preference-item {
        padding: 1.5rem;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 1rem;
        transition: all 0.3s;
    }
    
    .preference-item:hover {
        background: #f8f9fa;
        border-color: #dc3545;
    }
    
    .preference-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .icon-email { background: #e7f5ff; color: #0d6efd; }
    .icon-push { background: #d1e7dd; color: #198754; }
    .icon-order { background: #fff3cd; color: #ffc107; }
    .icon-product { background: #f8d7da; color: #dc3545; }
    .icon-promo { background: #d1ecf1; color: #0c5460; }
    .icon-stock { background: #ffeaa7; color: #fdcb6e; }
    
    .form-switch {
        padding-left: 0;
    }
    
    .form-switch .form-check-input {
        width: 3rem;
        height: 1.5rem;
        cursor: pointer;
    }
    
    .form-switch .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }
    
    .preference-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.25rem;
    }
    
    .preference-description {
        color: #666;
        font-size: 0.9rem;
        margin: 0;
    }
    
    .info-box {
        background: #e7f5ff;
        border-left: 4px solid #0d6efd;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 2rem;
    }
</style>
</head>
<body>
<?php include("../headfoot/header.php"); ?>

<div class="container py-5" style="margin-top: 100px;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-danger mb-0">
                    <i class="bi bi-gear"></i> Notification Preferences
                </h2>
                <a href="notifications.php" class="btn btn-outline-danger">
                    <i class="bi bi-arrow-left"></i> Back to Notifications
                </a>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="preferences-card">
                <div class="info-box">
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-2"></i> 
                        <strong>Manage how you receive notifications from SioSio Store.</strong><br>
                        <small>You can customize your preferences at any time. Changes take effect immediately.</small>
                    </p>
                </div>
                
                <form method="POST">
                    <!-- Email Notifications -->
                    <div class="preference-item">
                        <div class="d-flex align-items-center">
                            <div class="preference-icon icon-email">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="preference-title">Email Notifications</div>
                                <div class="preference-description">
                                    Receive notifications via email about your orders and updates
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="email_notifications" name="email_notifications" 
                                       <?= $prefs['email_notifications'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Push Notifications -->
                    <div class="preference-item">
                        <div class="d-flex align-items-center">
                            <div class="preference-icon icon-push">
                                <i class="bi bi-bell"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="preference-title">Push Notifications</div>
                                <div class="preference-description">
                                    Receive real-time notifications in your browser
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="push_notifications" name="push_notifications" 
                                       <?= $prefs['push_notifications'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Updates -->
                    <div class="preference-item">
                        <div class="d-flex align-items-center">
                            <div class="preference-icon icon-order">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="preference-title">Order Updates</div>
                                <div class="preference-description">
                                    Get notified about order status changes, shipping, and delivery
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="order_updates" name="order_updates" 
                                       <?= $prefs['order_updates'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <!-- New Products -->
                    <div class="preference-item">
                        <div class="d-flex align-items-center">
                            <div class="preference-icon icon-product">
                                <i class="bi bi-stars"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="preference-title">New Product Alerts</div>
                                <div class="preference-description">
                                    Be the first to know when we add new siomai and siopao flavors
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="new_products" name="new_products" 
                                       <?= $prefs['new_products'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Promotions -->
                    <div class="preference-item">
                        <div class="d-flex align-items-center">
                            <div class="preference-icon icon-promo">
                                <i class="bi bi-gift"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="preference-title">Promotions & Deals</div>
                                <div class="preference-description">
                                    Receive special offers, discounts, and promotional announcements
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="promotions" name="promotions" 
                                       <?= $prefs['promotions'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Alerts -->
                    <div class="preference-item">
                        <div class="d-flex align-items-center">
                            <div class="preference-icon icon-stock">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="preference-title">Low Stock Alerts</div>
                                <div class="preference-description">
                                    Get notified when your favorite items are running low in stock
                                </div>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="low_stock_alerts" name="low_stock_alerts" 
                                       <?= $prefs['low_stock_alerts'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Additional Settings Info -->
                    <div class="alert alert-light border">
                        <h6 class="alert-heading">
                            <i class="bi bi-lightbulb"></i> Tips for Managing Notifications
                        </h6>
                        <ul class="mb-0 small">
                            <li>Email notifications are sent to: <strong><?= htmlspecialchars($_SESSION['valid']) ?></strong></li>
                            <li>Order updates are highly recommended to track your purchases</li>
                            <li>Disable promotions if you prefer not to receive marketing emails</li>
                            <li>Low stock alerts help you reorder your favorites before they run out</li>
                        </ul>
                    </div>
                    
                    <!-- Save Button -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="notifications.php" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg px-5">
                            <i class="bi bi-check-circle"></i> Save Preferences
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="bi bi-lightning"></i> Quick Actions
                    </h6>
                    <div class="d-grid gap-2">
                        <a href="notifications.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-bell"></i> View All Notifications
                        </a>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testNotification()">
                            <i class="bi bi-envelope"></i> Send Test Notification
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../headfoot/footer.php"); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Add visual feedback when toggling switches
document.querySelectorAll('.form-check-input').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const preferenceItem = this.closest('.preference-item');
        if (this.checked) {
            preferenceItem.style.borderColor = '#dc3545';
            preferenceItem.style.backgroundColor = '#fff5f5';
        } else {
            preferenceItem.style.borderColor = '#e9ecef';
            preferenceItem.style.backgroundColor = 'white';
        }
        
        // Smooth transition back
        setTimeout(() => {
            preferenceItem.style.transition = 'all 0.3s';
            preferenceItem.style.borderColor = '#e9ecef';
            preferenceItem.style.backgroundColor = 'white';
        }, 300);
    });
});

// Test notification function
function testNotification() {
    if (confirm('This will send a test notification to your account. Continue?')) {
        fetch('send_test_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test notification sent! Check your notifications.');
            } else {
                alert('Failed to send test notification.');
            }
        });
    }
}

// Show confirmation before leaving if form is dirty
let formChanged = false;
document.querySelectorAll('.form-check-input').forEach(input => {
    input.addEventListener('change', () => {
        formChanged = true;
    });
});

window.addEventListener('beforeunload', (e) => {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Reset form changed flag on submit
document.querySelector('form').addEventListener('submit', () => {
    formChanged = false;
});
</script>
</body>
</html>