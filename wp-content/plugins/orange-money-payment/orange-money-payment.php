<?php
/**
 * Plugin Name: Orange Money Payment Gateway
 * Plugin URI: https://developer.orange.com
 * Description: Intégration Orange Money pour WooCommerce 
 * Version: 1.0.0
 * Author: David DEMBELE / ABDOULAYE CISSE
 * Text Domain: orange-money-payment
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OM_PAYMENT_VERSION', '1.0.0');
define('OM_PAYMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OM_PAYMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OM_PAYMENT_PLUGIN_BASENAME', plugin_basename(__FILE__));

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'om_payment_woocommerce_missing_notice');
    return;
}

function om_payment_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>' . 
         esc_html__('Orange Money Payment Gateway nécessite WooCommerce pour fonctionner.', 'orange-money-payment') . 
         '</strong></p></div>';
}

add_action('plugins_loaded', 'om_payment_init', 11);

function om_payment_init() {
    load_plugin_textdomain('orange-money-payment', false, dirname(OM_PAYMENT_PLUGIN_BASENAME) . '/languages');
    
    require_once OM_PAYMENT_PLUGIN_DIR . 'includes/class-om-logger.php';
    require_once OM_PAYMENT_PLUGIN_DIR . 'includes/class-om-api-client.php';
    require_once OM_PAYMENT_PLUGIN_DIR . 'includes/class-om-payment-gateway.php';
    require_once OM_PAYMENT_PLUGIN_DIR . 'includes/class-om-webhook-handler.php';
    require_once OM_PAYMENT_PLUGIN_DIR . 'includes/class-om-ajax-handler.php';
    require_once OM_PAYMENT_PLUGIN_DIR . 'includes/class-om-hooks.php';
    
    add_action('woocommerce_blocks_loaded', 'om_payment_blocks_support');
    add_filter('woocommerce_payment_gateways', 'om_payment_add_gateway');
    add_filter('woocommerce_available_payment_gateways', 'om_force_gateway_available', 9999);
}

function om_payment_blocks_support() {
    if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        require_once OM_PAYMENT_PLUGIN_DIR . 'includes/class-om-blocks-support.php';
        
        add_action('woocommerce_blocks_payment_method_type_registration', function($payment_method_registry) {
            $payment_method_registry->register(new WC_Orange_Money_Blocks_Support());
        });
    }
}

function om_payment_add_gateway($gateways) {
    $gateways[] = 'WC_Orange_Money_Gateway';
    return $gateways;
}

function om_force_gateway_available($gateways) {
    $all_gateways = WC()->payment_gateways->payment_gateways();
    
    if (isset($all_gateways['orange_money']) && $all_gateways['orange_money']->enabled === 'yes') {
        $gateways['orange_money'] = $all_gateways['orange_money'];
    }
    
    return $gateways;
}

add_action('admin_enqueue_scripts', 'om_payment_admin_scripts');

function om_payment_admin_scripts($hook) {
    if ('woocommerce_page_wc-settings' !== $hook || !isset($_GET['section']) || $_GET['section'] !== 'orange_money') {
        return;
    }
    
    wp_enqueue_style('om-payment-admin', OM_PAYMENT_PLUGIN_URL . 'assets/css/admin.css', array(), OM_PAYMENT_VERSION);
    wp_enqueue_script('om-payment-admin', OM_PAYMENT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), OM_PAYMENT_VERSION, true);
    
    wp_localize_script('om-payment-admin', 'om_admin_params', array(
        'nonce' => wp_create_nonce('om_test_connection'),
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}

add_action('wp_enqueue_scripts', 'om_payment_frontend_scripts');

function om_payment_frontend_scripts() {
    if (is_checkout() || is_cart()) {
        wp_enqueue_style('om-payment-frontend', OM_PAYMENT_PLUGIN_URL . 'assets/css/frontend.css', array(), OM_PAYMENT_VERSION);
    }
}

add_filter('plugin_action_links_' . OM_PAYMENT_PLUGIN_BASENAME, 'om_payment_plugin_action_links');

function om_payment_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=orange_money') . '">' . 
                     esc_html__('Paramètres', 'orange-money-payment') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

register_activation_hook(__FILE__, 'om_payment_activate');

function om_payment_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'orange_money_transactions';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        pay_token varchar(255) NOT NULL,
        notif_token varchar(255) NOT NULL,
        txnid varchar(100) DEFAULT NULL,
        status varchar(50) NOT NULL,
        amount decimal(10,2) NOT NULL,
        currency varchar(10) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY pay_token (pay_token),
        KEY txnid (txnid)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    if (!get_option('om_payment_version')) {
        add_option('om_payment_version', OM_PAYMENT_VERSION);
    }
}