<?php
/**
 * Update invoices with date prior to invoice #68 (ID 173) to paid status
 * Run this file by placing it in WordPress root and accessing via browser
 * OR use: php -r "require('wp-load.php');" from WordPress root
 */

// Try to find wp-load.php
$wp_load_paths = array(
    dirname(__FILE__) . '/wp-load.php',
    dirname(__FILE__) . '/../wp-load.php',
    '/var/www/html/wp-load.php',
    '/home/stephen/Documents/www/wp-load.php',
);

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Could not find wp-load.php. Please place this file in WordPress root directory.');
}

// Check permissions
if (!current_user_can('manage_options')) {
    die('Insufficient permissions. Please log in as admin.');
}

// Get invoice 173's date
$invoice_173_date = get_post_meta(173, '_invoice_date', true);
if (!$invoice_173_date) {
    die('Could not find date for invoice 173');
}

echo "<h3>Invoice 173 date: " . esc_html($invoice_173_date) . "</h3>";

// Get all invoices with date prior to invoice 173's date
$args = array(
    'post_type' => 'wp_invoice',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'meta_query' => array(
        array(
            'key' => '_invoice_date',
            'value' => $invoice_173_date,
            'compare' => '<',
            'type' => 'DATE'
        )
    )
);

$invoices = get_posts($args);
echo "<p>Found " . count($invoices) . " invoices with date prior to " . esc_html($invoice_173_date) . "</p>";

$updated = 0;
echo "<ul>";
foreach ($invoices as $invoice) {
    $current_status = get_post_meta($invoice->ID, '_invoice_status', true) ?: 'open';
    $invoice_date = get_post_meta($invoice->ID, '_invoice_date', true);
    
    if ($current_status !== 'paid') {
        update_post_meta($invoice->ID, '_invoice_status', 'paid');
        echo "<li>Updated Invoice #" . $invoice->ID . " (Date: " . esc_html($invoice_date) . ", Status: " . esc_html($current_status) . " -> paid)</li>";
        $updated++;
    } else {
        echo "<li>Skipped Invoice #" . $invoice->ID . " (Date: " . esc_html($invoice_date) . ", Already paid)</li>";
    }
}
echo "</ul>";
echo "<p><strong>Total updated: " . $updated . " invoices</strong></p>";
