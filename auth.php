<?php
session_start();

// Store hashed credentials (in a real application, these would be in a secure database)
$valid_credentials = [
    'olegp' => password_hash('458555', PASSWORD_DEFAULT)
];

function isAuthenticated() {
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function login($username, $password) {
    global $valid_credentials;
    
    if (isset($valid_credentials[$username]) && 
        password_verify($password, $valid_credentials[$username])) {
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?> 