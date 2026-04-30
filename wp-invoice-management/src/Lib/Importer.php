<?php
namespace Wpim\Invoice\Lib;

class Importer {
    /**
     * Get the total number of rows (excluding header) in a CSV file.
     *
     * @param string $file_path
     * @return int|WP_Error
     */
    public function get_row_count( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return new \WP_Error( 'file_not_found', 'CSV file not found' );
        }
        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return new \WP_Error( 'file_read_error', 'Could not open CSV file' );
        }
        $count = 0;
        fgetcsv( $handle ); // Skip header
        while ( fgetcsv( $handle ) !== false ) {
            $count++;
        }
        fclose( $handle );
        return $count;
    }

    /**
     * Import invoices from a CSV file.
     *
     * @param string $file_path Path to the CSV file.
     * @param int|null $user_id Optional user ID to assign as author.
     * @return int|WP_Error Number of imported invoices or WP_Error.
     */
    /**
     * Parse CSV file into an array of grouped invoices.
     *
     * @param string $file_path
     * @return array|WP_Error
     */
    public function parse_to_array( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return new \WP_Error( 'file_not_found', 'CSV file not found' );
        }

        $handle = fopen( $file_path, 'r' );
        if ( ! $handle ) {
            return new \WP_Error( 'file_read_error', 'Could not open CSV file' );
        }

        $header = fgetcsv( $handle );
        if ( ! $header || ! is_array( $header ) ) {
            fclose( $handle );
            return new \WP_Error( 'invalid_csv', 'Invalid or empty CSV header' );
        }

        $invoices = array();

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( count( $row ) !== count( $header ) ) {
                continue;
            }

            $data = array_combine( $header, $row );
            $invoice_number = $data['number'];
            $customer = $data['customer'];
            $key = $customer . '_' . $invoice_number;

            if ( ! isset( $invoices[ $key ] ) ) {
                $invoices[ $key ] = array(
                    'title'               => 'Invoice #' . $invoice_number,
                    'from'                => '', // Missing from CSV, will need manual update or setting
                    'to'                  => $customer,
                    'date'                => $this->format_date( $data['date'] ),
                    'due_date'            => $this->format_date( $data['due_date'] ),
                    'po_number'           => $data['purchase_order'],
                    'notes'               => $data['notes'],
                    'terms'               => $data['terms'],
                    'discount'            => floatval( $data['discount'] ),
                    'tax'                 => floatval( $data['tax'] ),
                    'shipping'            => floatval( $data['shipping'] ),
                    'amount_paid'         => floatval( $data['amount_paid'] ),
                    'items'               => array(),
                );
            }

            $description = ! empty( $data['description'] ) ? $data['description'] : $data['item'];
            $invoices[ $key ]['items'][] = array(
                'description' => $description,
                'quantity'    => floatval( $data['quantity'] ),
                'rate'        => floatval( $data['unit_cost'] ),
            );
        }
        fclose( $handle );
        return array_values( $invoices );
    }

    /**
     * Import a single invoice array into WordPress.
     *
     * @param array $invoice_data
     * @param int $author_id
     * @return int|WP_Error
     */
    public function import_single_invoice( $invoice_data, $author_id ) {
        $post_id = wp_insert_post( array(
            'post_type'   => 'wp_invoice',
            'post_status' => 'publish',
            'post_title'  => $invoice_data['title'],
            'post_author' => $author_id,
        ) );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $this->save_invoice_meta( $post_id, $invoice_data );
        return $post_id;
    }

    public function import_csv( $file_path, $user_id = null ) {
        $invoices = $this->parse_to_array( $file_path );
        if ( is_wp_error( $invoices ) ) {
            return $invoices;
        }

        $author_id = $user_id ?: get_current_user_id();
        $imported_count = 0;
        foreach ( $invoices as $invoice_data ) {
            $result = $this->import_single_invoice( $invoice_data, $author_id );
            if ( ! is_wp_error( $result ) ) {
                $imported_count++;
            }
        }

        return $imported_count;
    }

    /**
     * Format date to YYYY-MM-DD for HTML inputs.
     *
     * @param string $date_string
     * @return string
     */
    private function format_date( $date_string ) {
        if ( empty( $date_string ) ) {
            return '';
        }
        $timestamp = strtotime( $date_string );
        return $timestamp ? date( 'Y-m-d', $timestamp ) : '';
    }

    /**
     * Save metadata for an imported invoice.
     *
     * @param int $post_id
     * @param array $data
     */
    private function save_invoice_meta( $post_id, $data ) {
        // Reuse the logic from REST_API if possible, or duplicate for now
        // Mapping as per the new REST_API structure
        $meta_fields = array(
            '_invoice_from'        => 'from',
            '_invoice_to'          => 'to',
            '_invoice_date'        => 'date',
            '_invoice_due_date'    => 'due_date',
            '_invoice_po_number'   => 'po_number',
            '_invoice_notes'       => 'notes',
            '_invoice_terms'       => 'terms',
            '_invoice_tax'         => 'tax',
            '_invoice_discount'    => 'discount',
            '_invoice_shipping'    => 'shipping',
            '_invoice_amount_paid' => 'amount_paid',
        );

        foreach ( $meta_fields as $meta_key => $data_key ) {
            if ( isset( $data[ $data_key ] ) ) {
                $value = $data[ $data_key ];
                if ( in_array( $data_key, array( 'from', 'to', 'notes', 'terms' ) ) ) {
                    $value = sanitize_textarea_field( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }
                update_post_meta( $post_id, $meta_key, $value );
            }
        }

        if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
            $items = array();
            foreach ( $data['items'] as $item ) {
                $items[] = array(
                    'description' => sanitize_textarea_field( $item['description'] ),
                    'quantity'    => floatval( $item['quantity'] ),
                    'rate'        => floatval( $item['rate'] ),
                    'amount'      => floatval( $item['quantity'] ) * floatval( $item['rate'] ),
                );
            }
            update_post_meta( $post_id, '_invoice_items', $items );

            $subtotal = 0;
            foreach ( $items as $item ) {
                $subtotal += floatval( $item['amount'] );
            }

            $tax      = isset( $data['tax'] ) ? floatval( $data['tax'] ) : 0;
            $discount = isset( $data['discount'] ) ? floatval( $data['discount'] ) : 0;
            $shipping = isset( $data['shipping'] ) ? floatval( $data['shipping'] ) : 0;
            $total    = $subtotal + $tax - $discount + $shipping;

            update_post_meta( $post_id, '_invoice_subtotal', $subtotal );
            update_post_meta( $post_id, '_invoice_total', $total );
            update_post_meta( $post_id, '_invoice_status', 'open' );
        }
    }
}
