<?php
function logActivity($user_id, $username, $action, $details) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $sql = "INSERT INTO activity_logs (user_id, username, action, details, ip_address) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $username, $action, $details, $ip_address);
    
    return $stmt->execute();
}
?>