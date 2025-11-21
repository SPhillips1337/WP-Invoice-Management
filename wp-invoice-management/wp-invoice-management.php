<?php
/**
 * Plugin Name: WP Invoice Management
 * Plugin URI:  https://example.com/wp-invoice-management
 * Description: Multi user invoicing functionality similar to invoice-generator.com
 * Version:     0.1.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPLv2 or later
 * Text Domain: wp-invoice-management
 * Domain Path: /languages
 */

use WpInvoiceManagement\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

function run_wp_invoice_management() {
    $plugin = Plugin::instance();
    $plugin->run();
}
run_wp_invoice_management();
