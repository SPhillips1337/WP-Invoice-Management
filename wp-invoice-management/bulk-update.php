<?php
/**
 * Bulk update invoices to paid status
 * Access via: http://localhost:9992/wp-content/plugins/wp-invoice-management/bulk-update.php
 */

// Bootstrap WordPress from the plugin directory
$wp_load_paths = array(
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../wp-load.php',
    dirname(__FILE__) . '/../../../../wp-load.php',
);

foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!defined('ABSPATH')) {
    die('Could not load WordPress. Please place this file correctly.');
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Bulk Update Invoices</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .updated { background: #d4edda; padding: 10px; border-radius: 4px; }
        .notice { background: #fff3cd; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Bulk Update Invoices to Paid Status</h1>
    
    <?php
    if (isset($_POST['update']) && check_admin_referer('bulk_update_invoices')) {
        // Get invoice 173's date
        $invoice_173_date = get_post_meta(173, '_invoice_date', true);
        
        if (!$invoice_173_date) {
            echo '<div class="notice"><p>Could not find date for invoice 173</p></div>';
            return;
        }
        
        echo '<p>Invoice 173 date: <strong>' . esc_html($invoice_173_date) . '</strong></p>';
        
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
        echo '<p>Found ' . count($invoices) . ' invoices with date prior to ' . esc_html($invoice_173_date) . '</p>';
        
        $updated = 0;
        echo '<ul>';
        foreach ($invoices as $invoice) {
            $current_status = get_post_meta($invoice->ID, '_invoice_status', true) ?: 'open';
            $invoice_date = get_post_meta($invoice->ID, '_invoice_date', true);
            
            if ($current_status !== 'paid') {
                update_post_meta($invoice->ID, '_invoice_status', 'paid');
                echo '<li>Updated Invoice #' . $invoice->ID . ' (Date: ' . esc_html($invoice_date) . ', Status: ' . esc_html($current_status) . ' -> paid)</li>';
                $updated++;
            } else {
                echo '<li>Skipped Invoice #' . $invoice->ID . ' (Date: ' . esc_html($invoice_date) . ', Already paid)</li>';
            }
        }
        echo '</ul>';
        echo '<div class="updated"><p><strong>Total updated: ' . $updated . ' invoices</strong></p></div>';
        
    } else {
        // Show preview
        $invoice_173_date = get_post_meta(173, '_invoice_date', true);
        
        if ($invoice_173_date) {
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
            
            echo '<div class="notice">';
            echo '<p>This will update <strong>' . count($invoices) . '</strong> invoices (with date prior to ' . esc_html($invoice_173_date) . ') to "paid" status.</p>';
            echo '</div>';
            
            if (count($invoices) > 0) {
                echo '<form method="post" action="">';
                wp_nonce_field('bulk_update_invoices');
                echo '<p><input type="submit" name="update" value="Update Invoices to Paid" class="button button-primary" onclick="return confirm(\'Are you sure?\');"></p>';
                echo '</form>';
            }
        }
    }
    ?>
</body>
</html>
