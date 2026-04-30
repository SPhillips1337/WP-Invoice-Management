<?php
namespace Wpim\Invoice\Admin;

use Wpim\Invoice\Lib\Importer;

class ImportPage {
    public function __construct() {
        add_action( 'admin_init', array( $this, 'handle_import' ) );
    }

    public function render() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Import Invoices from CSV', 'wp-invoice-management' ); ?></h1>
            <div class="card" style="max-width: 800px; padding: 20px;">
                <p><?php _e( 'Upload a CSV file exported from invoice-generator.com to import your invoices.', 'wp-invoice-management' ); ?></p>

                <div id="wp-invoice-import-setup">
                    <input type="file" id="invoice_csv_file" accept=".csv" />
                    <button type="button" id="start-import" class="button button-primary"><?php _e( 'Start Import', 'wp-invoice-management' ); ?></button>
                </div>

                <div id="wp-invoice-import-progress" style="display:none; margin-top: 20px;">
                    <div class="progress-bar-container" style="background: #f0f0f0; border: 1px solid #ccc; height: 25px; border-radius: 4px; overflow: hidden;">
                        <div id="progress-bar-fill" style="background: #10b981; height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <p id="progress-status" style="margin-top: 10px; font-weight: bold;"></p>
                    <div id="import-log" style="background: #1e293b; color: #f8fafc; padding: 15px; border-radius: 4px; height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px; margin-top: 10px;">
                        <div>[System] Ready to import...</div>
                    </div>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var job_id = '';
            var total = 0;
            var offset = 0;
            var limit = 5;

            $('#start-import').on('click', function() {
                var file = $('#invoice_csv_file')[0].files[0];
                if (!file) {
                    alert('Please select a file.');
                    return;
                }

                var formData = new FormData();
                formData.append('file', file);

                $('#wp-invoice-import-setup').hide();
                $('#wp-invoice-import-progress').show();
                log('Uploading file...');

                $.ajax({
                    url: '/wp-json/wp-invoice/v1/import/upload',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce( "wp_rest" ); ?>');
                    },
                    success: function(response) {
                        job_id = response.job_id;
                        total = response.total;
                        log('File uploaded. Found ' + total + ' invoices. Starting process...');
                        processBatch();
                    },
                    error: function(xhr) {
                        log('Upload failed: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
                    }
                });
            });

            function processBatch() {
                $.ajax({
                    url: '/wp-json/wp-invoice/v1/import/process',
                    method: 'POST',
                    data: {
                        job_id: job_id,
                        offset: offset,
                        limit: limit
                    },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce( "wp_rest" ); ?>');
                    },
                    success: function(response) {
                        if (response.success) {
                            response.imported.forEach(function(title) {
                                log('Imported: ' + title);
                            });

                            offset += limit;
                            var percent = Math.min(100, Math.round((offset / total) * 100));
                            $('#progress-bar-fill').css('width', percent + '%');
                            $('#progress-status').text('Processing: ' + percent + '% (' + Math.min(offset, total) + '/' + total + ')');

                            if (response.finished) {
                                log('Import completed successfully!', 'success');
                                $('#progress-status').text('Import Completed!');
                            } else {
                                processBatch();
                            }
                        }
                    },
                    error: function(xhr) {
                        log('Batch failed at offset ' + offset + ': ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'), 'error');
                    }
                });
            }

            function log(msg, type) {
                var color = '#f8fafc';
                if (type === 'error') color = '#f87171';
                if (type === 'success') color = '#4ade80';
                
                var $line = $('<div>').text('[' + new Date().toLocaleTimeString() + '] ' + msg).css('color', color);
                $('#import-log').append($line).scrollTop($('#import-log')[0].scrollHeight);
            }
        });
        </script>
        <?php
    }

    public function handle_import() {
        // Handled by AJAX now
    }
}
