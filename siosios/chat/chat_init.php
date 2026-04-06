<?php
/**
 * Universal Chat Widget Initializer
 * File: /chat/chat_init.php
 * 
 * Include this file at the END of EVERY PHP page (before closing </body> tag)
 * Usage: <?php include($_SERVER['DOCUMENT_ROOT'] . '/siosio_store/chat/chat_init.php'); ?>
 */

// Only load chat on non-admin pages
$is_admin_page = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);

if ($is_admin_page) {
    return; // Don't load chat on admin pages
}

// Gather user information
$userName = '';
$userEmail = '';
$userId = '';
$userType = 'Guest';
$userPhone = '';

// Check if customer is logged in
if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    $userId = $_SESSION['user_id'];
    $userType = 'Registered Customer';
    
    // Fetch user details from database
    if (isset($con) && $con) {
        $stmt = $con->prepare("SELECT name, email, contact_num FROM userss WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $userName = $row['name'];
            $userEmail = $row['email'];
            $userPhone = $row['contact_num'] ?? '';
        }
        $stmt->close();
    }
}

// Get current page context
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Determine page category for AI context
$pageContext = '';
switch($currentDir) {
    case 'products':
        $pageContext = 'browsing products';
        break;
    case 'cart':
        $pageContext = 'viewing cart';
        break;
    case 'checkout':
        $pageContext = 'at checkout';
        break;
    case 'contact':
        $pageContext = 'on contact page';
        break;
    case 'homepage':
        $pageContext = 'on homepage';
        break;
    default:
        $pageContext = 'browsing website';
}
?>

<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();

<?php if (!empty($userId)): ?>
// Set visitor information
Tawk_API.visitor = {
    name: '<?= htmlspecialchars($userName, ENT_QUOTES) ?>',
    email: '<?= htmlspecialchars($userEmail, ENT_QUOTES) ?>',
    <?php if (!empty($userPhone)): ?>
    phone: '<?= htmlspecialchars($userPhone, ENT_QUOTES) ?>',
    <?php endif; ?>
};
<?php endif; ?>

// Initialize before widget loads
Tawk_API.onLoad = function(){
    // Set custom attributes for AI context
    Tawk_API.setAttributes({
        'userId': '<?= $userId ?: "guest" ?>',
        'userType': '<?= $userType ?>',
        'currentPage': '<?= $currentPage ?>',
        'pageContext': '<?= $pageContext ?>',
        'timestamp': '<?= date('Y-m-d H:i:s') ?>'
    }, function(error){
        if(error){
            console.log('Tawk.to: Error setting attributes');
        }
    });
    
    <?php if (!empty($userId)): ?>
    // Add registered customer tag for AI routing
    Tawk_API.addTags(['registered-customer', '<?= $currentDir ?>-page'], function(error){});
    <?php else: ?>
    // Add guest tag
    Tawk_API.addTags(['guest', '<?= $currentDir ?>-page'], function(error){});
    <?php endif; ?>
};

// Product inquiry context
<?php if (isset($_GET['product_id']) && isset($_GET['product_name'])): ?>
Tawk_API.onLoad = function(){
    Tawk_API.addEvent('Product Inquiry', {
        'product_id': '<?= htmlspecialchars($_GET['product_id']) ?>',
        'product_name': '<?= htmlspecialchars($_GET['product_name']) ?>'
    }, function(error){});
};
<?php endif; ?>

// Order inquiry context
<?php if (isset($_GET['order_id']) && isset($_GET['tracking'])): ?>
Tawk_API.onLoad = function(){
    Tawk_API.addEvent('Order Inquiry', {
        'order_id': '<?= htmlspecialchars($_GET['order_id']) ?>',
        'tracking': '<?= htmlspecialchars($_GET['tracking']) ?>'
    }, function(error){});
};
<?php endif; ?>

// Load Tawk.to widget
(function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    // ⚠️ REPLACE WITH YOUR TAWK.TO IDs
    s1.src='https://embed.tawk.to/YOUR_PROPERTY_ID/YOUR_WIDGET_ID';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
})();

// Widget customization
Tawk_API.onLoad = function(){
    Tawk_API.customStyle = {
        visibility: {
            desktop: {
                position: 'br',
                xOffset: 20,
                yOffset: 20
            },
            mobile: {
                position: 'br',
                xOffset: 10,
                yOffset: 80
            }
        }
    };
};

// Event tracking for analytics
Tawk_API.onChatStarted = function(){
    console.log('Chat started');
    // Optional: Add Google Analytics tracking
    // gtag('event', 'chat_started', {'page': '<?= $currentPage ?>'});
};

Tawk_API.onChatMaximized = function(){
    console.log('Chat maximized');
};
</script>
<!--End of Tawk.to Script-->

<style>
/* Tawk.to widget styling */
@media (max-width: 768px) {
    #tawk-bubble-container {
        bottom: 70px !important;
        right: 10px !important;
    }
}

@media print {
    #tawk-bubble-container,
    #tawk-chat-widget {
        display: none !important;
    }
}
</style>

<noscript>
    <div style="position: fixed; bottom: 20px; right: 20px; background: #dc3545; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999;">
        <strong>Live Chat Unavailable</strong><br>
        <small>Please enable JavaScript or contact us at:<br>
        📧 hello@siosio.ph | 📞 (+63) 917-123-4567</small>
    </div>
</noscript>