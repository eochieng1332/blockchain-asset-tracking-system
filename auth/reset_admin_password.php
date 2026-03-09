<?php
require_once '../config/db.php';

// New password
$new_password = 'Admin@123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update admin user
$sql = "UPDATE users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "Admin password has been reset successfully!\n";
        echo "Username: admin\n";
        echo "Password: Admin@123\n";
        echo "Hash: " . $hashed_password . "\n";
    } else {
        echo "Admin user not found. Creating new admin user...\n";
        
        // Create admin user if doesn't exist
        $insert_sql = "INSERT INTO users (username, password, email, full_name, role) 
                       VALUES ('admin', ?, 'admin@sbat.local', 'System Administrator', 'Admin')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("s", $hashed_password);
        
        if ($insert_stmt->execute()) {
            echo "Admin user created successfully!\n";
        } else {
            echo "Error creating admin user: " . $conn->error . "\n";
        }
    }
} else {
    echo "Error updating password: " . $conn->error . "\n";
}

// Verify the password works
$verify_sql = "SELECT password FROM users WHERE username = 'admin'";
$verify_result = $conn->query($verify_sql);

if ($verify_result->num_rows > 0) {
    $user = $verify_result->fetch_assoc();
    if (password_verify('Admin@123', $user['password'])) {
        echo "\n✓ Password verification successful!\n";
    } else {
        echo "\n✗ Password verification failed!\n";
    }
}

$conn->close();
?>