<?php
/**
 * AJAX Pagination Handler
 * Place this file in: public_html/dashboard/includes/ajax-pagination.php
 */

// Start session
session_start();

// Load WordPress correctly
 $wordpress_path = dirname(dirname(dirname(__FILE__)));
if (file_exists($wordpress_path . '/wp-load.php')) {
    require_once($wordpress_path . '/wp-load.php');
} else {
    // Try alternative path
    require_once('../../../wp-load.php');
}

// Set content type
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['dashboard_user_id']) || empty($_SESSION['dashboard_user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit;
}

// Get action
 $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
 $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
 $per_page = 6;
 $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';

if ($action === 'get_orders') {
    // Get orders for pagination with date filter
    $offset = ($page - 1) * $per_page;
    
    $args = array(
        'limit' => $per_page,
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects'
    );
    
    // Add date filter if specified
    if ($filter === 'today') {
        $args['date_created'] = date('Y-m-d');
    } elseif ($filter === 'yesterday') {
        $args['date_created'] = date('Y-m-d', strtotime('-1 day'));
    } elseif ($filter === 'last7') {
        $args['date_created'] = date('Y-m-d', strtotime('-7 days'));
    }
    
    $orders = wc_get_orders($args);
    
    // Get total orders with same filter
    $total_args = array(
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'ids'
    );
    
    // Add date filter if specified
    if ($filter === 'today') {
        $total_args['date_created'] = date('Y-m-d');
    } elseif ($filter === 'yesterday') {
        $total_args['date_created'] = date('Y-m-d', strtotime('-1 day'));
    } elseif ($filter === 'last7') {
        $total_args['date_created'] = date('Y-m-d', strtotime('-7 days'));
    }
    
    $all_order_ids = wc_get_orders($total_args);
    $total_orders = count($all_order_ids);
    $total_pages = ceil($total_orders / $per_page);
    
    // Format orders
    $formatted_orders = array();
    foreach ($orders as $order) {
        $billing_first = $order->get_billing_first_name();
        $billing_last = $order->get_billing_last_name();
        $billing_email = $order->get_billing_email();
        $customer_name = trim($billing_first . ' ' . $billing_last);
        if (empty($customer_name)) {
            $customer_name = 'Guest';
        }
        
        $formatted_orders[] = array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'customer_name' => $customer_name,
            'email' => $billing_email,
            'total' => html_entity_decode(wc_price($order->get_total())),
            'status' => $order->get_status(),
            'date' => $order->get_date_created()->date_i18n('M d, Y')
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'orders' => $formatted_orders,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_orders' => $total_orders,
        'filter' => $filter
    ));
    
} elseif ($action === 'search_orders') {
    // Search orders with date filter
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    if (empty($search_term)) {
        echo json_encode(array('success' => false, 'message' => 'Search term is empty'));
        exit;
    }
    
    // Get all orders
    $args = array(
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects'
    );
    
    // Add date filter if specified
    if ($filter === 'today') {
        $args['date_created'] = date('Y-m-d');
    } elseif ($filter === 'yesterday') {
        $args['date_created'] = date('Y-m-d', strtotime('-1 day'));
    } elseif ($filter === 'last7') {
        $args['date_created'] = date('Y-m-d', strtotime('-7 days'));
    }
    
    $all_orders = wc_get_orders($args);
    
    // Filter orders
    $filtered_orders = array();
    $search_lower = strtolower($search_term);
    
    foreach ($all_orders as $order) {
        $order_id = $order->get_id();
        $order_number = $order->get_order_number();
        $billing_first = $order->get_billing_first_name();
        $billing_last = $order->get_billing_last_name();
        $billing_email = $order->get_billing_email();
        $customer_name = trim($billing_first . ' ' . $billing_last);
        
        if (
            strpos(strtolower($order_number), $search_lower) !== false ||
            strpos(strtolower($customer_name), $search_lower) !== false ||
            strpos(strtolower($billing_email), $search_lower) !== false ||
            strpos(strtolower($order_id), $search_lower) !== false
        ) {
            if (empty($customer_name)) {
                $customer_name = 'Guest';
            }
            
            $filtered_orders[] = array(
                'id' => $order_id,
                'order_number' => $order_number,
                'customer_name' => $customer_name,
                'email' => $billing_email,
                'total' => html_entity_decode(wc_price($order->get_total())),
                'status' => $order->get_status(),
                'date' => $order->get_date_created()->date_i18n('M d, Y')
            );
        }
    }
    
    // Paginate results
    $total_orders = count($filtered_orders);
    $total_pages = ceil($total_orders / $per_page);
    $offset = ($page - 1) * $per_page;
    $page_orders = array_slice($filtered_orders, $offset, $per_page);
    
    echo json_encode(array(
        'success' => true,
        'orders' => $page_orders,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_orders' => $total_orders,
        'filter' => $filter
    ));
    
} else {
    echo json_encode(array('success' => false, 'message' => 'Invalid action'));
}
?>