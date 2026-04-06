<?php
include("../config.php");
require_once("../cms_helper.php");

$isLoggedin = isset($_SESSION['valid']);

// Auto-fill for logged-in users
$prefill_name = '';
$prefill_email = '';
$prefill_phone = '';

if ($isLoggedin) {
    $user_id = $_SESSION['user_id'];
    $stmt = $con->prepare("SELECT name, email, contact_num FROM userss WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $prefill_name = $row['name'];
        $prefill_email = $row['email'];
        $prefill_phone = $row['contact_num'];
    }
    $stmt->close();
}

// Fetch all contact page CMS content
$cms = getAllCMSContent($con, 'contact');

// Parse address with line breaks
$address_lines = isset($cms['address']) ? explode("\n", $cms['address']) : ['123 Filipino Street, Makati City', 'Metro Manila, Philippines 1234'];
$business_hours = isset($cms['business_hours']) ? explode("\n", $cms['business_hours']) : ['Monday - Sunday', '8:00 AM - 8:00 PM'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - SioSio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../products/bootstrap-custom.css">
    <link rel="stylesheet" href="../products/custom.css">
    <link rel="stylesheet" href="contact.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Joti+One&display=swap" rel="stylesheet">
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script type="text/javascript">
        (function(){
            emailjs.init("YOUR_PUBLIC_KEY"); // Replace with your EmailJS Public Key
        })();
    </script>
</head>

<body>
    <?php include("../headfoot/header.php") ?>

    <header class="page-header text-center text-white d-flex align-items-center justify-content-center">
        <div class="container">
            <h1 class="page-title display-4 fw-bold"><?= $cms['page_title'] ?? 'Get In <span class="sio-highlight">Touch</span>' ?></h1>
            <p class="page-subtitle lead"><?= htmlspecialchars($cms['page_subtitle'] ?? 'We are here to help! Reach out to us with any questions or feedback.') ?></p>
        </div>
    </header>

    <section class="contact-section py-5">
        <div class="container">
            
            <div id="alert-placeholder"></div>

            <div class="row g-5">
                <div class="col-lg-5">
                    <div class="contact-info p-4 shadow-lg h-100">
                        <h3 class="section-title mb-4">Contact Information</h3>
                        
                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div class="contact-text">
                                <h5 class="mb-1">Call Us</h5>
                                <a href="tel:<?= htmlspecialchars($cms['phone'] ?? '') ?>" class="text-decoration-none"><?= htmlspecialchars($cms['phone'] ?? '(02) 8-123-4567') ?></a>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div class="contact-text">
                                <h5 class="mb-1">Email Us</h5>
                                <a href="mailto:<?= htmlspecialchars($cms['email'] ?? '') ?>" class="text-decoration-none"><?= htmlspecialchars($cms['email'] ?? 'hello@siosio.ph') ?></a>
                            </div>
                        </div>

                        <div class="contact-item">
                            <div class="contact-icon">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div class="contact-text">
                                <h5 class="mb-1">Visit Us</h5>
                                <?php foreach ($address_lines as $line): ?>
                                    <p class="mb-0"><?= htmlspecialchars(trim($line)) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="contact-item border-0">
                            <div class="contact-icon">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div class="contact-text">
                                <h5 class="mb-1">Business Hours</h5>
                                <?php foreach ($business_hours as $line): ?>
                                    <p class="mb-0"><?= htmlspecialchars(trim($line)) ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-form p-4 shadow-lg h-100">
                        <h3 class="section-title mb-4">Send Us a Message</h3>
                        <form id="contact-form" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($prefill_name) ?>" required>
                                    <div class="invalid-feedback">Please enter your full name.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($prefill_email) ?>" required>
                                    <div class="invalid-feedback">Please enter a valid email.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($prefill_phone) ?>" required>
                                    <div class="invalid-feedback">Please enter your phone number.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="" disabled selected>Choose...</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Order Follow-up">Order Follow-up</option>
                                        <option value="Bulk Order">Bulk Order</option>
                                        <option value="Feedback/Complaint">Feedback/Complaint</option>
                                        <option value="Others">Others</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a subject.</div>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                    <div class="invalid-feedback">Please enter your message.</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-danger btn-lg w-100" id="submit-btn">
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" id="btn-spinner"></span>
                                <span id="btn-text">Send Message</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="faq-section py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center"><?= $cms['faq_title'] ?? 'Frequently Asked <span class="sio-highlight">Questions</span>' ?></h2>
            <div class="accordion accordion-flush" id="faqAccordion">
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne">
                            <?= htmlspecialchars($cms['faq1_question'] ?? 'What are your best-sellers?') ?>
                        </button>
                    </h2>
                    <div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?= $cms['faq1_answer'] ?? 'Our absolute best-sellers are the classic <strong>Pork Siomai</strong> and our <strong>Asado Siopao</strong>. You can\'t go wrong with these!' ?>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo">
                            <?= htmlspecialchars($cms['faq2_question'] ?? 'Where are you located?') ?>
                        </button>
                    </h2>
                    <div id="flush-collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?= $cms['faq2_answer'] ?? 'We are located at: <br>123 SioSio Building<br>Makati City, Metro Manila<br>Philippines 1234. <br>We also have numerous food carts across the metro. Visit our "Locations" page for a full list!' ?>
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseThree">
                            <?= htmlspecialchars($cms['faq3_question'] ?? 'Do you accept bulk orders?') ?>
                        </button>
                    </h2>
                    <div id="flush-collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?= $cms['faq3_answer'] ?? 'Yes, we do! For bulk orders for parties and events, please contact us at least 3-5 business days in advance. You can email us at events@siosio.ph or call our main hotline.' ?>
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header" id="flush-headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseFour">
                            <?= htmlspecialchars($cms['faq4_question'] ?? 'What is your return policy?') ?>
                        </button>
                    </h2>
                    <div id="flush-collapseFour" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <?= $cms['faq4_answer'] ?? 'Due to the perishable nature of our food products, we do not accept returns. However, if you are unsatisfied with your order or if there was an error, please contact us immediately or message our live chat support so we can make it right.' ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <?php include("../headfoot/footer.php") ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        (function () {
            'use strict';

            var alertPlaceholder = document.getElementById('alert-placeholder');
            function showAlert(message, type) {
                var wrapper = document.createElement('div');
                wrapper.innerHTML = [
                    '<div class="alert alert-' + type + ' alert-dismissible" role="alert">',
                    '   <div>' + message + '</div>',
                    '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
                    '</div>'
                ].join('');
                alertPlaceholder.append(wrapper);
                
                // Auto-dismiss
                setTimeout(function() {
                    if (wrapper) {
                        var bsAlert = new bootstrap.Alert(wrapper.firstChild);
                        if (bsAlert) {
                            bsAlert.close();
                        }
                    }
                }, 5000);
            }

            var form = document.getElementById('contact-form');
            var submitBtn = document.getElementById('submit-btn');
            var btnText = document.getElementById('btn-text');
            var btnSpinner = document.getElementById('btn-spinner');

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (form.checkValidity()) {
                    submitBtn.disabled = true;
                    btnText.textContent = 'Sending...';
                    btnSpinner.classList.remove('d-none');
                    
                    var templateParams = {
                        name: document.getElementById('name').value,
                        email: document.getElementById('email').value,
                        phone: document.getElementById('phone').value,
                        subject: document.getElementById('subject').options[document.getElementById('subject').selectedIndex].text,
                        message: document.getElementById('message').value,
                        to_email: '<?= htmlspecialchars($cms['email'] ?? 'hello@siosio.ph') ?>' // Use CMS email
                    };

                    // NOTE: Replace 'YOUR_SERVICE_ID' and 'YOUR_TEMPLATE_ID' with your EmailJS details
                    emailjs.send('YOUR_SERVICE_ID', 'YOUR_TEMPLATE_ID', templateParams)
                        .then(function(response) {
                            console.log('SUCCESS!', response.status, response.text);
                            showAlert('<strong>Thank you!</strong> Your message has been sent successfully. We\'ll get back to you soon!', 'success');
                            form.reset();
                            form.classList.remove('was-validated');
                        }, function(error) {
                            console.log('FAILED...', error);
                            showAlert('<strong>Error:</strong> Failed to send message. Please try again or use the live chat.', 'danger');
                        })
                        .finally(function() {
                            submitBtn.disabled = false;
                            btnText.textContent = 'Send Message';
                            btnSpinner.classList.add('d-none');
                        });
                } else {
                    form.classList.add('was-validated');
                }
            });
        })();
    </script>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/siosios/chat/chat_init.php'); ?>
</body>
</html>