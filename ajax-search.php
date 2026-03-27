<?php
/**
 * AJAX Search Handler
 * Place this file in: public_html/dashboard/includes/ajax-search.php
 * 
 * This file handles order search functionality
 */

// Start session
session_start();

// Load WordPress
require_once(dirname(dirname(__DIR__)) . '/wp-load.php');

// Check if user is logged in
if (!isset($_SESSION['dashboard_user_id']) || empty($_SESSION['dashboard_user_id'])) {
    wp_send_json_error(array('message' => 'Unauthorized'));
    exit;
}

// Get search term
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

if (empty($search_term)) {
    wp_send_json_error(array('message' => 'Search term is required'));
    exit;
}

// Search in orders
$args = array(
    'limit' => 50,
    'orderby' => 'date',
    'order' => 'DESC',
    'return' => 'objects'
);

// Get all orders
$orders = wc_get_orders($args);

// Filter orders based on search term
$filtered_orders = array();

foreach ($orders as $order) {
    $order_id = $order->get_id();
    $order_number = $order->get_order_number();
    $billing_first = $order->get_billing_first_name();
    $billing_last = $order->get_billing_last_name();
    $billing_email = $order->get_billing_email();
    $customer_name = trim($billing_first . ' ' . $billing_last);
    
    // Check if search term matches
    $search_lower = strtolower($search_term);
    
    if (
        strpos(strtolower($order_number), $search_lower) !== false ||
        strpos(strtolower($customer_name), $search_lower) !== false ||
        strpos(strtolower($billing_email), $search_lower) !== false ||
        strpos(strtolower($order_id), $search_lower) !== false
    ) {
        $filtered_orders[] = array(
            'id' => $order_id,
            'order_number' => $order_number,
            'customer_name' => $customer_name,
            'email' => $billing_email,
            'total' => $order->get_total(),
            'status' => $order->get_status(),
            'date' => $order->get_date_created()->date_i18n('M d, Y'),
            'date_timestamp' => $order->get_date_created()->getTimestamp()
        );
    }
}

wp_send_json_success(array(
    'orders' => $filtered_orders,
    'total' => count($filtered_orders)
));