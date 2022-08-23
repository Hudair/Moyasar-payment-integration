<?php
/*
Plugin Name: PmP Fondy Payment
Plugin URI: https://fondy.io/gb/plugins/paid-memberships-pro/
Description: Fondy Gateway for Paid Memberships Pro
Version: 1.0.9
Domain Path: /languages
Text Domain: pmp-fondy-payment
Requires at least: 2.5
Requires PHP: 5.6
Author: Fondy
Author URI: https://fondy.io/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/


if (!class_exists('PMProGateway')) {
    return; // mb add dismissible error notice
}

define("PMPRO_FONDY_DIR", dirname(__FILE__));
define("PMPRO_FONDY_BASE_FILE", __FILE__);
define("PMPRO_FONDY_VERSION", '1.0.9');

register_activation_hook(__FILE__, 'PMProGateway_fondy::install');
register_uninstall_hook(__FILE__, 'PMProGateway_fondy::uninstall');
add_action('init', array('PMProGateway_fondy', 'init'));

require_once(PMPRO_FONDY_DIR . "/classes/class.pmprogateway_fondy.php");
require_once(PMPRO_FONDY_DIR . "/services/services.php");
