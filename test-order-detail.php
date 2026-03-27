<?php
/**
 * Order Details Page
 * Place this file in: public_html/dashboard/order-details.php
 */

// Start session
session_start();

// Load WordPress (go up one level from dashboard folder)
require_once(dirname(__DIR__) . '/wp-load.php');

// Check if user is logged in
if (!isset($_SESSION['dashboard_user_id']) || empty($_SESSION['dashboard_user_id'])) {
    header('Location: index.php');
    exit;
}

// Get user data
 $current_user = get_userdata($_SESSION['dashboard_user_id']);
if (!$current_user) {
    // Invalid user, logout
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get order ID from URL
 $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Get order object
 $order = wc_get_order($order_id);

// Check if order exists
if (!$order) {
    echo '<div class="min-h-screen flex items-center justify-center p-6 bg-gray-50">';
    echo '<div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 border border-gray-100 max-w-md w-full">';
    echo '<div class="text-center">';
    echo '<i class="bi bi-exclamation-triangle text-6xl text-yellow-500 mb-4"></i>';
    echo '<h2 class="text-2xl font-bold text-gray-900 mb-2">Order Not Found</h2>';
    echo '<p class="text-gray-500 mb-6">The order you are looking for does not exist or you do not have permission to view it.</p>';
    echo '<a href="index.php" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors inline-block">Back to Dashboard</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitize_text_field($_POST['action']);
    
    switch ($action) {
        case 'upload_image':
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_type = isset($_POST['image_type']) ? sanitize_text_field($_POST['image_type']) : '';
                
                if (in_array($image_type, array('before', 'after'))) {
                    $file = $_FILES['image'];
                    $upload_dir = wp_upload_dir();
                    $filename = 'order-' . $order_id . '-' . $image_type . '-' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filepath = $upload_dir['path'] . '/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $attachment = array(
                            'post_mime_type' => $file['type'],
                            'post_title' => 'Order ' . $order_id . ' ' . $image_type . ' Image',
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );
                        
                        $attachment_id = wp_insert_attachment($attachment, $filepath);
                        
                        if ($attachment_id) {
                            require_once(ABSPATH . 'wp-admin/includes/image.php');
                            $attachment_data = wp_generate_attachment_metadata($attachment_id, $filepath);
                            wp_update_attachment_metadata($attachment_id, $attachment_data);
                            
                            $image_url = wp_get_attachment_url($attachment_id);
                            update_post_meta($order_id, $image_type . '_image', $image_url);
                            
                            echo json_encode(array('success' => true, 'message' => 'Image uploaded successfully', 'url' => $image_url));
                            exit;
                        }
                    }
                }
            }
            echo json_encode(array('success' => false, 'message' => 'Error uploading image'));
            exit;
            break;
            
        case 'update_location':
            $pin_location = isset($_POST['pin_location']) ? sanitize_text_field($_POST['pin_location']) : '';
            $pin_address = isset($_POST['pin_address']) ? sanitize_text_field($_POST['pin_address']) : '';
            $pin_code = isset($_POST['pin_code']) ? sanitize_text_field($_POST['pin_code']) : '';
            
            if (!empty($pin_location)) {
                update_post_meta($order_id, 'pin_location', $pin_location);
                update_post_meta($order_id, 'pin_address', $pin_address);
                update_post_meta($order_id, 'pin_code', $pin_code);
                echo json_encode(array('success' => true, 'message' => 'Location updated successfully'));
                exit;
            }
            echo json_encode(array('success' => false, 'message' => 'Invalid location'));
            exit;
            break;
            
        case 'save_order_details':
            // Save all form data
            $deceased_name = isset($_POST['deceased_name']) ? sanitize_text_field($_POST['deceased_name']) : '';
            $date_of_death = isset($_POST['date_of_death']) ? sanitize_text_field($_POST['date_of_death']) : '';
            $cemetery = isset($_POST['cemetery']) ? sanitize_text_field($_POST['cemetery']) : '';
            $how_hear = isset($_POST['how_hear']) ? sanitize_text_field($_POST['how_hear']) : '';
            $cleaning_type = isset($_POST['cleaning_type']) ? sanitize_text_field($_POST['cleaning_type']) : '';
            $flowers_delivered = isset($_POST['flowers_delivered']) ? sanitize_text_field($_POST['flowers_delivered']) : '';
            $accessories = isset($_POST['accessories']) ? sanitize_text_field($_POST['accessories']) : '';
            $pin_location = isset($_POST['pin_location']) ? sanitize_text_field($_POST['pin_location']) : '';
            $pin_address = isset($_POST['pin_address']) ? sanitize_text_field($_POST['pin_address']) : '';
            $pin_code = isset($_POST['pin_code']) ? sanitize_text_field($_POST['pin_code']) : '';
            
            // Update all meta fields
            update_post_meta($order_id, 'deceased_name', $deceased_name);
            update_post_meta($order_id, 'date_of_death', $date_of_death);
            update_post_meta($order_id, 'cemetery', $cemetery);
            update_post_meta($order_id, 'how_hear', $how_hear);
            update_post_meta($order_id, 'cleaning_type', $cleaning_type);
            update_post_meta($order_id, 'flowers_delivered', $flowers_delivered);
            update_post_meta($order_id, 'accessories', $accessories);
            update_post_meta($order_id, 'pin_location', $pin_location);
            update_post_meta($order_id, 'pin_address', $pin_address);
            update_post_meta($order_id, 'pin_code', $pin_code);
            
            echo json_encode(array('success' => true, 'message' => 'Order details saved successfully'));
            exit;
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid action'));
            exit;
    }
}

// Get order details
 $order_number = $order->get_order_number();
 $order_status = $order->get_status();
 $order_total = $order->get_total();
 $order_date = $order->get_date_created();
 $payment_method = $order->get_payment_method_title();
 $customer_note = $order->get_customer_note();

// Get customer info
 $billing_first_name = $order->get_billing_first_name();
 $billing_last_name = $order->get_billing_last_name();
 $billing_email = $order->get_billing_email();
 $billing_phone = $order->get_billing_phone();
 $billing_company = $order->get_billing_company();
 $billing_address_1 = $order->get_billing_address_1();
 $billing_address_2 = $order->get_billing_address_2();
 $billing_city = $order->get_billing_city();
 $billing_state = $order->get_billing_state();
 $billing_postcode = $order->get_billing_postcode();
 $billing_country = $order->get_billing_country();

// Get shipping info
 $shipping_first_name = $order->get_shipping_first_name();
 $shipping_last_name = $order->get_shipping_last_name();
 $shipping_address_1 = $order->get_shipping_address_1();
 $shipping_address_2 = $order->get_shipping_address_2();
 $shipping_city = $order->get_shipping_city();
 $shipping_state = $order->get_shipping_state();
 $shipping_postcode = $order->get_shipping_postcode();
 $shipping_country = $order->get_shipping_country();

// Get order items
 $order_items = $order->get_items();

// Get status badge
function get_status_badge($status) {
    $badges = array(
        'pending' => array(
            'class' => 'bg-yellow-100 text-yellow-700',
            'label' => 'Pending'
        ),
        'processing' => array(
            'class' => 'bg-blue-100 text-blue-700',
            'label' => 'Processing'
        ),
        'on-hold' => array(
            'class' => 'bg-orange-100 text-orange-700',
            'label' => 'On Hold'
        ),
        'completed' => array(
            'class' => 'bg-green-100 text-green-700',
            'label' => 'Completed'
        ),
        'cancelled' => array(
            'class' => 'bg-red-100 text-red-700',
            'label' => 'Cancelled'
        ),
        'refunded' => array(
            'class' => 'bg-purple-100 text-purple-700',
            'label' => 'Refunded'
        ),
        'failed' => array(
            'class' => 'bg-red-100 text-red-700',
            'label' => 'Failed'
        )
    );
    
    $status = str_replace('wc-', '', $status);
    
    return isset($badges[$status]) ? $badges[$status] : array(
        'class' => 'bg-gray-100 text-gray-700',
        'label' => ucfirst($status)
    );
}

 $status_badge = get_status_badge($order_status);

// Get custom fields
 $deceased_name = get_post_meta($order_id, 'deceased_name', true);
 $date_of_death = get_post_meta($order_id, 'date_of_death', true);
 $cemetery = get_post_meta($order_id, 'cemetery', true);
 $how_hear = get_post_meta($order_id, 'how_hear', true);
 $cleaning_type = get_post_meta($order_id, 'cleaning_type', true);
 $flowers_delivered = get_post_meta($order_id, 'flowers_delivered', true);
 $accessories = get_post_meta($order_id, 'accessories', true);
 $before_image = get_post_meta($order_id, 'before_image', true);
 $after_image = get_post_meta($order_id, 'after_image', true);
 $pin_location = get_post_meta($order_id, 'pin_location', true);
 $pin_address = get_post_meta($order_id, 'pin_address', true);
 $pin_code = get_post_meta($order_id, 'pin_code', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Order Details - #<?php echo esc_html($order_number); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Leaflet CSS for Map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; }
    
    /* Camera and location styles */
    .camera-btn {
      position: relative;
      overflow: hidden;
    }
    
    .camera-btn input[type="file"] {
      position: absolute;
      left: 0;
      top: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }
    
    .map-container {
      position: relative;
      overflow: hidden;
    }
    
    #locationPicker {
      height: 400px;
      width: 100%;
      border-radius: 0.75rem;
      z-index: 1;
    }
    
    .map-overlay {
      position: absolute;
      top: 10px;
      right: 10px;
      background: white;
      padding: 10px;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      z-index: 1000;
      width: 250px;
    }
    
    .location-search {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin-bottom: 10px;
    }
    
    .search-results {
      max-height: 200px;
      overflow-y: auto;
      margin-top: 5px;
    }
    
    .search-result-item {
      padding: 8px;
      cursor: pointer;
      border-bottom: 1px solid #eee;
    }
    
    .search-result-item:hover {
      background-color: #f5f5f5;
    }
    
    .live-location-btn {
      background-color: #4285F4;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      font-size: 14px;
      width: 100%;
      transition: background-color 0.3s;
    }
    
    .live-location-btn:hover {
      background-color: #3367D6;
    }
    
    .live-location-btn:disabled {
      background-color: #cccccc;
      cursor: not-allowed;
    }
    
    .loading-spinner {
      display: inline-block;
      width: 14px;
      height: 14px;
      border: 2px solid rgba(255,255,255,.3);
      border-radius: 50%;
      border-top-color: white;
      animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    .map-container {
    z-index: 0;
}
  </style>
</head>
<body class="bg-gray-50">
  <!-- Header -->
  <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-6 py-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button onclick="window.history.back()" class="w-10 h-10 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
            <i class="bi bi-arrow-left text-gray-700"></i>
          </button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Order #<?php echo esc_html($order_number); ?></h1>
            <p class="text-sm text-gray-500">Complete order details</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="px-4 py-2 <?php echo $status_badge['class']; ?> text-sm font-semibold rounded-xl"><?php echo $status_badge['label']; ?></span>
          <button onclick="window.print()" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors">
            <i class="bi bi-printer mr-2"></i>Print
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Content -->
  <div class="max-w-7xl mx-auto px-6 py-8">
    <div class="space-y-6">
      <!-- Customer Info -->
      <div class="bg-white rounded-2xl p-6 border border-gray-200">
        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="bi bi-person-badge text-blue-600"></i>
          Customer Information
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Email</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_email); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">First Name</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_first_name); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Last Name</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_last_name); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Company</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_company ? $billing_company : 'N/A'); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Phone</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_phone ? $billing_phone : 'N/A'); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Country</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html(WC()->countries->countries[$billing_country] ?? $billing_country); ?></p>
          </div>
        </div>
      </div>

      <!-- Address -->
      <div class="bg-white rounded-2xl p-6 border border-gray-200">
        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="bi bi-geo-alt text-purple-600"></i>
          Address Details
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 md:col-span-2">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Street Address</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_address_1); ?><?php echo $billing_address_2 ? ', ' . esc_html($billing_address_2) : ''; ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">City</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_city); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">State</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_state); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ZIP</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($billing_postcode); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Location</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($pin_address ? $pin_address : 'Not set'); ?></p>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="bg-white rounded-2xl p-6 border border-gray-200">
        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="bi bi-cart3 text-green-600"></i>
          Order Items
        </h4>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Product</th>
                <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Quantity</th>
                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Price</th>
                <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($order_items as $item_id => $item) : ?>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4">
                  <div class="flex items-center gap-3">
                    <?php 
                    $product = $item->get_product();
                    $image_id = $product ? $product->get_image_id() : 0;
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                    ?>
                    <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($item->get_name()); ?>" class="w-12 h-12 rounded-lg object-cover">
                    <?php else : ?>
                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                      <i class="bi bi-image text-gray-400"></i>
                    </div>
                    <?php endif; ?>
                    <div>
                      <p class="font-medium text-gray-900"><?php echo esc_html($item->get_name()); ?></p>
                      <?php if ($item->get_variation_id()) : ?>
                      <p class="text-xs text-gray-500"><?php echo wc_get_formatted_variation($item->get_variation(), true); ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-4 text-sm text-gray-700"><?php echo esc_html($item->get_quantity()); ?></td>
                <td class="py-3 px-4 text-sm text-gray-700 text-right"><?php echo wc_price($item->get_subtotal() / $item->get_quantity()); ?></td>
                <td class="py-3 px-4 text-sm font-medium text-gray-900 text-right"><?php echo wc_price($item->get_subtotal()); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="border-t border-gray-200">
                <td colspan="3" class="py-3 px-4 text-sm font-medium text-gray-700 text-right">Subtotal:</td>
                <td class="py-3 px-4 text-sm font-medium text-gray-900 text-right"><?php echo wc_price($order->get_subtotal()); ?></td>
              </tr>
              <?php if ($order->get_shipping_method()) : ?>
              <tr>
                <td colspan="3" class="py-3 px-4 text-sm font-medium text-gray-700 text-right">Shipping:</td>
                <td class="py-3 px-4 text-sm font-medium text-gray-900 text-right"><?php echo wc_price($order->get_shipping_total()); ?></td>
              </tr>
              <?php endif; ?>
              <?php if ($order->get_discount_total()) : ?>
              <tr>
                <td colspan="3" class="py-3 px-4 text-sm font-medium text-gray-700 text-right">Discount:</td>
                <td class="py-3 px-4 text-sm font-medium text-gray-900 text-right">-<?php echo wc_price($order->get_discount_total()); ?></td>
              </tr>
              <?php endif; ?>
              <?php if ($order->get_total_tax()) : ?>
              <tr>
                <td colspan="3" class="py-3 px-4 text-sm font-medium text-gray-700 text-right">Tax:</td>
                <td class="py-3 px-4 text-sm font-medium text-gray-900 text-right"><?php echo wc_price($order->get_total_tax()); ?></td>
              </tr>
              <?php endif; ?>
              <tr>
                <td colspan="3" class="py-3 px-4 text-sm font-bold text-gray-900 text-right">Total:</td>
                <td class="py-3 px-4 text-lg font-bold text-gray-900 text-right"><?php echo wc_price($order->get_total()); ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Order Details -->
      <div class="bg-white rounded-2xl p-6 border border-gray-200">
        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="bi bi-info-circle text-orange-600"></i>
          Order Details
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Order Date</p>
            <p class="text-sm font-medium text-gray-900"><?php echo $order_date->date_i18n('F j, Y'); ?></p>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Payment Method</p>
            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($payment_method); ?></p>
          </div>
          <?php if ($customer_note) : ?>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 md:col-span-2">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Customer Note</p>
            <p class="text-sm text-gray-700"><?php echo esc_html($customer_note); ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Additional Details -->
      <div class="bg-white rounded-2xl p-6 border border-gray-200">
        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="bi bi-info-circle text-orange-600"></i>
          Additional Details
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Deceased Name</p>
            <input type="text" id="deceased_name" name="deceased_name" value="<?php echo esc_attr($deceased_name); ?>" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900">
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Date of Death</p>
            <input type="date" id="date_of_death" name="date_of_death" value="<?php echo esc_attr($date_of_death); ?>" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900">
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cemetery</p>
            <input type="text" id="cemetery" name="cemetery" value="<?php echo esc_attr($cemetery); ?>" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900">
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">How did you hear about us?</p>
            <select id="how_hear" name="how_hear" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900">
              <option value="">Select an option</option>
              <option value="social_media" <?php echo ($how_hear === 'social_media') ? 'selected' : ''; ?>>Social Media</option>
              <option value="search_engine" <?php echo ($how_hear === 'search_engine') ? 'selected' : ''; ?>>Search Engine</option>
              <option value="friend" <?php echo ($how_hear === 'friend') ? 'selected' : ''; ?>>Friend</option>
              <option value="advertisement" <?php echo ($how_hear === 'advertisement') ? 'selected' : ''; ?>>Advertisement</option>
              <option value="other" <?php echo ($how_hear === 'other') ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Services Rendered -->
      <div class="bg-white rounded-2xl p-6 border border-gray-200">
        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="bi bi-gear text-green-600"></i>
          Services Rendered
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Type of Cleaning</p>
            <select id="cleaning_type" name="cleaning_type" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900">
              <option value="">Select a service</option>
              <option value="basic" <?php echo ($cleaning_type === 'basic') ? 'selected' : ''; ?>>Basic Cleaning</option>
              <option value="deep" <?php echo ($cleaning_type === 'deep') ? 'selected' : ''; ?>>Deep Cleaning</option>
              <option value="restoration" <?php echo ($cleaning_type === 'restoration') ? 'selected' : ''; ?>>Restoration</option>
              <option value="polishing" <?php echo ($cleaning_type === 'polishing') ? 'selected' : ''; ?>>Polishing</option>
            </select>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Flowers Delivered</p>
            <select id="flowers_delivered" name="flowers_delivered" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900">
              <option value="">Select an option</option>
              <option value="yes" <?php echo ($flowers_delivered === 'yes') ? 'selected' : ''; ?>>Yes</option>
              <option value="no" <?php echo ($flowers_delivered === 'no') ? 'selected' : ''; ?>>No</option>
            </select>
          </div>
          <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 md:col-span-2">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Accessories</p>
            <input type="text" id="accessories" name="accessories" value="<?php echo esc_attr($accessories); ?>" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900">
          </div>
        </div>
      </div>

      <!-- Media & Location -->
      <div class="bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl p-6 border border-blue-200">
        <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
          <i class="bi bi-camera text-blue-600"></i>
          Media & Location
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Before Image</label>
            <?php if ($before_image) : ?>
            <div class="relative">
              <img src="<?php echo esc_url($before_image); ?>" alt="Before" class="w-full h-40 object-cover rounded-xl">
              <button type="button" class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center" onclick="removeImage('before')">
                <i class="bi bi-x"></i>
              </button>
            </div>
            <?php else : ?>
            <div class="camera-btn">
              <input type="file" id="beforeImage" accept="image/*" onchange="uploadImage('before')" class="w-full h-40 bg-white border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center cursor-pointer hover:border-blue-500 hover:bg-blue-50/50 transition-all">
              <div class="text-center">
                <i class="bi bi-camera text-4xl text-blue-600 mb-2"></i>
                <p class="text-sm font-medium text-gray-600">Click to upload before image</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">After Image</label>
            <?php if ($after_image) : ?>
            <div class="relative">
              <img src="<?php echo esc_url($after_image); ?>" alt="After" class="w-full h-40 object-cover rounded-xl">
              <button type="button" class="absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center" onclick="removeImage('after')">
                <i class="bi bi-x"></i>
              </button>
            </div>
            <?php else : ?>
            <div class="camera-btn">
              <input type="file" id="afterImage" accept="image/*" onchange="uploadImage('after')" class="w-full h-40 bg-white border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center cursor-pointer hover:border-blue-500 hover:bg-blue-50/50 transition-all">
              <div class="text-center">
                <i class="bi bi-camera text-4xl text-green-600 mb-2"></i>
                <p class="text-sm font-medium text-gray-600">Click to upload after image</p>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Pin Location</label>
            <div class="map-container">
              <div id="locationPicker" class="bg-white rounded-2xl border-2 border-dashed border-blue-300 cursor-pointer hover:border-blue-500 hover:bg-blue-50/50 transition-all"></div>
              <div class="map-overlay">
                <input type="text" id="locationSearch" class="location-search" placeholder="Search for a location...">
                <button id="liveLocationBtn" class="live-location-btn" type="button">
                  <i class="bi bi-geo-alt-fill"></i>
                  <span>Use My Current Location</span>
                </button>
                <div id="searchResults" class="search-results"></div>
              </div>
            </div>
            <input type="hidden" id="pinLocation" name="pin_location" value="<?php echo esc_attr($pin_location ? $pin_location : ''); ?>">
            <input type="text" id="pinAddress" name="pin_address" value="<?php echo esc_attr($pin_address ? $pin_address : ''); ?>" class="mt-3 w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900" placeholder="Selected location address">
            <input type="text" id="pinCode" name="pin_code" value="<?php echo esc_attr($pin_code ? $pin_code : ''); ?>" class="mt-3 w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-900" placeholder="Pin code">
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-3 sticky bottom-6 bg-white rounded-2xl p-4 border border-gray-200 shadow-lg">
        <button onclick="window.history.back()" class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors">
          <i class="bi bi-x-circle mr-2"></i>Back to Dashboard
        </button>
        <button onclick="saveOrderDetails()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 transition-all">
          <i class="bi bi-check-circle mr-2"></i>Save Changes
        </button>
      </div>
    </div>
  </div>

  <!-- Leaflet JS for Map -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    // Function to remove image
    function removeImage(type) {
      if (!confirm('Are you sure you want to remove this image?')) return;
      
      const formData = new FormData();
      formData.append('action', 'remove_order_image');
      formData.append('order_id', <?php echo $order_id; ?>);
      formData.append('image_type', type);
      
      fetch('includes/ajax-order-update.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to show the change
          window.location.reload();
        } else {
          alert('Error removing image: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the image');
      });
    }
    
    // Function to upload image
    function uploadImage(type) {
      const fileInput = document.getElementById(type + 'Image');
      const file = fileInput.files[0];
      
      if (!file) return;
      
      const formData = new FormData();
      formData.append('action', 'upload_order_image');
      formData.append('order_id', <?php echo $order_id; ?>);
      formData.append('image_type', type);
      formData.append('image', file);
      
      // Show loading state
      fileInput.disabled = true;
      fileInput.nextElementSibling = document.createElement('div');
      fileInput.nextElementSibling.className = 'text-center text-sm font-medium text-blue-600';
      fileInput.nextElementSibling.textContent = 'Uploading...';
      
      fetch('includes/ajax-order-update.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload the page to show the uploaded image
          window.location.reload();
        } else {
          alert('Error uploading image: ' + data.message);
          fileInput.disabled = false;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while uploading the image');
        fileInput.disabled = false;
      });
    }
    
    // Function to save order details
    function saveOrderDetails() {
      const formData = new FormData();
      formData.append('action', 'save_order_details');
      
      // Add all form fields
      const deceasedName = document.getElementById('deceased_name').value;
      const dateOfDeath = document.getElementById('date_of_death').value;
      const cemetery = document.getElementById('cemetery').value;
      const howHear = document.getElementById('how_hear').value;
      const cleaningType = document.getElementById('cleaning_type').value;
      const flowersDelivered = document.getElementById('flowers_delivered').value;
      const accessories = document.getElementById('accessories').value;
      const pinLocation = document.getElementById('pinLocation').value;
      const pinAddress = document.getElementById('pinAddress').value;
      const pinCode = document.getElementById('pinCode').value;
      
      formData.append('deceased_name', deceasedName);
      formData.append('date_of_death', dateOfDeath);
      formData.append('cemetery', cemetery);
      formData.append('how_hear', howHear);
      formData.append('cleaning_type', cleaningType);
      formData.append('flowers_delivered', flowersDelivered);
      formData.append('accessories', accessories);
      formData.append('pin_location', pinLocation);
      formData.append('pin_address', pinAddress);
      formData.append('pin_code', pinCode);
      
      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Order details saved successfully!');
        } else {
          alert('Error saving order details: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving order details');
      });
    }
    
    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize the map
      let map;
      let marker;
      
      // Default location (you can change this to your preferred default)
      const defaultLat = 40.7128;
      const defaultLng = -74.0060;
      
      // Get existing location if available
      const pinLocation = document.getElementById('pinLocation').value;
      const pinAddress = document.getElementById('pinAddress').value;
      const pinCode = document.getElementById('pinCode').value;
      
      let lat = defaultLat;
      let lng = defaultLng;
      
      if (pinLocation) {
        const coords = pinLocation.split(',');
        if (coords.length === 2) {
          lat = parseFloat(coords[0]);
          lng = parseFloat(coords[1]);
        }
      }
      
      // Create map
      map = L.map('locationPicker').setView([lat, lng], 13);
      
      // Add tile layer
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);
      
      // Add marker if location exists
      if (pinLocation) {
        marker = L.marker([lat, lng]).addTo(map);
      }
      
      // Function to extract pin code from address
      function extractPinCode(address) {
        // This regex matches common pin code patterns (5-6 digits)
        const pinCodeRegex = /\b\d{5,6}\b/g;
        const matches = address.match(pinCodeRegex);
        
        if (matches && matches.length > 0) {
          // Return the last match (most likely to be the pin code)
          return matches[matches.length - 1];
        }
        
        return '';
      }
      
      // Function to update marker position
      function updateMarkerPosition(lat, lng, address) {
        // Remove existing marker if it exists
        if (marker) {
          map.removeLayer(marker);
        }
        
        // Add new marker
        marker = L.marker([lat, lng]).addTo(map);
        
        // Extract pin code from address
        const extractedPinCode = extractPinCode(address);
        
        // Update form fields
        document.getElementById('pinLocation').value = lat + ',' + lng;
        document.getElementById('pinAddress').value = address;
        document.getElementById('pinCode').value = extractedPinCode;
        
        // Save location to backend
        saveLocationToBackend(lat + ',' + lng, address, extractedPinCode);
      }
      
      // Function to save location to backend
      function saveLocationToBackend(location, address, pinCode) {
        const formData = new FormData();
        formData.append('action', 'update_location');
        formData.append('order_id', <?php echo $order_id; ?>);
        formData.append('pin_location', location);
        formData.append('pin_address', address);
        formData.append('pin_code', pinCode);
        
        fetch('includes/ajax-order-update.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            console.log('Location saved successfully');
          } else {
            console.error('Error saving location:', data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
        });
      }
      
      // Add click event to map
      map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        // Reverse geocoding to get address
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
        .then(response => response.json())
        .then(data => {
          const address = data.display_name || 'Unknown location';
          updateMarkerPosition(lat, lng, address);
        })
        .catch(error => {
          console.error('Error getting address:', error);
          updateMarkerPosition(lat, lng, 'Unknown location');
        });
      });
      
      // Live location functionality
      const liveLocationBtn = document.getElementById('liveLocationBtn');
      
      liveLocationBtn.addEventListener('click', function() {
        // Show loading state
        const btnText = this.querySelector('span');
        const originalText = btnText.textContent;
        btnText.innerHTML = '<span class="loading-spinner"></span> Getting location...';
        this.disabled = true;
        
        // Check if geolocation is supported
        if (!navigator.geolocation) {
          alert('Geolocation is not supported by your browser');
          btnText.textContent = originalText;
          this.disabled = false;
          return;
        }
        
        // Get current position
        navigator.geolocation.getCurrentPosition(
          // Success callback
          function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Update map view
            map.setView([lat, lng], 16);
            
            // Reverse geocoding to get address
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
              const address = data.display_name || 'Current location';
              updateMarkerPosition(lat, lng, address);
              
              // Reset button
              btnText.textContent = originalText;
              liveLocationBtn.disabled = false;
            })
            .catch(error => {
              console.error('Error getting address:', error);
              updateMarkerPosition(lat, lng, 'Current location');
              
              // Reset button
              btnText.textContent = originalText;
              liveLocationBtn.disabled = false;
            });
          },
          // Error callback
          function(error) {
            let errorMessage = 'Unable to retrieve your location';
            
            switch(error.code) {
              case error.PERMISSION_DENIED:
                errorMessage = 'Location access denied by user';
                break;
              case error.POSITION_UNAVAILABLE:
                errorMessage = 'Location information unavailable';
                break;
              case error.TIMEOUT:
                errorMessage = 'Location request timed out';
                break;
            }
            
            alert(errorMessage);
            
            // Reset button
            btnText.textContent = originalText;
            liveLocationBtn.disabled = false;
          },
          // Options
          {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
          }
        );
      });
      
      // Search functionality
      const searchInput = document.getElementById('locationSearch');
      const searchResults = document.getElementById('searchResults');
      
      searchInput.addEventListener('input', function() {
        const query = this.value;
        
        if (query.length < 3) {
          searchResults.innerHTML = '';
          return;
        }
        
        // Search for locations
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
          searchResults.innerHTML = '';
          
          data.forEach(item => {
            const resultItem = document.createElement('div');
            resultItem.className = 'search-result-item';
            resultItem.textContent = item.display_name;
            
            resultItem.addEventListener('click', function() {
              const lat = parseFloat(item.lat);
              const lng = parseFloat(item.lon);
              const address = item.display_name;
              
              // Update map view
              map.setView([lat, lng], 16);
              
              // Update marker
              updateMarkerPosition(lat, lng, address);
              
              // Clear search
              searchResults.innerHTML = '';
              searchInput.value = '';
            });
            
            searchResults.appendChild(resultItem);
          });
        })
        .catch(error => {
          console.error('Error searching:', error);
        });
      });
      
      // Close search results when clicking outside
      document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
          searchResults.innerHTML = '';
        }
      });
    });
  </script>
</body>
</html>