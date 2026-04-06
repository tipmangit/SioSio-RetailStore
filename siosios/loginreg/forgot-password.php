<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
include("../config.php");

// Reset session if the page is freshly visited
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['reset_email'], $_SESSION['reset_verified']);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$statusMessage = "";

// ==========================
// Step 1: Request OTP
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $email = trim($_POST['email']);

    $stmt = $con->prepare("SELECT user_id, name FROM userss WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();
        $otp = rand(100000, 999999);

        // --- FIXED: Store OTP in the database, not the session ---
        $stmt_otp = $con->prepare("INSERT INTO otp_verifications (email, otp_code, otp_type, expires_at) VALUES (?, ?, 'password_reset', DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
        $stmt_otp->bind_param("ss", $email, $otp);
        $stmt_otp->execute();

        $_SESSION['reset_email'] = $email; // Store email to know who is resetting
        sendOtpEmail($email, $row['name'], $otp);
        $statusMessage = "<div class='success-popup'><div class='success-popup-content'><h3>OTP Sent</h3><p>An OTP has been sent to {$email}.</p></div></div>";
    } else {
        $statusMessage = "<div class='error-popup'><div class='error-popup-content'><h3>User Not Found</h3><p>No account is registered with this email.</p></div></div>";
    }
}

// ==========================
// Step 2: Verify OTP
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $enteredOtp = trim($_POST['otp']);
    $email = $_SESSION['reset_email'] ?? '';

    // --- FIXED: Verify OTP against the database ---
    $stmt = $con->prepare("SELECT id FROM otp_verifications WHERE email = ? AND otp_code = ? AND otp_type = 'password_reset' AND is_verified = 0 AND expires_at > NOW() LIMIT 1");
    $stmt->bind_param("ss", $email, $enteredOtp);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $otpRow = $res->fetch_assoc();
        $con->query("UPDATE otp_verifications SET is_verified = 1 WHERE id = " . $otpRow['id']);
        $_SESSION['reset_verified'] = true;
        $statusMessage = "<div class='success-popup'><div class='success-popup-content'><h3>OTP Verified</h3><p>You may now set a new password.</p></div></div>";
    } else {
        $statusMessage = "<div class='error-popup'><div class='error-popup-content'><h3>Invalid or Expired OTP</h3><p>Please try again.</p></div></div>";
    }
}

// ==========================
// Step 3: Reset Password
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (isset($_SESSION['reset_verified']) && $_SESSION['reset_verified'] === true && isset($_SESSION['reset_email'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // --- NEW: Add password confirmation check ---
        if ($new_password !== $confirm_password) {
            $statusMessage = "<div class='error-popup'><div class='error-popup-content'><h3>Error</h3><p>Passwords do not match.</p></div></div>";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $email = $_SESSION['reset_email'];

            $stmt = $con->prepare("UPDATE userss SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            $stmt->execute();

            unset($_SESSION['reset_email'], $_SESSION['reset_verified']);

            $statusMessage = "<div class='success-popup'><div class='success-popup-content'><h3>Password Reset</h3><p>Your password has been updated successfully.</p><a href='logreg.php' class='success-link'>Go to Login</a></div></div>";
        }
    }
}


function sendOtpEmail($email, $name, $otp) {
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
        $mail->Subject = "Your Password Reset OTP Code";
        $mail->Body    = "Hello $name,<br><br>Your OTP code for password reset is: <b>$otp</b>";
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error (forgot-password): " . $mail->ErrorInfo);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>

<section class="auth-section">
  <div class="auth-container">

    <?php if (!isset($_SESSION['reset_email'])): ?>
    <form class="auth-form" method="POST">
      <h2>Forgot Password</h2>
      <p>Enter your registered email address to receive an OTP.</p>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" required>
      </div>
      <button type="submit" name="request_otp" class="auth-btn">Send OTP</button>
    </form>
    <?php endif; ?>

    <?php if (isset($_SESSION['reset_email']) && !isset($_SESSION['reset_verified'])): ?>
    <form class="auth-form" method="POST">
      <h2>Verify OTP</h2>
      <p>We sent an OTP to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>.</p>
      <div class="form-group">
        <label>OTP</label>
        <input type="text" name="otp" maxlength="6" required>
      </div>
      <button type="submit" name="verify_otp" class="auth-btn">Verify OTP</button>
    </form>
    <?php endif; ?>

    <?php if (isset($_SESSION['reset_verified']) && $_SESSION['reset_verified'] === true): ?>
    <form class="auth-form" method="POST">
      <h2>Reset Password</h2>
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" minlength="6" required>
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" required>
      </div>
      <button type="submit" name="reset_password" class="auth-btn">Update Password</button>
    </form>
    <?php endif; ?>

    <div class="back-to-login">
      <a href="logreg.php">Back to Login</a>
    </div>

  </div>
</section>

<?php echo $statusMessage; ?>
<?php include($_SERVER['DOCUMENT_ROOT'] . '/siosios/chat/chat_init.php'); ?>
</body>
</html>