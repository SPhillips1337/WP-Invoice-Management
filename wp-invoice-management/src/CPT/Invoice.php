<?php
namespace Wpim\Invoice\CPT;

class Invoice {
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Invoices', 'wp-invoice-management' ),
            'singular_name'      => __( 'Invoice', 'wp-invoice-management' ),
            'menu_name'          => __( 'Invoices', 'wp-invoice-management' ),
            'name_admin_bar'     => __( 'Invoice', 'wp-invoice-management' ),
            'add_new'            => __( 'Add New', 'wp-invoice-management' ),
            'add_new_item'       => __( 'Add New Invoice', 'wp-invoice-management' ),
            'edit_item'          => __( 'Edit Invoice', 'wp-invoice-management' ),
            'new_item'           => __( 'New Invoice', 'wp-invoice-management' ),
            'view_item'          => __( 'View Invoice', 'wp-invoice-management' ),
            'search_items'       => __( 'Search Invoices', 'wp-invoice-management' ),
            'not_found'          => __( 'No invoices found', 'wp-invoice-management' ),
            'not_found_in_trash' => __( 'No invoices found in trash', 'wp-invoice-management' ),
        );

        $args = array(
            'label'               => __( 'Invoices', 'wp-invoice-management' ),
            'labels'              => $labels,
            'public'              => true,
            'show_in_menu'        => 'edit.php?post_type=wp_invoice',
            'menu_position'       => 50,
            'menu_icon'           => 'dashicons-money',
            'supports'            => array( 'title', 'editor', 'author', 'custom-fields', 'thumbnail' ),
            'taxonomies'          => array(),
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_nav_menus'   => false,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'invoices' ),
            'capability_type'     => 'post',
            'capabilities'        => array(
                'create_posts' => 'edit_posts',
                'edit_post'   => 'edit_post',
                'read_post'   => 'read_post',
                'delete_post' => 'delete_post',
            ),
            'map_meta_cap'        => true,
        );

        register_post_type( 'wp_invoice', $args );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'invoice_details',
            __( 'Invoice Details', 'wp-invoice-management' ),
            array( $this, 'render_invoice_details_metabox' ),
            'wp_invoice',
            'normal',
            'high'
        );

        add_meta_box(
            'invoice_items',
            __( 'Line Items', 'wp-invoice-management' ),
            array( $this, 'render_invoice_items_metabox' ),
            'wp_invoice',
            'normal',
            'high'
        );

        add_meta_box(
            'invoice_totals',
            __( 'Totals', 'wp-invoice-management' ),
            array( $this, 'render_invoice_totals_metabox' ),
            'wp_invoice',
            'side',
            'default'
        );

        add_meta_box(
            'invoice_status',
            __( 'Status', 'wp-invoice-management' ),
            array( $this, 'render_invoice_status_metabox' ),
            'wp_invoice',
            'side',
            'default'
        );
    }

    public function render_invoice_details_metabox( $post ) {
        wp_nonce_field( 'invoice_save_meta', 'invoice_meta_nonce' );

        $logo_id     = get_post_meta( $post->ID, '_invoice_logo_id', true );
        $from        = get_post_meta( $post->ID, '_invoice_from', true );
        $to          = get_post_meta( $post->ID, '_invoice_to', true );
        $ship_to     = get_post_meta( $post->ID, '_invoice_ship_to', true );
        $date        = get_post_meta( $post->ID, '_invoice_date', true );
        $due_date    = get_post_meta( $post->ID, '_invoice_due_date', true );
        $po_number   = get_post_meta( $post->ID, '_invoice_po_number', true );
        $notes       = get_post_meta( $post->ID, '_invoice_notes', true );
        $terms       = get_post_meta( $post->ID, '_invoice_terms', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="invoice_logo"><?php _e( 'Logo', 'wp-invoice-management' ); ?></label></th>
                <td>
                    <?php
                    if ( $logo_id ) {
                        echo wp_get_attachment_image( $logo_id, 'medium' );
                        echo '<br/>';
                    }
                    ?>
                    <input type="hidden" id="invoice_logo_id" name="invoice_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
                    <button type="button" class="button upload-logo-button"><?php _e( 'Upload Logo', 'wp-invoice-management' ); ?></button>
                </td>
            </tr>
            <tr>
                <th><label for="invoice_from"><?php _e( 'From', 'wp-invoice-management' ); ?></label></th>
                <td><textarea id="invoice_from" name="invoice_from" rows="4" class="large-text"><?php echo esc_textarea( $from ); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="invoice_to"><?php _e( 'To', 'wp-invoice-management' ); ?></label></th>
                <td><textarea id="invoice_to" name="invoice_to" rows="4" class="large-text"><?php echo esc_textarea( $to ); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="invoice_ship_to"><?php _e( 'Ship To', 'wp-invoice-management' ); ?></label></th>
                <td><textarea id="invoice_ship_to" name="invoice_ship_to" rows="4" class="large-text"><?php echo esc_textarea( $ship_to ); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="invoice_date"><?php _e( 'Invoice Date', 'wp-invoice-management' ); ?></label></th>
                <td><input type="date" id="invoice_date" name="invoice_date" value="<?php echo esc_attr( $date ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="invoice_due_date"><?php _e( 'Due Date', 'wp-invoice-management' ); ?></label></th>
                <td><input type="date" id="invoice_due_date" name="invoice_due_date" value="<?php echo esc_attr( $due_date ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="invoice_po_number"><?php _e( 'PO Number', 'wp-invoice-management' ); ?></label></th>
                <td><input type="text" id="invoice_po_number" name="invoice_po_number" value="<?php echo esc_attr( $po_number ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="invoice_notes"><?php _e( 'Notes', 'wp-invoice-management' ); ?></label></th>
                <td><textarea id="invoice_notes" name="invoice_notes" rows="3" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="invoice_terms"><?php _e( 'Terms', 'wp-invoice-management' ); ?></label></th>
                <td><textarea id="invoice_terms" name="invoice_terms" rows="3" class="large-text"><?php echo esc_textarea( $terms ); ?></textarea></td>
            </tr>
        </table>
        <?php
    }

    public function render_invoice_items_metabox( $post ) {
        $items = get_post_meta( $post->ID, '_invoice_items', true );
        if ( ! is_array( $items ) ) {
            $items = array();
        }
        ?>
        <div id="invoice-items-wrapper">
            <table class="widefat" id="invoice-items-table">
                <thead>
                    <tr>
                        <th style="width: 130px;"><?php _e( 'Date', 'wp-invoice-management' ); ?></th>
                        <th><?php _e( 'Description', 'wp-invoice-management' ); ?></th>
                        <th style="width: 80px;"><?php _e( 'Quantity', 'wp-invoice-management' ); ?></th>
                        <th style="width: 100px;"><?php _e( 'Rate', 'wp-invoice-management' ); ?></th>
                        <th style="width: 100px;"><?php _e( 'Amount', 'wp-invoice-management' ); ?></th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ( ! empty( $items ) ) :
                        foreach ( $items as $index => $item ) :
                            $is_section = isset( $item['type'] ) && $item['type'] === 'section';
                            ?>
                            <tr class="<?php echo $is_section ? 'invoice-section-row' : 'invoice-item-row'; ?>" data-index="<?php echo $index; ?>">
                                <?php if ( $is_section ) : ?>
                                    <td colspan="5">
                                        <input type="hidden" name="invoice_items[<?php echo $index; ?>][type]" value="section" />
                                        <input type="text" name="invoice_items[<?php echo $index; ?>][description]" value="<?php echo esc_attr( $item['description'] ?? '' ); ?>" class="large-text project-header-input" placeholder="Project / Section Header" style="font-weight: bold; background: #f0f6fb;" />
                                    </td>
                                <?php else : ?>
                                    <td><input type="date" name="invoice_items[<?php echo $index; ?>][date]" value="<?php echo esc_attr( $item['date'] ?? '' ); ?>" style="width: 100%;" /></td>
                                    <td><input type="text" name="invoice_items[<?php echo $index; ?>][description]" value="<?php echo esc_attr( $item['description'] ?? '' ); ?>" class="large-text" /></td>
                                    <td><input type="number" name="invoice_items[<?php echo $index; ?>][quantity]" value="<?php echo esc_attr( $item['quantity'] ?? 1 ); ?>" class="quantity" min="0" step="0.01" style="width: 100%;" /></td>
                                    <td><input type="number" name="invoice_items[<?php echo $index; ?>][rate]" value="<?php echo esc_attr( $item['rate'] ?? 0 ); ?>" class="rate" min="0" step="0.01" style="width: 100%;" /></td>
                                    <td><input type="number" name="invoice_items[<?php echo $index; ?>][amount]" value="<?php echo esc_attr( $item['amount'] ?? 0 ); ?>" class="amount" readonly style="width: 100%;" /></td>
                                <?php endif; ?>
                                <td><button type="button" class="button remove-item">X</button></td>
                            </tr>
                            <?php
                        endforeach;
                    else :
                        ?>
                        <tr class="invoice-item-row" data-index="0">
                            <td><input type="date" name="invoice_items[0][date]" value="" style="width: 100%;" /></td>
                            <td><input type="text" name="invoice_items[0][description]" value="" class="large-text" /></td>
                            <td><input type="number" name="invoice_items[0][quantity]" value="1" class="quantity" min="0" step="0.01" style="width: 100%;" /></td>
                            <td><input type="number" name="invoice_items[0][rate]" value="0" class="rate" min="0" step="0.01" style="width: 100%;" /></td>
                            <td><input type="number" name="invoice_items[0][amount]" value="0" class="amount" readonly style="width: 100%;" /></td>
                            <td><button type="button" class="button remove-item">X</button></td>
                        </tr>
                        <?php
                    endif;
                    ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="add-invoice-item"><?php _e( 'Add Item', 'wp-invoice-management' ); ?></button>
                <button type="button" class="button" id="add-invoice-project"><?php _e( 'Add Project Row', 'wp-invoice-management' ); ?></button>
            </p>
        </div>
        <?php
    }

    public function render_invoice_totals_metabox( $post ) {
        $subtotal     = get_post_meta( $post->ID, '_invoice_subtotal', true );
        $tax          = get_post_meta( $post->ID, '_invoice_tax', true );
        $discount     = get_post_meta( $post->ID, '_invoice_discount', true );
        $shipping     = get_post_meta( $post->ID, '_invoice_shipping', true );
        $amount_paid  = get_post_meta( $post->ID, '_invoice_amount_paid', true );
        $total        = get_post_meta( $post->ID, '_invoice_total', true );
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e( 'Subtotal', 'wp-invoice-management' ); ?></th>
                <td><input type="number" name="invoice_subtotal" value="<?php echo esc_attr( $subtotal ); ?>" class="regular-text" step="0.01" readonly /></td>
            </tr>
            <tr>
                <th><?php _e( 'Tax', 'wp-invoice-management' ); ?></th>
                <td><input type="number" name="invoice_tax" value="<?php echo esc_attr( $tax ); ?>" class="regular-text" step="0.01" /></td>
            </tr>
            <tr>
                <th><?php _e( 'Discount', 'wp-invoice-management' ); ?></th>
                <td><input type="number" name="invoice_discount" value="<?php echo esc_attr( $discount ); ?>" class="regular-text" step="0.01" /></td>
            </tr>
            <tr>
                <th><?php _e( 'Shipping', 'wp-invoice-management' ); ?></th>
                <td><input type="number" name="invoice_shipping" value="<?php echo esc_attr( $shipping ); ?>" class="regular-text" step="0.01" /></td>
            </tr>
            <tr>
                <th><?php _e( 'Amount Paid', 'wp-invoice-management' ); ?></th>
                <td><input type="number" name="invoice_amount_paid" value="<?php echo esc_attr( $amount_paid ); ?>" class="regular-text" step="0.01" /></td>
            </tr>
            <tr>
                <th><strong><?php _e( 'Total', 'wp-invoice-management' ); ?></strong></th>
                <td><strong><input type="number" name="invoice_total" value="<?php echo esc_attr( $total ); ?>" class="regular-text" step="0.01" readonly /></strong></td>
            </tr>
        </table>
        <?php
    }

    public function render_invoice_status_metabox( $post ) {
        $status = get_post_meta( $post->ID, '_invoice_status', true );
        if ( ! $status ) {
            $status = 'open';
        }
        ?>
        <select name="invoice_status" id="invoice_status">
            <option value="open" <?php selected( $status, 'open' ); ?>><?php _e( 'Open', 'wp-invoice-management' ); ?></option>
            <option value="paid" <?php selected( $status, 'paid' ); ?>><?php _e( 'Paid', 'wp-invoice-management' ); ?></option>
            <option value="overdue" <?php selected( $status, 'overdue' ); ?>><?php _e( 'Overdue', 'wp-invoice-management' ); ?></option>
            <option value="draft" <?php selected( $status, 'draft' ); ?>><?php _e( 'Draft', 'wp-invoice-management' ); ?></option>
        </select>
        <?php
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['invoice_meta_nonce'] ) || ! wp_verify_nonce( $_POST['invoice_meta_nonce'], 'invoice_save_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $meta_fields = array(
            'invoice_logo_id',
            'invoice_from',
            'invoice_to',
            'invoice_ship_to',
            'invoice_date',
            'invoice_due_date',
            'invoice_po_number',
            'invoice_notes',
            'invoice_terms',
            'invoice_subtotal',
            'invoice_tax',
            'invoice_discount',
            'invoice_shipping',
            'invoice_amount_paid',
            'invoice_total',
            'invoice_status',
        );

        foreach ( $meta_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        if ( isset( $_POST['invoice_items'] ) ) {
            $items = array();
            foreach ( $_POST['invoice_items'] as $item ) {
                if ( ! empty( $item['description'] ) ) {
                    $new_item = array(
                        'description' => sanitize_textarea_field( $item['description'] ),
                        'type'        => isset( $item['type'] ) ? sanitize_text_field( $item['type'] ) : 'item',
                    );

                    if ( $new_item['type'] === 'item' ) {
                        $new_item['date']     = sanitize_text_field( $item['date'] ?? '' );
                        $new_item['quantity'] = floatval( $item['quantity'] );
                        $new_item['rate']     = floatval( $item['rate'] );
                        $new_item['amount']   = floatval( $item['quantity'] ) * floatval( $item['rate'] );
                    }

                    $items[] = $new_item;
                }
            }
            update_post_meta( $post_id, '_invoice_items', $items );
            $this->calculate_totals( $post_id, $items );
        }
    }

    private function calculate_totals( $post_id, $items ) {
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
