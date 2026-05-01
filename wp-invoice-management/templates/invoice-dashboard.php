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
                    <button id="wp-invoice-settings-trigger" class="wp-invoice-btn wp-invoice-btn-secondary">
                        <span class="icon">⚙️</span> Settings
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
        <div class="wp-invoice-tabs" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <button id="wp-invoice-view-invoices" class="wp-invoice-tab-btn active">Invoices</button>
            <button id="wp-invoice-view-customers" class="wp-invoice-tab-btn">Customers</button>
        </div>

        <div id="wp-invoice-invoices-view" class="wp-invoice-card">
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

        <div id="wp-invoice-customers-view" class="wp-invoice-card" style="display:none;">
            <div class="wp-invoice-table-toolbar">
                <button id="wp-invoice-add-customer-trigger" class="wp-invoice-btn wp-invoice-btn-primary" style="margin-right: auto;">
                    <span class="icon">＋</span> Add Customer
                </button>
                <div class="wp-invoice-search">
                    <span class="icon">🔍</span>
                    <input type="text" id="wp-customer-search-input" placeholder="Search customers..." />
                </div>
            </div>

            <div class="wp-invoice-table-container">
                <table id="wp-customer-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Website</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="wp-customer-list-body">
                        <tr>
                            <td colspan="6" class="text-center py-8">Loading customers...</td>
                        </tr>
                    </tbody>
                </table>
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

    <!-- Customer Modal -->
    <div id="wp-invoice-customer-modal" class="wp-invoice-overlay" style="display:none;">
        <div class="wp-invoice-modal">
            <div class="wp-invoice-modal-header">
                <h3 id="customer-modal-title">Add Customer</h3>
                <button class="wp-invoice-modal-close" style="float:right; border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <div class="wp-invoice-modal-body" style="padding: 20px 0;">
                <form id="wp-invoice-customer-form">
                    <input type="hidden" name="customer_id" value="" />
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Customer Name</label>
                        <input type="text" name="name" required class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Company Name</label>
                        <input type="text" name="company" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Email</label>
                        <input type="email" name="email" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Phone</label>
                        <input type="tel" name="phone" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Website URL</label>
                        <input type="url" name="url" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Address</label>
                        <textarea name="address" rows="3" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="wp-invoice-btn wp-invoice-btn-primary" style="width:100%;">Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="wp-invoice-settings-modal" class="wp-invoice-overlay" style="display:none;">
        <div class="wp-invoice-modal">
            <div class="wp-invoice-modal-header">
                <h3>Invoice Settings</h3>
                <button class="wp-invoice-modal-close" style="float:right; border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <div class="wp-invoice-modal-body" style="padding: 20px 0;">
                <form id="wp-invoice-settings-form">
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="" placeholder="$" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Currency Code</label>
                        <input type="text" name="currency_code" value="" placeholder="USD" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Tax Label</label>
                        <input type="text" name="tax_label" value="" placeholder="Tax" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Default Country</label>
                        <input type="text" name="default_country" value="" placeholder="United Kingdom" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;" />
                    </div>
                    <div class="wp-invoice-form-group" style="margin-bottom: 25px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Default Sender Address</label>
                        <textarea name="default_address" rows="4" placeholder="Your Company Name&#10;Your Address&#10;City, Postcode" class="wp-invoice-input" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="wp-invoice-btn wp-invoice-btn-primary" style="width:100%;">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
