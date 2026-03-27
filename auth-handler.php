<?php
/**
 * Authentication Handler
 * Place this file in: public_html/dashboard/includes/auth-handler.php
 * 
 * This file handles WordPress user login validation
 */

// Start session
session_start();

// Load WordPress (go up two levels from dashboard/includes/)
require_once(dirname(dirname(__DIR__)) . '/wp-load.php');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// Get form data
$username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$remember = isset($_POST['remember']);

// Validate inputs
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Please enter both username/email and password.';
    header('Location: ../index.php');
    exit;
}

// Prepare credentials
$credentials = array(
    'user_login'    => $username,
    'user_password' => $password,
    'remember'      => $remember
);

// Attempt to authenticate
$user = wp_signon($credentials, false);

// Check for errors
if (is_wp_error($user)) {
    // Get error message
    $error_code = $user->get_error_code();
    
    switch ($error_code) {
        case 'invalid_username':
            $error_message = 'Invalid username or email address.';
            break;
        case 'incorrect_password':
            $error_message = 'Incorrect password. Please try again.';
            break;
        case 'empty_username':
            $error_message = 'Please enter your username or email.';
            break;
        case 'empty_password':
            $error_message = 'Please enter your password.';
            break;
        default:
            $error_message = 'Login failed. Please check your credentials and try again.';
            break;
    }
    
    $_SESSION['login_error'] = $error_message;
    header('Location: ../index.php');
    exit;
}

// Login successful
// Store user ID in session
$_SESSION['dashboard_user_id'] = $user->ID;
$_SESSION['login_success'] = 'Welcome back, ' . $user->display_name . '!';

// Optional: Set WordPress auth cookies (for integration with WordPress)
wp_set_current_user($user->ID);
wp_set_auth_cookie($user->ID, $remember);

// Redirect to dashboard
header('Location: ../index.php');
exit;