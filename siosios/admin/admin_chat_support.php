<?php
/**
 * Admin Chat Support Management
 * File Location: /admin/admin_chat_support.php
 * 
 * This page provides:
 * - Direct link to Tawk.to dashboard
 * - Instructions for managing live chat
 * - FAQ management interface
 * - Chat statistics (if Tawk.to API is configured)
 */

session_start();
include("../config.php");
include("admin_session_check.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Support Management - SioSio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <style>
        :root {
            --siosio-red: #dc3545;
            --sidebar-width: 260px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            font-family: 'Joti One', cursive;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .sio-highlight {
            color: var(--siosio-red);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(220, 53, 69, 0.1);
            color: white;
            border-left: 3px solid var(--siosio-red);
        }
        
        .sidebar-menu i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 2rem;
            width: calc(100% - var(--sidebar-width));
        }
        
        .top-bar {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .content-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .quick-action-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            height: 100%;
        }
        
        .quick-action-card:hover {
            border-color: var(--siosio-red);
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.2);
        }
        
        .quick-action-card i {
            font-size: 3rem;
            color: var(--siosio-red);
            margin-bottom: 1rem;
        }
        
        .faq-item {
            border-left: 3px solid var(--siosio-red);
            padding-left: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .instruction-step {
            background: #f8f9fa;
            border-left: 4px solid var(--siosio-red);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .badge-online {
            background: #28a745;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Admin</h3>
                <p class="mb-0 small text-muted">Management Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php" class="active"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                <li><a href="admin_cms.php"><i class="bi bi-file-text"></i> Content Management</a></li>
                <li><a href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
                <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h4 class="mb-0">
                    <i class="bi bi-chat-dots"></i> Chat Support Management
                </h4>
                <small class="text-muted">Manage live chat and customer support</small>
            </div>
            
            <!-- Status Banner -->
            <div class="alert alert-success d-flex align-items-center mb-4">
                <i class="bi bi-check-circle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="mb-0">Chat Widget is Active</h5>
                    <small>Customers can now chat with you in real-time across the website</small>
                </div>
                <span class="badge badge-online ms-auto">
                    <i class="bi bi-circle-fill"></i> ONLINE
                </span>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="quick-action-card" onclick="window.open('https://dashboard.tawk.to/', '_blank')">
                        <i class="bi bi-box-arrow-up-right"></i>
                        <h5>Open Tawk.to Dashboard</h5>
                        <p class="text-muted mb-0">Respond to customer chats</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="quick-action-card" onclick="window.location.href='../contact/contact.php'">
                        <i class="bi bi-envelope"></i>
                        <h5>View Contact Form</h5>
                        <p class="text-muted mb-0">Check email submissions</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="quick-action-card" data-bs-toggle="modal" data-bs-target="#faqModal">
                        <i class="bi bi-question-circle"></i>
                        <h5>FAQ Reference</h5>
                        <p class="text-muted mb-0">Quick answers guide</p>
                    </div>
                </div>
            </div>
            
            <!-- Setup Instructions -->
            <div class="content-card">
                <h5 class="mb-4"><i class="bi bi-gear"></i> Chat Support Setup & Management</h5>
                
                <div class="instruction-step">
                    <h6><i class="bi bi-1-circle-fill text-danger"></i> Tawk.to Dashboard Access</h6>
                    <p class="mb-2">Login to your Tawk.to dashboard to respond to chats:</p>
                    <a href="https://dashboard.tawk.to/" target="_blank" class="btn btn-danger btn-sm">
                        <i class="bi bi-box-arrow-up-right"></i> Open Tawk.to Dashboard
                    </a>
                </div>
                
                <div class="instruction-step">
                    <h6><i class="bi bi-2-circle-fill text-danger"></i> Desktop & Mobile Apps</h6>
                    <p class="mb-2">Download Tawk.to apps to respond from anywhere:</p>
                    <ul>
                        <li><strong>Desktop:</strong> <a href="https://www.tawk.to/download/" target="_blank">Windows, Mac, Linux</a></li>
                        <li><strong>Mobile:</strong> <a href="https://www.tawk.to/mobile-apps/" target="_blank">iOS & Android</a></li>
                    </ul>
                </div>
                
                <div class="instruction-step">
                    <h6><i class="bi bi-3-circle-fill text-danger"></i> Enable Notifications</h6>
                    <p class="mb-0">Go to Tawk.to Dashboard → Settings → Notifications to enable:</p>
                    <ul class="mb-0">
                        <li>Email notifications for new chats</li>
                        <li>SMS notifications (optional)</li>
                        <li>Browser push notifications</li>
                    </ul>
                </div>
                
                <div class="instruction-step">
                    <h6><i class="bi bi-4-circle-fill text-danger"></i> Set Business Hours</h6>
                    <p class="mb-0">Configure your availability in Tawk.to:</p>
                    <ul class="mb-0">
                        <li>Go to: Administration → Chat Widget → Settings</li>
                        <li>Set your active hours (e.g., Mon-Sun 8 AM - 8 PM)</li>
                        <li>Enable offline message form for after-hours</li>
                    </ul>
                </div>
            </div>
            
            <!-- Quick Response Templates -->
            <div class="content-card">
                <h5 class="mb-4"><i class="bi bi-lightning"></i> Quick Response Templates</h5>
                <p class="text-muted">Setup these shortcuts in Tawk.to (Administration → Shortcuts):</p>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Shortcut</th>
                                <th>Response</th>
                                <th>Use Case</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>/hello</code></td>
                                <td>"Hello! Welcome to SioSio Store! How can I help you today? 😊"</td>
                                <td>Greeting</td>
                            </tr>
                            <tr>
                                <td><code>/hours</code></td>
                                <td>"We're open Monday-Sunday, 8 AM - 8 PM. Delivery hours: 9 AM - 7 PM."</td>
                                <td>Business Hours</td>
                            </tr>
                            <tr>
                                <td><code>/delivery</code></td>
                                <td>"Our delivery fee is ₱50 flat rate within Metro Manila. Typical delivery time is 1-3 hours."</td>
                                <td>Delivery Info</td>
                            </tr>
                            <tr>
                                <td><code>/track</code></td>
                                <td>"To track your order, please log in to your account and go to 'My Orders'. May I have your order number?"</td>
                                <td>Order Tracking</td>
                            </tr>
                            <tr>
                                <td><code>/payment</code></td>
                                <td>"We accept Cash on Delivery (COD) and online payment via Stripe (credit/debit cards)."</td>
                                <td>Payment Methods</td>
                            </tr>
                            <tr>
                                <td><code>/bulk</code></td>
                                <td>"We offer special bulk pricing for orders of 50+ pieces. How many would you like to order?"</td>
                                <td>Bulk Orders</td>
                            </tr>
                            <tr>
                                <td><code>/thanks</code></td>
                                <td>"Thank you for contacting SioSio Store! Have a great day! 🍜"</td>
                                <td>Closing</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Chat Widget Features -->
            <div class="content-card">
                <h5 class="mb-4"><i class="bi bi-stars"></i> Active Chat Widget Features</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>User Identification:</strong> Logged-in customers' names and emails are pre-filled
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Product Context:</strong> Product inquiries show which product customer is viewing
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Order Context:</strong> Order inquiries show order number and tracking
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Page Tracking:</strong> You can see which page customer is on
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Mobile Optimized:</strong> Works perfectly on all devices
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>SioSio Branded:</strong> Custom colors matching your brand
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Offline Form:</strong> Customers can leave messages when you're offline
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                <strong>Chat History:</strong> All conversations are saved in Tawk.to
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Tips for Support Team -->
            <div class="content-card">
                <h5 class="mb-4"><i class="bi bi-lightbulb"></i> Best Practices for Chat Support</h5>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-danger"><i class="bi bi-hand-thumbs-up"></i> DO:</h6>
                        <ul>
                            <li>Respond within 2 minutes if possible</li>
                            <li>Use customer's name when you know it</li>
                            <li>Be friendly and professional</li>
                            <li>Provide order numbers for reference</li>
                            <li>Offer to escalate complex issues</li>
                            <li>Thank customers for their patience</li>
                            <li>Use emojis occasionally for warmth 😊</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger"><i class="bi bi-hand-thumbs-down"></i> DON'T:</h6>
                        <ul>
                            <li>Keep customers waiting without acknowledgment</li>
                            <li>Use overly formal or robotic language</li>
                            <li>Make promises you can't keep</li>
                            <li>Share sensitive customer data in chat</li>
                            <li>End chat without asking if they need more help</li>
                            <li>Ignore chat notifications</li>
                            <li>Get defensive if customer is upset</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- FAQ Reference Modal -->
    <div class="modal fade" id="faqModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-question-circle"></i> Quick FAQ Reference
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="faq-item">
                        <h6 class="text-danger">How much is delivery?</h6>
                        <p>Delivery fee is ₱50 flat rate for all orders within Metro Manila.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h6 class="text-danger">What payment methods do you accept?</h6>
                        <p>We accept Cash on Delivery (COD) and online payment via Stripe (credit/debit cards).</p>
                    </div>
                    
                    <div class="faq-item">
                        <h6 class="text-danger">Do you offer bulk orders?</h6>
                        <p>Yes! We offer bulk order options (16, 32, 64, 128, 256 pieces). For orders over 50 pieces, please contact us 24 hours in advance for special pricing.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h6 class="text-danger">How can customers track orders?</h6>
                        <p>Customers can track orders by logging into their account and going to "My Orders" section. They'll receive email and SMS updates on status changes.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h6 class="text-danger">What if a customer forgets their password?</h6>
                        <p>Direct them to the login page and click "Forgot Password". They'll receive an OTP via email to reset their password.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h6 class="text-danger">Can orders be cancelled?</h6>
                        <p>Yes, customers can cancel orders before they are shipped. They can do this from "My Orders" page. Once shipped, orders cannot be cancelled.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="https://docs.google.com/document/d/YOUR_DOC_ID" target="_blank" class="btn btn-danger">
                        View Complete FAQ Guide
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Session timeout protection
        (function() {
            const timeoutInMilliseconds = 1800000; // 30 minutes
            let inactivityTimer;
            
            function logout() {
                window.location.href = 'admin_logout.php?reason=idle';
            }
            
            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(logout, timeoutInMilliseconds);
            }
            
            window.addEventListener('load', resetTimer);
            document.addEventListener('mousemove', resetTimer);
            document.addEventListener('mousedown', resetTimer);
            document.addEventListener('keypress', resetTimer);
            document.addEventListener('touchmove', resetTimer);
            document.addEventListener('scroll', resetTimer);
        })();
    </script>
</body>
</html>