<?php
/**
 * BackupRestAPI — registers and handles the three backup/restore REST endpoints.
 *
 * Routes (all require manage_options):
 *   GET  wp-invoice/v1/backup/export  → export()
 *   POST wp-invoice/v1/backup/upload  → upload()
 *   POST wp-invoice/v1/backup/import  → import_batch()
 *
 * @package Wpim\Invoice\API
 */

namespace Wpim\Invoice\API;

class BackupRestAPI {

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    /**
     * Hook into rest_api_init to register routes.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    // -------------------------------------------------------------------------
    // Permission callback
    // -------------------------------------------------------------------------

    /**
     * Require manage_options for all backup endpoints.
     *
     * A full data export/import is a privileged operation — intentionally
     * stricter than the edit_posts check used by the regular invoice endpoints.
     *
     * @return bool
     */
    public function check_permission(): bool {
        return current_user_can( 'manage_options' );
    }

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    /**
     * Register the three backup REST routes under the wp-invoice/v1 namespace.
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route( 'wp-invoice/v1', '/backup/export', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'export' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/backup/upload', array(
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'upload' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/backup/import', array(
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'import_batch' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );
    }

    // -------------------------------------------------------------------------
    // Endpoint callbacks
    // -------------------------------------------------------------------------

    /**
     * GET /wp-invoice/v1/backup/export
     *
     * Builds the full backup payload via BackupExporter and returns it as a
     * JSON REST response. The BackupPage JS converts the response body into a
     * downloadable .json file client-side.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function export( \WP_REST_Request $request ) {
        try {
            $exporter = new \Wpim\Invoice\Lib\BackupExporter();
            $backup   = $exporter->build_backup();
            return rest_ensure_response( $backup );
        } catch ( \Exception $e ) {
            error_log( 'WP Invoice Backup Export Error: ' . $e->getMessage() );
            return new \WP_Error( 'export_failed', 'Export failed. Please try again.', array( 'status' => 500 ) );
        }
    }

    /**
     * POST /wp-invoice/v1/backup/upload
     *
     * Accepts a multipart file upload, validates the MIME type, decodes the
     * JSON, and delegates structure validation + transient storage to
     * BackupImporter::validate_and_store().
     *
     * Returns: { job_id, invoice_count, customer_count }
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function upload( \WP_REST_Request $request ) {
        $files = $request->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new \WP_Error( 'no_file', 'No file uploaded', array( 'status' => 400 ) );
        }

        $update_mode = $request->get_param( 'update_mode' ) === '1';

        $file     = $files['file'];
        $tmp_name = $file['tmp_name'];

        // Validate MIME type using finfo for reliability.
        if ( function_exists( 'finfo_open' ) ) {
            $finfo     = finfo_open( FILEINFO_MIME_TYPE );
            $mime_type = finfo_file( $finfo, $tmp_name );
            finfo_close( $finfo );
        } else {
            $mime_type = $file['type'];
        }

        // Accept application/json and text/plain (some browsers send text/plain for .json files).
        $allowed_mimes = array( 'application/json', 'text/plain', 'text/json' );
        if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
            return new \WP_Error( 'invalid_mime', 'File must be a JSON backup file', array( 'status' => 400 ) );
        }

        if ( ! file_exists( $tmp_name ) || ! is_readable( $tmp_name ) ) {
            return new \WP_Error( 'file_error', 'Uploaded file is not accessible', array( 'status' => 500 ) );
        }

        $contents = file_get_contents( $tmp_name );
        $decoded  = json_decode( $contents, true );

        if ( null === $decoded ) {
            return new \WP_Error( 'invalid_json', 'Invalid backup file: not valid JSON', array( 'status' => 400 ) );
        }

        $importer = new \Wpim\Invoice\Lib\BackupImporter( $update_mode );
        $result   = $importer->validate_and_store( $decoded );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( array(
            'job_id'         => $result['job_id'],
            'invoice_count'  => $result['invoice_count'],
            'customer_count' => $result['customer_count'],
        ) );
    }

    /**
     * POST /wp-invoice/v1/backup/import
     *
     * Processes a single batch of records from a previously uploaded backup job.
     * The caller increments offset by limit and repeats until finished === true.
     *
     * Expected body params:
     *   job_id (string)  — transient key returned by the upload endpoint
     *   offset (int)     — zero-based start position in the combined record list
     *   limit  (int)     — records per batch; defaults to 10
     *
     * Returns: { success, imported: string[], finished: bool }
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function import_batch( \WP_REST_Request $request ) {
        $job_id     = sanitize_text_field( $request->get_param( 'job_id' ) ?? '' );
        $offset     = absint( $request->get_param( 'offset' ) );
        $limit      = absint( $request->get_param( 'limit' ) ?: 10 );
        $update_mode = $request->get_param( 'update_mode' ) === '1';

        if ( empty( $job_id ) ) {
            return new \WP_Error( 'missing_job_id', 'job_id is required', array( 'status' => 400 ) );
        }

        $importer = new \Wpim\Invoice\Lib\BackupImporter( $update_mode );
        $result   = $importer->restore_batch( $job_id, $offset, $limit );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( array(
            'success'  => true,
            'imported' => $result['imported'],
            'finished' => $result['finished'],
        ) );
    }
}
