<?php
session_start();
require __DIR__ . '/../loginreg/vendor/autoload.php';
include("../config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Get user's email
$stmt = $con->prepare("SELECT email, name FROM userss WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

switch ($action) {
    case 'upload_photo':
        if (!isset($_FILES['profile_photo'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit;
        }
        
        $file = $_FILES['profile_photo'];
        
        // Validate file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed']);
            exit;
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB']);
            exit;
        }
        
        // Create upload directory if not exists
        $upload_dir = __DIR__ . '/../uploads/profile_photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        $db_path = '../uploads/profile_photos/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old profile photo if exists
            $stmt = $con->prepare("SELECT profile_photo FROM userss WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $old_photo = $stmt->get_result()->fetch_assoc()['profile_photo'];
            $stmt->close();
            
            if ($old_photo && file_exists(__DIR__ . '/' . $old_photo)) {
                unlink(__DIR__ . '/' . $old_photo);
            }
            
            // Update database
            $stmt = $con->prepare("UPDATE userss SET profile_photo = ? WHERE user_id = ?");
            $stmt->bind_param("si", $db_path, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Profile photo updated successfully', 'photo_url' => $db_path]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update database']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        }
        break;
        
    case 'send_otp':
        $change_type = $_POST['change_type'] ?? '';
        
        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Delete any old unverified OTPs for this user and type
        $stmt = $con->prepare("DELETE FROM otp_verifications WHERE email = ? AND otp_type = 'profile_change' AND is_verified = 0");
        $stmt->bind_param("s", $user['email']);
        $stmt->execute();
        $stmt->close();
        
        // Store OTP in database
$stmt = $con->prepare("INSERT INTO otp_verifications (email, otp_code, otp_type, expires_at, created_at) VALUES (?, ?, 'profile_change', NOW() + INTERVAL 5 MINUTE, NOW())");
$stmt->bind_param("ss", $user['email'], $otp);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Store change data in session
            $_SESSION['pending_profile_change'] = [
                'type' => $change_type,
                'data' => $_POST,
                'otp_sent_at' => time()
            ];
            
            // Send OTP via email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'siosioretailstore@gmail.com';
                $mail->Password = 'hqlw sute xjea wcmo';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ]
                ];
                
                $mail->setFrom('siosioretailstore@gmail.com', 'SioSio Retail Store');
                $mail->addAddress($user['email'], $user['name']);
                $mail->isHTML(true);
                $mail->Subject = "Profile Change Verification - OTP Code";
                
                $change_type_text = [
                    'personal_info' => 'Personal Information',
                    'address' => 'Delivery Address',
                    'password' => 'Password'
                ];
                
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f8f9fa;'>
                        <div style='background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                            <div style='text-align: center; margin-bottom: 30px;'>
                                <h1 style='color: #dc3545; margin: 0;'>SioSio Retail Store</h1>
                                <p style='color: #666; margin-top: 10px;'>Profile Change Verification</p>
                            </div>
                            
                            <h2 style='color: #333; margin-bottom: 20px;'>Profile Change Request</h2>
                            
                            <p style='color: #555; line-height: 1.6;'>Hello <strong>{$user['name']}</strong>,</p>
                            
                            <p style='color: #555; line-height: 1.6;'>
                                You have requested to change your <strong>{$change_type_text[$change_type]}</strong>.
                            </p>
                            
                            <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                                <p style='margin: 0; color: #856404;'>
                                    <strong>⚠️ Security Notice:</strong> If you did not request this change, 
                                    please ignore this email and ensure your account is secure.
                                </p>
                            </div>
                            
                            <p style='color: #555; margin-top: 20px;'>Your verification code is:</p>
                            
                            <div style='background: #f8f9fa; padding: 25px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                                <div style='font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #dc3545; font-family: monospace;'>
                                    {$otp}
                                </div>
                            </div>
                            
                            <div style='background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 20px 0;'>
                                <p style='margin: 0; color: #0c5460;'>
                                    <strong>⏱️ Important:</strong> This code will expire in <strong>5 minutes</strong>.
                                </p>
                            </div>
                            
                            <p style='color: #555; line-height: 1.6; margin-top: 20px;'>
                                Enter this code on the verification page to complete your profile update.
                            </p>
                            
                            <hr style='margin: 30px 0; border: none; border-top: 1px solid #dee2e6;'>
                            
                            <p style='color: #999; font-size: 12px; text-align: center; margin: 0;'>
                                SioSio Retail Store - Your Trusted Siomai & Siopao Partner<br>
                                This is an automated message, please do not reply to this email.
                            </p>
                        </div>
                    </div>
                ";
                
                $mail->send();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'OTP sent to your email address. Please check your inbox.'
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send OTP email: ' . $mail->ErrorInfo
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate OTP. Please try again.'
            ]);
        }
        break;
        
    case 'verify_and_update':
        $otp_code = trim($_POST['otp_code'] ?? '');
        
        if (empty($otp_code) || strlen($otp_code) !== 6 || !ctype_digit($otp_code)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please enter a valid 6-digit OTP code'
            ]);
            exit;
        }
        
        // Verify OTP with proper validation
        $stmt = $con->prepare("
            SELECT * FROM otp_verifications 
            WHERE email = ? 
            AND otp_code = ? 
            AND otp_type = 'profile_change' 
            AND is_verified = 0 
            AND expires_at > NOW() 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $user['email'], $otp_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $otp_record = $result->fetch_assoc();
        $stmt->close();
        
        if (!$otp_record) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired OTP code. Please request a new one.'
            ]);
            exit;
        }
        
        // Mark OTP as verified
        $stmt = $con->prepare("UPDATE otp_verifications SET is_verified = 1, verified_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $otp_record['id']);
        $stmt->execute();
        $stmt->close();
        
        // Get pending change data
        $pending_change = $_SESSION['pending_profile_change'] ?? null;
        
        if (!$pending_change) {
            echo json_encode([
                'success' => false,
                'message' => 'No pending changes found. Please try again.'
            ]);
            exit;
        }
        
        // Process the change based on type
        $change_type = $pending_change['type'];
        $data = $pending_change['data'];
        
        try {
            switch ($change_type) {
                case 'personal_info':
                    $name = trim($data['name']);
                    $username = trim($data['username']);
                    $email_new = trim($data['email']);
                    $contact_num = trim($data['contact_num']);
                    
                    // Validate contact number
                    if (!preg_match('/^(09|\+639)\d{9}$/', $contact_num)) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Invalid Philippine mobile number format'
                        ]);
                        exit;
                    }
                    
                    // Check if username or email already exists (excluding current user)
                    $stmt = $con->prepare("SELECT user_id FROM userss WHERE (username = ? OR email = ?) AND user_id != ?");
                    $stmt->bind_param("ssi", $username, $email_new, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Username or email already in use by another account'
                        ]);
                        $stmt->close();
                        exit;
                    }
                    $stmt->close();
                    
                    // Update personal info
                    $stmt = $con->prepare("UPDATE userss SET name = ?, username = ?, email = ?, contact_num = ? WHERE user_id = ?");
                    $stmt->bind_param("ssssi", $name, $username, $email_new, $contact_num, $user_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['user_name'] = $name;
                        echo json_encode([
                            'success' => true,
                            'message' => 'Personal information updated successfully!'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to update personal information'
                        ]);
                    }
                    $stmt->close();
                    break;
                    
                case 'address':
                    $address_line1 = trim($data['address_line1']);
                    $address_line2 = trim($data['address_line2'] ?? '');
                    $barangay = trim($data['barangay']);
                    $city = trim($data['city']);
                    $postal_code = trim($data['postal_code']);
                    
                    if (empty($address_line1) || empty($barangay) || empty($city) || empty($postal_code)) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'All required address fields must be filled'
                        ]);
                        exit;
                    }
                    
                    // Update address
                    $stmt = $con->prepare("UPDATE userss SET address_line1 = ?, address_line2 = ?, barangay = ?, city = ?, postal_code = ? WHERE user_id = ?");
                    $stmt->bind_param("sssssi", $address_line1, $address_line2, $barangay, $city, $postal_code, $user_id);
                    
                    if ($stmt->execute()) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Delivery address updated successfully!'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to update delivery address'
                        ]);
                    }
                    $stmt->close();
                    break;
                    
                case 'password':
                    $current_password = $data['current_password'];
                    $new_password = $data['new_password'];
                    
                    // Verify current password
                    $stmt = $con->prepare("SELECT password FROM userss WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user_data = $result->fetch_assoc();
                    $stmt->close();
                    
                    if (!password_verify($current_password, $user_data['password'])) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Current password is incorrect'
                        ]);
                        exit;
                    }
                    
                    // Validate new password
                    $password_errors = [];
                    if (strlen($new_password) < 8) {
                        $password_errors[] = "Password must be at least 8 characters";
                    }
                    if (!preg_match('/[A-Z]/', $new_password)) {
                        $password_errors[] = "Must contain uppercase letter";
                    }
                    if (!preg_match('/[a-z]/', $new_password)) {
                        $password_errors[] = "Must contain lowercase letter";
                    }
                    if (!preg_match('/[0-9]/', $new_password)) {
                        $password_errors[] = "Must contain number";
                    }
                    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
                        $password_errors[] = "Must contain special character";
                    }
                    
                    if (!empty($password_errors)) {
                        echo json_encode([
                            'success' => false,
                            'message' => implode(', ', $password_errors)
                        ]);
                        exit;
                    }
                    
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $con->prepare("UPDATE userss SET password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Password updated successfully! Please use your new password for next login.'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Failed to update password'
                        ]);
                    }
                    $stmt->close();
                    break;
                    
                default:
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid change type'
                    ]);
            }
            
            // Clear pending change from session
            unset($_SESSION['pending_profile_change']);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}
?>