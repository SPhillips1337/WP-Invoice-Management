// WP Invoice Management Frontend Scripts
(function($) {
    'use strict';

    // Dashboard functionality can be added here
    $(document).ready(function() {
        $('#wp-invoice-import-trigger').on('click', function(e) {
            e.preventDefault();
            $('#wp-invoice-csv-input').click();
        });

        $('#wp-invoice-csv-input').on('change', function() {
            var file = this.files[0];
            if (!file) return;

            var formData = new FormData();
            formData.append('file', file);

            var $btn = $('#wp-invoice-import-trigger');
            var originalText = $btn.text();
            $btn.text('Importing...').prop('disabled', true);

            $.ajax({
                url: '/wp-json/wp-invoice/v1/import',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                },
                success: function(response) {
                    alert('Successfully imported ' + response.count + ' invoices.');
                    location.reload();
                },
                error: function(xhr) {
                    var msg = 'Import failed.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ' ' + xhr.responseJSON.message;
                    }
                    alert(msg);
                },
                complete: function() {
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
    });

})(jQuery);
