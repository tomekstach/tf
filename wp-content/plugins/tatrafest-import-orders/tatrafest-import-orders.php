<?php
/**
 * Plugin Name: Tatrafest Import Orders
 * Plugin URI: https://tatrafest.pl
 * Description: Importuje zamowienia z tabel starting_list, starting_events i starting_distances z prefiksem WordPressa do WooCommerce
 * Version: 1.0.0
 * Author: AstoSoft
 * Author URI: https://astosoft.pl
 * License: GPL-2.0+
 * Text Domain: tatrafest-import-orders
 * Domain Path: /languages
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TATRAFEST_IMPORT_ORDERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TATRAFEST_IMPORT_ORDERS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TATRAFEST_IMPORT_ORDERS_VERSION', '1.0.0');

// Include necessary files
require_once TATRAFEST_IMPORT_ORDERS_PLUGIN_DIR . 'includes/admin-page.php';
require_once TATRAFEST_IMPORT_ORDERS_PLUGIN_DIR . 'includes/import-logic.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'tatrafest_import_orders_activate');
function tatrafest_import_orders_activate()
{
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Ten plugin wymaga aktywnego WooCommerce');
    }
}

// Add admin menu
add_action('admin_menu', 'tatrafest_import_orders_add_admin_menu');
function tatrafest_import_orders_add_admin_menu()
{
    add_menu_page(
        'Tatrafest Import Zamowien',
        'Tatrafest Import',
        'manage_options',
        'tatrafest-import-orders',
        'tatrafest_import_orders_render_admin_page',
        'dashicons-upload',
        56
    );
}

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', 'tatrafest_import_orders_enqueue_admin_assets');
function tatrafest_import_orders_enqueue_admin_assets($hook)
{
    if ('toplevel_page_tatrafest-import-orders' !== $hook) {
        return;
    }

    wp_enqueue_style(
        'tatrafest-import-orders-style',
        TATRAFEST_IMPORT_ORDERS_PLUGIN_URL . 'assets/admin-style.css',
        [],
        TATRAFEST_IMPORT_ORDERS_VERSION
    );

    wp_enqueue_script(
        'tatrafest-import-orders-script',
        TATRAFEST_IMPORT_ORDERS_PLUGIN_URL . 'assets/admin-script.js',
        ['jquery'],
        TATRAFEST_IMPORT_ORDERS_VERSION,
        true
    );

    wp_localize_script('tatrafest-import-orders-script', 'tatrafestImportOrders', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tatrafest_import_orders_nonce'),
    ]);
}

// Handle AJAX request for import
add_action('wp_ajax_tatrafest_import_order', 'tatrafest_import_orders_handle_ajax');
function tatrafest_import_orders_handle_ajax()
{
    check_ajax_referer('tatrafest_import_orders_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Brak uprawnien']);
    }

    $order_number = isset($_POST['orderNumber']) ? sanitize_text_field($_POST['orderNumber']) : '';

    if (empty($order_number)) {
        wp_send_json_error(['message' => 'Prosze podac numer zamowienia']);
    }

    $result = tatrafest_import_order_by_number($order_number);

    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
