jQuery(document).ready(function($) {
    // Invoice items calculation
    $('#invoice-items-wrapper').on('input', '.quantity, .rate', function() {
        var row = $(this).closest('tr');
        var quantity = parseFloat(row.find('.quantity').val()) || 0;
        var rate = parseFloat(row.find('.rate').val()) || 0;
        row.find('.amount').val((quantity * rate).toFixed(2));
        calculateInvoiceTotals();
    });

    // Add new item
    $('#add-invoice-item').on('click', function() {
        var table = $('#invoice-items-table tbody');
        var rowCount = table.find('tr').length;
        
        var newRow = $(`
            <tr class="invoice-item-row" data-index="${rowCount}">
                <td><input type="date" name="invoice_items[${rowCount}][date]" value="" style="width: 100%;" /></td>
                <td><input type="text" name="invoice_items[${rowCount}][description]" value="" class="large-text" /></td>
                <td><input type="number" name="invoice_items[${rowCount}][quantity]" value="1" class="quantity" min="0" step="0.01" style="width: 100%;" /></td>
                <td><input type="number" name="invoice_items[${rowCount}][rate]" value="0" class="rate" min="0" step="0.01" style="width: 100%;" /></td>
                <td><input type="number" name="invoice_items[${rowCount}][amount]" value="0" class="amount" readonly style="width: 100%;" /></td>
                <td><button type="button" class="button remove-item">X</button></td>
            </tr>
        `);
        
        table.append(newRow);
    });

    // Add new project header
    $('#add-invoice-project').on('click', function() {
        var table = $('#invoice-items-table tbody');
        var rowCount = table.find('tr').length;
        
        var newRow = $(`
            <tr class="invoice-section-row" data-index="${rowCount}">
                <td colspan="5">
                    <input type="hidden" name="invoice_items[${rowCount}][type]" value="section" />
                    <input type="text" name="invoice_items[${rowCount}][description]" value="" class="large-text project-header-input" placeholder="Project / Section Header" style="font-weight: bold; background: #f0f6fb;" />
                </td>
                <td><button type="button" class="button remove-item">X</button></td>
            </tr>
        `);
        
        table.append(newRow);
    });

    // Remove item
    $('#invoice-items-wrapper').on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateInvoiceTotals();
    });

    function calculateInvoiceTotals() {
        var subtotal = 0;
        $('.invoice-item-row').each(function() {
            var amount = parseFloat($(this).find('.amount').val()) || 0;
            subtotal += amount;
        });

        var tax = parseFloat($('input[name="invoice_tax"]').val()) || 0;
        var discount = parseFloat($('input[name="invoice_discount"]').val()) || 0;
        var shipping = parseFloat($('input[name="invoice_shipping"]').val()) || 0;

        var total = subtotal + tax - discount + shipping;

        $('input[name="invoice_subtotal"]').val(subtotal.toFixed(2));
        $('input[name="invoice_total"]').val(total.toFixed(2));
    }

    // Recalculate on tax/discount/shipping change
    $('input[name="invoice_tax"], input[name="invoice_discount"], input[name="invoice_shipping"]').on('input', calculateInvoiceTotals);

    // Logo upload
    $('.upload-logo-button').on('click', function(e) {
        e.preventDefault();
        var frame = wp.media({
            title: 'Select or Upload Logo',
            button: { text: 'Use as Logo' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#invoice_logo_id').val(attachment.id);
            // Show preview
            var preview = '<img src="' + attachment.sizes.medium.url + '" style="max-width:200px;"/>';
            $(this).before(preview);
        });

        frame.open();
    });
});
