<?php
include("../config.php");
require_once("../cms_helper.php");

$isLoggedin = isset($_SESSION['valid']);

// Fetch all homepage CMS content
$cms = getAllCMSContent($con, 'homepage');
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SioSio</title>
    <!-- Bootstrap 5.3.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Bootstrap Styles -->
    <link rel="stylesheet" href="../products/bootstrap-custom.css">
    <link rel="stylesheet" href="../products/custom.css">
    <!-- Original Custom CSS -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
</head>

<body>
    <?php include("../headfoot/header.php")   ?>
    <?php include("../chat/tawk_widget.php") ?>

    <!-- Main Content -->
    <main>
<section class="hero" style="background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)), url('<?= !empty($cms['hero_background']['content_value']) ? '../' . htmlspecialchars($cms['hero_background']['content_value']) : '../images/Homebg.jpg' ?>');">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <h2 class="hero-title">
                    <?php echoCMS($con, 'homepage', 'hero_title', 'The <span class="sio-highlight">medyo NO.1 <span class="sio-highlight">Sio</span>mai and <span class="sio-highlight">Sio</span>pao Brand</span>', false); ?>
                </h2>
                <p class="hero-subtitle">
                    <?php echoCMS($con, 'homepage', 'hero_subtitle', 'in the Philippines'); ?>
                </p>
                <p class="hero-tagline">
                    <?php echoCMS($con, 'homepage', 'hero_tagline', '<em><span class="sio-highlight">Sio</span>per Sarap, <span class="sio-highlight">Sio</span>per Affordable pa!</em>', false); ?>
                </p>
            </div>
            <div class="hero-bottom"></div>
        </section>
        <!-- Active Promotions Section -->
<?php
$stmt_promos = $con->prepare("SELECT * FROM vouchers WHERE status = 'active' AND start_date <= NOW() AND end_date >= NOW() ORDER BY discount_value DESC LIMIT 3");
$stmt_promos->execute();
$promos = $stmt_promos->get_result();
?>

<?php if ($promos->num_rows > 0): ?>
<section class="promo-section py-5" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);">
    <div class="container">
        <h2 class="text-center mb-4">
            <i class="bi bi-ticket-perforated"></i> Active Promotions
        </h2>
        <div class="row g-4">
            <?php while ($promo = $promos->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="card promo-card h-100 shadow-sm">
                    <div class="card-body text-center">
                        <div class="promo-badge mb-3">
                            <?php if ($promo['discount_type'] == 'percentage'): ?>
                                <h1 class="display-3 text-danger mb-0"><?= $promo['discount_value'] ?>%</h1>
                                <p class="text-muted">OFF</p>
                            <?php else: ?>
                                <h1 class="display-3 text-danger mb-0">₱<?= number_format($promo['discount_value'], 0) ?></h1>
                                <p class="text-muted">OFF</p>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title"><?= htmlspecialchars($promo['description']) ?></h5>
                        <div class="voucher-code my-3">
                            <code class="fs-5 fw-bold text-danger"><?= htmlspecialchars($promo['code']) ?></code>
                        </div>
                        <p class="text-muted small">
                            <?php if ($promo['applicable_to'] != 'all'): ?>
                                Valid for <?= ucfirst($promo['applicable_to']) ?> only<br>
                            <?php endif; ?>
                            Min. purchase: ₱<?= number_format($promo['min_purchase'], 2) ?><br>
                            Expires: <?= date('M d, Y', strtotime($promo['end_date'])) ?>
                        </p>
                        <a href="../products/product.php" class="btn btn-danger">Shop Now</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php $stmt_promos->close(); ?>

       <section id="siomai-section" class="py-5">
            <div class="container">
                <h2 class="section-title"><span class="sio-highlight">Sio</span>mai Flavors</h2>
                <div class="flavors-grid">
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/2182583656/photo/chinese-steamed-dumpling-or-shumai-in-japanese-language-meatball-dumpling-with-wanton-skin.jpg?s=612x612&w=0&k=20&c=0K7_ee0dwfAZhcZZajZRSv8uTifXZhG6LVmlKnSe-0U=" alt="Pork & Shrimp Siomai">
                        </div>
                        <h3 class="flavor-title">Pork <span class="sio-highlight">Sio</span>mai</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/1336438874/photo/delicious-dim-sum-home-made-chinese-dumplings-served-on-plate.jpg?s=612x612&w=0&k=20&c=11KB0bXoZeMrlzaHN2q9aZq8kqtdvp-d4Oggc2TF8M4=" alt="Chicken Siomai">
                        </div>
                        <h3 class="flavor-title">Chicken <span class="sio-highlight">Sio</span>mai</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/2189370578/photo/delicious-shumai-shumay-siomay-chicken-in-bowl-snack-menu.jpg?s=612x612&w=0&k=20&c=hD4kuZsiGIjgyUPq-seqv229pFE43CnS0Do3EH_2E_Y=" alt="Beef Siomai">
                        </div>
                        <h3 class="flavor-title">Beef <span class="sio-highlight">Sio</span>mai</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/1084916088/photo/close-up-cooking-homemade-shumai.jpg?s=612x612&w=0&k=20&c=M1RyWV62MACQffBC40UzZ_h-BsXOj4bkaMBrxnbMTzc=" alt="Tuna Siomai">
                        </div>
                        <h3 class="flavor-title">Tuna <span class="sio-highlight">Sio</span>mai</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/1330456626/photo/steamed-shark-fin-dumplings-served-with-chili-garlic-oil-and-calamansi.jpg?s=612x612&w=0&k=20&c=9Zi1JmbwvYtIlZJqZb6tHOVC21rS-IbwZXS-IeflE30=" alt="Shark's Fin Siomai">
                        </div>
                        <h3 class="flavor-title">Shark's Fin <span class="sio-highlight">Sio</span>mai</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/1221287744/photo/ground-pork-with-crab-stick-wrapped-in-nori.jpg?s=612x612&w=0&k=20&c=Rniq7tdyCqVZHpwngsbzOk1dG1u8pTEeUDE8arsfOUY=" alt="Japanese Siomai">
                        </div>
                        <h3 class="flavor-title">Japanese <span class="sio-highlight">Sio</span>mai</h3>
                    </div>
                </div>
            </div>
        </section>

        <section id="siopao-section" class="py-5 bg-light">
            <div class="container">
                <h2 class="section-title"><span class="sio-highlight">Sio</span>pao Flavors</h2>
                <div class="flavors-grid">
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/1163708923/photo/hong-kong-style-chicken-char-siew-in-classic-polo-bun-polo-bun-or-is-a-kind-of-crunchy-and.jpg?s=612x612&w=0&k=20&c=R9DC49-UsxYUPlImX6O47LQyafOu1Cp5rNxp3XifFNI=" alt="Asado Siopao">
                        </div>
                        <h3 class="flavor-title">Asado <span class="sio-highlight">Sio</span>pao</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/1184080523/photo/wanton-noodle-soup-and-siopao.jpg?s=612x612&w=0&k=20&c=oRJanjrTxICQfuzm9bXVPYkw9nKh74tcwjH1cVzXzN8=" alt="Bola-Bola Siopao">
                        </div>
                        <h3 class="flavor-title">Bola-Bola <span class="sio-highlight">Sio</span>pao</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTxSCl2zlIK85vMZ6nRYuWpqde6JnIxBUTe-w&s" alt="Choco Siopao">
                        </div>
                        <h3 class="flavor-title">Choco <span class="sio-highlight">Sio</span>pao</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/2161276374/photo/vivid-steamed-purple-ube-sweet-potato-dumplings.jpg?s=612x612&w=0&k=20&c=Mb2rl1JZPvG0d5v-_gSC7Mx50DNggFJiTEcoTayqB1Q=" alt="Ube Siopao">
                        </div>
                        <h3 class="flavor-title">Ube <span class="sio-highlight">Sio</span>pao</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/1172915611/photo/asian-steamed-bun-with-adzuki-red-bean-paste-filling-or-bakpao.jpg?s=612x612&w=0&k=20&c=hImY86ZyoR8y2FC17yLpkCA5amxrZDxCeuVokJnY5w0=" alt="Red Bean Siopao">
                        </div>
                        <h3 class="flavor-title">Red Bean <span class="sio-highlight">Sio</span>pao</h3>
                    </div>
                    <div class="flavor-item">
                        <div class="flavor-image">
                            <img src="https://media.istockphoto.com/id/957584318/photo/chinese-steamed-bun-and-orange-sweet-creamy-lava-on-chinese-pattern-dish.jpg?s=612x612&w=0&k=20&c=5CJuHZdTLVIlN5gq_jmer--RWri-TDliTtQoIvAc97M=" alt="Custard Siopao">
                        </div>
                        <h3 class="flavor-title">Custard <span class="sio-highlight">Sio</span>pao</h3>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <?php include("../headfoot/footer.php")   ?>

    <!-- Bootstrap 5.3.2 JavaScript Bundle -->

        <?php 
    if (isset($_SESSION['user_id'])) {
        include("../includes/setup_address_modal.php"); 
    }
    ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script src="script.js"></script>


    <?php include($_SERVER['DOCUMENT_ROOT'] . '/siosios/chat/chat_init.php'); ?>


</body>
</html>