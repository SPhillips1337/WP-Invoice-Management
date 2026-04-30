<?php
namespace Wpim\Invoice\API;

class REST_API {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'wp-invoice/v1', '/invoices', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_invoices' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'create_invoice' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/invoices/(?P<id>\d+)', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_invoice' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'  => 'PUT',
                'callback' => array( $this, 'update_invoice' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'  => 'DELETE',
                'callback' => array( $this, 'delete_invoice' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/invoices/(?P<id>\d+)/status', array(
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'update_status' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/customers', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_customers' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );
 
        register_rest_route( 'wp-invoice/v1', '/import/upload', array(
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'upload_import_file' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/import/process', array(
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'process_import_batch' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );
    }

    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    public function get_invoices( $request ) {
        $per_page = $request->get_param( 'per_page' ) ?: 10;
        $page     = $request->get_param( 'page' ) ?: 1;
        $search   = $request->get_param( 'search' );
        $orderby  = $request->get_param( 'orderby' ) ?: 'date';
        $order    = $request->get_param( 'order' ) ?: 'DESC';

        $args = array(
            'post_type'      => 'wp_invoice',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => $orderby,
            'order'          => $order,
            's'              => $search,
            'post_status'    => 'publish',
        );

        if ( ! current_user_can( 'manage_options' ) ) {
            $args['author'] = get_current_user_id();
        }

        $query = new \WP_Query( $args );
        $invoices = array();

        foreach ( $query->posts as $post ) {
            $invoices[] = $this->prepare_invoice( $post );
        }

        return rest_ensure_response( array(
            'items' => $invoices,
            'total' => (int) $query->found_posts,
            'pages' => $query->max_num_pages,
        ) );
    }

    public function get_invoice( $request ) {
        $post = get_post( $request->get_param( 'id' ) );

        if ( ! $post || $post->post_type !== 'wp_invoice' ) {
            return new \WP_Error( 'not_found', 'Invoice not found', array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return new \WP_Error( 'forbidden', 'You do not have permission to view this invoice', array( 'status' => 403 ) );
        }

        return rest_ensure_response( $this->prepare_invoice( $post ) );
    }

    public function create_invoice( $request ) {
        $data = $request->get_json_params();

        $post_id = wp_insert_post( array(
            'post_type'   => 'wp_invoice',
            'post_status' => 'publish',
            'post_title'  => isset( $data['title'] ) ? $data['title'] : 'Invoice',
        ) );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $this->save_invoice_meta( $post_id, $data );

        return rest_ensure_response( $this->prepare_invoice( get_post( $post_id ) ) );
    }

    public function update_invoice( $request ) {
        $post_id = $request->get_param( 'id' );
        $post    = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'wp_invoice' ) {
            return new \WP_Error( 'not_found', 'Invoice not found', array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return new \WP_Error( 'forbidden', 'You do not have permission to edit this invoice', array( 'status' => 403 ) );
        }

        $data = $request->get_json_params();

        if ( isset( $data['title'] ) ) {
            wp_update_post( array( 'ID' => $post_id, 'post_title' => $data['title'] ) );
        }

        $this->save_invoice_meta( $post_id, $data );

        return rest_ensure_response( $this->prepare_invoice( get_post( $post_id ) ) );
    }

    public function delete_invoice( $request ) {
        $post_id = $request->get_param( 'id' );
        $post    = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'wp_invoice' ) {
            return new \WP_Error( 'not_found', 'Invoice not found', array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'delete_post', $post_id ) ) {
            return new \WP_Error( 'forbidden', 'You do not have permission to delete this invoice', array( 'status' => 403 ) );
        }

        wp_delete_post( $post_id, true );

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    public function update_status( $request ) {
        $post_id = $request->get_param( 'id' );
        $post    = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'wp_invoice' ) {
            return new \WP_Error( 'not_found', 'Invoice not found', array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return new \WP_Error( 'forbidden', 'You do not have permission to edit this invoice', array( 'status' => 403 ) );
        }

        $data = $request->get_json_params();
        update_post_meta( $post_id, '_invoice_status', sanitize_text_field( $data['status'] ) );

        return rest_ensure_response( $this->prepare_invoice( get_post( $post_id ) ) );
    }

    public function get_customers( $request ) {
        $args = array(
            'post_type'      => 'wp_customer',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        if ( ! current_user_can( 'manage_options' ) ) {
            $args['author'] = get_current_user_id();
        }

        $query = new \WP_Query( $args );
        $customers = array();

        foreach ( $query->posts as $post ) {
            $customers[] = array(
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'company'  => get_post_meta( $post->ID, '_customer_company', true ),
                'email'    => get_post_meta( $post->ID, '_customer_email', true ),
                'phone'    => get_post_meta( $post->ID, '_customer_phone', true ),
                'address'  => get_post_meta( $post->ID, '_customer_address', true ),
            );
        }

        return rest_ensure_response( $customers );
    }

    public function upload_import_file( $request ) {
        $files = $request->get_file_params();
        if ( empty( $files['file'] ) ) {
            return new \WP_Error( 'no_file', 'No file uploaded', array( 'status' => 400 ) );
        }

        $importer = new \Wpim\Invoice\Lib\Importer();
        $invoices = $importer->parse_to_array( $files['file']['tmp_name'] );

        if ( is_wp_error( $invoices ) ) {
            return $invoices;
        }

        $job_id = uniqid( 'import_' );
        set_transient( $job_id, $invoices, HOUR_IN_SECONDS );

        return rest_ensure_response( array(
            'success' => true,
            'job_id'  => $job_id,
            'total'   => count( $invoices ),
        ) );
    }

    public function process_import_batch( $request ) {
        $job_id = $request->get_param( 'job_id' );
        $offset = (int) $request->get_param( 'offset' );
        $limit  = (int) $request->get_param( 'limit' ) ?: 5;

        $invoices = get_transient( $job_id );
        if ( ! $invoices ) {
            return new \WP_Error( 'invalid_job', 'Import job not found or expired', array( 'status' => 404 ) );
        }

        $batch = array_slice( $invoices, $offset, $limit );
        $importer = new \Wpim\Invoice\Lib\Importer();
        $author_id = get_current_user_id();
        $imported = array();

        foreach ( $batch as $invoice_data ) {
            $result = $importer->import_single_invoice( $invoice_data, $author_id );
            if ( ! is_wp_error( $result ) ) {
                $imported[] = $invoice_data['title'];
            }
        }

        // If finished, delete transient
        if ( $offset + $limit >= count( $invoices ) ) {
            delete_transient( $job_id );
        }

        return rest_ensure_response( array(
            'success'  => true,
            'imported' => $imported,
            'finished' => ( $offset + $limit >= count( $invoices ) ),
        ) );
    }

    public function import_csv( $request ) {
        // ... (can be deprecated or kept for simple imports)
    }

    private function save_invoice_meta( $post_id, $data ) {
        $meta_fields = array(
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
        );

        foreach ( $meta_fields as $field ) {
            $key = str_replace( '_invoice_', 'invoice_', $field );
            if ( isset( $data[ $key ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $data[ $key ] ) );
            }
        }

        if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
            $items = array();
            foreach ( $data['items'] as $item ) {
                if ( ! empty( $item['description'] ) ) {
                    $items[] = array(
                        'description' => sanitize_textarea_field( $item['description'] ),
                        'quantity'    => floatval( $item['quantity'] ),
                        'rate'        => floatval( $item['rate'] ),
                        'amount'      => floatval( $item['quantity'] ) * floatval( $item['rate'] ),
                    );
                }
            }
            update_post_meta( $post_id, '_invoice_items', $items );

            $subtotal = 0;
            foreach ( $items as $item ) {
                $subtotal += floatval( $item['amount'] );
            }

            $tax      = floatval( get_post_meta( $post_id, '_invoice_tax', true ) );
            $discount = floatval( get_post_meta( $post_id, '_invoice_discount', true ) );
            $shipping = floatval( get_post_meta( $post_id, '_invoice_shipping', true ) );
            $total    = $subtotal + $tax - $discount + $shipping;

            update_post_meta( $post_id, '_invoice_subtotal', $subtotal );
            update_post_meta( $post_id, '_invoice_total', $total );
        }
    }

    private function prepare_invoice( $post ) {
        $items = get_post_meta( $post->ID, '_invoice_items', true );
        if ( ! is_array( $items ) ) {
            $items = array();
        }

        $logo_id = get_post_meta( $post->ID, '_invoice_logo_id', true );
        return array(
            'id'           => $post->ID,
            'title'        => $post->post_title,
            'status'       => get_post_meta( $post->ID, '_invoice_status', true ) ?: 'open',
            'logo_id'      => $logo_id,
            'logo_url'     => $logo_id ? wp_get_attachment_url( $logo_id ) : '',
            'from'         => get_post_meta( $post->ID, '_invoice_from', true ),
            'to'           => get_post_meta( $post->ID, '_invoice_to', true ),
            'ship_to'      => get_post_meta( $post->ID, '_invoice_ship_to', true ),
            'date'         => get_post_meta( $post->ID, '_invoice_date', true ),
            'due_date'     => get_post_meta( $post->ID, '_invoice_due_date', true ),
            'po_number'    => get_post_meta( $post->ID, '_invoice_po_number', true ),
            'items'        => $items,
            'notes'        => get_post_meta( $post->ID, '_invoice_notes', true ),
            'terms'        => get_post_meta( $post->ID, '_invoice_terms', true ),
            'subtotal'     => floatval( get_post_meta( $post->ID, '_invoice_subtotal', true ) ),
            'tax'          => floatval( get_post_meta( $post->ID, '_invoice_tax', true ) ),
            'discount'     => floatval( get_post_meta( $post->ID, '_invoice_discount', true ) ),
            'shipping'     => floatval( get_post_meta( $post->ID, '_invoice_shipping', true ) ),
            'amount_paid' => floatval( get_post_meta( $post->ID, '_invoice_amount_paid', true ) ),
            'total'        => floatval( get_post_meta( $post->ID, '_invoice_total', true ) ),
            'created_at'   => $post->post_date,
            'modified_at'  => $post->post_modified,
            'view_url'     => add_query_arg( 'wp_invoice_pdf', $post->ID, home_url() ),
            'edit_url'     => add_query_arg( array( 'invoice_editor' => 1, 'id' => $post->ID ), home_url() ),
        );
    }
}
