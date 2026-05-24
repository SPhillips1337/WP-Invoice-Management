<?php
/**
 * BackupExporter — collects all published invoices and customers and assembles
 * the complete backup payload ready for json_encode().
 *
 * @package Wpim\Invoice\Lib
 */

namespace Wpim\Invoice\Lib;

class BackupExporter {

    /**
     * Backup format version. Increment when the schema changes in a
     * backwards-incompatible way so the importer can reject stale files.
     */
    const FORMAT_VERSION = '1.0';

    /**
     * Number of posts fetched per WP_Query page.
     * Keeps memory usage predictable on large sites.
     */
    const BATCH_SIZE = 100;

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Build the complete backup array.
     *
     * Fetches all published invoices and customers (in batches to avoid
     * memory exhaustion) and wraps them with a metadata header.
     *
     * @return array  Backup payload ready for json_encode().
     */
    public function build_backup(): array {
        $invoices  = $this->collect_invoices();
        $customers = $this->collect_customers();

        $metadata = array(
            'export_date'       => date( 'c' ),
            'plugin_version'    => defined( 'WPIM_VERSION' ) ? WPIM_VERSION : '',
            'wordpress_version' => get_bloginfo( 'version' ),
            'format_version'    => self::FORMAT_VERSION,
        );

        return array(
            'metadata'  => $metadata,
            'invoices'  => $invoices,
            'customers' => $customers,
        );
    }

    // -------------------------------------------------------------------------
    // Collection helpers
    // -------------------------------------------------------------------------

    /**
     * Collect all published wp_invoice posts with their meta.
     *
     * Uses a paginated WP_Query (BATCH_SIZE posts per page) so that sites
     * with thousands of invoices do not exhaust PHP memory in a single query.
     *
     * @return array[]  Each element: ['post_data' => [...], 'meta_data' => [...]]
     */
    private function collect_invoices(): array {
        $invoices = array();
        $page     = 1;

        do {
            $args = array(
                'post_type'      => 'wp_invoice',
                'post_status'    => 'publish',
                'posts_per_page' => self::BATCH_SIZE,
                'paged'          => $page,
                'no_found_rows'  => false, // we need max_num_pages
            );

            if ( ! current_user_can( 'manage_options' ) ) {
                $args['author'] = get_current_user_id();
            }

            $query = new \WP_Query( $args );

            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post ) {
                    $invoices[] = array(
                        'post_data' => $this->prepare_post_data( $post ),
                        'meta_data' => $this->prepare_invoice_meta( $post->ID ),
                    );
                }
            }

            $max_pages = $query->max_num_pages;
            wp_reset_postdata();
            $page++;

        } while ( $page <= $max_pages );

        return $invoices;
    }

    /**
     * Collect all published wp_customer posts with their meta.
     *
     * Customers are typically far fewer than invoices, so a single query
     * with posts_per_page=-1 is acceptable here.
     *
     * @return array[]  Each element: ['post_data' => [...], 'meta_data' => [...]]
     */
    private function collect_customers(): array {
        $customers = array();

        $args = array(
            'post_type'      => 'wp_customer',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        if ( ! current_user_can( 'manage_options' ) ) {
            $args['author'] = get_current_user_id();
        }

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $customers[] = array(
                    'post_data' => $this->prepare_post_data( $post ),
                    'meta_data' => $this->prepare_customer_meta( $post->ID ),
                );
            }
        }

        wp_reset_postdata();

        return $customers;
    }

    // -------------------------------------------------------------------------
    // Data preparation helpers
    // -------------------------------------------------------------------------

    /**
     * Build the post_data sub-array from a WP_Post object.
     *
     * Only the fields needed to recreate the post on import are included.
     * The post ID is intentionally omitted — the importer will assign a new one.
     *
     * @param \WP_Post $post
     * @return array
     */
    private function prepare_post_data( \WP_Post $post ): array {
        return array(
            'post_title'    => $post->post_title,
            'post_date'     => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'post_status'   => $post->post_status,
            'post_author'   => $post->post_author,
        );
    }

    /**
     * Build the meta_data sub-array for an invoice post.
     *
     * Numeric fields are cast to float so the JSON output contains number
     * literals (e.g. 150.0) rather than quoted strings (e.g. "150.0").
     * _invoice_items is kept as a PHP array; json_encode() will serialise it
     * as a JSON array automatically.
     *
     * @param int $post_id
     * @return array
     */
    private function prepare_invoice_meta( int $post_id ): array {
        // Fields that must be cast to float in the export.
        $numeric_keys = array(
            '_invoice_tax',
            '_invoice_discount',
            '_invoice_shipping',
            '_invoice_amount_paid',
            '_invoice_subtotal',
            '_invoice_total',
        );

        // All 17 invoice meta keys defined in the backup format spec.
        $all_keys = array(
            '_invoice_logo_id',
            '_invoice_from',
            '_invoice_to',
            '_invoice_ship_to',
            '_invoice_date',
            '_invoice_due_date',
            '_invoice_po_number',
            '_invoice_notes',
            '_invoice_terms',
            '_invoice_tax',
            '_invoice_discount',
            '_invoice_shipping',
            '_invoice_amount_paid',
            '_invoice_status',
            '_invoice_subtotal',
            '_invoice_total',
            '_invoice_items',
        );

        $meta = array();

        foreach ( $all_keys as $key ) {
            if ( '_invoice_items' === $key ) {
                // Line items are stored as a serialised array in post meta.
                // get_post_meta with $single=true returns the unserialized value.
                $items        = get_post_meta( $post_id, '_invoice_items', true );
                $meta[ $key ] = is_array( $items ) ? $items : array();
            } elseif ( in_array( $key, $numeric_keys, true ) ) {
                $meta[ $key ] = floatval( get_post_meta( $post_id, $key, true ) );
            } else {
                $meta[ $key ] = get_post_meta( $post_id, $key, true );
            }
        }

        return $meta;
    }

    /**
     * Build the meta_data sub-array for a customer post.
     *
     * All 6 customer meta fields are plain strings; no casting is needed.
     *
     * @param int $post_id
     * @return array
     */
    private function prepare_customer_meta( int $post_id ): array {
        $keys = array(
            '_customer_name',
            '_customer_company',
            '_customer_email',
            '_customer_phone',
            '_customer_url',
            '_customer_address',
        );

        $meta = array();

        foreach ( $keys as $key ) {
            $meta[ $key ] = get_post_meta( $post_id, $key, true );
        }

        return $meta;
    }
}
