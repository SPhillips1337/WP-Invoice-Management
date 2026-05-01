(function() {
    'use strict';

    let currentInvoiceId = null;
    let invoices = [];

    const elements = {
        invoiceList: document.getElementById('invoiceList'),
        emptyState: document.getElementById('emptyState'),
        invoiceEditor: document.getElementById('invoiceEditor'),
        invoiceId: document.getElementById('invoiceId'),
        invoiceStatus: document.getElementById('invoiceStatus'),
        logoUpload: document.getElementById('logoUpload'),
        logoPreview: document.getElementById('logoPreview'),
        logoId: document.getElementById('logoId'),
        fromAddress: document.getElementById('fromAddress'),
        toAddress: document.getElementById('toAddress'),
        invoiceDate: document.getElementById('invoiceDate'),
        dueDate: document.getElementById('dueDate'),
        poNumber: document.getElementById('poNumber'),
        lineItemsBody: document.getElementById('lineItemsBody'),
        addLineItem: document.getElementById('addLineItem'),
        notes: document.getElementById('notes'),
        terms: document.getElementById('terms'),
        subtotalDisplay: document.getElementById('subtotalDisplay'),
        taxAmount: document.getElementById('taxAmount'),
        discountAmount: document.getElementById('discountAmount'),
        shippingAmount: document.getElementById('shippingAmount'),
        totalDisplay: document.getElementById('totalDisplay'),
        saveInvoiceBtn: document.getElementById('saveInvoiceBtn'),
        downloadPdfBtn: document.getElementById('downloadPdfBtn'),
        deleteInvoiceBtn: document.getElementById('deleteInvoiceBtn'),
        newInvoiceBtn: document.getElementById('newInvoiceBtn'),
        invoiceNumber: document.getElementById('invoiceNumber'),
        confirmModal: document.getElementById('confirmModal'),
        cancelDelete: document.getElementById('cancelDelete'),
        confirmDelete: document.getElementById('confirmDelete'),
        sidebarToggle: document.getElementById('sidebarToggle'),
        sortInvoices: document.getElementById('sortInvoices'),
        prevPage: document.getElementById('prevPage'),
        nextPage: document.getElementById('nextPage'),
        currentPage: document.getElementById('currentPage'),
        appContainer: document.querySelector('.invoice-app')
    };

    let sortOrder = 'desc'; // Default: most recent first
    let pagination = {
        page: 1,
        per_page: 10,
        total: 0,
        pages: 1
    };

    async function apiCall(endpoint, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': WP_INVOICE_API.nonce
            }
        };
        if (body) {
            options.body = JSON.stringify(body);
        }

        // Handle plain permalinks where root already has a query string (?rest_route=)
        let url = WP_INVOICE_API.root + endpoint;
        if (WP_INVOICE_API.root.includes('?') && endpoint.includes('?')) {
            url = WP_INVOICE_API.root + endpoint.replace('?', '&');
        }

        const response = await fetch(url, options);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'API request failed');
        }
        return response.json();
    }

    async function loadInvoices() {
        try {
            const response = await apiCall(`/invoices?page=${pagination.page}&per_page=${pagination.per_page}&order=${sortOrder.toUpperCase()}`);
            invoices = response.items || [];
            pagination.total = response.total || 0;
            pagination.pages = response.pages || 1;
            
            updatePaginationUI();
            renderInvoiceList();

            // Check if we have an ID in the URL to load
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');
            if (id && !currentInvoiceId) {
                loadInvoice(parseInt(id));
            }
        } catch (error) {
            console.error('Failed to load invoices:', error);
            elements.invoiceList.innerHTML = '<div class="loading">Failed to load invoices</div>';
        }
    }

    function renderInvoiceList() {
        if (invoices.length === 0) {
            elements.invoiceList.innerHTML = '<div class="loading">No invoices yet</div>';
            return;
        }

        elements.invoiceList.innerHTML = invoices.map(inv => `
            <div class="invoice-item ${inv.id === currentInvoiceId ? 'active' : ''}" data-id="${inv.id}">
                <div class="invoice-item-header">
                    <div class="invoice-item-title">${inv.title || 'Invoice #' + inv.id}</div>
                    <span class="invoice-item-amount">$${(inv.total || 0).toFixed(2)}</span>
                </div>
                <div class="invoice-item-customer">${inv.to ? inv.to.split('\n')[0] : 'No Client'}</div>
                <div class="invoice-item-meta">
                    <span class="status-badge ${inv.status || 'draft'}">${inv.status || 'draft'}</span>
                    <span class="invoice-item-date">${inv.date || ''}</span>
                </div>
            </div>
        `).join('');

        elements.invoiceList.querySelectorAll('.invoice-item').forEach(item => {
            item.addEventListener('click', () => loadInvoice(parseInt(item.dataset.id)));
        });
    }

    function updatePaginationUI() {
        if (elements.currentPage) {
            elements.currentPage.textContent = `${pagination.page} / ${pagination.pages}`;
        }
        if (elements.prevPage) {
            elements.prevPage.disabled = pagination.page <= 1;
        }
        if (elements.nextPage) {
            elements.nextPage.disabled = pagination.page >= pagination.pages;
        }
    }

    // Remove client-side sortInvoices as we now sort on server
    function sortInvoices() {
        // No longer needed, loadInvoices handles it
    }

    async function loadInvoice(id) {
        currentInvoiceId = id;
        renderInvoiceList();

        try {
            const invoice = await apiCall(`/invoices/${id}`);
            populateEditor(invoice);
            showEditor();
        } catch (error) {
            console.error('Failed to load invoice:', error);
        }
    }

    function populateEditor(invoice) {
        elements.invoiceId.textContent = invoice.id;
        elements.invoiceNumber.value = invoice.title || '';
        elements.invoiceStatus.value = invoice.status || 'draft';
        elements.logoId.value = invoice.logo_id || '';
        
        if (invoice.logo_id) {
            elements.logoPreview.innerHTML = `<img src="${invoice.logo_url}" alt="Logo">`;
        } else {
            elements.logoPreview.innerHTML = `
                <span class="dashicons dashicons-upload"></span>
                <span>Add Logo</span>
            `;
        }

        elements.fromAddress.value = invoice.from || '';
        elements.toAddress.value = invoice.to || '';
        elements.invoiceDate.value = invoice.date || '';
        elements.dueDate.value = invoice.due_date || '';
        elements.poNumber.value = invoice.po_number || '';
        elements.notes.value = invoice.notes || '';
        elements.terms.value = invoice.terms || '';

        renderLineItems(invoice.items || []);
        calculateTotals(invoice);
    }

    function renderLineItems(items) {
        if (items.length === 0) {
            items = [{ description: '', quantity: 1, rate: 0, amount: 0 }];
        }

        elements.lineItemsBody.innerHTML = items.map((item, index) => `
            <tr data-index="${index}">
                <td><input type="text" class="item-description" value="${escapeHtml(item.description)}" placeholder="Item description"></td>
                <td><input type="number" class="item-quantity" value="${item.quantity}" min="0" step="1"></td>
                <td><input type="number" class="item-rate" value="${item.rate}" min="0" step="0.01"></td>
                <td><input type="number" class="item-amount" value="${item.amount || 0}" readonly></td>
                <td><button type="button" class="remove-item-btn"><span class="dashicons dashicons-trash"></span></button></td>
            </tr>
        `).join('');

        attachLineItemListeners();
    }

    function attachLineItemListeners() {
        elements.lineItemsBody.querySelectorAll('tr').forEach(row => {
            const quantityInput = row.querySelector('.item-quantity');
            const rateInput = row.querySelector('.item-rate');
            const amountInput = row.querySelector('.item-amount');

            const updateAmount = () => {
                const quantity = parseFloat(quantityInput.value) || 0;
                const rate = parseFloat(rateInput.value) || 0;
                amountInput.value = (quantity * rate).toFixed(2);
                calculateTotals();
            };

            quantityInput.addEventListener('input', updateAmount);
            rateInput.addEventListener('input', updateAmount);

            row.querySelector('.remove-item-btn').addEventListener('click', () => {
                const rows = elements.lineItemsBody.querySelectorAll('tr');
                if (rows.length > 1) {
                    row.remove();
                    calculateTotals();
                }
            });
        });
    }

    function calculateTotals(invoice = null) {
        let subtotal = 0;
        
        elements.lineItemsBody.querySelectorAll('tr').forEach(row => {
            const amount = parseFloat(row.querySelector('.item-amount').value) || 0;
            subtotal += amount;
        });

        const tax = parseFloat(elements.taxAmount.value) || 0;
        const discount = parseFloat(elements.discountAmount.value) || 0;
        const shipping = parseFloat(elements.shippingAmount.value) || 0;
        
        const total = subtotal + tax - discount + shipping;

        elements.subtotalDisplay.textContent = `$${subtotal.toFixed(2)}`;
        elements.totalDisplay.textContent = `$${total.toFixed(2)}`;
    }

    function getInvoiceData() {
        const items = [];
        elements.lineItemsBody.querySelectorAll('tr').forEach(row => {
            const description = row.querySelector('.item-description').value.trim();
            if (description) {
                items.push({
                    description,
                    quantity: parseFloat(row.querySelector('.item-quantity').value) || 0,
                    rate: parseFloat(row.querySelector('.item-rate').value) || 0,
                    amount: parseFloat(row.querySelector('.item-amount').value) || 0
                });
            }
        });

        return {
            title: elements.invoiceNumber.value.trim() || `Invoice #${elements.invoiceId.textContent}`,
            status: elements.invoiceStatus.value,
            logo_id: elements.logoId.value,
            from: elements.fromAddress.value,
            to: elements.toAddress.value,
            date: elements.invoiceDate.value,
            due_date: elements.dueDate.value,
            po_number: elements.poNumber.value,
            items,
            notes: elements.notes.value,
            terms: elements.terms.value,
            tax: parseFloat(elements.taxAmount.value) || 0,
            discount: parseFloat(elements.discountAmount.value) || 0,
            shipping: parseFloat(elements.shippingAmount.value) || 0
        };
    }

    async function saveInvoice() {
        const data = getInvoiceData();
        elements.saveInvoiceBtn.disabled = true;
        elements.saveInvoiceBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Saving...';

        try {
            if (currentInvoiceId) {
                await apiCall(`/invoices/${currentInvoiceId}`, 'PUT', data);
            } else {
                const result = await apiCall('/invoices', 'POST', data);
                currentInvoiceId = result.id;
            }
            await loadInvoices();
            loadInvoice(currentInvoiceId);
        } catch (error) {
            console.error('Failed to save invoice:', error);
            alert('Failed to save invoice: ' + error.message);
        } finally {
            elements.saveInvoiceBtn.disabled = false;
            elements.saveInvoiceBtn.innerHTML = '<span class="dashicons dashicons-saved"></span> Save';
        }
    }

    async function deleteInvoice() {
        if (!currentInvoiceId) return;

        try {
            await apiCall(`/invoices/${currentInvoiceId}`, 'DELETE');
            currentInvoiceId = null;
            hideEditor();
            await loadInvoices();
        } catch (error) {
            console.error('Failed to delete invoice:', error);
            alert('Failed to delete invoice: ' + error.message);
        }
    }

    function newInvoice() {
        currentInvoiceId = null;
        hideEditor();
        showEditor();
        
        elements.invoiceId.textContent = 'New';
        elements.invoiceNumber.value = '';
        elements.invoiceStatus.value = 'draft';
        elements.logoId.value = '';
        elements.logoPreview.innerHTML = `
            <span class="dashicons dashicons-upload"></span>
            <span>Add Logo</span>
        `;
        elements.fromAddress.value = '';
        elements.toAddress.value = '';
        elements.invoiceDate.value = new Date().toISOString().split('T')[0];
        elements.dueDate.value = '';
        elements.poNumber.value = '';
        elements.notes.value = '';
        elements.terms.value = '';
        elements.taxAmount.value = 0;
        elements.discountAmount.value = 0;
        elements.shippingAmount.value = 0;
        
        renderLineItems([{ description: '', quantity: 1, rate: 0, amount: 0 }]);
        calculateTotals();
    }

    function showEditor() {
        elements.emptyState.style.display = 'none';
        elements.invoiceEditor.style.display = 'flex';
    }

    function hideEditor() {
        elements.emptyState.style.display = 'flex';
        elements.invoiceEditor.style.display = 'none';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    elements.addLineItem.addEventListener('click', () => {
        const tbody = elements.lineItemsBody;
        const index = tbody.children.length;
        const tr = document.createElement('tr');
        tr.dataset.index = index;
        tr.innerHTML = `
            <td><input type="text" class="item-description" value="" placeholder="Item description"></td>
            <td><input type="number" class="item-quantity" value="1" min="0" step="1"></td>
            <td><input type="number" class="item-rate" value="0" min="0" step="0.01"></td>
            <td><input type="number" class="item-amount" value="0" readonly></td>
            <td><button type="button" class="remove-item-btn"><span class="dashicons dashicons-trash"></span></button></td>
        `;
        tbody.appendChild(tr);
        attachLineItemListeners();
    });

    elements.saveInvoiceBtn.addEventListener('click', saveInvoice);
    elements.newInvoiceBtn.addEventListener('click', newInvoice);
    
    elements.deleteInvoiceBtn.addEventListener('click', () => {
        elements.confirmModal.classList.add('active');
    });
    
    elements.cancelDelete.addEventListener('click', () => {
        elements.confirmModal.classList.remove('active');
    });
    
    elements.confirmDelete.addEventListener('click', () => {
        elements.confirmModal.classList.remove('active');
        deleteInvoice();
    });

    elements.downloadPdfBtn.addEventListener('click', () => {
        if (currentInvoiceId) {
            window.open(window.location.origin + window.location.pathname + '?wp_invoice_pdf=' + currentInvoiceId, '_blank');
        }
    });

    elements.taxAmount.addEventListener('input', calculateTotals);
    elements.discountAmount.addEventListener('input', calculateTotals);
    elements.shippingAmount.addEventListener('input', calculateTotals);

    elements.logoUpload.addEventListener('click', () => {
        const frame = wp.media({
            title: 'Select or Upload Logo',
            button: { text: 'Use as Logo' },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            elements.logoId.value = attachment.id;
            elements.logoPreview.innerHTML = `<img src="${attachment.sizes.medium.url}" alt="Logo">`;
        });

        frame.open();
    });

    // Sidebar Toggle Logic
    if (elements.sidebarToggle) {
        elements.sidebarToggle.addEventListener('click', () => {
            const isCollapsed = elements.appContainer.classList.toggle('sidebar-collapsed');
            localStorage.setItem('wp_invoice_sidebar_collapsed', isCollapsed);
        });

        // Initialize state from localStorage
        const savedState = localStorage.getItem('wp_invoice_sidebar_collapsed');
        if (savedState === 'true') {
            elements.appContainer.classList.add('sidebar-collapsed');
        }
    }

    if (elements.sortInvoices) {
        elements.sortInvoices.addEventListener('click', () => {
            sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
            pagination.page = 1;
            loadInvoices();
            
            elements.sortInvoices.title = `Sorted by Date (${sortOrder === 'desc' ? 'Newest First' : 'Oldest First'})`;
        });
    }

    if (elements.prevPage) {
        elements.prevPage.addEventListener('click', () => {
            if (pagination.page > 1) {
                pagination.page--;
                loadInvoices();
            }
        });
    }

    if (elements.nextPage) {
        elements.nextPage.addEventListener('click', () => {
            if (pagination.page < pagination.pages) {
                pagination.page++;
                loadInvoices();
            }
        });
    }

    loadInvoices();
})();
