<?php
/**
 * Plugin Name: Bulk Update Invoices
 * Description: Updates invoices with date prior to invoice #68 (ID 173) to paid status
 * Version: 1.0
 * Author: Admin
 */

add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=wp_invoice',
        'Bulk Update Invoices',
        'Bulk Update',
        'manage_options',
        'bulk-update-invoices',
        'bulk_update_invoices_page'
    );
});

function bulk_update_invoices_page() {
    ?>
    <div class="wrap">
        <h1>Bulk Update Invoices to Paid</h1>
        <p>This will update all invoices with date prior to invoice #68 (ID 173) to "paid" status.</p>
        <?php
        if (isset($_POST['update_invoices']) && check_admin_referer('bulk_update_invoices_nonce')) {
            // Get invoice 173's date
            $invoice_173_date = get_post_meta(173, '_invoice_date', true);
            if (!$invoice_173_date) {
                echo '<div class="error"><p>Could not find date for invoice 173</p></div>';
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
            echo '<p><strong>Total updated: ' . $updated . ' invoices</strong></p>';
        } else {
            ?>
            <form method="post" action="">
                <?php wp_nonce_field('bulk_update_invoices_nonce'); ?>
                <p>
                    <input type="submit" name="update_invoices" class="button button-primary" value="Update Invoices to Paid" onclick="return confirm('Are you sure you want to update these invoices?');">
                </p>
            </form>
            <?php
        }
        ?>
    </div>
    <?php
}
