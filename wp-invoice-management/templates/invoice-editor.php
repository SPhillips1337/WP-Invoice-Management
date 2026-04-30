<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Editor | WP Invoices</title>
    <link rel="stylesheet" href="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>assets/css/frontend.css">
    <link rel="stylesheet" href="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>assets/css/invoice-editor.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body>
    <div class="invoice-app">
        <header class="app-header">
            <div class="header-left">
                <h1>WP Invoices</h1>
            </div>
            <div class="header-right">
                <button class="btn btn-secondary" id="newInvoiceBtn">
                    <span class="dashicons dashicons-plus"></span>
                    New Invoice
                </button>
            </div>
        </header>

        <main class="app-main">
            <aside class="invoice-list-panel">
                <div class="panel-header">
                    <h2>Invoices</h2>
                </div>
                <div class="invoice-list" id="invoiceList">
                    <div class="loading">Loading invoices...</div>
                </div>
            </aside>

            <section class="invoice-editor-panel">
                <div class="editor-empty-state" id="emptyState">
                    <div class="empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p>Select an invoice or create a new one</p>
                </div>

                <div class="invoice-editor" id="invoiceEditor" style="display: none;">
                    <div class="editor-toolbar">
                        <div class="toolbar-left">
                            <span class="invoice-number">Invoice #<span id="invoiceId">-</span></span>
                        </div>
                        <div class="toolbar-right">
                            <select id="invoiceStatus" class="status-select">
                                <option value="draft">Draft</option>
                                <option value="open">Open</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                            </select>
                            <button class="btn btn-primary" id="saveInvoiceBtn">
                                <span class="dashicons dashicons-saved"></span>
                                Save
                            </button>
                            <button class="btn btn-secondary" id="downloadPdfBtn">
                                <span class="dashicons dashicons-download"></span>
                                PDF
                            </button>
                            <button class="btn btn-danger" id="deleteInvoiceBtn" title="Delete Invoice">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>

                    <div class="editor-content">
                        <div class="invoice-header-section">
                            <div class="company-info">
                                <div class="logo-upload" id="logoUpload">
                                    <div class="logo-preview" id="logoPreview">
                                        <span class="dashicons dashicons-upload"></span>
                                        <span>Add Logo</span>
                                    </div>
                                    <input type="hidden" id="logoId" name="logo_id">
                                </div>
                                <textarea id="fromAddress" placeholder="From:&#10;Your Company Name&#10;Address Line 1&#10;City, State ZIP"></textarea>
                            </div>
                            <div class="invoice-meta">
                                <div class="meta-row">
                                    <label>Invoice Number</label>
                                    <input type="text" id="invoiceNumber" placeholder="#MW003">
                                </div>
                                <div class="meta-row">
                                    <label>Invoice Date</label>
                                    <input type="date" id="invoiceDate">
                                </div>
                                <div class="meta-row">
                                    <label>Due Date</label>
                                    <input type="date" id="dueDate">
                                </div>
                                <div class="meta-row">
                                    <label>PO Number</label>
                                    <input type="text" id="poNumber" placeholder="PO-001">
                                </div>
                            </div>
                        </div>

                        <div class="bill-to-section">
                            <h3>Bill To</h3>
                            <textarea id="toAddress" placeholder="Client Name&#10;Address Line 1&#10;City, State ZIP"></textarea>
                        </div>

                        <div class="line-items-section">
                            <table class="line-items-table">
                                <thead>
                                    <tr>
                                        <th class="col-description">Description</th>
                                        <th class="col-qty">Qty</th>
                                        <th class="col-rate">Rate</th>
                                        <th class="col-amount">Amount</th>
                                        <th class="col-actions"></th>
                                    </tr>
                                </thead>
                                <tbody id="lineItemsBody">
                                </tbody>
                            </table>
                            <button class="btn btn-text" id="addLineItem">
                                <span class="dashicons dashicons-plus"></span>
                                Add Line Item
                            </button>
                        </div>

                        <div class="totals-section">
                            <div class="totals-grid">
                                <div class="totals-left">
                                    <textarea id="notes" placeholder="Notes"></textarea>
                                    <textarea id="terms" placeholder="Terms & Conditions"></textarea>
                                </div>
                                <div class="totals-right">
                                    <div class="total-row">
                                        <span>Subtotal</span>
                                        <span id="subtotalDisplay">$0.00</span>
                                    </div>
                                    <div class="total-row editable">
                                        <span>Tax</span>
                                        <input type="number" id="taxAmount" value="0" min="0" step="0.01">
                                    </div>
                                    <div class="total-row editable">
                                        <span>Discount</span>
                                        <input type="number" id="discountAmount" value="0" min="0" step="0.01">
                                    </div>
                                    <div class="total-row editable">
                                        <span>Shipping</span>
                                        <input type="number" id="shippingAmount" value="0" min="0" step="0.01">
                                    </div>
                                    <div class="total-row grand-total">
                                        <span>Total</span>
                                        <span id="totalDisplay">$0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete this invoice? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn btn-secondary" id="cancelDelete">Cancel</button>
                <button class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <script>
    const WP_INVOICE_API = {
        nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
        root: '<?php echo rest_url( 'wp-invoice/v1' ); ?>',
        ajaxUrl: '<?php echo admin_url( 'admin-ajax.php' ); ?>'
    };
    </script>
    <script src="<?php echo plugin_dir_url( dirname( __FILE__ ) ); ?>assets/js/invoice-editor.js"></script>
    <?php wp_footer(); ?>
</body>
</html>
