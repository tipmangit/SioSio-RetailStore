<?php
session_start();
include("admin_session_check.php");
include("../config.php");
require_once('audit_trail_functions.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$success_message = '';
$error_message = '';
$admin_id = $_SESSION['admin_id'];

/**
 * Helper function to reformat the complex $_FILES array.
 */
function reformat_files_array($files) {
    $reformatted = [];
    if (empty($files) || !isset($files['name'])) {
        return $reformatted;
    }
    foreach ($files['name'] as $id => $name) {
        if ($files['error'][$id] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $reformatted[$id] = [
            'name'     => $name,
            'type'     => $files['type'][$id],
            'tmp_name' => $files['tmp_name'][$id],
            'error'    => $files['error'][$id],
            'size'     => $files['size'][$id]
        ];
    }
    return $reformatted;
}

// --- Handle Form Submission (Per Tab) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tab_cms'])) {
    
    $page_name = $_POST['page_name'] ?? null; // Get the page name from hidden input
    if (!$page_name || !in_array($page_name, ['homepage', 'about', 'contact'])) {
        $error_message = "Invalid page submitted.";
    } else {
        $text_updates = 0;
        $image_updates = 0;
        $errors = [];

        // --- 1. Handle Text Content Updates for the submitted tab ---
        if (isset($_POST['content']) && is_array($_POST['content'])) {
            $stmt_text = $con->prepare("UPDATE cms_content SET content_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND page_name = ?");
            
// In the text content update section:
foreach ($_POST['content'] as $id => $value) {
    $id = (int)$id;
    if ($id > 0) {
        // Auto-format: Add HTML highlighting to "Sio" words
        $formatted_value = preg_replace('/\b(Sio)(mai|pao|Sio)\b/i', '<span class="sio-highlight">$1</span>$2', $value);
        
        $stmt_text->bind_param("siis", $formatted_value, $admin_id, $id, $page_name);
        if ($stmt_text->execute()) {
            $text_updates++;
        }
    }
}
            $stmt_text->close();
        }

        // --- 2. Handle Image Content Updates for the submitted tab ---
        if (isset($_FILES['image_content'])) {
            $image_files = reformat_files_array($_FILES['image_content']);
            $stmt_image = $con->prepare("UPDATE cms_content SET content_value = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND page_name = ?");

            foreach ($image_files as $id => $file) {
                $id = (int)$id;
                
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "Error uploading image for ID $id. Code: " . $file['error'];
                    continue;
                }

                $target_dir = "../uploads/cms/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_name = basename($file["name"]);
                $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $new_filename = uniqid('cms_', true) . '.' . $file_type;
                $target_file = $target_dir . $new_filename;
                $db_path = "uploads/cms/" . $new_filename;

                $check = getimagesize($file["tmp_name"]);
                if ($check === false) {
                    $errors[] = "File for ID $id is not a valid image."; continue;
                }
                if ($file["size"] > 15000000) { // 15MB limit
                    $errors[] = "Image for ID $id is too large (15MB limit)."; continue;
                }
                
                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    $stmt_image->bind_param("siis", $db_path, $admin_id, $id, $page_name); // Added page_name check
                    if ($stmt_image->execute()) {
                        $image_updates++;
                    } else {
                        $errors[] = "Failed to update image path in DB for ID: $id.";
                    }
                } else {
                    $errors[] = "Failed to move uploaded file for ID: $id.";
                }
            }
            $stmt_image->close();
        }
        
        // --- Set Success/Error Messages ---
        $page_display_name = ucwords(str_replace('_', ' ', $page_name));
        if (empty($errors)) {
            if ($text_updates > 0 || $image_updates > 0) {
                $success_message = "Successfully updated content for the '$page_display_name' page.";
            } else {
                 $success_message = "No new changes submitted for the '$page_display_name' page.";
            }
        } else {
            $error_message = "Some updates failed for '$page_display_name':<br>" . implode("<br>", $errors);
            if ($text_updates > 0 || $image_updates > 0) {
                $success_message = "Partially updated content for the '$page_display_name' page.";
            }
        }
    }
}


/**
 * Helper function to fetch all editable content for a page.
 */
function getAdminCMSContent($con, $page) {
    $stmt = $con->prepare("SELECT id, section_name, content_value, content_type FROM cms_content WHERE page_name = ? ORDER BY display_order, id");
    if (!$stmt) { 
        error_log("Prepare failed: (" . $con->errno . ") " . $con->error);
        return []; 
    }
    $stmt->bind_param("s", $page);
    if (!$stmt->execute()) {
        error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        return [];
    }
    $result = $stmt->get_result();
    $content = [];
    while ($row = $result->fetch_assoc()) {
        $content[$row['section_name']] = $row; 
    }
    $stmt->close();
    return $content;
}

/**
 * Helper function to render a user-friendly form INPUT for each CMS item.
 */
function renderCmsInput($item, $label) {
    if (!$item || !is_array($item)) {
        echo "<div class='alert alert-warning'><strong>Missing Content:</strong> '{$label}' not found.</div>";
        return;
    }
    
    $content_id = $item['id'];
    $content_value = $item['content_value'];
    $content_type = $item['content_type'];

    echo "<div class='card card-body mb-3 shadow-sm'>";
    echo "<h5 class='mb-3'>$label</h5>";

    if ($content_type == 'image') {
        if (!empty($content_value)) {
            echo "<div class='mb-2'><img src='../$content_value' alt='Current Image' style='max-width: 300px; height: auto; border-radius: 5px; border: 1px solid #ddd;'></div>";
        }
        echo "<label class='form-label'>Upload new image:</label>";
        echo "<input type='file' class='form-control' name='image_content[$content_id]' accept='image/*'>";
        echo "<small class='text-muted'>Recommended: JPG or PNG, max 5MB</small>";
    } else {
        // For text content, remove HTML tags and show plain text
        $plain_text = strip_tags($content_value);
        $plain_text = str_replace(['<span class="sio-highlight">', '</span>'], '', $plain_text);
        $plain_text = htmlspecialchars($plain_text, ENT_QUOTES);
        
        echo "<label class='form-label fw-bold'>$label</label>";
        if (strlen($plain_text) > 100) {
            echo "<textarea class='form-control' name='content[$content_id]' rows='4'>$plain_text</textarea>";
        } else {
            echo "<input type='text' class='form-control' name='content[$content_id]' value='$plain_text'>";
        }
        echo "<small class='text-muted'>Enter plain text only. Red highlighting will be applied automatically to brand names.</small>";
    }
    
    echo "</div>";
}

// --- Fetch all content for all pages ---
$cms_homepage = getAdminCMSContent($con, 'homepage');
$cms_contact = getAdminCMSContent($con, 'contact');
$cms_about = getAdminCMSContent($con, 'about');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - SioSio Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <style>
        :root { --siosio-red: #dc3545; --sidebar-width: 260px; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, #1a1a1a 0%, #2d2d2d 100%); color: white; position: fixed; height: 100vh; overflow-y: auto; z-index: 1000; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h3 { font-family: 'Joti One', cursive; margin: 0; font-size: 1.5rem; }
        .sio-highlight { color: var(--siosio-red); }
        .sidebar-menu { list-style: none; padding: 1rem 0; margin: 0; }
        .sidebar-menu a { display: flex; align-items: center; padding: 0.875rem 1.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(220, 53, 69, 0.1); color: white; border-left: 3px solid var(--siosio-red); }
        .sidebar-menu i { margin-right: 0.75rem; font-size: 1.2rem; }
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 2rem; width: calc(100% - var(--sidebar-width)); }
        .btn-siosio { background: var(--siosio-red); color: white; border: none; }
        .btn-siosio:hover { background: #c82333; color: white; }
        .card.card-body { border: 1px solid #e9ecef; }
        .tab-save-button { text-align: right; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; }
        .tab-save-button .btn { min-width: 180px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header"><h3><span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Admin</h3><p class="mb-0 small text-muted">Management Panel</p></div>
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a></li>
                <li><a href="admin_orders.php"><i class="bi bi-cart-check"></i> Orders</a></li>
                <li><a href="admin_inventory.php"><i class="bi bi-clipboard-data"></i> Inventory</a></li>
                <li><a href="admin_users.php"><i class="bi bi-people"></i> Users</a></li>
                <li><a href="admin_chat_support.php"><i class="bi bi-chat-dots"></i> Chat Support</a></li>
                <li><a href="admin_reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
                <li><a href="admin_returns.php"><i class="bi bi-box-arrow-in-left"></i> Returns</a></li>
                <li><a href="admin_cms.php" class="active"><i class="bi bi-file-text"></i> Content Management</a></li>
                <li><a href="admin_notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
               <li><a href="admin_audit_trail.php"><i class="bi bi-clock-history"></i> Audit Trail</a></li>
                <li><a href="admin_user_audit.php"><i class="bi bi-person-lines-fill"></i> User Audit</a></li>
                <li><a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                <li><a href="admin_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div><h2><i class="bi bi-file-text"></i> Content Management System</h2><p class="text-muted mb-0">Manage website content for Homepage, About Us, and Contact Us pages</p></div>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill me-2"></i><?= $success_message ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $error_message ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-4" id="cmsTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" id="homepage-tab" data-bs-toggle="tab" data-bs-target="#homepage" type="button" role="tab" aria-controls="homepage" aria-selected="true"><i class="bi bi-house-fill"></i> Homepage</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab" aria-controls="about" aria-selected="false"><i class="bi bi-info-circle-fill"></i> About Us</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false"><i class="bi bi-person-lines-fill"></i> Contact Us</button></li>
            </ul>

            <div class="tab-content pt-3" id="cmsTabsContent">
                
                <div class="tab-pane fade show active" id="homepage" role="tabpanel" aria-labelledby="homepage-tab">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="page_name" value="homepage"> 
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Edit Homepage</h3>
                            <a href="../homepage/index.php" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> View Page</a>
                        </div>
                        
                        <?php
                        echo "<h4>Hero Section</h4>";
                        renderCmsInput($cms_homepage['hero_title'] ?? null, 'Hero - Main Title');
                        renderCmsInput($cms_homepage['hero_subtitle'] ?? null, 'Hero - Subtitle');
                        renderCmsInput($cms_homepage['hero_tagline'] ?? null, 'Hero - Tagline');
                        renderCmsInput($cms_homepage['hero_background'] ?? null, 'Hero - Background Image');
                        ?>
                        
                        <div class="tab-save-button">
                            <button type="submit" name="save_tab_cms" class="btn btn-siosio btn-lg">
                                <i class="bi bi-save"></i> Save Homepage Changes
                            </button>
                        </div>
                    </form>
                    </div>
                
                <div class="tab-pane fade" id="about" role="tabpanel" aria-labelledby="about-tab">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="page_name" value="about"> 
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Edit About Us Page</h3>
                            <a href="../company/about.php" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> View Page</a>
                        </div>
                        
                        <?php
                        echo "<h4>Page Header</h4>";
                        renderCmsInput($cms_about['page_title'] ?? null, 'Page Title');
                        renderCmsInput($cms_about['page_subtitle'] ?? null, 'Page Subtitle');
                        
                        echo "<hr class='my-4'>";
                        echo "<h4>Our Story Section</h4>";
                        renderCmsInput($cms_about['story_title'] ?? null, 'Story - Title');
                        renderCmsInput($cms_about['story_lead'] ?? null, 'Story - Lead Paragraph');
                        renderCmsInput($cms_about['story_content'] ?? null, 'Story - Main Content');
                        renderCmsInput($cms_about['story_image'] ?? null, 'Story - Image');
                        
                        echo "<hr class='my-4'>";
                        echo "<h4>Our Values Section</h4>";
                        renderCmsInput($cms_about['value1_title'] ?? null, 'Value 1 - Title');
                        renderCmsInput($cms_about['value1_content'] ?? null, 'Value 1 - Content');
                        renderCmsInput($cms_about['value2_title'] ?? null, 'Value 2 - Title');
                        renderCmsInput($cms_about['value2_content'] ?? null, 'Value 2 - Content');
                        renderCmsInput($cms_about['value3_title'] ?? null, 'Value 3 - Title');
                        renderCmsInput($cms_about['value3_content'] ?? null, 'Value 3 - Content');

                        echo "<hr class='my-4'>";
                        echo "<h4>Signature Products Section</h4>";
                        renderCmsInput($cms_about['products_title'] ?? null, 'Section Title');
                        renderCmsInput($cms_about['products_subtitle'] ?? null, 'Section Subtitle');
                        
                        echo "<h5 class='mt-3'>Product 1: Siomai</h5>";
                        renderCmsInput($cms_about['siomai_title'] ?? null, 'Siomai - Title');
                        renderCmsInput($cms_about['siomai_desc'] ?? null, 'Siomai - Description');
                        renderCmsInput($cms_about['siomai_image'] ?? null, 'Siomai - Image');
                        
                        echo "<h5 class='mt-3'>Product 2: Siopao</h5>";
                        renderCmsInput($cms_about['siopao_title'] ?? null, 'Siopao - Title');
                        renderCmsInput($cms_about['siopao_desc'] ?? null, 'Siopao - Description');
                        renderCmsInput($cms_about['siopao_image'] ?? null, 'Siopao - Image');
                        ?>
                        
                        <div class="tab-save-button">
                            <button type="submit" name="save_tab_cms" class="btn btn-siosio btn-lg">
                                <i class="bi bi-save"></i> Save About Us Changes
                            </button>
                        </div>
                    </form>
                    </div>

                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                     <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="page_name" value="contact"> 
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="mb-0">Edit Contact Us Page</h3>
                            <a href="../contact/contact.php" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right"></i> View Page</a>
                        </div>

                        <?php
                        echo "<h4>Page Header</h4>";
                        renderCmsInput($cms_contact['page_title'] ?? null, 'Page Title');
                        renderCmsInput($cms_contact['page_subtitle'] ?? null, 'Page Subtitle');

                        echo "<hr class='my-4'>";
                        echo "<h4>Contact Details</h4>";
                        renderCmsInput($cms_contact['phone'] ?? null, 'Phone Number');
                        renderCmsInput($cms_contact['email'] ?? null, 'Email Address');
                        renderCmsInput($cms_contact['address'] ?? null, 'Full Address');
                        renderCmsInput($cms_contact['business_hours'] ?? null, 'Business Hours');
                        
                        echo "<hr class='my-4'>";
                        echo "<h4>Frequently Asked Questions</h4>";
                        renderCmsInput($cms_contact['faq_title'] ?? null, 'Section Title');

                        echo "<h5 class='mt-3'>FAQ 1</h5>";
                        renderCmsInput($cms_contact['faq1_question'] ?? null, 'Question 1');
                        renderCmsInput($cms_contact['faq1_answer'] ?? null, 'Answer 1');
                        
                        echo "<h5 class='mt-3'>FAQ 2</h5>";
                        renderCmsInput($cms_contact['faq2_question'] ?? null, 'Question 2');
                        renderCmsInput($cms_contact['faq2_answer'] ?? null, 'Answer 2');
                        
                        echo "<h5 class='mt-3'>FAQ 3</h5>";
                        renderCmsInput($cms_contact['faq3_question'] ?? null, 'Question 3');
                        renderCmsInput($cms_contact['faq3_answer'] ?? null, 'Answer 3');
                        
                        echo "<h5 class='mt-3'>FAQ 4</h5>";
                        renderCmsInput($cms_contact['faq4_question'] ?? null, 'Question 4');
                        renderCmsInput($cms_contact['faq4_answer'] ?? null, 'Answer 4');
                        ?>
                        
                        <div class="tab-save-button">
                            <button type="submit" name="save_tab_cms" class="btn btn-siosio btn-lg">
                                <i class="bi bi-save"></i> Save Contact Us Changes
                            </button>
                        </div>
                    </form>
                     </div>

            </div> </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                if (alert) { const bsAlert = new bootstrap.Alert(alert); if (bsAlert) { bsAlert.close(); } }
            });
        }, 5000);

        // Session timeout
        (function() {
            const timeoutInMilliseconds = 1800000; // 30 minutes
            let inactivityTimer;
            function logout() { window.location.href = 'admin_logout.php?reason=idle'; }
            function resetTimer() { clearTimeout(inactivityTimer); inactivityTimer = setTimeout(logout, timeoutInMilliseconds); }
            window.addEventListener('load', resetTimer);
            document.addEventListener('mousemove', resetTimer);
            document.addEventListener('mousedown', resetTimer);
            document.addEventListener('keypress', resetTimer);
            document.addEventListener('touchmove', resetTimer);
            document.addEventListener('scroll', resetTimer);
        })();
        
        // Persist active tab on reload (after form submission)
        document.addEventListener('DOMContentLoaded', function() {
            let activeTabTarget = localStorage.getItem('activeCMSTab');
            if (activeTabTarget) {
                let tabElement = document.querySelector(`button[data-bs-target="${activeTabTarget}"]`);
                if (tabElement) { new bootstrap.Tab(tabElement).show(); }
            }
            const tabElList = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabElList.forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', event => {
                    localStorage.setItem('activeCMSTab', event.target.getAttribute('data-bs-target'));
                });
            });
        });
    </script>
</body>
</html>