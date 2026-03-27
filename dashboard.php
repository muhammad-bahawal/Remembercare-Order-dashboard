<?php
/**
 * Orders Dashboard - Main File
 * Place this file in: public_html/dashboard/index.php
 */

// Start session
session_start();

// Load WordPress (go up one level from dashboard folder)
require_once(dirname(__DIR__) . '/wp-load.php');

// Check if user is already logged in
 $is_logged_in = isset($_SESSION['dashboard_user_id']) && !empty($_SESSION['dashboard_user_id']);

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get user data if logged in
 $current_user = null;
if ($is_logged_in) {
    $current_user = get_userdata($_SESSION['dashboard_user_id']);
    if (!$current_user) {
        // Invalid user, logout
        session_destroy();
        header('Location: index.php');
        exit;
    }
}

// Function to get order statistics
function get_order_statistics() {
    // Get all orders count
    $total_args = array(
        'limit' => -1,
        'return' => 'ids'
    );
    $total_orders = count(wc_get_orders($total_args));
    
    // Get pending orders (on-hold + pending payment)
    $pending_args = array(
        'limit' => -1,
        'status' => array('pending', 'on-hold'),
        'return' => 'ids'
    );
    $pending_orders = count(wc_get_orders($pending_args));
    
    // Get completed orders
    $completed_args = array(
        'limit' => -1,
        'status' => 'completed',
        'return' => 'ids'
    );
    $completed_orders = count(wc_get_orders($completed_args));
    
    return array(
        'total' => $total_orders,
        'pending' => $pending_orders,
        'completed' => $completed_orders
    );
}

// Function to get orders with pagination (6 per page = 2 rows of 3)
function get_dashboard_orders($page = 1, $per_page = 6) {
    $offset = ($page - 1) * $per_page;
    
    $args = array(
        'limit' => $per_page,
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects'
    );
    
    $orders = wc_get_orders($args);
    
    // Get total count for pagination
    $total_args = array(
        'limit' => -1,
        'return' => 'ids'
    );
    $total_orders = count(wc_get_orders($total_args));
    $total_pages = ceil($total_orders / $per_page);
    
    return array(
        'orders' => $orders,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'total_orders' => $total_orders
    );
}

// Function to get order status badge
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

// Function to get user initials
function get_user_initials($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

// Function to get gradient colors for avatar
function get_avatar_gradient($index) {
    $gradients = array(
        'from-blue-500 to-purple-600',
        'from-green-500 to-emerald-600',
        'from-purple-500 to-pink-600',
        'from-orange-500 to-red-600',
        'from-teal-500 to-cyan-600',
        'from-pink-500 to-rose-600',
        'from-indigo-500 to-blue-600',
        'from-yellow-500 to-orange-600',
        'from-red-500 to-pink-600',
        'from-cyan-500 to-blue-600',
        'from-emerald-500 to-teal-600',
        'from-rose-500 to-pink-600'
    );
    
    return $gradients[$index % count($gradients)];
}

// Get statistics
 $stats = get_order_statistics();

// Get current page
 $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Get orders (6 per page = 2 rows of 3)
 $orders_data = get_dashboard_orders($current_page, 6);

// Display error message if exists
 $error_message = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);

 $success_message = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : '';
unset($_SESSION['login_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Orders Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    body { font-family: 'Inter', sans-serif; }
    
    /* Loading spinner */
    .spinner {
      border: 3px solid rgba(0, 0, 0, 0.1);
      border-radius: 50%;
      border-top: 3px solid #3498db;
      width: 30px;
      height: 30px;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body class="bg-gray-50">

  <!-- LOGIN -->
  <section id="loginSection" class="min-h-screen flex items-center justify-center p-6 bg-gradient-to-br from-blue-50 via-white to-purple-50" <?php echo $is_logged_in ? 'style="display:none;"' : ''; ?>>
    <div class="w-full max-w-md">
      <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 border border-gray-100">
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mb-4">
            <i class="bi bi-bag-check text-white text-2xl"></i>
          </div>
          <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome back</h2>
          <p class="text-gray-500">Sign in to manage your orders</p>
        </div>

        <?php if ($error_message): ?>
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
          <i class="bi bi-exclamation-circle text-red-600 text-xl"></i>
          <div class="flex-1">
            <p class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error_message); ?></p>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
          <i class="bi bi-check-circle text-green-600 text-xl"></i>
          <div class="flex-1">
            <p class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($success_message); ?></p>
          </div>
        </div>
        <?php endif; ?>

        <form class="space-y-5" action="includes/auth-handler.php" method="POST">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email or Username</label>
            <div class="relative">
              <i class="bi bi-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input id="email" name="username" type="text" required
                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all"
                placeholder="name@company.com or username">
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <div class="relative">
              <i class="bi bi-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input id="password" name="password" type="password" required minlength="6"
                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all"
                placeholder="••••••••">
            </div>
          </div>

          <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20">
              <span class="text-gray-600">Remember me</span>
            </label>
            <a href="<?php echo wp_lostpassword_url(); ?>" class="text-blue-600 hover:text-blue-700 font-medium">
              Forgot password?
            </a>
          </div>

          <button type="submit" 
            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-medium py-3.5 rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 transition-all duration-200">
            Sign in
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- DASHBOARD -->
  <main id="dashboard" class="<?php echo !$is_logged_in ? 'hidden' : ''; ?> min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 backdrop-blur-sm bg-white/80">
      <div class="max-w-7xl mx-auto px-6 py-5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl flex items-center justify-center">
              <i class="bi bi-bag-check text-white text-lg"></i>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Orders</h1>
              <p class="text-sm text-gray-500">Manage and track all orders</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <?php if ($current_user): ?>
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 rounded-xl">
              <i class="bi bi-person-circle text-gray-700 text-xl"></i>
              <div>
                <p class="text-sm font-medium text-gray-900"><?php echo esc_html($current_user->display_name); ?></p>
                <p class="text-xs text-gray-500"><?php echo esc_html($current_user->user_email); ?></p>
              </div>
            </div>
            <a href="?action=logout" class="px-4 py-2.5 bg-red-100 hover:bg-red-200 text-red-700 font-medium rounded-xl transition-colors">
              <i class="bi bi-box-arrow-right mr-2"></i>Logout
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 border border-gray-200 hover:shadow-lg hover:shadow-gray-200/50 transition-all">
          <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
              <i class="bi bi-cart-check text-blue-600 text-xl"></i>
            </div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2.5 py-1 rounded-full">+12%</span>
          </div>
          <p class="text-gray-500 text-sm mb-1">Total Orders</p>
          <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total']); ?></p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 hover:shadow-lg hover:shadow-gray-200/50 transition-all">
          <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
              <i class="bi bi-clock-history text-yellow-600 text-xl"></i>
            </div>
            <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2.5 py-1 rounded-full"><?php echo $stats['pending']; ?></span>
          </div>
          <p class="text-gray-500 text-sm mb-1">Pending</p>
          <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending']); ?></p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 hover:shadow-lg hover:shadow-gray-200/50 transition-all">
          <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <i class="bi bi-check-circle text-green-600 text-xl"></i>
            </div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2.5 py-1 rounded-full">+8%</span>
          </div>
          <p class="text-gray-500 text-sm mb-1">Completed</p>
          <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['completed']); ?></p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-gray-200 hover:shadow-lg hover:shadow-gray-200/50 transition-all cursor-pointer hover:scale-105">
          <div class="flex items-center justify-between mb-3">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <i class="bi bi-file-earmark-excel text-green-600 text-xl"></i>
            </div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2.5 py-1 rounded-full">Export</span>
          </div>
          <p class="text-gray-500 text-sm mb-1">Export Data</p>
          <p class="text-lg font-bold text-gray-900">Export to Excel</p>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="flex flex-col md:flex-row gap-4 mb-6">
        <!-- Search Bar -->
        <div class="flex-1">
          <div class="relative">
            <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" id="searchInput" placeholder="Search by order ID, customer name, or email..." 
              onkeyup="searchOrdersWithDebounce()"
              class="w-full pl-12 pr-4 py-3.5 bg-white border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all">
          </div>
        </div>

        <!-- Filters -->
        <!--<div class="bg-white rounded-2xl p-2 border border-gray-200 inline-flex gap-2 flex-shrink-0 overflow-x-auto">-->
        <!--  <button class="filter-btn active px-5 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap" data-filter="today" onclick="filterOrders(event)">-->
        <!--    Today-->
        <!--  </button>-->
        <!--  <button class="filter-btn px-5 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap" data-filter="yesterday" onclick="filterOrders(event)">-->
        <!--    Yesterday-->
        <!--  </button>-->
        <!--  <button class="filter-btn px-5 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap" data-filter="last7" onclick="filterOrders(event)">-->
        <!--    Last 7 Days-->
        <!--  </button>-->
        <!--</div>-->
        <!-- Filters -->
<div class="bg-white rounded-2xl p-2 border border-gray-200 inline-flex gap-2 flex-shrink-0 overflow-x-auto">
    <button class="filter-btn px-5 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap text-gray-600 hover:bg-gray-100" data-filter="today" onclick="filterOrders(event)">
        Today
    </button>
    <button class="filter-btn px-5 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap text-gray-600 hover:bg-gray-100" data-filter="yesterday" onclick="filterOrders(event)">
        Yesterday
    </button>
    <button class="filter-btn px-5 py-2.5 rounded-xl text-sm font-medium transition-all whitespace-nowrap text-gray-600 hover:bg-gray-100" data-filter="last7" onclick="filterOrders(event)">
        Last 7 Days
    </button>
</div>
      </div>

      <!-- Loading Spinner -->
      <div id="loadingSpinner" class="hidden text-center py-16">
        <div class="spinner"></div>
        <p class="text-gray-500 mt-4">Loading orders...</p>
      </div>

      <!-- Orders Grid (2 rows of 3 = 6 per page) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" id="ordersGrid">
        <?php 
        if (!empty($orders_data['orders'])) {
          $index = 0;
          foreach ($orders_data['orders'] as $order) {
            $order_id = $order->get_id();
            $order_number = $order->get_order_number();
            $order_status = $order->get_status();
            $order_total = $order->get_total();
            $order_date = $order->get_date_created();
            
            // Get customer info
            $billing_first = $order->get_billing_first_name();
            $billing_last = $order->get_billing_last_name();
            $billing_email = $order->get_billing_email();
            $customer_name = trim($billing_first . ' ' . $billing_last);
            if (empty($customer_name)) {
              $customer_name = 'Guest';
            }
            
            // Get status badge
            $status_badge = get_status_badge($order_status);
            
            // Get initials and gradient
            $initials = get_user_initials($customer_name);
            $gradient = get_avatar_gradient($index);
            
            // Determine hover color based on status
            $hover_colors = array(
              'pending' => 'hover:border-yellow-300 hover:shadow-yellow-500/10',
              'processing' => 'hover:border-blue-300 hover:shadow-blue-500/10',
              'completed' => 'hover:border-green-300 hover:shadow-green-500/10',
              'on-hold' => 'hover:border-orange-300 hover:shadow-orange-500/10',
              'cancelled' => 'hover:border-red-300 hover:shadow-red-500/10',
              'refunded' => 'hover:border-purple-300 hover:shadow-purple-500/10',
              'failed' => 'hover:border-red-300 hover:shadow-red-500/10'
            );
            
            $hover_color = isset($hover_colors[$order_status]) ? $hover_colors[$order_status] : 'hover:border-gray-300 hover:shadow-gray-500/10';
            
            $index++;
        ?>
        
        <div class="group bg-white rounded-2xl p-6 border border-gray-200 <?php echo $hover_color; ?> hover:shadow-xl transition-all cursor-pointer order-card" 
          data-order-id="<?php echo esc_attr($order_number); ?>" 
          data-customer="<?php echo esc_attr($customer_name); ?>" 
          data-email="<?php echo esc_attr($billing_email); ?>" 
          data-status="<?php echo esc_attr($order_status); ?>"
          onclick="openOrderDetails(<?php echo $order_id; ?>)">
          
          <div class="flex items-start justify-between mb-4">
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Order ID</p>
              <p class="text-lg font-bold text-gray-900">#<?php echo esc_html($order_number); ?></p>
            </div>
            <span class="px-3 py-1.5 <?php echo $status_badge['class']; ?> text-xs font-semibold rounded-full">
              <?php echo $status_badge['label']; ?>
            </span>
          </div>

          <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
            <div class="w-10 h-10 bg-gradient-to-br <?php echo $gradient; ?> rounded-xl flex items-center justify-center">
              <span class="text-white font-bold text-sm"><?php echo esc_html($initials); ?></span>
            </div>
            <div>
              <p class="font-semibold text-gray-900"><?php echo esc_html($customer_name); ?></p>
              <p class="text-xs text-gray-500"><?php echo esc_html($billing_email ? $billing_email : 'No email'); ?></p>
            </div>
          </div>

          <div class="space-y-2 mb-4">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Amount</span>
              <span class="font-semibold text-gray-900"><?php echo wc_price($order_total); ?></span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-500">Date</span>
              <span class="font-medium text-gray-700"><?php echo $order_date->date_i18n('M d, Y'); ?></span>
            </div>
          </div>

          <button class="w-full bg-gray-900 hover:bg-gray-800 text-white font-medium py-3 rounded-xl transition-colors group-hover:bg-blue-600 group-hover:shadow-lg group-hover:shadow-blue-500/25">
            <i class="bi bi-box-arrow-up-right mr-2"></i>View Details
          </button>
        </div>
        
        <?php 
          }
        } else {
        ?>
        
        <div class="col-span-full text-center py-16">
          <i class="bi bi-inbox text-6xl text-gray-300 mb-4"></i>
          <h3 class="text-xl font-bold text-gray-900 mb-2">No orders found</h3>
          <p class="text-gray-500">There are no orders in your store yet.</p>
        </div>
        
        <?php } ?>
      </div>

      <!-- No Results Message -->
      <div id="noResults" class="hidden text-center py-16">
        <i class="bi bi-search text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-bold text-gray-900 mb-2">No orders found</h3>
        <p class="text-gray-500">Try adjusting your search or filters</p>
      </div>

      <!-- Pagination -->
      <div id="paginationContainer" class="flex flex-col items-center gap-4 mt-8">
        <!-- Pagination Info -->
        <div class="text-sm text-gray-600">
          Showing <?php echo (($orders_data['current_page'] - 1) * 6) + 1; ?> to <?php echo min($orders_data['current_page'] * 6, $orders_data['total_orders']); ?> of <?php echo $orders_data['total_orders']; ?> orders
        </div>
        
        <!-- Pagination Buttons -->
        <div class="flex items-center gap-2">
          <?php if ($current_page > 1) { ?>
          <button class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="<?php echo ($current_page - 1); ?>">
            <i class="bi bi-chevron-left"></i>
          </button>
          <?php } else { ?>
          <button class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-400 cursor-not-allowed" disabled>
            <i class="bi bi-chevron-left"></i>
          </button>
          <?php } ?>
          
          <?php 
          // Show max 5 page numbers
          $start_page = max(1, $current_page - 2);
          $end_page = min($orders_data['total_pages'], $current_page + 2);
          
          // Show first page if not in range
          if ($start_page > 1) {
          ?>
          <button class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="1">1</button>
          <?php 
            if ($start_page > 2) {
          ?>
          <span class="px-2 text-gray-400">...</span>
          <?php 
            }
          }
          
          for ($i = $start_page; $i <= $end_page; $i++) { 
            if ($i == $current_page) {
          ?>
          <button class="px-4 py-2 bg-blue-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/25"><?php echo $i; ?></button>
          <?php } else { ?>
          <button class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
          <?php 
            }
          } 
          
          // Show last page if not in range
          if ($end_page < $orders_data['total_pages']) {
            if ($end_page < $orders_data['total_pages'] - 1) {
          ?>
          <span class="px-2 text-gray-400">...</span>
          <?php 
            }
          ?>
          <button class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="<?php echo $orders_data['total_pages']; ?>"><?php echo $orders_data['total_pages']; ?></button>
          <?php 
          }
          ?>
          
          <?php if ($current_page < $orders_data['total_pages']) { ?>
          <button class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="<?php echo ($current_page + 1); ?>">
            <i class="bi bi-chevron-right"></i>
          </button>
          <?php } else { ?>
          <button class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-400 cursor-not-allowed" disabled>
            <i class="bi bi-chevron-right"></i>
          </button>
          <?php } ?>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Global variables
    let currentPage = <?php echo $current_page; ?>;
    let totalPages = <?php echo $orders_data['total_pages']; ?>;
    let searchTimeout;
    let currentFilter = '';
    
    // Status badge function
    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="px-3 py-1.5 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-full">Pending</span>',
            'processing': '<span class="px-3 py-1.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">Processing</span>',
            'on-hold': '<span class="px-3 py-1.5 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full">On Hold</span>',
            'completed': '<span class="px-3 py-1.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">Completed</span>',
            'cancelled': '<span class="px-3 py-1.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Cancelled</span>',
            'refunded': '<span class="px-3 py-1.5 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full">Refunded</span>',
            'failed': '<span class="px-3 py-1.5 bg-red-100 text-red-700 text-xs font-semibold rounded-full">Failed</span>'
        };
        status = status.replace('wc-', '');
        return badges[status] || '<span class="px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-full">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }
    
    // Gradient function
    function getAvatarGradient(index) {
        const gradients = [
            'from-blue-500 to-purple-600',
            'from-green-500 to-emerald-600',
            'from-purple-500 to-pink-600',
            'from-orange-500 to-red-600',
            'from-teal-500 to-cyan-600',
            'from-pink-500 to-rose-600'
        ];
        return gradients[index % gradients.length];
    }
    
    // Hover color function
    function getHoverColor(status) {
        const hoverColors = {
            'pending': 'hover:border-yellow-300 hover:shadow-yellow-500/10',
            'processing': 'hover:border-blue-300 hover:shadow-blue-500/10',
            'completed': 'hover:border-green-300 hover:shadow-green-500/10',
            'on-hold': 'hover:border-orange-300 hover:shadow-orange-500/10',
            'cancelled': 'hover:border-red-300 hover:shadow-red-500/10',
            'refunded': 'hover:border-purple-300 hover:shadow-purple-500/10',
            'failed': 'hover:border-red-300 hover:shadow-red-500/10'
        };
        status = status.replace('wc-', '');
        return hoverColors[status] || 'hover:border-gray-300 hover:shadow-gray-500/10';
    }
    
    // Initials function
    function getUserInitials(name) {
        const words = name.split(' ');
        if (words.length >= 2) {
            return words[0].substring(0, 1).toUpperCase() + words[1].substring(0, 1).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }
    
    // Render orders function
    function renderOrderCards(orders, filter = '') {
        const ordersGrid = document.getElementById('ordersGrid');
        ordersGrid.innerHTML = '';
        
        if (orders.length === 0) {
            let message = 'There are no orders matching your criteria.';
            if (filter === 'today') {
                message = 'No orders found for today.';
            } else if (filter === 'yesterday') {
                message = 'No orders found for yesterday.';
            } else if (filter === 'last7') {
                message = 'No orders found in the last 7 days.';
            }
            
            ordersGrid.innerHTML = `
                <div class="col-span-full text-center py-16">
                    <i class="bi bi-inbox text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No orders found</h3>
                    <p class="text-gray-500">${message}</p>
                </div>
            `;
            return;
        }
        
        orders.forEach((order, index) => {
            const initials = getUserInitials(order.customer_name);
            const gradient = getAvatarGradient(index);
            const hoverColor = getHoverColor(order.status);
            const statusBadge = getStatusBadge(order.status);
            
            const orderCard = document.createElement('div');
            orderCard.className = `group bg-white rounded-2xl p-6 border border-gray-200 ${hoverColor} hover:shadow-xl transition-all cursor-pointer order-card`;
            orderCard.setAttribute('data-order-id', order.order_number);
            orderCard.setAttribute('data-customer', order.customer_name);
            orderCard.setAttribute('data-email', order.email);
            orderCard.setAttribute('data-status', order.status);
            
            // Add click event listener to card
            orderCard.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openOrderDetails(order.id);
            });
            
            orderCard.innerHTML = `
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Order ID</p>
                        <p class="text-lg font-bold text-gray-900">#${order.order_number}</p>
                    </div>
                    ${statusBadge}
                </div>
                <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                    <div class="w-10 h-10 bg-gradient-to-br ${gradient} rounded-xl flex items-center justify-center">
                        <span class="text-white font-bold text-sm">${initials}</span>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">${order.customer_name}</p>
                        <p class="text-xs text-gray-500">${order.email || 'No email'}</p>
                    </div>
                </div>
                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Amount</span>
                        <span class="font-semibold text-gray-900">${order.total}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Date</span>
                        <span class="font-medium text-gray-700">${order.date}</span>
                    </div>
                </div>
                <button class="view-details-btn w-full bg-gray-900 hover:bg-gray-800 text-white font-medium py-3 rounded-xl transition-colors group-hover:bg-blue-600 group-hover:shadow-lg group-hover:shadow-blue-500/25">
                    <i class="bi bi-box-arrow-up-right mr-2"></i>View Details
                </button>
            `;
            
            // Add click event listener to button
            const viewDetailsBtn = orderCard.querySelector('.view-details-btn');
            viewDetailsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openOrderDetails(order.id);
            });
            
            ordersGrid.appendChild(orderCard);
        });
    }
    
    // Render pagination function
    function renderPagination(currentPage, totalPages, totalOrders) {
        const paginationContainer = document.getElementById('paginationContainer');
        
        const startItem = ((currentPage - 1) * 6) + 1;
        const endItem = Math.min(currentPage * 6, totalOrders);
        
        let paginationHTML = `
            <div class="text-sm text-gray-600">
                Showing ${startItem} to ${endItem} of ${totalOrders} orders
            </div>
            <div class="flex items-center gap-2">
        `;
        
        // Previous button
        if (currentPage > 1) {
            paginationHTML += `<button type="button" class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="${currentPage - 1}">
                <i class="bi bi-chevron-left"></i>
            </button>`;
        } else {
            paginationHTML += `<button type="button" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-400 cursor-not-allowed" disabled>
                <i class="bi bi-chevron-left"></i>
            </button>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            paginationHTML += `<button type="button" class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="1">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span class="px-2 text-gray-400">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                paginationHTML += `<button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-medium shadow-lg shadow-blue-500/25">${i}</button>`;
            } else {
                paginationHTML += `<button type="button" class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="${i}">${i}</button>`;
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<span class="px-2 text-gray-400">...</span>`;
            }
            paginationHTML += `<button type="button" class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="${totalPages}">${totalPages}</button>`;
        }
        
        // Next button
        if (currentPage < totalPages) {
            paginationHTML += `<button type="button" class="pagination-btn px-4 py-2 bg-white border border-gray-200 hover:border-blue-300 rounded-xl text-gray-700 hover:text-blue-600 transition-colors" data-page="${currentPage + 1}">
                <i class="bi bi-chevron-right"></i>
            </button>`;
        } else {
            paginationHTML += `<button type="button" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-400 cursor-not-allowed" disabled>
                <i class="bi bi-chevron-right"></i>
            </button>`;
        }
        
        paginationHTML += `</div>`;
        paginationContainer.innerHTML = paginationHTML;
        
        // Add event listeners
        document.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                loadOrders(page, false, currentFilter);
            });
        });
    }
    
    // Loading functions
    function showLoading() {
        document.getElementById('loadingSpinner').classList.remove('hidden');
        document.getElementById('ordersGrid').classList.add('hidden');
        document.getElementById('paginationContainer').classList.add('hidden');
    }
    
    function hideLoading() {
        document.getElementById('loadingSpinner').classList.add('hidden');
        document.getElementById('ordersGrid').classList.remove('hidden');
        document.getElementById('paginationContainer').classList.remove('hidden');
    }
    
    // Load orders function
    function loadOrders(page, isSearch = false, filter = '') {
        showLoading();
        
        const formData = new FormData();
        formData.append('action', isSearch ? 'search_orders' : 'get_orders');
        formData.append('page', page);
        
        if (filter) {
            formData.append('filter', filter);
        }
        
        if (isSearch) {
            const searchTerm = document.getElementById('searchInput').value;
            formData.append('search', searchTerm);
        }
        
        fetch('includes/ajax-pagination.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentPage = page;
                totalPages = data.total_pages;
                currentFilter = data.filter || '';
                
                renderOrderCards(data.orders, currentFilter);
                renderPagination(page, data.total_pages, data.total_orders);
                
                // Update URL
                const url = new URL(window.location);
                url.searchParams.set('paged', page);
                if (filter) {
                    url.searchParams.set('filter', filter);
                } else {
                    url.searchParams.delete('filter');
                }
                window.history.pushState({}, '', url);
            } else {
                console.error('Error loading orders:', data.message);
                alert('Error loading orders: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Network error: ' + error.message);
        })
        .finally(() => {
            hideLoading();
        });
    }
    
    // Search with debounce
    function searchOrdersWithDebounce() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = document.getElementById('searchInput').value.trim();
            
            if (searchTerm === '') {
                loadOrders(1, false, currentFilter);
            } else {
                loadOrders(1, true, currentFilter);
            }
        }, 500);
    }
    
    // Filter function
    function filterOrders(ev) {
        // Update button styles
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-gray-900', 'text-white', 'shadow-lg');
            btn.classList.add('text-gray-600', 'hover:bg-gray-100');
        });
        
        const filter = ev.currentTarget.dataset.filter;
        
        if (currentFilter === filter) {
            // If clicking the same filter, deselect it
            ev.currentTarget.classList.remove('active', 'bg-gray-900', 'text-white', 'shadow-lg');
            ev.currentTarget.classList.add('text-gray-600', 'hover:bg-gray-100');
            currentFilter = '';
            loadOrders(1, false, '');
        } else {
            // Select new filter
            ev.currentTarget.classList.add('active', 'bg-gray-900', 'text-white', 'shadow-lg');
            ev.currentTarget.classList.remove('text-gray-600', 'hover:bg-gray-100');
            currentFilter = filter;
            loadOrders(1, false, filter);
        }
    }
    
    // Open order details - Direct navigation
    function openOrderDetails(orderId) {
        console.log('Opening order details for:', orderId);
        // Directly navigate to order details page
        window.location.href = 'order-details.php?order_id=' + orderId;
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Set active filter style if filter is in URL
        const urlParams = new URLSearchParams(window.location.search);
        const urlFilter = urlParams.get('filter');
        
        if (urlFilter) {
            currentFilter = urlFilter;
            const filterBtn = document.querySelector(`.filter-btn[data-filter="${urlFilter}"]`);
            if (filterBtn) {
                filterBtn.classList.add('active', 'bg-gray-900', 'text-white', 'shadow-lg');
                filterBtn.classList.remove('text-gray-600', 'hover:bg-gray-100');
            }
        }
        
        // Add event listeners to pagination buttons
        document.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.getAttribute('data-page'));
                loadOrders(page, false, currentFilter);
            });
        });
    });
</script>
</body>
</html>