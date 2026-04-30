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
        var newRow = table.find('tr:first').clone();
        
        newRow.find('input').each(function() {
            var name = $(this).attr('name');
            name = name.replace(/\[\d+\]/, '[' + rowCount + ']');
            $(this).attr('name', name).val('');
        });
        
        newRow.find('.quantity').val(1);
        newRow.find('.rate, .amount').val(0);
        
        table.append(newRow);
    });

    // Remove item
    $('#invoice-items-wrapper').on('click', '.remove-item', function() {
        var rows = $('#invoice-items-table tbody tr');
        if (rows.length > 1) {
            $(this).closest('tr').remove();
            calculateInvoiceTotals();
        }
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
