<?php
/**
 * Invoice Dashboard Template
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$editor_url = add_query_arg( 'invoice_editor', '1', home_url() );
?>
<div id="wp-invoice-dashboard-app" class="wp-invoice-premium-ui">
    <div class="wp-invoice-header">
        <div class="wp-invoice-container">
            <div class="wp-invoice-header-inner">
                <h1><a href="<?php echo esc_url( add_query_arg( 'invoice_dashboard', '1', home_url() ) ); ?>" style="text-decoration:none; color:inherit;">My Invoices</a></h1>
                <div class="wp-invoice-actions">
                    <button id="wp-invoice-import-trigger" class="wp-invoice-btn wp-invoice-btn-secondary">
                        <span class="icon">📥</span> Import
                    </button>
                    <input type="file" id="wp-invoice-csv-input" style="display:none;" accept=".csv" />
                    <a href="<?php echo esc_url( $editor_url ); ?>" class="wp-invoice-btn wp-invoice-btn-primary">
                        <span class="icon">＋</span> New Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="wp-invoice-container">
        <div class="wp-invoice-card">
            <div class="wp-invoice-table-toolbar">
                <div class="wp-invoice-search">
                    <span class="icon">🔍</span>
                    <input type="text" id="wp-invoice-search-input" placeholder="Search invoices..." />
                </div>
            </div>

            <div class="wp-invoice-table-container">
                <table id="wp-invoice-table">
                    <thead>
                        <tr>
                            <th data-sort="title">Reference <span class="sort-icon">↕</span></th>
                            <th data-sort="customer">Customer <span class="sort-icon">↕</span></th>
                            <th data-sort="date">Date <span class="sort-icon">↕</span></th>
                            <th data-sort="due_date">Due Date <span class="sort-icon">↕</span></th>
                            <th data-sort="status">Status <span class="sort-icon">↕</span></th>
                            <th data-sort="total" class="text-right">Total <span class="sort-icon">↕</span></th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="wp-invoice-list-body">
                        <!-- Loaded via JS -->
                        <tr>
                            <td colspan="7" class="text-center py-8">Loading invoices...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="wp-invoice-pagination">
                <div id="wp-invoice-pagination-info">Showing 0 to 0 of 0 entries</div>
                <div class="wp-invoice-pagination-btns">
                    <button id="wp-invoice-prev-page" disabled>Previous</button>
                    <button id="wp-invoice-next-page" disabled>Next</button>
                </div>
            </div>
        </div>

        <div id="wp-invoice-import-overlay" class="wp-invoice-overlay" style="display:none;">
            <div class="wp-invoice-modal">
                <h3>Importing Invoices</h3>
                <div class="wp-invoice-progress-bar">
                    <div id="wp-invoice-progress-fill"></div>
                </div>
                <p id="wp-invoice-progress-status">Uploading...</p>
                <div id="wp-invoice-import-log"></div>
            </div>
        </div>
    </div>
</div>
