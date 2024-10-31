<?php
/*
 * Plugin Name: OrkestaPay
 * Plugin URI: https://wordpress.org/plugins/orkestapay/
 * Description: Orchestrate multiple payment gateways for a frictionless, reliable, and secure checkout experience.
 * Author: Zenkipay
 * Author URI: https://orkestapay.com
 * Version: 1.0.2
 * Requires at least: 5.8
 * Tested up to: 6.6.2
 * WC requires at least: 6.8
 * WC tested up to: 9.3.3
 * Requires Plugins: woocommerce
 * Text Domain: orkestapay
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
    exit();
}

define('ORKESTAPAY_WC_PLUGIN_FILE', __FILE__);
define('ORKESTAPAY_API_URL', 'https://api.orkestapay.com');
define('ORKESTAPAY_API_SAND_URL', 'https://api.sand.orkestapay.com');

// Languages traslation
load_plugin_textdomain('orkestapay', false, dirname(plugin_basename(__FILE__)) . '/languages/');

/**
 * Custom function to declare compatibility
 */
add_action('before_woocommerce_init', function () {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare HPOS compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);

        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// This action hook registers our PHP class as a WooCommerce payment gateway
add_action('plugins_loaded', 'orkestapay_init_gateway_class', 0);
function orkestapay_init_gateway_class()
{
    // If the WC payment gateway class
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    include plugin_dir_path(__FILE__) . 'includes/class-orkestapay-logger.php';
    include plugin_dir_path(__FILE__) . 'includes/class-orkestapay-api.php';
    include plugin_dir_path(__FILE__) . 'includes/class-orkestapay-helper.php';
    include plugin_dir_path(__FILE__) . 'includes/class-orkestapay.php';

    // Add custom action links
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'orkestapay_plugin_action_links');
    function orkestapay_plugin_action_links($links)
    {
        $settings_url = esc_url(get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout&section=orkestapay'));
        array_unshift($links, "<a title='OrkestaPay Settings Page' href='$settings_url'>" . __('Settings', 'orkestapay') . '</a>');

        return $links;
    }
}

/**
 * Add the Gateway to WooCommerce
 *
 * @return Array Gateway list with our gateway added
 */
add_filter('woocommerce_payment_gateways', 'orkestapay_add_gateway_class');
function orkestapay_add_gateway_class($gateways)
{
    $gateways[] = 'OrkestaPay_Gateway';
    return $gateways;
}

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action('woocommerce_blocks_loaded', 'orkestapay_block_support');
function orkestapay_block_support()
{
    // Check if the required class exists
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'includes/class-orkestapay-blocks-support.php';

    // Registering the PHP class we have just included
    add_action('woocommerce_blocks_payment_method_type_registration', function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
        // Register an instance of Orkestapay_Blocks_Support
        $payment_method_registry->register(new Orkestapay_Blocks_Support());
    });
}

add_filter('woocommerce_payment_complete_order_status', 'orkestapay_complete_order_status', 10, 2);
function orkestapay_complete_order_status($status, $order_id)
{
    $order = wc_get_order($order_id);
    $virtual_order = true;
    foreach ($order->get_items() as $item) {
        if (!$item->is_type('line_item')) {
            continue;
        }
        // Verificar si el producto no es virtual o descargable
        $product = $item->get_product();
        if (!$product->is_virtual() && !$product->is_downloadable()) {
            $virtual_order = false;
            break;
        }
    }

    if ($virtual_order) {
        return 'completed'; // Cambia a "completed" para pedidos virtuales/descargables
    }

    return $status; // Mantener el estado por defecto
}
