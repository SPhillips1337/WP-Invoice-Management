<?php
namespace Wpim\Invoice\Lib;

/**
 * BackupImporter
 *
 * Validates a decoded backup JSON array, stores it in a transient, and
 * restores invoices and customers in batches via restore_batch().
 *
 * Mirrors the Importer + REST_API::process_import_batch() pattern:
 *   1. validate_and_store()  — upload step, returns job_id
 *   2. restore_batch()       — called repeatedly until finished === true
 */
class BackupImporter {

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    const COMPATIBLE_FORMAT_VERSIONS = array( '1.0' );

    // -------------------------------------------------------------------------
    // Properties
    // -------------------------------------------------------------------------

    /**
     * @var bool  When true, attempt to update existing records by title match.
     */
    private $update_mode;

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    /**
     * @param bool $update_mode  If true, update existing records instead of always creating new ones.
     */
    public function __construct( bool $update_mode = false ) {
        $this->update_mode = $update_mode;
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Validate a decoded backup array and store it in a transient.
     *
     * @param array $data  Decoded JSON as PHP array.
     * @return array|\WP_Error  ['job_id' => string, 'invoice_count' => int, 'customer_count' => int]
     */
    public function validate_and_store( array $data ) {
        $result = $this->validate_structure( $data );
        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $job_id = uniqid( 'backup_' );
        $data['_update_mode'] = $this->update_mode;
        set_transient( $job_id, $data, HOUR_IN_SECONDS );

        return array(
            'job_id'         => $job_id,
            'invoice_count'  => count( $data['invoices'] ),
            'customer_count' => count( $data['customers'] ),
        );
    }

    /**
     * Restore a batch of records from a stored import job.
     *
     * The combined record list is [ ...invoices, ...customers ].
     * $offset is a single integer across both arrays, matching the existing
     * process_import_batch() pattern in REST_API.php.
     *
     * @param string $job_id
     * @param int    $offset  Combined offset across invoices then customers.
     * @param int    $limit   Records per batch (default 10).
     * @return array|\WP_Error  ['imported' => string[], 'finished' => bool]
     */
    public function restore_batch( string $job_id, int $offset, int $limit = 10 ) {
        $data = get_transient( $job_id );
        if ( ! $data ) {
            return new \WP_Error( 'job_not_found', 'Import job not found or expired' );
        }

        $update_mode = ! empty( $data['_update_mode'] );
        unset( $data['_update_mode'] ); // Clean up internal flag.

        $combined  = array_merge( $data['invoices'], $data['customers'] );
        $batch     = array_slice( $combined, $offset, $limit );
        $author_id = get_current_user_id();
        $imported  = array();
        $finished  = false;

        foreach ( $batch as $record ) {
            // Detect type: invoices carry _invoice_items in meta_data; customers do not.
            if ( isset( $record['meta_data']['_invoice_items'] ) ) {
                $result = $this->restore_invoice( $record, $author_id, $update_mode );
            } else {
                $result = $this->restore_customer( $record, $author_id, $update_mode );
            }

            if ( ! is_wp_error( $result ) ) {
                $imported[] = $record['post_data']['post_title'];
            }
        }

        if ( $offset + $limit >= count( $combined ) ) {
            delete_transient( $job_id );
            $finished = true;
        }

        return array(
            'imported' => $imported,
            'finished' => $finished,
        );
    }

    /**
     * Validate the structure of a decoded backup array.
     *
     * Checks:
     *   - Required root keys: metadata, invoices, customers
     *   - format_version compatibility
     *   - invoices and customers are arrays
     *
     * @param array $data
     * @return true|\WP_Error
     */
    public function validate_structure( array $data ) {
        // Check required root keys.
        $required = array( 'metadata', 'invoices', 'customers' );
        $missing  = array();
        foreach ( $required as $key ) {
            if ( ! array_key_exists( $key, $data ) ) {
                $missing[] = $key;
            }
        }
        if ( ! empty( $missing ) ) {
            return new \WP_Error(
                'missing_keys',
                'Backup file is missing required keys: ' . implode( ', ', $missing )
            );
        }

        // Check format_version compatibility.
        $format_version = isset( $data['metadata']['format_version'] )
            ? $data['metadata']['format_version']
            : null;

        if ( ! in_array( $format_version, self::COMPATIBLE_FORMAT_VERSIONS, true ) ) {
            return new \WP_Error( 'incompatible_version', 'Incompatible backup format version' );
        }

        // Check invoices and customers are arrays.
        if ( ! is_array( $data['invoices'] ) || ! is_array( $data['customers'] ) ) {
            return new \WP_Error( 'invalid_structure', 'invoices and customers must be arrays' );
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Private — restore helpers
    // -------------------------------------------------------------------------

    /**
     * Restore a single invoice from backup data.
     *
     * When $update_mode is true, attempts to find an existing invoice with the
     * same post_title and update it instead of creating a new one.
     *
     * @param array $invoice    ['post_data' => [...], 'meta_data' => [...]]
     * @param int   $author_id
     * @param bool  $update_mode  If true, update existing records by title match.
     * @return int|\WP_Error  Post ID (new or updated) or error.
     */
    private function restore_invoice( array $invoice, int $author_id, bool $update_mode = false ) {
        if ( ! isset( $invoice['post_data'] ) || ! isset( $invoice['meta_data'] ) ) {
            return new \WP_Error( 'invalid_invoice', 'Invoice record is missing post_data or meta_data' );
        }

        $post_data = $invoice['post_data'];
        $title     = sanitize_text_field( $post_data['post_title'] ?? 'Invoice' );
        $post_id   = 0;

        // Update mode: try to find existing invoice by title.
        if ( $update_mode ) {
            $existing = get_page_by_title( $title, OBJECT, 'wp_invoice' );
            if ( $existing ) {
                $post_id = $existing->ID;
            }
        }

        // Create new post or update existing one.
        if ( $post_id > 0 ) {
            wp_update_post( array(
                'ID'            => $post_id,
                'post_status'   => sanitize_text_field( $post_data['post_status'] ?? 'publish' ),
                'post_title'    => $title,
                'post_date'     => sanitize_text_field( $post_data['post_date'] ?? '' ),
                'post_date_gmt' => sanitize_text_field( $post_data['post_date_gmt'] ?? '' ),
                'post_author'   => $author_id,
            ) );
        } else {
            $post_id = wp_insert_post( array(
                'post_type'     => 'wp_invoice',
                'post_status'   => sanitize_text_field( $post_data['post_status'] ?? 'publish' ),
                'post_title'    => $title,
                'post_date'     => sanitize_text_field( $post_data['post_date'] ?? '' ),
                'post_date_gmt' => sanitize_text_field( $post_data['post_date_gmt'] ?? '' ),
                'post_author'   => $author_id,
            ) );
        }

        if ( is_wp_error( $post_id ) ) {
            error_log( 'WP Invoice Backup Import: Failed to create/update invoice: ' . $post_id->get_error_message() );
            return $post_id;
        }

        $this->restore_invoice_meta( $post_id, $invoice['meta_data'] );
        $this->recalculate_totals( $post_id );

        return $post_id;
    }

    /**
     * Restore a single customer from backup data.
     *
     * When $update_mode is true, attempts to find an existing customer with the
     * same post_title and update it instead of creating a new one.
     *
     * @param array $customer    ['post_data' => [...], 'meta_data' => [...]]
     * @param int   $author_id
     * @param bool  $update_mode  If true, update existing records by title match.
     * @return int|\WP_Error  Post ID (new or updated) or error.
     */
    private function restore_customer( array $customer, int $author_id, bool $update_mode = false ) {
        if ( ! isset( $customer['post_data'] ) || ! isset( $customer['meta_data'] ) ) {
            return new \WP_Error( 'invalid_customer', 'Customer record is missing post_data or meta_data' );
        }

        $post_data = $customer['post_data'];
        $title     = sanitize_text_field( $post_data['post_title'] ?? 'Customer' );
        $post_id   = 0;

        // Update mode: try to find existing customer by title.
        if ( $update_mode ) {
            $existing = get_page_by_title( $title, OBJECT, 'wp_customer' );
            if ( $existing ) {
                $post_id = $existing->ID;
            }
        }

        // Create new post or update existing one.
        if ( $post_id > 0 ) {
            wp_update_post( array(
                'ID'          => $post_id,
                'post_title'  => $title,
                'post_author' => $author_id,
            ) );
        } else {
            $post_id = wp_insert_post( array(
                'post_type'   => 'wp_customer',
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_author' => $author_id,
            ) );
        }

        if ( is_wp_error( $post_id ) ) {
            error_log( 'WP Invoice Backup Import: Failed to create/update customer: ' . $post_id->get_error_message() );
            return $post_id;
        }

        $this->restore_customer_meta( $post_id, $customer['meta_data'] );

        return $post_id;
    }

    /**
     * Sanitize and write all invoice meta fields to a post.
     *
     * Sanitization rules:
     *   - Textarea fields:   sanitize_textarea_field()
     *   - Single-line text:  sanitize_text_field()
     *   - Numeric fields:    floatval()
     *   - Status:            validated against allowed list, defaults to 'open'
     *   - Line items:        key-whitelisted, each field individually sanitized
     *
     * @param int   $post_id
     * @param array $meta_data
     */
    private function restore_invoice_meta( int $post_id, array $meta_data ): void {
        // -- Textarea fields --------------------------------------------------
        $textarea_fields = array(
            '_invoice_from',
            '_invoice_to',
            '_invoice_ship_to',
            '_invoice_notes',
            '_invoice_terms',
        );
        foreach ( $textarea_fields as $key ) {
            if ( isset( $meta_data[ $key ] ) ) {
                update_post_meta( $post_id, $key, sanitize_textarea_field( $meta_data[ $key ] ) );
            }
        }

        // -- Single-line text fields ------------------------------------------
        $text_fields = array(
            '_invoice_date',
            '_invoice_due_date',
            '_invoice_po_number',
            '_invoice_logo_id',
        );
        foreach ( $text_fields as $key ) {
            if ( isset( $meta_data[ $key ] ) ) {
                update_post_meta( $post_id, $key, sanitize_text_field( $meta_data[ $key ] ) );
            }
        }

        // -- Status (validated against allowed list) --------------------------
        if ( isset( $meta_data['_invoice_status'] ) ) {
            $allowed_statuses = array( 'open', 'paid', 'overdue', 'draft' );
            $status           = sanitize_text_field( $meta_data['_invoice_status'] );
            if ( ! in_array( $status, $allowed_statuses, true ) ) {
                $status = 'open';
            }
            update_post_meta( $post_id, '_invoice_status', $status );
        }

        // -- Numeric fields ---------------------------------------------------
        $numeric_fields = array(
            '_invoice_tax',
            '_invoice_discount',
            '_invoice_shipping',
            '_invoice_amount_paid',
            '_invoice_subtotal',
            '_invoice_total',
        );
        foreach ( $numeric_fields as $key ) {
            if ( isset( $meta_data[ $key ] ) ) {
                update_post_meta( $post_id, $key, floatval( $meta_data[ $key ] ) );
            }
        }

        // -- Line items -------------------------------------------------------
        if ( isset( $meta_data['_invoice_items'] ) && is_array( $meta_data['_invoice_items'] ) ) {
            $items = array();
            foreach ( $meta_data['_invoice_items'] as $item ) {
                // Skip non-array entries to prevent type confusion.
                if ( ! is_array( $item ) ) {
                    continue;
                }

                $type = isset( $item['type'] ) && $item['type'] === 'section' ? 'section' : 'item';

                // Whitelist-only keys — never persist unexpected fields.
                $sanitized_item = array(
                    'description' => isset( $item['description'] ) ? sanitize_textarea_field( $item['description'] ) : '',
                    'type'        => $type,
                    'quantity'    => isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0,
                    'rate'        => isset( $item['rate'] ) ? floatval( $item['rate'] ) : 0,
                    'amount'      => isset( $item['amount'] ) ? floatval( $item['amount'] ) : 0,
                    'date'        => isset( $item['date'] ) ? sanitize_text_field( $item['date'] ) : '',
                );

                $items[] = $sanitized_item;
            }
            update_post_meta( $post_id, '_invoice_items', $items );
        }
    }

    /**
     * Sanitize and write all customer meta fields to a post.
     *
     * @param int   $post_id
     * @param array $meta_data
     */
    private function restore_customer_meta( int $post_id, array $meta_data ): void {
        // -- Single-line text fields ------------------------------------------
        $text_fields = array(
            '_customer_name',
            '_customer_company',
            '_customer_phone',
        );
        foreach ( $text_fields as $key ) {
            if ( isset( $meta_data[ $key ] ) ) {
                update_post_meta( $post_id, $key, sanitize_text_field( $meta_data[ $key ] ) );
            }
        }

        // -- Email ------------------------------------------------------------
        if ( isset( $meta_data['_customer_email'] ) ) {
            update_post_meta( $post_id, '_customer_email', sanitize_email( $meta_data['_customer_email'] ) );
        }

        // -- URL --------------------------------------------------------------
        if ( isset( $meta_data['_customer_url'] ) ) {
            update_post_meta( $post_id, '_customer_url', esc_url_raw( $meta_data['_customer_url'] ) );
        }

        // -- Textarea ---------------------------------------------------------
        if ( isset( $meta_data['_customer_address'] ) ) {
            update_post_meta( $post_id, '_customer_address', sanitize_textarea_field( $meta_data['_customer_address'] ) );
        }
    }

    /**
     * Recalculate and update _invoice_subtotal and _invoice_total from line items.
     *
     * Subtotal = sum of item['amount'] for all items where type !== 'section'.
     * Total    = subtotal + tax - discount + shipping.
     *
     * @param int $post_id
     */
    private function recalculate_totals( int $post_id ): void {
        $items = get_post_meta( $post_id, '_invoice_items', true );
        if ( ! is_array( $items ) ) {
            $items = array();
        }

        $subtotal = 0;
        foreach ( $items as $item ) {
            if ( ( $item['type'] ?? 'item' ) !== 'section' ) {
                $subtotal += floatval( $item['amount'] );
            }
        }

        $tax      = floatval( get_post_meta( $post_id, '_invoice_tax', true ) );
        $discount = floatval( get_post_meta( $post_id, '_invoice_discount', true ) );
        $shipping = floatval( get_post_meta( $post_id, '_invoice_shipping', true ) );
        $total    = $subtotal + $tax - $discount + $shipping;

        update_post_meta( $post_id, '_invoice_subtotal', $subtotal );
        update_post_meta( $post_id, '_invoice_total', $total );
    }
}
