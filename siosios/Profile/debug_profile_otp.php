<?php
session_start();
include("../config.php");

echo "<!DOCTYPE html><html><head><title>Profile OTP Debug</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 1000px; margin: 0 auto; }
h2 { color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
th { background: #dc3545; color: white; }
tr:nth-child(even) { background: #f8f9fa; }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.info { background: #d1ecf1; border-left: 4px solid #0c5460; padding: 15px; margin: 15px 0; border-radius: 4px; }
.warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; border-radius: 4px; }
.code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
</style></head><body><div class='container'>";

echo "<h2>🔍 Profile OTP Debug Tool</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>❌ No user logged in. Please log in first.</div>";
    echo "</div></body></html>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $con->prepare("SELECT user_id, name, email, username FROM userss WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<div class='error'>❌ User not found in database.</div>";
    echo "</div></body></html>";
    exit;
}

echo "<div class='info'>";
echo "<strong>📧 Current User Information:</strong><br>";
echo "User ID: <strong>{$user['user_id']}</strong><br>";
echo "Name: <strong>{$user['name']}</strong><br>";
echo "Email: <strong>{$user['email']}</strong><br>";
echo "Username: <strong>{$user['username']}</strong>";
echo "</div>";

// Check if table exists
echo "<h2>📋 OTP Verifications Table Status</h2>";
$result = $con->query("SHOW TABLES LIKE 'otp_verifications'");
if ($result->num_rows > 0) {
    echo "<div class='success'>✓ Table 'otp_verifications' exists</div>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $structure = $con->query("DESCRIBE otp_verifications");
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if ENUM includes 'profile_change'
    $check_enum = $con->query("SHOW COLUMNS FROM otp_verifications WHERE Field = 'otp_type'");
    $enum_row = $check_enum->fetch_assoc();
    if (strpos($enum_row['Type'], 'profile_change') === false) {
        echo "<div class='error'>❌ ENUM 'otp_type' does NOT include 'profile_change'!</div>";
        echo "<div class='warning'>Current ENUM values: <code>{$enum_row['Type']}</code><br>";
        echo "You need to add 'profile_change' to the ENUM. Run this SQL:<br>";
        echo "<div class='code'>ALTER TABLE otp_verifications MODIFY COLUMN otp_type ENUM('registration', 'password_reset', 'profile_change') NOT NULL;</div></div>";
    } else {
        echo "<div class='success'>✓ ENUM 'otp_type' includes 'profile_change'</div>";
    }
} else {
    echo "<div class='error'>❌ Table 'otp_verifications' does not exist!</div>";
    echo "<div class='warning'>You need to create the table. Run this SQL:<br>";
    echo "<div class='code'>CREATE TABLE otp_verifications (<br>
        &nbsp;&nbsp;id INT AUTO_INCREMENT PRIMARY KEY,<br>
        &nbsp;&nbsp;email VARCHAR(255) NOT NULL,<br>
        &nbsp;&nbsp;otp_code VARCHAR(6) NOT NULL,<br>
        &nbsp;&nbsp;otp_type ENUM('registration', 'password_reset', 'profile_change') NOT NULL,<br>
        &nbsp;&nbsp;is_verified TINYINT(1) DEFAULT 0,<br>
        &nbsp;&nbsp;created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,<br>
        &nbsp;&nbsp;expires_at DATETIME NOT NULL,<br>
        &nbsp;&nbsp;verified_at DATETIME DEFAULT NULL<br>
    );</div></div>";
    echo "</div></body></html>";
    exit;
}

// Show recent profile change OTPs
echo "<h2>🔐 Recent Profile Change OTPs</h2>";
$stmt = $con->prepare("SELECT * FROM otp_verifications 
                       WHERE email = ? AND otp_type = 'profile_change' 
                       ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>OTP Code</th><th>Created At</th><th>Expires At</th><th>Status</th><th>Time Left</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $now = time();
        $expires = strtotime($row['expires_at']);
        $timeLeft = $expires - $now;
        
        $status = $row['is_verified'] ? 
            "<span class='success'>✓ Verified</span>" : 
            ($timeLeft > 0 ? "<span style='color: #0066cc;'>⏳ Active</span>" : "<span class='error'>⌛ Expired</span>");
        
        $timeLeftText = $timeLeft > 0 ? 
            floor($timeLeft / 60) . "m " . ($timeLeft % 60) . "s" : 
            "Expired " . abs(floor($timeLeft / 60)) . "m ago";
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong style='font-size: 1.2em; color: #dc3545;'>{$row['otp_code']}</strong></td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td>{$row['expires_at']}</td>";
        echo "<td>{$status}</td>";
        echo "<td>{$timeLeftText}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ No OTP records found for this email.</div>";
}
$stmt->close();

// Check session data
echo "<h2>💾 Session Data</h2>";
if (isset($_SESSION['pending_profile_change'])) {
    echo "<div class='info'>";
    echo "<strong>Pending Profile Change Found:</strong><br>";
    echo "<pre>" . print_r($_SESSION['pending_profile_change'], true) . "</pre>";
    echo "</div>";
} else {
    echo "<div class='warning'>⚠️ No pending profile change in session.</div>";
}

// Show all OTP types for this email
echo "<h2>📊 All OTP Types for This Email</h2>";
$stmt = $con->prepare("SELECT otp_type, COUNT(*) as count, 
                       SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_count
                       FROM otp_verifications 
                       WHERE email = ? 
                       GROUP BY otp_type");
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>OTP Type</th><th>Total</th><th>Verified</th><th>Pending</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $pending = $row['count'] - $row['verified_count'];
        echo "<tr>";
        echo "<td><strong>{$row['otp_type']}</strong></td>";
        echo "<td>{$row['count']}</td>";
        echo "<td class='success'>{$row['verified_count']}</td>";
        echo "<td>" . ($pending > 0 ? "<span class='error'>{$pending}</span>" : "0") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>⚠️ No OTP records found.</div>";
}
$stmt->close();

// Test instructions
echo "<h2>🧪 Testing Instructions</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>Send OTP:</strong> Go to your profile page and try to update any information. Click the update button.</li>";
echo "<li><strong>Check Email:</strong> Check your email inbox (and spam folder) for the OTP code.</li>";
echo "<li><strong>Refresh This Page:</strong> After requesting OTP, refresh this page to see the new OTP record.</li>";
echo "<li><strong>Copy OTP:</strong> Copy the OTP code from the table above (most recent one).</li>";
echo "<li><strong>Verify:</strong> Enter the OTP in the modal on your profile page.</li>";
echo "</ol>";
echo "</div>";

// Common issues
echo "<h2>⚠️ Common Issues & Solutions</h2>";
echo "<div class='warning'>";
echo "<ul>";
echo "<li><strong>OTP Expired:</strong> OTPs expire after 5 minutes. Request a new one.</li>";
echo "<li><strong>Wrong Email:</strong> Make sure the email in your profile matches the one receiving OTPs.</li>";
echo "<li><strong>Email Not Arriving:</strong> Check spam folder, or try using the resend OTP button.</li>";
echo "<li><strong>OTP Already Used:</strong> Each OTP can only be used once. Request a new one if needed.</li>";
echo "<li><strong>Session Lost:</strong> If you see 'No pending changes', you need to submit the form again to generate a new OTP.</li>";
echo "</ul>";
echo "</div>";

// Clean up old OTPs button
echo "<h2>🧹 Maintenance</h2>";
if (isset($_GET['cleanup'])) {
    $stmt = $con->prepare("DELETE FROM otp_verifications WHERE email = ? AND (is_verified = 1 OR expires_at < NOW())");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    echo "<div class='success'>✓ Cleaned up {$deleted} old/verified OTP records.</div>";
}
echo "<a href='?cleanup=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Clean Up Old OTPs</a>";

echo "</div></body></html>";
$con->close();
?>