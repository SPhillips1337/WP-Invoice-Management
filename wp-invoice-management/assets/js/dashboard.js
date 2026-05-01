(function($) {
    'use strict';

    $(document).ready(function() {
        if (!$('#wp-invoice-dashboard-app').length) return;

        var state = {
            page: 1,
            per_page: 10,
            search: '',
            orderby: 'date',
            order: 'DESC',
            total: 0,
            pages: 0
        };

        function loadInvoices() {
            var $body = $('#wp-invoice-list-body');
            $body.html('<tr><td colspan="7" class="text-center py-8">Loading invoices...</td></tr>');

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
                    renderTable(response.items);
                    renderPagination();
                },
                error: function() {
                    $body.html('<tr><td colspan="7" class="text-center py-8 text-red-500">Failed to load invoices.</td></tr>');
                }
            });
        }

        function renderTable(items) {
            var $body = $('#wp-invoice-list-body');
            if (!items.length) {
                $body.html('<tr><td colspan="7" class="text-center py-8">No invoices found.</td></tr>');
                return;
            }

            var html = '';
            items.forEach(function(item) {
                html += '<tr>' +
                    '<td><a href="' + item.edit_url + '" class="wp-invoice-ref">' + item.title + '</a></td>' +
                    '<td>' + (item.to || '-') + '</td>' +
                    '<td>' + item.date + '</td>' +
                    '<td>' + item.due_date + '</td>' +
                    '<td><span class="status-badge status-' + (item.status ? item.status.toLowerCase() : 'open') + '">' + (item.status || 'Open') + '</span></td>' +
                    '<td class="text-right">$' + parseFloat(item.total).toFixed(2) + '</td>' +
                    '<td class="text-center">' +
                        '<a href="' + item.view_url + '" target="_blank" title="View PDF">📄</a> ' +
                        '<a href="' + item.edit_url + '" title="Edit">✏️</a>' +
                    '</td>' +
                '</tr>';
            });
            $body.html(html);
        }

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
        $('#wp-invoice-search-input').on('input', function() {
            clearTimeout(searchTimer);
            state.search = $(this).val();
            state.page = 1;
            searchTimer = setTimeout(loadInvoices, 500);
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

        // Initial Load
        loadInvoices();
    });

})(jQuery);
