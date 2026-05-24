(function($) {
    'use strict';

    $(document).ready(function() {
        if (!$('#wp-invoice-dashboard-app').length) return;

        var state = {
            view: 'invoices',
            page: 1,
            per_page: 10,
            search: '',
            orderby: 'date',
            order: 'DESC',
            total: 0,
            pages: 0
        };

        function loadInvoices() {
            resetCheckboxSelection();
            var $body = $('#wp-invoice-list-body');
            $body.html('<tr><td colspan="8" class="text-center py-8">Loading invoices...</td></tr>');

            $.ajax({
                url: wpApiSettings.root + 'wp-invoice/v1/invoices',
                data: {
                    page: state.page,
                    per_page: state.per_page,
                    search: state.search,
                    orderby: state.orderby,
                    order: state.order
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(response) {
                    state.total = response.total;
                    state.pages = response.pages;
                    renderTable(response.invoices);
                    renderPagination();
                },
                error: function() {
                    $body.html('<tr><td colspan="8" class="text-center py-8 text-red-500">Failed to load invoices.</td></tr>');
                }
            });
        }

        function loadCustomers() {
            var $body = $('#wp-customer-list-body');
            $body.html('<tr><td colspan="6" class="text-center py-8">Loading customers...</td></tr>');

            $.ajax({
                url: wpApiSettings.root + 'wp-invoice/v1/customers',
                data: {
                    search: state.search
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(response) {
                    renderCustomersTable(response);
                },
                error: function() {
                    $body.html('<tr><td colspan="6" class="text-center py-8 text-red-500">Failed to load customers.</td></tr>');
                }
            });
        }

        function renderCustomersTable(items) {
            var $body = $('#wp-customer-list-body');
            if (!items.length) {
                $body.html('<tr><td colspan="6" class="text-center py-8">No customers found.</td></tr>');
                return;
            }

            var html = '';
            items.forEach(function(item) {
                html += '<tr>' +
                    '<td><strong>' + (item.name || '-') + '</strong></td>' +
                    '<td>' + (item.company || '-') + '</td>' +
                    '<td>' + (item.email || '-') + '</td>' +
                    '<td>' + (item.phone || '-') + '</td>' +
                    '<td>' + (item.url ? '<a href="' + item.url + '" target="_blank">' + item.url.replace(/^https?:\/\//, '') + '</a>' : '-') + '</td>' +
                    '<td class="text-center">' +
                        '<button class="wp-invoice-btn wp-invoice-btn-secondary btn-sm edit-customer" data-id="' + item.id + '">Edit</button>' +
                    '</td>' +
                '</tr>';
            });
            $body.html(html);
        }

        // View Switching
        $('#wp-invoice-view-invoices').on('click', function() {
            state.view = 'invoices';
            $('.wp-invoice-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('#wp-invoice-invoices-view').show();
            $('#wp-invoice-customers-view').hide();
            loadInvoices();
        });

        $('#wp-invoice-view-customers').on('click', function() {
            state.view = 'customers';
            $('.wp-invoice-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('#wp-invoice-invoices-view').hide();
            $('#wp-invoice-customers-view').show();
            loadCustomers();
        });

        function formatCustomerInfo(to) {
            if (!to) return '-';
            var lines = to.split(/\r?\n/).map(function(l) { return l.trim(); }).filter(Boolean);
            if (lines.length > 0) {
                var name = lines[0];
                var address = lines.slice(1).join(', ');
                return name + (address ? ', ' + address : '');
            }
            return '-';
        }

        function renderTable(items) {
            var $body = $('#wp-invoice-list-body');
            if (!items.length) {
                $body.html('<tr><td colspan="8" class="text-center py-8">No invoices found.</td></tr>');
                return;
            }

            var html = '';
            items.forEach(function(item) {
                html += '<tr>' +
                    '<td class="text-center"><input type="checkbox" class="wp-invoice-select-row" data-id="' + item.id + '" /></td>' +
                    '<td><a href="' + item.edit_url + '" class="wp-invoice-ref">' + item.title + '</a></td>' +
                    '<td>' + formatCustomerInfo(item.to) + '</td>' +
                    '<td>' + item.date + '</td>' +
                    '<td>' + item.due_date + '</td>' +
                    '<td><span class="status-badge status-' + (item.status ? item.status.toLowerCase() : 'open') + '">' + (item.status || 'Open') + '</span></td>' +
                    '<td class="text-right">' + wpInvoiceSettings.settings.currency_symbol + parseFloat(item.total).toFixed(2) + '</td>' +
                    '<td class="text-center">' +
                        '<a href="' + item.view_url + '" target="_blank" title="View PDF">📄</a> ' +
                        '<a href="' + item.edit_url + '" title="Edit">✏️</a> ' +
                        '<a href="#" class="duplicate-invoice" data-id="' + item.id + '" title="Duplicate">📑</a> ' +
                        '<a href="#" class="delete-invoice" data-id="' + item.id + '" title="Delete" style="color:#dc2626; text-decoration:none; margin-left:6px;">🗑️</a>' +
                    '</td>' +
                '</tr>';
            });
            $body.html(html);
        }

        // Customer Logic
        $('#wp-invoice-add-customer-trigger').on('click', function() {
            var $modal = $('#wp-invoice-customer-modal');
            $modal.find('#customer-modal-title').text('Add Customer');
            $modal.find('form')[0].reset();
            $modal.find('[name="customer_id"]').val('');
            $modal.fadeIn();
        });

        function openEditCustomerModal(id) {
            if (!/^\d+$/.test(id)) {
                console.error('Invalid customer ID');
                return;
            }
            var $modal = $('#wp-invoice-customer-modal');
            $modal.find('#customer-modal-title').text('Edit Customer');
            
            $.ajax({
                url: wpApiSettings.root + 'wp-invoice/v1/customers/' + id,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(customer) {
                    $modal.find('[name="customer_id"]').val(customer.id);
                    $modal.find('[name="name"]').val(customer.name);
                    $modal.find('[name="company"]').val(customer.company);
                    $modal.find('[name="email"]').val(customer.email);
                    $modal.find('[name="phone"]').val(customer.phone);
                    $modal.find('[name="url"]').val(customer.url);
                    $modal.find('[name="address"]').val(customer.address);
                    $modal.fadeIn();
                },
                error: function() {
                    alert('Failed to load customer details.');
                }
            });
        }

        $(document).on('click', '.edit-customer', function() {
            var id = $(this).data('id');
            openEditCustomerModal(id);
        });

        $('#wp-invoice-customer-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            var id = $(this).find('[name="customer_id"]').val();
            var data = {
                name: $(this).find('[name="name"]').val(),
                company: $(this).find('[name="company"]').val(),
                email: $(this).find('[name="email"]').val(),
                phone: $(this).find('[name="phone"]').val(),
                url: $(this).find('[name="url"]').val(),
                address: $(this).find('[name="address"]').val()
            };

            var url = wpApiSettings.root + 'wp-invoice/v1/customers';
            var method = 'POST';
            
            if (id) {
                url += '/' + id;
                method = 'PUT';
            }

            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: url,
                method: method,
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function() {
                    $('#wp-invoice-customer-modal').fadeOut();
                    loadCustomers();
                    $btn.prop('disabled', false).text('Save Customer');
                },
                error: function() {
                    alert('Failed to save customer.');
                    $btn.prop('disabled', false).text('Save Customer');
                }
            });
        });

        function renderPagination() {
            var start = (state.page - 1) * state.per_page + 1;
            var end = Math.min(state.page * state.per_page, state.total);
            
            if (state.total === 0) {
                $('#wp-invoice-pagination-info').text('Showing 0 to 0 of 0 entries');
            } else {
                $('#wp-invoice-pagination-info').text('Showing ' + start + ' to ' + end + ' of ' + state.total + ' entries');
            }

            $('#wp-invoice-prev-page').prop('disabled', state.page <= 1);
            $('#wp-invoice-next-page').prop('disabled', state.page >= state.pages);
        }

        // Search
        var searchTimer;
        $('#wp-invoice-search-input, #wp-customer-search-input').on('input', function() {
            var $input = $(this);
            clearTimeout(searchTimer);
            state.search = $input.val();
            state.page = 1;
            
            searchTimer = setTimeout(function() {
                if (state.view === 'invoices') {
                    loadInvoices();
                } else {
                    loadCustomers();
                }
            }, 500);
        });

        // Sorting
        $('#wp-invoice-table th[data-sort]').on('click', function() {
            var field = $(this).data('sort');
            if (state.orderby === field) {
                state.order = (state.order === 'ASC') ? 'DESC' : 'ASC';
            } else {
                state.orderby = field;
                state.order = 'ASC';
            }
            state.page = 1;
            loadInvoices();
        });

        // Pagination
        $('#wp-invoice-prev-page').on('click', function() {
            if (state.page > 1) {
                state.page--;
                loadInvoices();
            }
        });

        $('#wp-invoice-next-page').on('click', function() {
            if (state.page < state.pages) {
                state.page++;
                loadInvoices();
            }
        });

        // Duplicate Invoice
        $(document).on('click', '.duplicate-invoice', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var $btn = $(this);
            
            if ($btn.hasClass('loading')) return;
            $btn.addClass('loading').text('⏳');

            // 1. Get original invoice data
            $.ajax({
                url: wpApiSettings.root + 'wp-invoice/v1/invoices/' + id,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(invoice) {
                    // 2. Prepare duplicate data
                    var duplicateData = {
                        title: invoice.title + ' (copy)',
                        status: 'draft',
                        logo_id: invoice.logo_id,
                        from: invoice.from,
                        to: invoice.to,
                        ship_to: invoice.ship_to,
                        date: new Date().toISOString().split('T')[0], // New date
                        due_date: invoice.due_date,
                        po_number: invoice.po_number,
                        items: invoice.items,
                        notes: invoice.notes,
                        terms: invoice.terms,
                        tax: invoice.tax,
                        discount: invoice.discount,
                        shipping: invoice.shipping
                    };

                    // 3. Create new invoice
                    $.ajax({
                        url: wpApiSettings.root + 'wp-invoice/v1/invoices',
                        method: 'POST',
                        data: JSON.stringify(duplicateData),
                        contentType: 'application/json',
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                        },
                        success: function(newInvoice) {
                            // 4. Redirect to editor
                            window.location.href = newInvoice.edit_url;
                        },
                        error: function() {
                            alert('Failed to create duplicate invoice.');
                            $btn.removeClass('loading').text('📑');
                        }
                    });
                },
                error: function() {
                    alert('Failed to load original invoice data.');
                    $btn.removeClass('loading').text('📑');
                }
            });
        });

        // --- Import Logic ---
        $('#wp-invoice-import-trigger').on('click', function(e) {
            e.preventDefault();
            $('#wp-invoice-csv-input').click();
        });

        $('#wp-invoice-csv-input').on('change', function() {
            var file = this.files[0];
            if (!file) return;

            var formData = new FormData();
            formData.append('file', file);

            $('#wp-invoice-import-overlay').show();
            $('#wp-invoice-import-log').empty();
            log('Uploading file...');

            $.ajax({
                url: wpApiSettings.root + 'wp-invoice/v1/import/upload',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(response) {
                    processBatch(response.job_id, 0, response.total);
                },
                error: function(xhr) {
                    var errorMsg = 'Unknown error';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        // Extract title from HTML if it's a WP error page
                        var match = xhr.responseText.match(/<title>(.*)<\/title>/);
                        errorMsg = match ? match[1] : 'Server Error (check console)';
                        console.error('Full Error Response:', xhr.responseText);
                    }
                    log('Upload failed: ' + errorMsg, 'error');
                }
            });
        });

        function processBatch(job_id, offset, total) {
            $.ajax({
                url: wpApiSettings.root + 'wp-invoice/v1/import/process',
                method: 'POST',
                data: { job_id: job_id, offset: offset, limit: 5 },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(response) {
                    response.imported.forEach(function(title) {
                        log('Imported: ' + title);
                    });

                    var newOffset = offset + 5;
                    var percent = Math.min(100, Math.round((newOffset / total) * 100));
                    $('#wp-invoice-progress-fill').css('width', percent + '%');
                    $('#wp-invoice-progress-status').text('Importing: ' + percent + '%');

                    if (response.finished) {
                        log('Finished!', 'success');
                        setTimeout(function() {
                            $('#wp-invoice-import-overlay').hide();
                            loadInvoices();
                        }, 1500);
                    } else {
                        processBatch(job_id, newOffset, total);
                    }
                }
            });
        }

        function log(msg, type) {
            var $line = $('<div>').text(msg);
            if (type === 'error') $line.css('color', '#f87171');
            if (type === 'success') $line.css('color', '#4ade80');
            $('#wp-invoice-import-log').append($line).scrollTop($('#wp-invoice-import-log')[0].scrollHeight);
        }

        // Handle full-width breakout for stubborn themes
        if ($('body').hasClass('wp-invoice-force-full-width')) {
            $('#wp-invoice-dashboard-app').parents().each(function() {
                var $parent = $(this);
                // If it's a layout container, clear constraints
                if ($parent.css('max-width') !== 'none' || $parent.css('overflow') === 'hidden') {
                    $parent.css({
                        'max-width': 'none',
                        'overflow': 'visible',
                        'width': '100%',
                        'margin-left': '0',
                        'margin-right': '0',
                        'padding-left': '0',
                        'padding-right': '0'
                    });
                }
            });
        }

        // Checkboxes & Bulk Actions
        function resetCheckboxSelection() {
            $('#wp-invoice-select-all').prop('checked', false);
            $('.wp-invoice-bulk-actions').hide();
            $('#wp-invoice-bulk-action-select').val('');
        }

        // Toggle Select All
        $(document).on('change', '#wp-invoice-select-all', function() {
            var checked = $(this).prop('checked');
            $('.wp-invoice-select-row').prop('checked', checked);
            toggleBulkActionsToolbar();
        });

        // Toggle row checkbox
        $(document).on('change', '.wp-invoice-select-row', function() {
            var allChecked = $('.wp-invoice-select-row').length === $('.wp-invoice-select-row:checked').length;
            $('#wp-invoice-select-all').prop('checked', allChecked);
            toggleBulkActionsToolbar();
        });

        function toggleBulkActionsToolbar() {
            var selectedCount = $('.wp-invoice-select-row:checked').length;
            if (selectedCount > 0) {
                $('.wp-invoice-bulk-actions').css('display', 'flex');
            } else {
                $('.wp-invoice-bulk-actions').hide();
            }
        }

        // Invoice Deletion Workflows
        var deleteInvoiceId = null;
        var deleteBulkIds = [];

        // Single delete trigger
        $(document).on('click', '.delete-invoice', function(e) {
            e.preventDefault();
            deleteInvoiceId = $(this).data('id');
            deleteBulkIds = [];
            
            $('#delete-confirm-title').text('Confirm Delete');
            $('#delete-confirm-message').text('Are you sure you want to delete this invoice? This action cannot be undone.');
            $('#wp-invoice-delete-confirm-modal').fadeIn();
        });

        // Bulk action apply trigger
        $('#wp-invoice-bulk-action-apply').on('click', function() {
            var action = $('#wp-invoice-bulk-action-select').val();
            if (action !== 'delete') {
                alert('Please select a valid bulk action.');
                return;
            }

            var ids = [];
            $('.wp-invoice-select-row:checked').each(function() {
                ids.push($(this).data('id'));
            });

            if (ids.length === 0) {
                alert('No invoices selected.');
                return;
            }

            deleteInvoiceId = null;
            deleteBulkIds = ids;

            $('#delete-confirm-title').text('Confirm Bulk Delete');
            $('#delete-confirm-message').text('Are you sure you want to delete the ' + ids.length + ' selected invoices? This action cannot be undone.');
            $('#wp-invoice-delete-confirm-modal').fadeIn();
        });

        // Close delete modal
        $('#wp-invoice-delete-cancel, #wp-invoice-delete-confirm-modal .wp-invoice-modal-close').on('click', function() {
            $('#wp-invoice-delete-confirm-modal').fadeOut();
        });

        // Confirm delete action
        $('#wp-invoice-delete-confirm').on('click', function() {
            var $btn = $(this);
            $btn.prop('disabled', true).text('Deleting...');

            var url = '';
            var method = '';
            var payload = null;

            if (deleteInvoiceId) {
                url = wpApiSettings.root + 'wp-invoice/v1/invoices/' + deleteInvoiceId;
                method = 'DELETE';
            } else if (deleteBulkIds.length > 0) {
                url = wpApiSettings.root + 'wp-invoice/v1/invoices/bulk-delete';
                method = 'POST';
                payload = JSON.stringify({ ids: deleteBulkIds });
            }

            $.ajax({
                url: url,
                method: method,
                data: payload,
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function() {
                    $('#wp-invoice-delete-confirm-modal').fadeOut();
                    resetCheckboxSelection();
                    loadInvoices();
                    $btn.prop('disabled', false).text('Delete');
                },
                error: function(xhr) {
                    var errorMsg = 'Failed to delete invoice(s).';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                    $btn.prop('disabled', false).text('Delete');
                }
            });
        });

        // Settings Modal
        $('#wp-invoice-settings-trigger').on('click', function() {
            var settings = wpInvoiceSettings.settings;
            var $form = $('#wp-invoice-settings-form');
            $form.find('[name="currency_symbol"]').val(settings.currency_symbol);
            $form.find('[name="currency_code"]').val(settings.currency_code);
            $form.find('[name="tax_label"]').val(settings.tax_label);
            $form.find('[name="default_tax_rate"]').val(settings.default_tax_rate || 0);
            $form.find('[name="default_country"]').val(settings.default_country);
            $form.find('[name="default_address"]').val(settings.default_address);
            $('#wp-invoice-settings-modal').fadeIn();
        });

        $('.wp-invoice-modal-close').on('click', function() {
            $('.wp-invoice-overlay').fadeOut();
        });

        $('#wp-invoice-settings-form').on('submit', function(e) {
            e.preventDefault();
            var $btn = $(this).find('button[type="submit"]');
            var data = {
                currency_symbol: $(this).find('[name="currency_symbol"]').val(),
                currency_code: $(this).find('[name="currency_code"]').val(),
                tax_label: $(this).find('[name="tax_label"]').val(),
                default_tax_rate: parseFloat($(this).find('[name="default_tax_rate"]').val()) || 0,
                default_country: $(this).find('[name="default_country"]').val(),
                default_address: $(this).find('[name="default_address"]').val()
            };

            $btn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: wpInvoiceSettings.root + '/settings',
                method: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpInvoiceSettings.nonce);
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('Failed to save settings.');
                    $btn.prop('disabled', false).text('Save Settings');
                }
            });
        });

        // Initialize
        function handleHashChange() {
            if (window.location.hash.indexOf('#customer-') === 0) {
                var customerId = window.location.hash.substring(10);
                $('#wp-invoice-view-customers').trigger('click');
                if (customerId) {
                    openEditCustomerModal(customerId);
                }
            } else if (window.location.hash === '#customers') {
                $('#wp-invoice-view-customers').trigger('click');
            } else {
                loadInvoices();
            }
        }

        $(window).on('hashchange', handleHashChange);
        handleHashChange();
    });
})(jQuery);
