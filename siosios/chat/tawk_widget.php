<?php
/**
 * Tawk.to Live Chat Widget Integration for SioSio Store
 * File Location: /chat/tawk_widget.php
 * 
 * Setup Instructions:
 * 1. Sign up at https://www.tawk.to/ (FREE)
 * 2. Create a property for "SioSio Store"
 * 3. Get your Property ID and Widget ID from dashboard
 * 4. Replace YOUR_PROPERTY_ID and YOUR_WIDGET_ID below (lines 53-54)
 * 5. Optional: Enable secure mode and add secret key (line 25)
 */

// Initialize variables
$userName = '';
$userEmail = '';
$userId = '';
$userType = 'Guest';

// Check if user is logged in (customer)
if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    $userId = $_SESSION['user_id'];
    $userType = 'Customer';
    
    // Fetch user details from database
    if (isset($con) && $con) {
        $stmt = $con->prepare("SELECT name, email, contact_num FROM userss WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $userName = $row['name'];
            $userEmail = $row['email'];
        }
        $stmt->close();
    }
}

// Check if admin is logged in
if (isset($_SESSION['admin_id'])) {
    $userId = 'admin_' . $_SESSION['admin_id'];
    $userName = $_SESSION['admin_name'] ?? 'Admin User';
    $userEmail = $_SESSION['admin_email'] ?? 'admin@siosio.com';
    $userType = 'Admin';
}
?>

<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();

<?php if (!empty($userId)): ?>
// Set visitor information for logged-in users
Tawk_API.visitor = {
    name: '<?= htmlspecialchars($userName, ENT_QUOTES) ?>',
    email: '<?= htmlspecialchars($userEmail, ENT_QUOTES) ?>'
    <?php if (!empty($userEmail)): ?>
    // Optional: Secure mode hash (requires secret key from Tawk.to dashboard)
    // , hash: '<?= hash_hmac("sha256", $userEmail, "YOUR_SECRET_KEY_HERE") ?>'
    <?php endif; ?>
};

// Set custom attributes when chat loads
Tawk_API.onLoad = function(){
    // Add custom user attributes
    Tawk_API.setAttributes({
        'userId': '<?= $userId ?>',
        'userType': '<?= $userType ?>',
        'registrationDate': '<?= date('Y-m-d') ?>'
    }, function(error){
        if (error) {
            console.log('Error setting attributes:', error);
        }
    });
};
<?php endif; ?>

// Pre-populate chat with product/order context if available
<?php 
// Check if coming from product page
if (isset($_GET['product_id']) && isset($_GET['product_name'])): 
?>
Tawk_API.onLoad = function(){
    Tawk_API.addEvent('Product Inquiry', {
        'product_id': '<?= htmlspecialchars($_GET['product_id']) ?>',
        'product_name': '<?= htmlspecialchars($_GET['product_name']) ?>'
    }, function(error){});
};
<?php endif; ?>

<?php 
// Check if coming from order page
if (isset($_GET['order_id']) && isset($_GET['tracking'])): 
?>
Tawk_API.onLoad = function(){
    Tawk_API.addEvent('Order Inquiry', {
        'order_id': '<?= htmlspecialchars($_GET['order_id']) ?>',
        'tracking': '<?= htmlspecialchars($_GET['tracking']) ?>'
    }, function(error){});
};
<?php endif; ?>

// Initialize Tawk.to widget
(function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    // ⚠️ IMPORTANT: Replace with YOUR Tawk.to Property ID and Widget ID
    s1.src='https://embed.tawk.to/68f1ef46531fcd19519823e4/1j7pfh7kg';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
})();

// Custom styling and behavior
Tawk_API.onLoad = function(){
    // Customize widget appearance
    Tawk_API.customStyle = {
        visibility: {
            desktop: {
                position: 'br', // bottom-right
                xOffset: 20,    // 20px from right
                yOffset: 20     // 20px from bottom
            },
            mobile: {
                position: 'br',
                xOffset: 10,
                yOffset: 80     // Higher to avoid mobile nav overlap
            }
        }
    };
};

// Track chat events (optional - for analytics)
Tawk_API.onChatMaximized = function(){
    console.log('Chat opened');
    // You can add Google Analytics tracking here if needed
    // Example: gtag('event', 'chat_opened', { 'event_category': 'engagement' });
};

Tawk_API.onChatMinimized = function(){
    console.log('Chat minimized');
};

Tawk_API.onChatStarted = function(){
    console.log('Chat conversation started');
};

Tawk_API.onChatEnded = function(){
    console.log('Chat conversation ended');
};

// Add custom tags based on page
<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$tags = [];

switch($currentPage) {
    case 'product':
    case 'product_details':
        $tags[] = 'product-page';
        break;
    case 'cart':
        $tags[] = 'cart-page';
        break;
    case 'checkout':
        $tags[] = 'checkout-page';
        break;
    case 'my_orders':
        $tags[] = 'orders-page';
        break;
    case 'contact':
        $tags[] = 'contact-page';
        break;
}

if (!empty($tags)):
?>
Tawk_API.onLoad = function(){
    Tawk_API.addTags(<?= json_encode($tags) ?>, function(error){});
};
<?php endif; ?>
</script>
<!--End of Tawk.to Script-->

<style>
/* Custom styling for Tawk.to widget to match SioSio brand */
#tawk-bubble-container {
    bottom: 20px !important;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    #tawk-bubble-container {
        bottom: 70px !important; /* Account for mobile navigation */
        right: 10px !important;
    }
}

/* Admin panel adjustments */
<?php if (isset($_SESSION['admin_id'])): ?>
@media (min-width: 769px) {
    #tawk-bubble-container {
        right: 20px !important;
        bottom: 20px !important;
    }
}
<?php endif; ?>

/* Hide chat widget on print */
@media print {
    #tawk-bubble-container,
    #tawk-chat-widget,
    .tawk-chatwidget,
    .tawk-min-container {
        display: none !important;
    }
}

/* Pulse animation for new messages (optional) */
@keyframes tawk-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.tawk-new-message {
    animation: tawk-pulse 2s infinite;
}
</style>

<!-- Fallback for browsers with JavaScript disabled -->
<noscript>
    <div style="position: fixed; bottom: 20px; right: 20px; background: #dc3545; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999;">
        <strong>Live Chat Unavailable</strong><br>
        <small>Please enable JavaScript or contact us at:<br>
        📧 hello@siosio.ph<br>
        📞 (+63) 917-123-4567</small>
    </div>
</noscript>