<?php
/**
* AJAX Order Update Handler
* Place this file in: public_html/dashboard/includes/ajax-order-update.php
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

// Check if action is set
if (!isset($_POST['action'])) {
wp_send_json_error(array('message' => 'No action specified'));
exit;
}

 $action = sanitize_text_field($_POST['action']);
 $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

// Validate order ID
if ($order_id <= 0) {
wp_send_json_error(array('message' => 'Invalid order ID'));
exit;
}

// Get order object
 $order = wc_get_order($order_id);

// Check if order exists
if (!$order) {
wp_send_json_error(array('message' => 'Order not found'));
exit;
}

// Handle different actions
switch ($action) {
case 'update_pin_location':
 $pin_location = isset($_POST['pin_location']) ? sanitize_text_field($_POST['pin_location']) : '';
 $pin_address = isset($_POST['pin_address']) ? sanitize_text_field($_POST['pin_address']) : '';
 $pin_code = isset($_POST['pin_code']) ? sanitize_text_field($_POST['pin_code']) : '';

update_post_meta($order_id, 'pin_location', $pin_location);
update_post_meta($order_id, 'pin_address', $pin_address);
update_post_meta($order_id, 'pin_code', $pin_code);

wp_send_json_success(array('message' => 'Pin location updated successfully'));
break;

case 'upload_order_image':
 $image_type = isset($_POST['image_type']) ? sanitize_text_field($_POST['image_type']) : '';

if (!in_array($image_type, array('before', 'after'))) {
wp_send_json_error(array('message' => 'Invalid image type'));
exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
wp_send_json_error(array('message' => 'Error uploading file'));
exit;
}

// Create custom directory for order images
 $upload_dir = wp_upload_dir();
 $order_image_dir = $upload_dir['basedir'] . '/order-images/' . $order_id;

// Create directory if doesn't exist
if (!file_exists($order_image_dir)) {
wp_mkdir_p($order_image_dir);
}

// Handle file upload
 $file = $_FILES['image'];
 $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
 $filename = $image_type . '_' . time() . '.' . $file_extension;
 $filepath = $order_image_dir . '/' . $filename;

// Validate file type (images only)
 $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
if (!in_array(strtolower($file_extension), $allowed_types)) {
wp_send_json_error(array('message' => 'Invalid file type. Only images allowed.'));
exit;
}

if (move_uploaded_file($file['tmp_name'], $filepath)) {
// Get URL for the uploaded file
 $image_url = $upload_dir['baseurl'] . '/order-images/' . $order_id . '/' . $filename;

// Save directly to order meta (no WordPress attachment)
update_post_meta($order_id, $image_type . '_image', $image_url);

// Also save file path for potential future use
update_post_meta($order_id, $image_type . '_image_path', $filepath);

wp_send_json_success(array(
'message' => 'Image uploaded successfully',
'url' => $image_url,
'type' => $image_type
));
} else {
wp_send_json_error(array('message' => 'Error moving uploaded file'));
}
break;

case 'remove_order_image':
 $image_type = isset($_POST['image_type']) ? sanitize_text_field($_POST['image_type']) : '';

if (!in_array($image_type, array('before', 'after'))) {
wp_send_json_error(array('message' => 'Invalid image type'));
exit;
}

 $image_url = get_post_meta($order_id, $image_type . '_image', true);
 $image_path = get_post_meta($order_id, $image_type . '_image_path', true);

// Delete physical file
if ($image_path && file_exists($image_path)) {
unlink($image_path);
}

// Remove from order meta
delete_post_meta($order_id, $image_type . '_image');
delete_post_meta($order_id, $image_type . '_image_path');

wp_send_json_success(array('message' => 'Image removed successfully'));
break;

default:
wp_send_json_error(array('message' => 'Unknown action'));
break;
}