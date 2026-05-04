<?php
namespace Wpim\Invoice\API;

class REST_API {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * JSON Schema args for invoice write endpoints (POST create / PUT update).
     * WordPress validates these before the callback runs.
     */
    private function invoice_write_args() {
        return array(
            'title'       => array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'status'      => array(
                'type' => 'string',
                'enum' => array( 'open', 'paid', 'overdue', 'draft', 'sent' ),
            ),
            'date'        => array( 'type' => 'string' ),
            'due_date'    => array( 'type' => 'string' ),
            'po_number'   => array( 'type' => 'string' ),
            'from'        => array( 'type' => 'string' ),
            'to'          => array( 'type' => 'string' ),
            'ship_to'     => array( 'type' => 'string' ),
            'notes'       => array( 'type' => 'string' ),
            'terms'       => array( 'type' => 'string' ),
            'tax'         => array( 'type' => 'number' ),
            'discount'    => array( 'type' => 'number' ),
            'shipping'    => array( 'type' => 'number' ),
            'amount_paid' => array( 'type' => 'number' ),
            'items'       => array(
                'type'  => 'array',
                'items' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'description' => array( 'type' => 'string' ),
                        'type'        => array(
                            'type' => 'string',
                            'enum' => array( 'item', 'section' ),
                        ),
                        'quantity'    => array( 'type' => 'number' ),
                        'rate'        => array( 'type' => 'number' ),
                        'date'        => array( 'type' => 'string' ),
                    ),
                ),
            ),
        );
    }

    public function register_routes() {
        register_rest_route( 'wp-invoice/v1', '/invoices', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_invoices' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'create_invoice' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => $this->invoice_write_args(),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/invoices/(?P<id>\d+)', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_invoice' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'update_invoice' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => $this->invoice_write_args(),
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
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'create_customer' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/customers/(?P<id>\d+)', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_customer' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'  => 'PUT',
                'callback' => array( $this, 'update_customer' ),
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

        register_rest_route( 'wp-invoice/v1', '/settings', array(
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'update_settings' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
            ),
        ) );

        register_rest_route( 'wp-invoice/v1', '/invoices/bulk-update-status', array(
            array(
                'methods'  => 'POST',
                'callback' => array( $this, 'bulk_update_status' ),
                'permission_callback' => array( $this, 'check_admin_permission' ),
                'args'     => array(
                    'before_invoice_id' => array( 'type' => 'integer', 'required' => true ),
                    'status'            => array(
                        'type' => 'string',
                        'enum' => array( 'open', 'paid', 'overdue', 'draft', 'sent' ),
                        'required' => true,
                    ),
                ),
            ),
        ) );
    }

    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }

    public function get_settings() {
        return \Wpim\Invoice\Admin\SettingsPage::get_settings();
    }

    public function update_settings( $request ) {
        $settings = $request->get_json_params();
        if ( ! $settings ) {
            return new \WP_Error( 'invalid_data', 'No data provided', array( 'status' => 400 ) );
        }

        $allowed_fields = array( 'currency_symbol', 'currency_code', 'tax_label', 'default_country', 'default_address' );
        $current_settings = \Wpim\Invoice\Admin\SettingsPage::get_settings();
        
        foreach ( $allowed_fields as $field ) {
            if ( isset( $settings[ $field ] ) ) {
                if ( $field === 'default_address' ) {
                    $current_settings[ $field ] = sanitize_textarea_field( $settings[ $field ] );
                } else {
                    $current_settings[ $field ] = sanitize_text_field( $settings[ $field ] );
                }
            }
        }

        update_option( 'wp_invoice_settings', $current_settings );
        return $current_settings;
    }

    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    public function get_invoices( $request ) {
        $per_page = absint( $request->get_param( 'per_page' ) ?: 10 );
        $page     = absint( $request->get_param( 'page' ) ?: 1 );
        $search   = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
        $order    = strtoupper( $request->get_param( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC';

        // Allowlist orderby values to prevent arbitrary meta key injection.
        $allowed_orderby = array( 'date', 'due_date', 'total', 'title' );
        $orderby         = in_array( $request->get_param( 'orderby' ), $allowed_orderby, true )
            ? $request->get_param( 'orderby' )
            : 'date';

        $args = array(
            'post_type'      => 'wp_invoice',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'order'          => $order,
            's'              => $search,
            'post_status'    => 'publish',
        );

        if ( $orderby === 'date' ) {
            $args['orderby']  = 'meta_value';
            $args['meta_key'] = '_invoice_date';
        } elseif ( $orderby === 'due_date' ) {
            $args['orderby']  = 'meta_value';
            $args['meta_key'] = '_invoice_due_date';
        } elseif ( $orderby === 'total' ) {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_invoice_total';
        } elseif ( $orderby === 'title' ) {
            $args['orderby'] = 'title';
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            $args['author'] = get_current_user_id();
        }

        $query = new \WP_Query( $args );
        $invoices = array();

        foreach ( $query->posts as $post ) {
            $invoices[] = $this->prepare_invoice( $post );
        }

        return rest_ensure_response( array(
            'invoices' => $invoices,
            'total'    => (int) $query->found_posts,
            'pages'    => $query->max_num_pages,
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

        $data   = $request->get_json_params();
        $status = isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : '';

        $allowed_statuses = array( 'open', 'paid', 'overdue', 'draft', 'sent' );
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            return new \WP_Error( 'invalid_status', 'Invalid status value', array( 'status' => 400 ) );
        }

        update_post_meta( $post_id, '_invoice_status', $status );

        return rest_ensure_response( $this->prepare_invoice( get_post( $post_id ) ) );
    }

    public function bulk_update_status( $request ) {
        $before_invoice_id = $request->get_param( 'before_invoice_id' );
        $status            = sanitize_text_field( $request->get_param( 'status' ) );

        // Get the date of the reference invoice
        $ref_date = get_post_meta( $before_invoice_id, '_invoice_date', true );
        if ( ! $ref_date ) {
            return new \WP_Error( 'invalid_invoice', 'Could not find reference invoice date', array( 'status' => 400 ) );
        }

        // Query invoices with date < ref_date
        $args = array(
            'post_type'      => 'wp_invoice',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_invoice_date',
                    'value'   => $ref_date,
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
            ),
        );

        $query    = new \WP_Query( $args );
        $updated  = 0;
        $skipped  = 0;

        foreach ( $query->posts as $post ) {
            $current = get_post_meta( $post->ID, '_invoice_status', true ) ?: 'open';
            if ( $current !== $status ) {
                update_post_meta( $post->ID, '_invoice_status', $status );
                $updated++;
            } else {
                $skipped++;
            }
        }

        return rest_ensure_response( array(
            'updated' => $updated,
            'skipped' => $skipped,
            'message' => sprintf( 'Updated %d invoices to "%s" status (skipped %d already with that status).', $updated, $status, $skipped ),
        ) );
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
                'name'     => get_post_meta( $post->ID, '_customer_name', true ),
                'company'  => get_post_meta( $post->ID, '_customer_company', true ),
                'email'    => get_post_meta( $post->ID, '_customer_email', true ),
                'phone'    => get_post_meta( $post->ID, '_customer_phone', true ),
                'url'      => get_post_meta( $post->ID, '_customer_url', true ),
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

        try {
            $tmp_file = $files['file']['tmp_name'];
            if ( ! file_exists( $tmp_file ) || ! is_readable( $tmp_file ) ) {
                error_log( 'Import Upload Error: Temp file not found or not readable at ' . $tmp_file );
                return new \WP_Error( 'file_error', 'Temporary file not accessible on server', array( 'status' => 500 ) );
            }

            $importer = new \Wpim\Invoice\Lib\Importer();
            $invoices = $importer->parse_to_array( $tmp_file );

            if ( is_wp_error( $invoices ) ) {
                error_log( 'Import Parse Error: ' . $invoices->get_error_message() );
                return $invoices;
            }

            if ( empty( $invoices ) ) {
                return new \WP_Error( 'empty_import', 'No valid invoices found in CSV', array( 'status' => 400 ) );
            }

            $job_id = uniqid( 'import_' );
            // Store invoices in transient for batch processing
            set_transient( $job_id, $invoices, HOUR_IN_SECONDS );

            return rest_ensure_response( array(
                'success' => true,
                'job_id'  => $job_id,
                'total'   => count( $invoices ),
            ) );
        } catch ( \Exception $e ) {
            error_log( 'Import Exception: ' . $e->getMessage() );
            return new \WP_Error( 'import_failed', 'Internal server error during upload: ' . $e->getMessage(), array( 'status' => 500 ) );
        }
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
            '_invoice_logo_id'     => 'logo_id',
            '_invoice_from'        => 'from',
            '_invoice_to'          => 'to',
            '_invoice_ship_to'     => 'ship_to',
            '_invoice_date'        => 'date',
            '_invoice_due_date'    => 'due_date',
            '_invoice_po_number'   => 'po_number',
            '_invoice_notes'       => 'notes',
            '_invoice_terms'       => 'terms',
            '_invoice_tax'         => 'tax',
            '_invoice_discount'    => 'discount',
            '_invoice_shipping'    => 'shipping',
            '_invoice_amount_paid' => 'amount_paid',
            '_invoice_status'      => 'status',
        );

        foreach ( $meta_fields as $meta_key => $data_key ) {
            if ( isset( $data[ $data_key ] ) ) {
                $value = $data[ $data_key ];
                
                // Use appropriate sanitization based on field type
                if ( in_array( $data_key, array( 'from', 'to', 'ship_to', 'notes', 'terms' ), true ) ) {
                    $value = sanitize_textarea_field( $value );
                } elseif ( in_array( $data_key, array( 'tax', 'discount', 'shipping', 'amount_paid' ), true ) ) {
                    $value = floatval( $value );
                } elseif ( $data_key === 'status' ) {
                    $allowed_statuses = array( 'open', 'paid', 'overdue', 'draft', 'sent' );
                    $value = in_array( $value, $allowed_statuses, true ) ? $value : 'open';
                } else {
                    $value = sanitize_text_field( $value );
                }

                update_post_meta( $post_id, $meta_key, $value );
            }
        }

        if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
            $items = array();
            foreach ( $data['items'] as $item ) {
                // Only process items that are actual arrays to prevent type confusion.
                if ( ! is_array( $item ) ) {
                    continue;
                }
                if ( ! empty( $item['description'] ) ) {
                    $type = isset( $item['type'] ) && $item['type'] === 'section' ? 'section' : 'item';

                    // Whitelist-only keys — never persist unexpected fields.
                    $sanitized_item = array(
                        'description' => sanitize_textarea_field( $item['description'] ),
                        'type'        => $type,
                    );

                    if ( $type === 'item' ) {
                        $qty    = isset( $item['quantity'] ) ? floatval( $item['quantity'] ) : 0;
                        $rate   = isset( $item['rate'] ) ? floatval( $item['rate'] ) : 0;
                        $sanitized_item['quantity'] = $qty;
                        $sanitized_item['rate']     = $rate;
                        $sanitized_item['amount']   = $qty * $rate;
                        $sanitized_item['date']     = isset( $item['date'] ) ? sanitize_text_field( $item['date'] ) : '';
                    }

                    $items[] = $sanitized_item;
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

    public function create_customer( $request ) {
        $data = $request->get_json_params();
        if ( empty( $data['name'] ) ) {
            return new \WP_Error( 'missing_name', 'Customer Name is required', array( 'status' => 400 ) );
        }

        $customer_id = wp_insert_post( array(
            'post_type'   => 'wp_customer',
            'post_title'  => $data['name'],
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $customer_id ) ) {
            return $customer_id;
        }

        $this->save_customer_meta( $customer_id, $data );

        return rest_ensure_response( $this->get_customer_data( $customer_id ) );
    }

    public function get_customer( $request ) {
        $id   = (int) $request['id'];
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== 'wp_customer' ) {
            return new \WP_Error( 'not_found', 'Customer not found', array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'manage_options' ) && (int) $post->post_author !== get_current_user_id() ) {
            return new \WP_Error( 'forbidden', 'You do not have permission to view this customer', array( 'status' => 403 ) );
        }

        return rest_ensure_response( $this->get_customer_data( $id ) );
    }

    public function update_customer( $request ) {
        $id   = (int) $request['id'];
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== 'wp_customer' ) {
            return new \WP_Error( 'not_found', 'Customer not found', array( 'status' => 404 ) );
        }

        if ( ! current_user_can( 'manage_options' ) && (int) $post->post_author !== get_current_user_id() ) {
            return new \WP_Error( 'forbidden', 'You do not have permission to edit this customer', array( 'status' => 403 ) );
        }

        $data = $request->get_json_params();

        if ( ! empty( $data['name'] ) ) {
            wp_update_post( array(
                'ID'         => $id,
                'post_title' => sanitize_text_field( $data['name'] ),
            ) );
        }

        $this->save_customer_meta( $id, $data );

        return rest_ensure_response( $this->get_customer_data( $id ) );
    }

    private function save_customer_meta( $customer_id, $data ) {
        $fields = array(
            'name'    => '_customer_name',
            'company' => '_customer_company',
            'email'   => '_customer_email',
            'phone'   => '_customer_phone',
            'url'     => '_customer_url',
            'address' => '_customer_address',
        );

        foreach ( $fields as $key => $meta_key ) {
            if ( isset( $data[ $key ] ) ) {
                if ( $key === 'address' ) {
                    $value = sanitize_textarea_field( $data[ $key ] );
                } elseif ( $key === 'url' ) {
                    $value = esc_url_raw( $data[ $key ] );
                } elseif ( $key === 'email' ) {
                    $value = sanitize_email( $data[ $key ] );
                } else {
                    $value = sanitize_text_field( $data[ $key ] );
                }
                update_post_meta( $customer_id, $meta_key, $value );
            }
        }
    }

    private function get_customer_data( $id ) {
        $post = get_post( $id );
        return array(
            'id'       => $post->ID,
            'title'    => $post->post_title,
            'name'     => get_post_meta( $post->ID, '_customer_name', true ),
            'company'  => get_post_meta( $post->ID, '_customer_company', true ),
            'email'    => get_post_meta( $post->ID, '_customer_email', true ),
            'phone'    => get_post_meta( $post->ID, '_customer_phone', true ),
            'url'      => get_post_meta( $post->ID, '_customer_url', true ),
            'address'  => get_post_meta( $post->ID, '_customer_address', true ),
        );
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
