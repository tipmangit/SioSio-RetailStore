<?php
// test_password.php - Place this in your admin folder
include("../config.php");

$test_password = "admin123";
$username = "admin";

// Check if admin exists
$stmt = $con->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "Admin found!<br>";
    echo "Username: " . $admin['username'] . "<br>";
    echo "Stored Hash: " . $admin['password'] . "<br><br>";
    
    // Test password
    if (password_verify($test_password, $admin['password'])) {
        echo "✓ Password 'admin123' is CORRECT!";
    } else {
        echo "✗ Password verification FAILED<br>";
        
        // Generate new hash
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        echo "<br>Use this new hash instead:<br>";
        echo $new_hash;
    }
} else {
    echo "Admin user not found in database!";
}
?>