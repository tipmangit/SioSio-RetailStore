<?php
include("../config.php");
require_once("../cms_helper.php");

$isLoggedin = isset($_SESSION['valid']);

// Fetch all about page CMS content
$cms = getAllCMSContent($con, 'about');

// --- FIX FOR IMAGE PATHS ---
// Prepend '../' to CMS paths to make them correct relative to the /company/ folder
// Use the default path (which already has '../') if the CMS value is empty.

$story_image_path = !empty($cms['story_image']) 
    ? '../' . $cms['story_image'] 
    : '../images/mascot.png';

$siomai_image_path = !empty($cms['siomai_image']) 
    ? '../' . $cms['siomai_image'] 
    : '../images/siomai.jpg';

$siopao_image_path = !empty($cms['siopao_image']) 
    ? '../' . $cms['siopao_image'] 
    : '../images/siopao.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(strip_tags($cms['page_title'] ?? 'Our Company')) ?> - SioSio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../products/bootstrap-custom.css">
    <link rel="stylesheet" href="../products/custom.css">
    <link rel="stylesheet" href="company.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
</head>

<body>
    <?php include("../headfoot/header.php") ?>

    <header class="page-header text-center text-white d-flex align-items-center justify-content-center">
        <div class="container">
            <h1 class="page-title display-4 fw-bold"><?= $cms['page_title'] ?? 'About <span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span>' ?></h1>
            <p class="page-subtitle lead"><?= htmlspecialchars($cms['page_subtitle'] ?? 'Discover the story behind the Philippines\' beloved Siomai and Siopao brand') ?></p>
        </div>
    </header>

    <section class="our-story-section py-5">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <h2 class="section-title"><?= htmlspecialchars($cms['story_title'] ?? 'Our Story') ?></h2>
                    <?= $cms['story_lead'] ?? '<p class="lead mb-4">From humble beginnings...</p>' ?>
                    <?= $cms['story_content'] ?? '<p class="mb-4">Founded with a simple mission...</p>' ?>
                </div>
                <div class="col-lg-6 text-center story-image">
                    <img src="<?= htmlspecialchars($story_image_path) ?>" alt="SioSio Mascot" class="img-fluid rounded shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <section class="our-values-section py-5 bg-light">
        <div class="container">
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="value-card h-100 p-4 shadow-sm">
                        <i class="bi bi-gem value-icon"></i>
                        <h4 class="mt-3"><?= htmlspecialchars($cms['value1_title'] ?? 'Quality First') ?></h4>
                        <p><?= htmlspecialchars($cms['value1_content'] ?? 'We use only the freshest ingredients...') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card h-100 p-4 shadow-sm">
                        <i class="bi bi-heart-fill value-icon"></i>
                        <h4 class="mt-3"><?= htmlspecialchars($cms['value2_title'] ?? 'Family Tradition') ?></h4>
                        <p><?= htmlspecialchars($cms['value2_content'] ?? 'Our recipes and cooking methods...') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="value-card h-100 p-4 shadow-sm">
                        <i class="bi bi-star-fill value-icon"></i>
                        <h4 class="mt-3"><?= htmlspecialchars($cms['value3_title'] ?? 'Affordable Excellence') ?></h4>
                        <p><?= htmlspecialchars($cms['value3_content'] ?? 'We believe great food shouldn\'t...') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="products-section" class="signature-products py-5">
        <div class="container">
            <h2 class="section-title text-center"><?= $cms['products_title'] ?? 'Our <span class="sio-highlight">Signature</span> Products' ?></h2>
            <p class="lead text-center mb-5"><?= htmlspecialchars($cms['products_subtitle'] ?? 'The icons that started it all...') ?></p>
            <div class="row g-4 justify-content-center">
                
                <div class="col-lg-5 col-md-6">
                    <div class="product-showcase h-100">
                        <div class="product-image">
                            <img src="<?= htmlspecialchars($siomai_image_path) ?>" class="img-fluid" alt="SioSio Siomai">
                        </div>
                        <div class="product-content">
                            <h3 class="product-name"><?= htmlspecialchars($cms['siomai_title'] ?? 'SioSio Siomai') ?></h3>
                            <p class="product-description">
                                <?= $cms['siomai_desc'] ?? 'Our classic pork siomai...' ?>
                            </p>
                            <a href="../products/product.php" class="btn btn-danger">
                                Order Now <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 col-md-6">
                    <div class="product-showcase h-100">
                         <div class="product-image">
                            <img src="<?= htmlspecialchars($siopao_image_path) ?>" class="img-fluid" alt="SioSio Siopao">
                        </div>
                        <div class="product-content">
                            <h3 class="product-name"><?= htmlspecialchars($cms['siopao_title'] ?? 'SioSio Siopao') ?></h3>
                            <p class="product-description">
                                <?= $cms['siopao_desc'] ?? 'Fluffy steamed buns...' ?>
                            </p>
                            <a href="../products/product.php" class="btn btn-danger">
                                Order Now <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section bg-dark text-white py-5">
        <div class="container text-center">
            <h2 class="mb-3">Ready to Taste the <span class="sio-highlight">Sio</span><span class="sio-highlight">Sio</span> Experience?</h2>
            <p class="lead mb-4">Join thousands of satisfied customers who have made us their go-to for authentic Filipino comfort food.</p>
            <div class="cta-buttons">
                <a href="../products/product.php" class="btn btn-danger btn-lg me-3 mb-2">
                    <i class="bi bi-cart-plus me-2"></i>Shop Now
                </a>
                <a href="../contact/contact.php" class="btn btn-outline-light btn-lg mb-2">
                    <i class="bi bi-envelope me-2"></i>Contact Us
                </a>
            </div>
        </div>
    </section>

    <?php include("../headfoot/footer.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>