<?php

/**
 * Plugin Name: Moyasar Payment Gateway
 * Plugin URI: https://www.moyasar.com/
 * Description: Adds credit card, Apple Pay, and STC Pay payment capabilities to Woocommerce. <div>Icons made by <a href="https://icon54.com/" title="Pixel perfect">Pixel perfect</a> from <a href="https://www.flaticon.com/" title="Flaticon">www.flaticon.com</a></div>
 * Version: 4.5.0
 * Requires at least: 4.6
 * Requires PHP: 5.6
 * WC tested up to: 5.6.0
 * Author: Moyasar Development Team
 * Author URI: https://www.moyasar.com/
 */

if (defined('MOYASAR_PAYMENT_VERSION')) {
    return;
}

define('MOYASAR_PAYMENT_VERSION', '4.5.0');
define('MOYASAR_PLUGIN_MIN_PHP_VER', '5.6.0');
define('MOYASAR_PLUGIN_MIN_WC_VER',  '3.0');
define('MOYASAR_PAYMENT_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
define('MOYASAR_PAYMENT_URL', untrailingslashit(plugin_dir_url(__FILE__)));
define('MOYASAR_PAYMENT_PLUGIN_DIR_NAME', basename(MOYASAR_PAYMENT_DIR));
define('MOYASAR_API_BASE_URL', 'https://api.moyasar.com');

function woocommerce_moyasar_notice_missing()
{
    $message = sprintf( esc_html__( 'Moyasar Payment Gateway requires WooCommerce to be installed and active. You can download %s here.', 'moyasar-payments-text' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' );
    echo '<div class="error"><p><strong>' . $message . '</strong></p></div>';
}

function woocommerce_moyasar_notice_unsupported()
{
    $message = sprintf( esc_html__( 'Moyasar Payment Gateway is disabled. WooCommerce %2$s is not supported.', 'moyasar-payments-text' ), WC_VERSION );
    echo '<div class="error"><p><strong>' . $message . '</strong></p></div>';
}

add_action('plugins_loaded', 'woocommerce_moyasar_init_plugin');

function woocommerce_moyasar_init_plugin()
{
    // Load Utils
    require_once 'utils/helpers.php';
    require_once 'utils/class-moyasar-quick-http.php';

    // Load texts
    load_plugin_textdomain( 'moyasar-payments-text', false, MOYASAR_PAYMENT_PLUGIN_DIR_NAME . '/i18n/languages');

    // If Woocommerce cannot be detected, add a notice that Moyasar Payment Plugin requires it
    if (! class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'woocommerce_moyasar_notice_missing');
        return;
    }

    if (version_compare(WC_VERSION, MOYASAR_PLUGIN_MIN_WC_VER, '<')) {
        add_action('admin_notices', 'woocommerce_moyasar_notice_unsupported');
        return;
    }

    // Load Dependencies
    require_once 'services/class-wc-moyasar-payment-service.php';
    require_once 'gateways/class-wc-moyasar-payment-form.php';
    require_once 'controllers/class-wc-controller-moyasar-payment.php';
    require_once 'controllers/class-wc-controller-moyasar-return.php';
    require_once 'controllers/class-wc-controller-moyasar-apple-pay-register.php';

    // Init REST Services
    WC_Controller_Moyasar_Payment::init();
    WC_Controller_Moyasar_Return::init();
    WC_Controller_Moyasar_Apple_Pay_Register::init();
}

add_filter('woocommerce_payment_gateways', 'add_moyasar_register_gateway');

function add_moyasar_register_gateway($methods)
{
    $methods[] = 'WC_Gateway_Moyasar_Payment_Form';
    return $methods;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__ ), 'add_moyasar_action_links');

function add_moyasar_action_links($links)
{
    $links[] = '<a href="'. wc_admin_url('&page=wc-settings&tab=checkout&section=moyasar-form') .'">' . __('Gateway Settings', 'moyasar-payments-text') . '</a>';
    return $links;
}
