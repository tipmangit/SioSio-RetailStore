<?php
// Make sure session_start() is called *before* this file is included
// OR call it here if it's not already called in config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("../config.php"); // Includes database connection $con

// --- UPDATED LOGIN & SUSPENSION CHECK ---

$isLoggedIn = false; // Default to not logged in
$user_id = null;     // Default user ID

// 1. Check if user has a session ID
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Store user ID

    // 2. Check if the database connection exists
    if (isset($con)) {
        // 3. Fetch the user's current status from the database
        $stmt_check_status = $con->prepare("SELECT status FROM userss WHERE user_id = ?");

        if ($stmt_check_status) {
            $stmt_check_status->bind_param("i", $user_id);
            $stmt_check_status->execute();
            $result_status = $stmt_check_status->get_result();

            if ($result_status->num_rows === 1) {
                $user_data = $result_status->fetch_assoc();
                
                // 4. Check if the user is suspended
                if ($user_data['status'] === 'suspended') {
                    // User is suspended - Destroy session and redirect
                    session_unset();    
                    session_destroy();  
                    header("Location: ../loginreg/logreg.php?error=suspended"); 
                    exit; // Stop script execution immediately
                } else {
                    // User is logged in and NOT suspended
                    $isLoggedIn = true; 
                }
            } else {
                // User ID from session not found in DB - Force logout
                session_unset();
                session_destroy();
                // Optionally redirect to login page or just treat as logged out
                // header("Location: ../loginreg/logreg.php?error=notfound"); exit; 
                $isLoggedIn = false; // Treat as logged out on this page
            }
            $stmt_check_status->close();
        } else {
            // DB query failed - Log error and treat as logged out for safety
            error_log("Failed to prepare statement to check user status: " . $con->error);
            $isLoggedIn = false; 
        }
    } else {
       // DB connection failed - Log error and treat as logged out
       error_log("Database connection variable not available for user status check."); 
       $isLoggedIn = false;
    }
} else {
    // No session user_id - definitely not logged in
    $isLoggedIn = false;
}

// --- END UPDATED LOGIN & SUSPENSION CHECK ---


// SQL query to get products and review data (no changes needed here)
$sql = "SELECT
            p.*,
            AVG(r.rating) as avg_rating,
            COUNT(r.id) as review_count
        FROM
            products p
        LEFT JOIN
            reviews r ON p.id = r.product_id
        WHERE
            p.status = 'active'
        GROUP BY
            p.id
        ORDER BY
            p.category, p.name";

$result = $con->query($sql);

$categories = [];
if ($result) { // Check if query was successful
    while ($row = $result->fetch_assoc()) {
        $categories[$row['category']][] = $row;
    }
} else {
    // Handle query error, e.g., log it or show a message
    error_log("Error fetching products: " . $con->error);
}

/**
 * PHP function to generate star icons.
 */
function generate_stars($rating) {
    // ... (your existing function - no changes needed)
    $stars = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    for ($i = 0; $i < $full_stars; $i++) {
        $stars .= '<i class="bi bi-star-fill"></i>';
    }
    if ($half_star) {
        $stars .= '<i class="bi bi-star-half"></i>';
    }
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars .= '<i class="bi bi-star"></i>';
    }
    return $stars;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SioSio - Premium Filipino Delicacies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-red: #dc3545;
            --dark-red: #c82333;
            --star-yellow: #ffc107;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding-top: 86px;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 60px 20px;
            text-align: center;
            margin-bottom: 50px;
        }

        .page-header h1 {
            font-family: 'Joti One', cursive;
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        .sio-highlight {
            color: var(--primary-red);
            text-shadow: 2px 2px 8px rgba(220, 53, 69, 0.3);
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Alert Container */
        .alert-container {
            position: fixed;
            top: 120px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1050;
            max-width: 500px;
            width: 90%;
        }

        /* Sorting Section */
        .sort-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .sort-section .row {
            align-items: flex-end;
        }

        .sort-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        #price-sort {
            border: 2px solid var(--primary-red);
            border-radius: 10px;
            padding: 10px 15px;
            font-weight: 600;
            color: var(--primary-red);
            background: white;
            transition: all 0.3s ease;
        }

        #price-sort:focus {
            border-color: var(--dark-red);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        #sort-price-btn {
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
        }

        #sort-price-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
        }

        /* Category Section */
        .category-section {
            margin-bottom: 60px;
        }

        .category-title {
            font-family: 'Joti One', cursive;
            font-size: 2.5rem;
            color: #1a1a1a;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            padding-bottom: 20px;
        }

        .category-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-red);
            border-radius: 2px;
        }

        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 3px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-red);
        }

        .product-image {
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f0f0f0;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.08);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary-red);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-name {
            font-family: 'Joti One', cursive;
            font-size: 1.3rem;
            color: #1a1a1a;
            margin-bottom: 8px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .product-name:hover {
            color: var(--primary-red);
        }

        .product-rating {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .product-rating .stars {
            color: var(--star-yellow);
        }
        .product-rating .review-count {
            font-size: 0.8rem;
            color: #666;
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 12px;
            flex-grow: 1;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .product-price-stock {
           /* Adjust alignment if needed */
        }

        .product-actions {
            display: flex;
            align-items: center;
            gap: 8px; /* Space between buttons */
        }

        .product-price {
            font-size: 1.6rem;
            font-weight: bold;
            color: var(--primary-red);
        }

        .product-stock {
            font-size: 0.8rem;
            color: #999;
        }

        .add-cart-btn {
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .add-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* --- Favorite Button Style --- */
        .favorite-btn {
            background: none;
            border: none;
            color: #adb5bd; /* Light gray initially */
            font-size: 1.4rem;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s ease, transform 0.2s ease;
            line-height: 1;
        }

        .favorite-btn:hover {
            color: var(--primary-red); /* Red on hover */
            transform: scale(1.1);
        }
        /* Style for when an item IS favorited (optional) */
        .favorite-btn.is-favorite {
             color: var(--primary-red);
        }
        /* --- END: Favorite Button Style --- */

        /* Product Modal */
        .product-modal .modal-body {
            padding: 40px 30px;
        }

        .modal-header {
            border-bottom: 3px solid var(--primary-red);
            padding: 25px 30px;
        }

        .modal-title {
            font-family: 'Joti One', cursive;
            font-size: 1.8rem;
            color: #1a1a1a;
        }

        .modal-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .modal-price {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-red);
            margin: 15px 0;
        }

        .modal-description {
            color: #555;
            line-height: 1.8;
            font-size: 1rem;
            margin: 20px 0;
        }

        .modal-stock {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 20px;
        }

        .modal-reviews {
            margin-top: 20px;
        }
        .reviews-title {
            font-family: 'Joti One', cursive;
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 15px;
        }
        .reviews-summary {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .summary-stars {
            font-size: 1.2rem;
            color: var(--star-yellow);
        }
        .summary-text {
            font-size: 1rem;
            font-weight: 600;
            color: #444;
        }
        .review-list {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 10px; /* for scrollbar */
        }
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .review-header strong {
            font-size: 0.95rem;
        }
        .review-stars {
            color: var(--star-yellow);
            font-size: 0.9rem;
        }
        .review-comment {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 0;
            line-height: 1.6;
        }

        /* Review Form Styles */
        .review-form-container {
            background: #f8f9fa;
            border: 1px solid #eee;
            padding: 20px;
            border-radius: 8px;
            margin-top: 25px;
        }
        .review-form-container h5 {
            font-family: 'Joti One', cursive;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .review-form-container .form-label {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .review-form-container .form-control,
        .review-form-container .form-select {
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        .review-form-container .form-control:focus,
        .review-form-container .form-select:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }
        .review-form-container .btn {
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            border: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .review-form-container .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }
        .login-prompt {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            font-weight: 500;
        }
        .login-prompt a {
            color: var(--primary-red);
            font-weight: 600;
            text-decoration: none;
        }
        .login-prompt a:hover {
            text-decoration: underline;
        }
        /* END: Review Form Styles */

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .modal-actions input {
            width: 80px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
        }

        .modal-actions button {
            flex-grow: 1;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary-red), var(--dark-red));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 20px;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2.5rem;
            }

            .category-title {
                font-size: 2rem;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .alert-container {
                top: 100px;
            }
        }
    </style>
</head>
<body>
    <?php include("../headfoot/header.php"); ?>

    <div class="alert-container" id="alertContainer">
        <?php
        if (isset($_SESSION['message'])) {
            $alert_type = (strpos(strtolower($_SESSION['message']), 'error') !== false || strpos(strtolower($_SESSION['message']), 'invalid') !== false || strpos(strtolower($_SESSION['message']), 'must be logged in') !== false || strpos(strtolower($_SESSION['message']), 'please log in') !== false) ? 'danger' : 'success';
            echo '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show" role="alert">';
            echo $_SESSION['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['message']);
        }
        ?>
    </div>

    <section class="page-header">
        <div class="container">
            <h1>Our <span class="sio-highlight">Sio</span>mai & <span class="sio-highlight">Sio</span>pao</h1>
            <p>Premium Filipino Delicacies | Fresh Daily | Best Quality</p>
        </div>
    </section>

    <div class="container">
        <div class="sort-section">
             <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <label class="sort-label">Sort by Price:</label>
                    <select id="price-sort" class="form-select">
                        <option value="min-max">Low to High</option>
                        <option value="max-min">High to Low</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="sort-label">&nbsp;</label>
                    <button id="sort-price-btn" class="w-100">Sort</button>
                </div>
                <div class="col-md-7 col-sm-12">
                    <label class="sort-label">Search Products:</label>
                    <div class="input-group">
                        <input type="text" id="product-search-input" class="form-control" placeholder="Search by name...">
                        <button class="btn btn-danger" id="product-search-btn" type="button">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php foreach ($categories as $category => $products): ?>
            <div class="category-section">
                <h2 class="category-title"><?= ucfirst($category) ?></h2>
                <div class="product-grid category-products" data-category="<?= $category ?>">
                    <?php foreach ($products as $product): ?>
                        <?php
                            $avg_rating = $product['avg_rating'] ? floatval($product['avg_rating']) : 0;
                            $review_count = $product['review_count'] ? intval($product['review_count']) : 0;
                        ?>

                        <div class="product-card" 
                        data-id="<?= $product['id'] ?>" 
                        data-price="<?= $product['price'] ?>"
                        onclick="openProductModal(<?= htmlspecialchars(json_encode($product)) ?>, <?= $avg_rating ?>, <?= $review_count ?>)">
                            <div class="product-image">
                                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                     alt="<?= htmlspecialchars($product['name']) ?>">
                                <span class="product-badge">₱<?= number_format($product['price'], 2) ?></span>
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>

                                <div class="product-rating" onclick="event.stopPropagation(); openProductModal(<?= htmlspecialchars(json_encode($product)) ?>, <?= $avg_rating ?>, <?= $review_count ?>)">
                                    <span class="stars"><?= generate_stars($avg_rating) ?></span>
                                    <span class="review-count">(<?= $review_count ?> reviews)</span>
                                </div>

                                <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>

                                <div class="product-footer">
                                    <div class="product-price-stock">
                                        <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                        <div class="product-stock">Stock: <?= $product['quantity'] ?></div>
                                    </div>
                                    <div class="product-actions">
                                        
                                        <button class="favorite-btn" title="Add to Favorites" onclick="event.stopPropagation(); addToFavorites(event, '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', <?= floatval($product['price']) ?>, '<?= htmlspecialchars($product['image_url'], ENT_QUOTES) ?>')">
                                            <i class="bi bi-heart"></i>
                                        </button>
                                        <button class="add-cart-btn" onclick="event.stopPropagation(); addToCart(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', <?= floatval($product['price']) ?>, '<?= htmlspecialchars($product['image_url'], ENT_QUOTES) ?>')">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="modal fade product-modal" id="productModal" tabindex="-1">
         <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <img id="productModalImage" class="modal-image" src="" alt="Product">
                    <h3 id="productModalName"></h3>
                    <div class="modal-price" id="productModalPrice"></div>
                    <p class="modal-description" id="productModalDescription"></p>
                    <div class="modal-stock" id="productModalStock"></div>

                    <div class="modal-actions">
                        <input type="number" id="modalQuantity" value="1" min="1" max="100" class="form-control">
                        <button onclick="addToCartFromModal()">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                    </div>

                    <hr>
                    <div class="modal-reviews">
                        <h5 class="reviews-title">Customer Reviews</h5>
                        <div class="reviews-summary">
                            <span class="summary-stars" id="modalReviewStars"></span>
                            <span class="summary-text" id="modalReviewSummary"></span>
                        </div>

                        <div class="review-list" id="modalReviewList">
                            </div>

                        <div class="review-form-container">
                            <?php if ($isLoggedIn): ?>
                                <form action="submit_review.php" method="POST">
                                    <h5>Leave Your Review</h5>
                                    <div class="mb-3">
                                        <label for="rating" class="form-label">Your Rating:</label>
                                        <select name="rating" id="rating" class="form-select" required>
                                            <option value="" disabled selected>Select a rating</option>
                                            <option value="5">5 Stars (Excellent)</option>
                                            <option value="4">4 Stars (Good)</option>
                                            <option value="3">3 Stars (Average)</option>
                                            <option value="2">2 Stars (Poor)</option>
                                            <option value="1">1 Star (Terrible)</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="comment" class="form-label">Your Comment:</label>
                                        <textarea name="comment" id="comment" rows="3" class="form-control" placeholder="Tell us what you think..." required></textarea>
                                    </div>

                                    <input type="hidden" name="product_id" id="modalReviewProductId" value="">
                                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

                                    <button type="submit" name="submit_review" class="btn btn-danger">
                                        <i class="bi bi-send-fill"></i> Submit Review
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="login-prompt">
                                    Please <a href="../loginreg/logreg.php">log in</a> to leave a review.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../headfoot/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentProduct = null;

        function generateStarsJS(rating) {
            let stars = '';
            const full_stars = Math.floor(rating);
            const half_star = (rating - full_stars) >= 0.5;
            const empty_stars = 5 - full_stars - (half_star ? 1 : 0);

            for (let i = 0; i < full_stars; i++) {
                stars += '<i class="bi bi-star-fill"></i>';
            }
            if (half_star) {
                stars += '<i class="bi bi-star-half"></i>';
            }
            for (let i = 0; i < empty_stars; i++) {
                stars += '<i class="bi bi-star"></i>';
            }
            return stars;
        }

        function openProductModal(product, avg_rating, review_count) {
            currentProduct = product;
            document.getElementById('productModalTitle').textContent = product.name;
            document.getElementById('productModalName').textContent = product.name;
            document.getElementById('productModalImage').src = product.image_url;
            document.getElementById('productModalPrice').textContent = '₱' + parseFloat(product.price).toFixed(2);
            document.getElementById('productModalDescription').textContent = product.description;
            document.getElementById('productModalStock').textContent = 'Available: ' + product.quantity + ' pieces';
            document.getElementById('modalQuantity').value = 1;
            document.getElementById('modalQuantity').max = product.quantity;

            document.getElementById('modalReviewStars').innerHTML = generateStarsJS(avg_rating);
            document.getElementById('modalReviewSummary').textContent = `${avg_rating.toFixed(1)} out of 5 (${review_count} reviews)`;

            const reviewProductIdInput = document.getElementById('modalReviewProductId');
            if (reviewProductIdInput) {
                reviewProductIdInput.value = product.id;
            }

            const reviewList = document.getElementById('modalReviewList');
            reviewList.innerHTML = '<div class="text-center"><div class="spinner-border text-danger" role="status"><span class="visually-hidden">Loading...</span></div></div>';

            fetch(`get_reviews.php?product_id=${product.id}&t=${new Date().getTime()}`)
               .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                   })
               .then(reviews => {
                    reviewList.innerHTML = '';
                    if (reviews && Array.isArray(reviews) && reviews.length > 0) {
                        reviews.forEach(review => {
                            // Basic sanitization for display
                            const userName = review.user_name.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                            const comment = review.comment.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                            reviewList.innerHTML += `
                                <div class="review-item">
                                    <div class="review-header">
                                        <strong>${userName}</strong>
                                        <span class="review-stars">${generateStarsJS(review.rating)}</span>
                                    </div>
                                    <p class="review-comment">"${comment}"</p>
                                </div>
                            `;
                        });
                    } else if (reviews.error) {
                         reviewList.innerHTML = `<p class="text-center text-danger">${reviews.error}</p>`;
                    }
                    else {
                        reviewList.innerHTML = '<p class="text-center text-muted">No reviews yet for this product.</p>';
                    }
                })
               .catch(error => {
                    console.error('Error fetching or parsing reviews:', error);
                    reviewList.innerHTML = '<p class="text-center text-danger">Could not load reviews at this time.</p>';
                });

            new bootstrap.Modal(document.getElementById('productModal')).show();
        }

        function addToCartFromModal() {
            if (!currentProduct) return;
            const quantityInput = document.getElementById('modalQuantity');
            const quantity = parseInt(quantityInput.value) || 1;
            const maxQuantity = parseInt(quantityInput.max);

             if (quantity <= 0) {
                 alert(`Please enter a valid quantity greater than 0.`);
                 return;
             }
            if (quantity > maxQuantity) {
                 alert(`Cannot add more than ${maxQuantity} items (stock limit).`);
                 quantityInput.value = maxQuantity; // Reset to max
                 return;
            }
            addToCart(currentProduct.id, currentProduct.name, currentProduct.price, currentProduct.image_url, quantity);
            bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
        }

        function addToCart(id, name, price, image_url, quantity = 1) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../cart/add_to_cart.php';
            form.innerHTML = `
                <input type="hidden" name="id" value="${id}">
                <input type="hidden" name="name" value="${name}">
                <input type="hidden" name="price" value="${price}">
                <input type="hidden" name="image_url" value="${image_url}">
                <input type="hidden" name="quantity" value="${quantity}">
                <input type="hidden" name="add_to_cart" value="1">
                <input type="hidden" name="redirect" value="${window.location.href}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

document.addEventListener('DOMContentLoaded', function() {
    // 1. Check if the notification script told us to open a modal
    const modalProductId = sessionStorage.getItem('openProductModal');
    
    if (modalProductId) {
        console.log("Notification: Looking for product " + modalProductId);
        
        // 2. Find the product card on the page using the 'data-id' we added
        const productCard = document.querySelector(`.product-card[data-id="${modalProductId}"]`);
        
        if (productCard) {
            console.log("Notification: Found product card, clicking...");
            
            // 3. Trigger the 'click' event on the card to open its modal
            productCard.click();
            
            // 4. (Optional) Scroll to the card so the user sees it
            productCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // 5. (Optional) Highlight the card briefly
            productCard.style.transition = 'all 0.3s ease';
            productCard.style.border = '3px solid var(--primary-red)';
            productCard.style.boxShadow = '0 12px 30px rgba(220, 53, 69, 0.3)';
            setTimeout(() => {
                productCard.style.border = '3px solid transparent';
                productCard.style.boxShadow = '';
            }, 2500);

        } else {
            console.warn("Notification: Could not find product card for ID " + modalProductId);
        }
        
        // 6. Clear the item so it doesn't open again on page refresh
        sessionStorage.removeItem('openProductModal');
    }
});

        // --- ================== MODIFIED ================== ---
        // --- Add to Favorites Function ---
        function addToFavorites(event, productName, productPrice, productImage) {
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                // Call the modal function from header.php
                showLoginNotification(event); 
                return;
            }

            // This code below will only run if the user IS logged in
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../favorites/add_favorites.php'; 
            form.innerHTML = `
                <input type="hidden" name="product_name" value="${productName}">
                <input type="hidden" name="product_price" value="${productPrice}">
                <input type="hidden" name="product_image" value="${productImage}">
                <input type="hidden" name="redirect" value="${window.location.href}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        // --- END: Add to Favorites Function ---
        // --- ============================================== ---


        document.getElementById('sort-price-btn').addEventListener('click', function() {
            const sortOrder = document.getElementById('price-sort').value;
            document.querySelectorAll('.category-products').forEach(grid => {
                const products = Array.from(grid.children);
                products.sort((a, b) => {
                    const priceA = parseFloat(a.dataset.price);
                    const priceB = parseFloat(b.dataset.price);
                    return sortOrder === 'min-max' ? priceA - priceB : priceB - priceA;
                });
                grid.innerHTML = '';
                products.forEach(p => grid.appendChild(p));
            });
        });

        document.getElementById('product-search-btn').addEventListener('click', function() {
            const query = document.getElementById('product-search-input').value.trim().toLowerCase();

            if (!query) {
                resetSearch();
                return;
            }

            const allProducts = document.querySelectorAll('.product-card');
            let foundCount = 0;

            allProducts.forEach(card => {
                const productName = card.querySelector('.product-name').textContent.toLowerCase();
                const productDescription = card.querySelector('.product-description').textContent.toLowerCase();

                if (productName.includes(query) || productDescription.includes(query)) {
                    card.style.display = '';
                    foundCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.querySelectorAll('.category-section').forEach(section => {
                const visibleProducts = Array.from(section.querySelectorAll('.product-card')).filter(p => p.style.display !== 'none');
                if (visibleProducts.length === 0) {
                    section.style.display = 'none';
                } else {
                    section.style.display = '';
                }
            });

            if (foundCount === 0) {
                alert(`No products found matching "${query}"`);
                resetSearch();
            }
        });

        document.getElementById('product-search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('product-search-btn').click();
            }
        });

        function resetSearch() {
            document.querySelectorAll('.product-card').forEach(card => {
                card.style.display = '';
            });
            document.querySelectorAll('.category-section').forEach(section => {
                section.style.display = '';
            });
            document.getElementById('product-search-input').value = '';
        }

    </script>
</body>
</html>