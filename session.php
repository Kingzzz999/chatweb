<?php
session_start();
require_once 'functions.php';

function checkAuth() {
    if (isset($_COOKIE['session_token'])) {
        $user = validateSession($_COOKIE['session_token']);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = $user;
            updateLastSeen($user['id']);
            return true;
        }
    }
    
    return isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!checkAuth()) {
        header('Location: login.php');
        exit();
    }
}

function getCurrentUser() {
    if (checkAuth()) {
        return $_SESSION['user_data'];
    }
    return null;
}
?>
