<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
include("../config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Registration Logic with Enhanced Validation    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $errors = [];
    
    // Sanitize and validate inputs
    $fname = trim($_POST['fname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_num = trim($_POST['contact_num'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate First Name
    if (empty($fname)) {
        $errors[] = "First name is required";
    } elseif (strlen($fname) < 2 || strlen($fname) > 50) {
        $errors[] = "First name must be between 2-50 characters";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $fname)) {
        $errors[] = "First name can only contain letters, spaces, hyphens and apostrophes";
    }
    
    // Validate Last Name
    if (empty($lname)) {
        $errors[] = "Last name is required";
    } elseif (strlen($lname) < 2 || strlen($lname) > 50) {
        $errors[] = "Last name must be between 2-50 characters";
    } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $lname)) {
        $errors[] = "Last name can only contain letters, spaces, hyphens and apostrophes";
    }
    
    // Validate Middle Name (optional but if provided, check format)
    if (!empty($mname)) {
        if (strlen($mname) > 50) {
            $errors[] = "Middle name must not exceed 50 characters";
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $mname)) {
            $errors[] = "Middle name can only contain letters, spaces, hyphens and apostrophes";
        }
    }
    
    // Validate Username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = "Username must be between 3-30 characters";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $errors[] = "Username can only contain letters, numbers and underscores";
    }
    
    // Validate Email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email must not exceed 100 characters";
    }
    
    // Validate Contact Number (Philippine format)
    if (!empty($contact_num)) {
        if (!preg_match("/^(09|\+639)\d{9}$/", $contact_num)) {
            $errors[] = "Invalid contact number format. Use format: 09XXXXXXXXX or +639XXXXXXXXX";
        }
    }
    
    // Validate Password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    // Validate Password Confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If there are validation errors, display them
    if (!empty($errors)) {
        echo "<div class='error-popup'><div class='error-popup-content'>";
        echo "<h3>Registration Failed</h3>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div></div>";
    } else {
        // All validations passed, proceed with registration
        $fullname = preg_replace('/\s+/', ' ', trim("$fname $mname $lname"));

        // Check if username or email already exists
        $stmt_check = $con->prepare("SELECT user_id FROM userss WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            echo "<div class='error-popup'><div class='error-popup-content'>
                    <h3>Registration Failed</h3>
                    <p>This username or email is already registered.</p>
                  </div></div>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $otp = rand(100000, 999999);
            $expiresAt = date("Y-m-d H:i:s", strtotime("+6 minutes"));

            $_SESSION['pending_user'] = [
                'name' => $fullname,
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'contact_num' => $contact_num
            ];

            // Delete old OTP codes for this email
            $stmt_delete = $con->prepare("DELETE FROM otp_verifications WHERE email = ? AND otp_type = 'registration'");
            $stmt_delete->bind_param("s", $email);
            $stmt_delete->execute();
            $stmt_delete->close();

            $stmt = $con->prepare("INSERT INTO otp_verifications (email, otp_code, otp_type, expires_at) VALUES (?, ?, 'registration', ?)");
            $stmt->bind_param("sss", $email, $otp, $expiresAt);
            $stmt->execute();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'siosioretailstore@gmail.com';
                $mail->Password   = 'hqlw sute xjea wcmo';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ]
                ];

                $mail->setFrom('siosioretailstore@gmail.com', 'SioSio Retail Store');
                $mail->addAddress($email, $fullname);
                $mail->isHTML(true);
                $mail->Subject = "Your Registration OTP Code";
                $mail->Body    = "Hello $fullname,<br><br>Your OTP code is: <b>$otp</b><br><br>Valid for 6 minutes.";
                $mail->send();

                header("Location: verify-otp.php?email=" . urlencode($email));
                exit;

            } catch (Exception $e) {
                echo "<div class='error-popup'><div class='error-popup-content'>
                        <h3>Email Failed</h3>
                        <p>Mailer Error: {$mail->ErrorInfo}</p>
                      </div></div>";
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login / Register</title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
<?php include("../headfoot/header.php") ?>
<section class="auth-section">
  <div class="auth-container">

    <div class="form-toggle">
      <button class="toggle-btn active" onclick="showForm('login')">Login</button>
      <button class="toggle-btn" onclick="showForm('register')">Register</button>
    </div>

    <!-- LOGIN FORM -->
    <form class="auth-form" id="login-form" onsubmit="handleLogin(event)">
      <h2>Login</h2>
      <div class="form-group">
          <label>Email or Username</label>
          <input type="text" name="login_identifier" id="login_identifier" required 
                 maxlength="100" pattern="[a-zA-Z0-9@._-]+" 
                 title="Only letters, numbers, @, ., _, - allowed">
      </div>
      <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" id="login_password" required minlength="6">
      </div>
      <div class="forgot-password"><a href="forgot-password.php">Forgot Password?</a></div>
      <button type="submit" class="auth-btn">Login</button>
      <div id="login-error" class="error-message" style="display:none;"></div>
    </form>

    <!-- REGISTER FORM -->
    <form class="auth-form hidden" id="register-form" method="POST">
      <h2>Register</h2>
      <div class="form-group">
        <label>First Name *</label>
        <input type="text" name="fname" required maxlength="50" 
               pattern="[a-zA-Z\s'-]+" 
               title="Only letters, spaces, hyphens and apostrophes allowed">
      </div>
      <div class="form-group">
        <label>Middle Name</label>
        <input type="text" name="mname" maxlength="50" 
               pattern="[a-zA-Z\s'-]*" 
               title="Only letters, spaces, hyphens and apostrophes allowed">
      </div>
      <div class="form-group">
        <label>Last Name *</label>
        <input type="text" name="lname" required maxlength="50" 
               pattern="[a-zA-Z\s'-]+" 
               title="Only letters, spaces, hyphens and apostrophes allowed">
      </div>
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" required minlength="3" maxlength="30" 
               pattern="[a-zA-Z0-9_]+" 
               title="3-30 characters, only letters, numbers and underscores">
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" required maxlength="100">
      </div>
      <div class="form-group">
        <label>Contact Number</label>
        <input type="tel" name="contact_num" placeholder="09XXXXXXXXX" 
               pattern="(09|\+639)\d{9}" 
               title="Format: 09XXXXXXXXX or +639XXXXXXXXX">
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input type="password" id="password" name="password" required minlength="8" 
               title="At least 8 characters with uppercase, lowercase, number and special character">
        <small class="text-muted">Must contain: uppercase, lowercase, number, special character</small>
      </div>
      <div class="form-group">
        <label>Re-enter Password *</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
      </div>
      <button type="submit" name="register" class="auth-btn">Register</button>
    </form>

  </div>
</section>

<?php include("../headfoot/footer.php") ?>

<style>
  .error-message {
    color: #dc3545;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 12px;
    margin-top: 12px;
    text-align: center;
  }
  
  .success-message {
    color: #155724;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    padding: 12px;
    margin-top: 12px;
    text-align: center;
  }
  
  .text-muted {
    font-size: 0.85rem;
    color: #6c757d;
  }
</style>

<script>
  // Handle Login with Attempt Tracking
  async function handleLogin(event) {
    event.preventDefault();
    
    const loginIdentifier = document.getElementById('login_identifier').value.trim();
    const loginPassword = document.getElementById('login_password').value;
    const errorDiv = document.getElementById('login-error');

    // Clear any existing timer
    if (window.loginErrorTimer) {
        clearTimeout(window.loginErrorTimer);
    }
    
    // Basic validation
    if (!loginIdentifier || !loginPassword) {
      errorDiv.style.display = 'block';
      errorDiv.textContent = '⚠️ Please enter both username/email and password';
      window.loginErrorTimer = setTimeout(() => { errorDiv.style.display = 'none'; }, 5000); // 5 seconds
      return;
    }
    
    try {
      const response = await fetch('track_login_attempt.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
          'login_identifier': loginIdentifier,
          'password': loginPassword
        })
      });
      
      const data = await response.json();
      
      if (data.locked) {
        errorDiv.style.display = 'block';
        errorDiv.textContent = '🔒 ' + data.message;
        window.loginErrorTimer = setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
        return;
      }
      
      if (data.suspended) {
        errorDiv.style.display = 'block';
        errorDiv.textContent = '⛔ ' + data.message;
        window.loginErrorTimer = setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
        return;
      }
      
      if (data.success) {
        errorDiv.style.display = 'none';
        window.location.href = data.redirect;
      } else {
        errorDiv.style.display = 'block';
        errorDiv.textContent = '❌ ' + data.message;
        window.loginErrorTimer = setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
      }
      
    } catch (error) {
      errorDiv.style.display = 'block';
      errorDiv.textContent = '⚠️ Error: ' + error.message;
      window.loginErrorTimer = setTimeout(() => { errorDiv.style.display = 'none'; }, 5000);
    }
  }

  // Toggle between login and register forms
  function showForm(type) {
    document.getElementById('login-form').classList.add('hidden');
    document.getElementById('register-form').classList.add('hidden');
    document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
    
    if (type === 'login') {
      document.getElementById('login-form').classList.remove('hidden');
      document.querySelector('.form-toggle button:first-child').classList.add('active');
    } else {
      document.getElementById('register-form').classList.remove('hidden');
      document.querySelector('.form-toggle button:last-child').classList.add('active');
    }
  }

  // Password confirmation validation
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirm_password');

  if (passwordInput && confirmPasswordInput) {
    const checkPasswords = () => {
      if (passwordInput.value !== confirmPasswordInput.value) {
        confirmPasswordInput.setCustomValidity('Passwords do not match.');
      } else {
        confirmPasswordInput.setCustomValidity('');
      }
    };
    passwordInput.addEventListener('input', checkPasswords);
    confirmPasswordInput.addEventListener('input', checkPasswords);
  }

// Admin redirect on 25 hovers & Popup Handlers
  let hoverCount = 0;
  document.addEventListener('DOMContentLoaded', function() {
    
    // Admin redirect logic
    const loginBtn = document.querySelector('#login-form .auth-btn');
    if(loginBtn) {
      loginBtn.addEventListener('mouseenter', function() {
        hoverCount++;
        if (hoverCount >= 25) {
          window.location.href = '../admin/admin_login.php';
        }
      });
    }

    // --- NEW: Auto-hide Registration Popups ---
    const errorPopups = document.querySelectorAll('.error-popup');
s     
    errorPopups.forEach(popup => {
        // Auto-hide after 7 seconds (longer for lists of errors)
        setTimeout(() => {
          if (popup) {
            popup.style.display = 'none';
          }
        }, 7000); // 7 seconds
    });
    // --- END OF NEW BLOCK ---

s });
</script>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/siosios/chat/chat_init.php'); ?>
</body>
</html>