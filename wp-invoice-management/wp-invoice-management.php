<?php
/**
 * Plugin Name: WP Invoice Management
 * Plugin URI:  https://example.com/wp-invoice-management
 * Description: Multi user invoicing functionality similar to invoice-generator.com
 * Version:     0.3.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * License:     GPLv2 or later
 * Text Domain: wp-invoice-management
 * Domain Path: /languages
 */

define( 'WPIM_VERSION', '0.3.1' );

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/CPT/Invoice.php';
require_once __DIR__ . '/src/CPT/Customer.php';
require_once __DIR__ . '/src/Lib/Importer.php';
require_once __DIR__ . '/src/Admin/ImportPage.php';
require_once __DIR__ . '/src/API/REST_API.php';
require_once __DIR__ . '/src/Plugin.php';

use Wpim\Invoice\Plugin;

function run_wp_invoice_management() {
    $plugin = Plugin::instance();
    $plugin->run();
}
add_action( 'plugins_loaded', 'run_wp_invoice_management', 5 );

add_action( 'admin_menu', function() {
    error_log( 'WP Invoice: admin_menu fired, CPT should be registered' );
} );
