<?php
namespace Wpim\Invoice\Admin;

class BackupPage {

    public function __construct() {
        // No hooks needed — registered entirely via Plugin.php
    }

    public function render(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Backup & Restore', 'wp-invoice-management' ); ?></h1>

            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">

                <!-- Export Backup Card -->
                <div class="card" style="flex: 1; min-width: 300px; max-width: 500px; padding: 20px;">
                    <h2><?php esc_html_e( 'Export Backup', 'wp-invoice-management' ); ?></h2>
                    <p><?php esc_html_e( 'Download a complete backup of all your invoices and customers as a JSON file. Use this to migrate data or restore after a reinstall.', 'wp-invoice-management' ); ?></p>
                    <button id="wp-invoice-export-btn" class="button button-primary"><?php esc_html_e( 'Download Backup', 'wp-invoice-management' ); ?></button>
                    <span class="spinner" id="wp-invoice-export-spinner" style="float:none; visibility:hidden; margin-left:8px;"></span>
                    <p id="wp-invoice-export-error" style="color:#dc3232; display:none;"></p>
                </div>

                <!-- Import Backup Card -->
                <div class="card" style="flex: 1; min-width: 300px; max-width: 500px; padding: 20px;">
                    <h2><?php esc_html_e( 'Import Backup', 'wp-invoice-management' ); ?></h2>
                    <p><?php esc_html_e( 'Restore invoices and customers from a previously downloaded backup file.', 'wp-invoice-management' ); ?></p>

                    <input type="file" id="wp-invoice-backup-file" accept=".json" />
                    <label style="display:block; margin: 10px 0;">
                        <input type="checkbox" id="wp-invoice-update-mode" value="1" />
                        <?php esc_html_e( 'Update existing records (match by title)', 'wp-invoice-management' ); ?>
                    </label>
                    <p style="font-size:12px; color:#666;"><?php esc_html_e( 'When checked: updates existing records with matching titles. When unchecked: creates new records.', 'wp-invoice-management' ); ?></p>
                    <button id="wp-invoice-upload-btn" class="button"><?php esc_html_e( 'Upload &amp; Preview', 'wp-invoice-management' ); ?></button>
                    <span class="spinner" id="wp-invoice-upload-spinner" style="float:none; visibility:hidden; margin-left:8px;"></span>

                    <!-- Preview (hidden until upload succeeds) -->
                    <div id="wp-invoice-backup-preview" style="display:none; margin-top:15px; padding:12px; background:#f0f6fb; border-left:4px solid #2271b1; border-radius:2px;">
                        <p id="wp-invoice-preview-counts"></p>
                        <button id="wp-invoice-start-import" class="button button-primary"><?php esc_html_e( 'Start Import', 'wp-invoice-management' ); ?></button>
                    </div>

                    <!-- Progress (hidden until import starts) -->
                    <div id="wp-invoice-backup-progress" style="display:none; margin-top:20px;">
                        <div class="progress-bar-container" style="background:#f0f0f0; border:1px solid #ccc; height:25px; border-radius:4px; overflow:hidden;">
                            <div id="backup-progress-fill" style="background:#10b981; height:100%; width:0%; transition:width 0.3s;"></div>
                        </div>
                        <p id="backup-progress-status" style="margin-top:10px; font-weight:bold;"></p>
                        <div id="backup-import-log" style="background:#1e293b; color:#f8fafc; padding:15px; border-radius:4px; height:200px; overflow-y:auto; font-family:monospace; font-size:12px; margin-top:10px;">
                            <div>[System] Ready...</div>
                        </div>
                    </div>
                </div>

            </div><!-- /.flex wrapper -->
        </div><!-- /.wrap -->

        <script>
        jQuery(document).ready(function($) {

            var nonce   = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';
            var restBase = '<?php echo esc_url_raw( rest_url( 'wp-invoice/v1' ) ); ?>';

            /* -------------------------------------------------------
             * Export flow
             * ----------------------------------------------------- */
            $('#wp-invoice-export-btn').on('click', function() {
                var $btn     = $(this);
                var $spinner = $('#wp-invoice-export-spinner');
                var $error   = $('#wp-invoice-export-error');

                $btn.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $error.hide();

                $.ajax({
                    url: restBase + '/backup/export',
                    method: 'GET',
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', nonce);
                    },
                    success: function(response) {
                        // Build filename with timestamp
                        var now = new Date();
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        var filename = 'wp-invoice-backup-' +
                            now.getFullYear() + '-' +
                            pad(now.getMonth() + 1) + '-' +
                            pad(now.getDate()) + '-' +
                            pad(now.getHours()) +
                            pad(now.getMinutes()) +
                            pad(now.getSeconds()) + '.json';

                        var blob = new Blob([JSON.stringify(response, null, 2)], {type: 'application/json'});
                        var url  = URL.createObjectURL(blob);
                        var a    = document.createElement('a');
                        a.href     = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Export failed. Please try again.';
                        $error.text(msg).show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.css('visibility', 'hidden');
                    }
                });
            });

            /* -------------------------------------------------------
             * Upload / preview flow
             * ----------------------------------------------------- */
            var jobId        = '';
            var totalRecords = 0;
            var updateMode   = false;

            $('#wp-invoice-upload-btn').on('click', function() {
                var file = $('#wp-invoice-backup-file')[0].files[0];
                if (!file) { alert('Please select a backup file.'); return; }

                updateMode = $('#wp-invoice-update-mode').is(':checked');

                var $btn     = $(this);
                var $spinner = $('#wp-invoice-upload-spinner');
                var formData = new FormData();
                formData.append('file', file);
                formData.append('update_mode', updateMode ? '1' : '0');

                $btn.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $('#wp-invoice-backup-preview').hide();

                $.ajax({
                    url: restBase + '/backup/upload',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
                    success: function(response) {
                        jobId        = response.job_id;
                        totalRecords = response.invoice_count + response.customer_count;
                        $('#wp-invoice-preview-counts').text(
                            'Found ' + response.invoice_count + ' invoice(s) and ' + response.customer_count + ' customer(s).'
                        );
                        $('#wp-invoice-backup-preview').show();
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload failed.';
                        alert('Error: ' + msg);
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $spinner.css('visibility', 'hidden');
                    }
                });
            });

            /* -------------------------------------------------------
             * Batched import flow
             * ----------------------------------------------------- */
            $('#wp-invoice-start-import').on('click', function() {
                $('#wp-invoice-backup-preview').hide();
                $('#wp-invoice-backup-progress').show();
                $('#backup-import-log').html('<div>[System] Starting import...</div>');
                processBatch(0);
            });

            function processBatch(offset) {
                var limit = 10;
                $.ajax({
                    url: restBase + '/backup/import',
                    method: 'POST',
                    data: { job_id: jobId, offset: offset, limit: limit, update_mode: updateMode ? '1' : '0' },
                    beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
                    success: function(response) {
                        if (response.success) {
                            response.imported.forEach(function(title) { log('Imported: ' + title); });

                            var newOffset = offset + limit;
                            var percent   = totalRecords > 0 ? Math.min(100, Math.round((newOffset / totalRecords) * 100)) : 100;
                            $('#backup-progress-fill').css('width', percent + '%');
                            $('#backup-progress-status').text('Processing: ' + percent + '% (' + Math.min(newOffset, totalRecords) + '/' + totalRecords + ')');

                            if (response.finished) {
                                log('✓ Import completed successfully!', 'success');
                                $('#backup-progress-status').text('Import Completed!');
                            } else {
                                processBatch(newOffset);
                            }
                        }
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unknown error';
                        log('Error at offset ' + offset + ': ' + msg, 'error');
                    }
                });
            }

            function log(msg, type) {
                var color = '#f8fafc';
                if (type === 'error')   color = '#f87171';
                if (type === 'success') color = '#4ade80';
                var $line = $('<div>').text('[' + new Date().toLocaleTimeString() + '] ' + msg).css('color', color);
                var $log  = $('#backup-import-log');
                $log.append($line).scrollTop($log[0].scrollHeight);
            }

        });
        </script>
        <?php
    }
}
