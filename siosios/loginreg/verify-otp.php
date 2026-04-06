<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
include("../config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$statusMessage = "";

// If no pending registration, redirect
if (!isset($_SESSION['pending_user'])) {
    header("Location: logreg.php");
    exit;
}

$pending = $_SESSION['pending_user']; 

// ----- Verify OTP -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $enteredOtp = trim($_POST['otp']);
    $email = $pending['email'];

    // Check OTP in DB
    $stmt = $con->prepare("SELECT id FROM otp_verifications 
                           WHERE email = ? AND otp_code = ? 
                           AND otp_type = 'registration' 
                           AND is_verified = 0 
                           AND expires_at > NOW()
                           LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("ss", $email, $enteredOtp);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        die("SQL prepare failed (check OTP): " . $con->error);
    }

    if ($res->num_rows === 1) {
        // Mark OTP as used
        $otpRow = $res->fetch_assoc();
        $con->query("UPDATE otp_verifications 
                     SET is_verified = 1, verified_at = NOW() 
                     WHERE id = {$otpRow['id']}");

        // --- FIXED: Insert user with all correct details from session ---
        $name = $pending['name'];
        $username = $pending['username'];
        $password = $pending['password'];
        $contact_num = $pending['contact_num'];

        $stmt_insert = $con->prepare("INSERT INTO userss (name, username, email, password, contact_num, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt_insert) {
            die("SQL prepare failed (insert user): " . $con->error);
        }
        $stmt_insert->bind_param("sssss", $name, $username, $email, $password, $contact_num);
        $stmt_insert->execute();

        unset($_SESSION['pending_user']);

        $statusMessage = "<div class='success-popup'><div class='success-popup-content'>
                            <h3>OTP Verified!</h3>
                            <p>Your account has been created successfully.</p>
                            <a href='logreg.php' class='success-link'>Go to Login</a>
                          </div></div>";
    } else {
        $statusMessage = "<div class='error-popup'><div class='error-popup-content'>
                            <h3>Invalid or Expired OTP</h3>
                            <p>Please request a new OTP.</p>
                          </div></div>";
    }
}

// ----- Resend OTP -----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    $newOtp = rand(100000, 999999);
    $email = $pending['email'];
    $name = $pending['name'];

    // Insert new OTP into the database
    $stmt = $con->prepare("INSERT INTO otp_verifications (email, otp_code, otp_type, expires_at) 
                           VALUES (?, ?, 'registration', DATE_ADD(NOW(), INTERVAL 6 MINUTE))");
    $stmt->bind_param("ss", $email, $newOtp);
    $stmt->execute();

    // Send the new OTP email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'siosioretailstore@gmail.com';
        $mail->Password   = 'hqlw sute xjea wcmo';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];

        $mail->setFrom('siosioretailstore@gmail.com', 'SioSio Retail Store');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Your New SioSio OTP Code";
        $mail->Body    = "Hello {$name},<br><br>Your new OTP code is: <b>{$newOtp}</b><br><br>Please use this code within 6 minutes.";

        $mail->send();

        $statusMessage = "<div class='success-popup'><div class='success-popup-content'>
                            <h3>New OTP Sent</h3>
                            <p>We sent a new OTP to {$email}. Please check your inbox.</p>
                          </div></div>";
    } catch (Exception $e) {
        $statusMessage = "<div class='error-popup'><div class='error-popup-content'>
                            <h3>Resend Failed</h3>
                            <p>Mailer Error: {$mail->ErrorInfo}</p>
                          </div></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Verify OTP</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="login.css">
</head>
<body>
<section class="auth-section">
  <div class="auth-container">
    <form class="auth-form" method="POST">
      <h2>Verify OTP</h2>
      <p>We sent a 6-digit OTP to <strong><?= htmlspecialchars($pending['email']); ?></strong>.  
         Please enter it below to complete your registration.</p>

      <div class="form-group">
        <label for="otp">Enter OTP</label>
        <input type="text" id="otp" name="otp" maxlength="6" required>
      </div>

      <button type="submit" class="auth-btn">Verify OTP</button>
    </form>

    <form method="POST" style="margin-top:15px; text-align:center;">
      <input type="hidden" name="resend" value="1">
      <button type="submit" class="resend-btn">Resend OTP</button>
    </form>

    <div class="back-to-login">
      <a href="logreg.php">Back to Login</a>
    </div>
  </div>
</section>

<?= $statusMessage ?>
</body>
</html>