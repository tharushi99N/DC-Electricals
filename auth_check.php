<?php
// auth_check.php - Include this file in pages that require authentication

function checkAuth($required_role = null) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../login.php");
        exit();
    }
    
    // Check role if specified
    if ($required_role && $_SESSION['user_role'] !== $required_role) {
        header("Location: ../login.php?error=access_denied");
        exit();
    }
    
    return true;
}

function getUserInfo() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role']
        ];
    }
    
    return null;
}

function redirectBasedOnRole($role) {
    switch($role) {
        case 'Admin':
            return 'admin/dashboard.php';
        case 'Customer':
            return 'customer/dashboard.php';
        case 'Technician':
            return 'technician/dashboard.php';
        case 'Storekeeper':
            return 'storekeeper/dashboard.php';
        default:
            return 'dashboard.php';
    }
}
?>