<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config.php"); // Make sure $con is available
require_once('notification_functions.php');

// Determine user login status and get current username
$isLoggedin = false;
$currentUsername = 'Guest'; // Default username
$user_id = null;
$cartCount = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    
    // --- NEW: Fetch current username and status from database ---
    if (isset($con)) {
        $stmt_user = $con->prepare("SELECT username, status FROM userss WHERE user_id = ?");
        if ($stmt_user) {
            $stmt_user->bind_param("i", $user_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();

            if ($result_user->num_rows === 1) {
                $user_data = $result_user->fetch_assoc();
                
                // Check if suspended
                if ($user_data['status'] === 'suspended') {
                    // Log out suspended user
                    session_unset();     
                    session_destroy();   
                    header("Location: ../loginreg/logreg.php?error=suspended"); 
                    exit; 
                } else {
                    // User is valid and not suspended
                    $isLoggedin = true;
                    $currentUsername = $user_data['username']; // Get current username
                    
                    // --- UPDATE SESSION IF NEEDED (Optional but recommended) ---
                    // If the username in DB is different from session, update session
                    if (isset($_SESSION['valid']) && $_SESSION['valid'] !== $currentUsername) {
                         $_SESSION['valid'] = $currentUsername;
                    }
                    // --- END OPTIONAL UPDATE ---
                }
            } else {
                // User ID not found, force logout
                session_unset();
                session_destroy();
                $isLoggedin = false; 
            }
            $stmt_user->close();
        } else {
             error_log("Failed to prepare statement to fetch user data: " . $con->error);
             $isLoggedin = false; // Treat as logged out if DB query fails
        }
    } else {
         error_log("Database connection not available in header.");
         $isLoggedin = false; // Treat as logged out if no DB connection
    }
    // --- END NEW FETCH ---

} else {
    $isLoggedin = false;
}


// For navigation highlighting (No changes needed here)
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

function isActiveNav($page) {
    global $currentPage, $currentDir;
    switch($page) {
        case 'home':
            return ($currentDir == 'homepage') || ($currentPage == 'index.php');
        case 'shop':
            return ($currentDir == 'products') || ($currentPage == 'product.php');
        case 'favorites':
            return ($currentDir == 'favorites') || ($currentPage == 'favorites.php');
        case 'about':
            return ($currentDir == 'company') || ($currentPage == 'about.php');
        case 'contact':
            return ($currentDir == 'contact') || ($currentPage == 'contact.php');
        case 'cart':
            return ($currentDir == 'cart') || ($currentPage == 'cart.php');
        case 'profile':
            return ($currentDir == 'profile') || ($currentPage == 'profile.php');
        default:
            return false;
    }
}

if ($isLoggedin && $user_id !== null) {
    // Logged-in user -> query database cart
    $stmt_cart = $con->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total_quantity 
        FROM cart 
        WHERE user_id = ? AND status = 'active'
    ");
    if ($stmt_cart) {
        $stmt_cart->bind_param("i", $user_id);
        $stmt_cart->execute();
        $result_cart = $stmt_cart->get_result();
        $row_cart = $result_cart->fetch_assoc();
        $cartCount = (int)$row_cart['total_quantity'];
        $stmt_cart->close();
    } else {
        error_log("Failed to prepare statement for cart count: " . $con->error);
        $cartCount = 0;
    }
} else {
    // Guest user → use session cart
    $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SioSio</title>

    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../products/bootstrap-custom.css">
    <link rel="stylesheet" href="../products/custom.css">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="headfoot.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">

    <style>
        /* Dropdown Fix Styles */
        .dropdown-wrapper {
            position: relative;
        }

        .custom-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-top: 8px;
            display: none;
            z-index: 1000;
            min-width: 250px;
        }

        .custom-dropdown-menu.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .notification-dropdown-menu {
            width: 380px;
            max-height: 500px;
            overflow: hidden;
        }

        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .notification-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }

        .notification-header h6 {
            margin: 0;
            font-weight: 600;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            gap: 12px;
        }

        .notification-item:hover {
            background: #f8f9fa;
        }

        .notification-item.unread {
            background: #fff5f5;
        }

        .notification-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f0f0;
            border-radius: 50%;
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 4px;
            color: #333;
        }

        .notification-message {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.4;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .notification-time {
            font-size: 0.75rem;
            color: #999;
        }

        .notification-footer {
            padding: 10px 15px;
            border-top: 1px solid #eee;
            text-align: center;
            background: #f8f9fa;
        }

        .notification-footer a {
            color: #dc3545;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .empty-notifications {
            padding: 40px 20px;
            text-align: center;
            color: #999;
        }

        .empty-notifications i {
            font-size: 3rem;
            margin-bottom: 10px;
            opacity: 0.3;
        }

        .mark-all-read {
            color: #dc3545;
            font-size: 0.85rem;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
        }

        .mark-all-read:hover {
            text-decoration: underline;
        }

        .notification-bell {
            cursor: pointer;
            transition: color 0.3s;
        }

        .notification-bell:hover {
            color: #dc3545 !important;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -10px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .profile-dropdown-item {
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .profile-dropdown-item:hover {
            background: #f8f9fa;
            color: #dc3545;
        }

        .profile-dropdown-item i {
            width: 20px;
        }

        .dropdown-divider {
            margin: 5px 0;
            border-top: 1px solid #eee;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
   <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid nav-container">
            <a class="navbar-brand mx-auto" href="../homepage/index.php">
                <h1 class="logo mb-0">
                    <?php if ($isLoggedin): ?>
                        Welcome, <span class="sio-highlight"><?= htmlspecialchars($currentUsername) ?></span>! <?php else: ?>
                        Welcome, mga ka-<span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span>!
                    <?php endif; ?>
                </h1>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="nav-left d-none d-lg-flex">
                <a href="../homepage/index.php" class="nav-link fw-bold <?= isActiveNav('home') ? 'active' : '' ?>">Home</a>
                <a href="../products/product.php" class="nav-link fw-bold <?= isActiveNav('shop') ? 'active' : '' ?>">Shop</a>
                
                <?php if ($isLoggedin): ?>
                    <a href="../favorites/favorites.php" class="nav-link text-danger fw-bold <?= isActiveNav('favorites') ? 'active' : '' ?>">
                        <i class="bi bi-heart-fill"></i> Favorites
                    </a>
                <?php else: ?>
                    <a href="#" class="nav-link text-danger fw-bold" onclick="showLoginNotification(event)">
                        <i class="bi bi-heart-fill"></i> Favorites
                    </a>
                <?php endif; ?>

                <a href="../company/about.php" class="nav-link fw-bold <?= isActiveNav('about') ? 'active' : '' ?>">About Us</a>
                <a href="../contact/contact.php" class="nav-link fw-bold <?= isActiveNav('contact') ? 'active' : '' ?>">Contact Us</a>
            </div>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav d-lg-none">
                    <li><a href="../homepage/index.php" class="nav-link <?= isActiveNav('home') ? 'active' : '' ?>">Home</a></li>
                    <li><a href="../products/product.php" class="nav-link <?= isActiveNav('shop') ? 'active' : '' ?>">Shop</a></li>
                    <li>
                        <?php if ($isLoggedin): ?>
                            <a href="../favorites/favorites.php" class="nav-link <?= isActiveNav('favorites') ? 'active' : '' ?>">Favorites</a>
                        <?php else: ?>
                            <a href="#" class="nav-link" onclick="showLoginNotification(event)">Favorites</a>
                        <?php endif; ?>
                    </li>
                    <li><a href="../company/about.php" class="nav-link <?= isActiveNav('about') ? 'active' : '' ?>">About Us</a></li>
                    <li><a href="../contact/contact.php" class="nav-link <?= isActiveNav('contact') ? 'active' : '' ?>">Contact Us</a></li>
                </ul>

                <div class="nav-right d-flex align-items-center ms-auto gap-3">
                    <?php if ($isLoggedin): ?>
                        <?php include('notification_dropdown.php'); ?>
                    <?php endif; ?>

                    <a href="../cart/cart.php" class="btn btn-outline-light position-relative rounded-circle hover-scale <?= isActiveNav('cart') ? 'active' : '' ?>">
                        <i class="bi bi-cart3"></i>
                        <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $cartCount ?>
                        </span>
                    </a>

                    <?php if ($isLoggedin): ?>
                        <div class="dropdown-wrapper">
                            <button class="btn btn-outline-light rounded-circle" 
                                    id="profileMenuBtn" type="button">
                                <i class="bi bi-person-fill"></i>
                            </button>
                            <div class="custom-dropdown-menu" id="profileDropdown" style="min-width: 220px;">
                                <a href="../user/notifications.php" class="profile-dropdown-item">
                                    <i class="bi bi-bell"></i> Notifications
                                </a>
                                <a href="../cart/my_orders.php" class="profile-dropdown-item">
                                    <i class="bi bi-bag-check"></i> My Orders
                                </a>
                                <a href="../Profile/profile.php" class="profile-dropdown-item">
                                    <i class="bi bi-person-badge"></i> Profile
                                </a>
                                <a href="../user/notification_preferences.php" class="profile-dropdown-item">
                                    <i class="bi bi-gear"></i> Notification Settings
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="#" class="profile-dropdown-item" onclick="openChatSupport(); return false;">
                                    <i class="bi bi-chat-dots"></i> Live Chat Support
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="../logout.php" class="profile-dropdown-item">
                                    <i class="bi bi-box-arrow-right"></i> Log Out
                                </a>
                            </div>
                        </div>
                        <span class="text-light small d-none d-lg-inline">Hi, <strong><?= htmlspecialchars($currentUsername); ?></strong></span>
                    <?php else: ?>
                        <a href="../loginreg/logreg.php" class="btn btn-outline-light px-3 py-2 rounded hover-scale">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Login Notification Modal -->
    <div class="modal fade" id="loginNotificationModal" tabindex="-1" aria-labelledby="loginNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="loginNotificationModalLabel">
                        <i class="bi bi-heart-fill text-danger me-2"></i>
                        Access Your Favorites
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="bi bi-person-circle display-1 text-muted mb-3"></i>
                        <h4 class="mb-3">To see your favorites, please create or login to your account!</h4>
                        <p class="text-muted">
                            Create an account to save your favorite <span class="sio-highlight">Sio</span>mai and 
                            <span class="sio-highlight">Sio</span>pao items for easy access later.
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <a href="../loginreg/logreg.php" class="btn btn-primary btn-lg px-4">
                        <i class="bi bi-person-plus me-2"></i>Login / Create Account
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" data-bs-dismiss="modal">
                        Maybe Later
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dropdown Management
        document.addEventListener('DOMContentLoaded', function() {
            // Notification Bell Dropdown
            const notifBellBtn = document.getElementById('notificationBellBtn');
            const notifDropdown = document.getElementById('notificationDropdown');
            
            if (notifBellBtn) {
                notifBellBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notifDropdown.classList.toggle('show');
                    closeProfileDropdown();
                });
            }

            // Profile Menu Dropdown
            const profileMenuBtn = document.getElementById('profileMenuBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            
            if (profileMenuBtn) {
                profileMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileDropdown.classList.toggle('show');
                    closeNotifDropdown();
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (notifDropdown && !notifDropdown.contains(e.target) && 
                    (!notifBellBtn || !notifBellBtn.contains(e.target))) {
                    closeNotifDropdown();
                }
                if (profileDropdown && !profileDropdown.contains(e.target) && 
                    (!profileMenuBtn || !profileMenuBtn.contains(e.target))) {
                    closeProfileDropdown();
                }
            });

            // Close dropdowns when clicking a link inside them
            if (notifDropdown) {
                notifDropdown.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', closeNotifDropdown);
                });
            }
            if (profileDropdown) {
                profileDropdown.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', closeProfileDropdown);
                });
            }
        });

        function closeNotifDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        function closeProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) dropdown.classList.remove('show');
        }

        // Favorites login modal
        function showLoginNotification(event) {
            event.preventDefault();
            var modal = new bootstrap.Modal(document.getElementById('loginNotificationModal'));
            modal.show();
        }

        // Open chat support function
        function openChatSupport() {
            if (typeof Tawk_API !== 'undefined' && Tawk_API.maximize) {
                Tawk_API.maximize();
            } else {
                alert('Live chat is currently unavailable. Please contact us at hello@siosio.ph or call (+63) 917-123-4567');
            }
        }

        // Highlight active nav links
        document.addEventListener('DOMContentLoaded', function() {
            const currentURL = window.location.pathname;
            const currentPage = currentURL.split('/').pop();
            const currentDir = currentURL.split('/').slice(-2, -1)[0];

            let activeLinks = [];
            if (currentDir === 'homepage' || currentPage === 'index.php')
                activeLinks = document.querySelectorAll('a[href*="homepage"], a[href*="index.php"]');
            else if (currentDir === 'products' || currentPage === 'product.php')
                activeLinks = document.querySelectorAll('a[href*="products"], a[href*="product.php"]');
            else if (currentDir === 'favorites' || currentPage === 'favorites.php')
                activeLinks = document.querySelectorAll('a[href*="favorites"]');
            else if (currentDir === 'company' || currentPage === 'about.php')
                activeLinks = document.querySelectorAll('a[href*="company"], a[href*="about.php"]');
            else if (currentDir === 'contact' || currentPage === 'contact.php')
                activeLinks = document.querySelectorAll('a[href*="contact"]');
            else if (currentDir === 'cart' || currentPage === 'cart.php')
                activeLinks = document.querySelectorAll('a[href*="cart"]');
            else if (currentDir === 'profile' || currentPage === 'profile.php')
                activeLinks = document.querySelectorAll('a[href*="profile"]');

            activeLinks.forEach(link => link.classList.add('active'));
        });
    </script>
    
    <?php 
    if (file_exists("../chat/tawk_widget.php")) {
        include("../chat/tawk_widget.php");
    } elseif (file_exists("chat/tawk_widget.php")) {
        include("chat/tawk_widget.php");
    }
    ?>
   
    <?php include($_SERVER['DOCUMENT_ROOT'] . './chat/chat_init.php'); ?>
</body>
</html>