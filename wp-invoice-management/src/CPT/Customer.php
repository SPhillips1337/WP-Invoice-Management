<?php
namespace Wpim\Invoice\CPT;

class Customer {
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Customers', 'wp-invoice-management' ),
            'singular_name'      => __( 'Customer', 'wp-invoice-management' ),
            'menu_name'          => __( 'Customers', 'wp-invoice-management' ),
            'name_admin_bar'     => __( 'Customer', 'wp-invoice-management' ),
            'add_new'            => __( 'Add New', 'wp-invoice-management' ),
            'add_new_item'       => __( 'Add New Customer', 'wp-invoice-management' ),
            'edit_item'          => __( 'Edit Customer', 'wp-invoice-management' ),
            'new_item'           => __( 'New Customer', 'wp-invoice-management' ),
            'view_item'          => __( 'View Customer', 'wp-invoice-management' ),
            'search_items'       => __( 'Search Customers', 'wp-invoice-management' ),
            'not_found'          => __( 'No customers found', 'wp-invoice-management' ),
            'not_found_in_trash' => __( 'No customers found in trash', 'wp-invoice-management' ),
        );

        $args = array(
            'label'               => __( 'Customers', 'wp-invoice-management' ),
            'labels'              => $labels,
            'public'              => true,
            'show_in_menu'        => 'edit.php?post_type=wp_invoice',
            'supports'            => array( 'title', 'author', 'custom-fields' ),
            'taxonomies'          => array(),
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_nav_menus'   => false,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'customers' ),
            'capability_type'     => 'post',
            'capabilities'        => array(
                'create_posts' => 'edit_posts',
                'edit_post'   => 'edit_post',
                'read_post'   => 'read_post',
                'delete_post' => 'delete_post',
            ),
            'map_meta_cap'        => true,
        );

        register_post_type( 'wp_customer', $args );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'customer_details',
            __( 'Customer Details', 'wp-invoice-management' ),
            array( $this, 'render_customer_details_metabox' ),
            'wp_customer',
            'normal',
            'high'
        );
    }

    public function render_customer_details_metabox( $post ) {
        wp_nonce_field( 'customer_save_meta', 'customer_meta_nonce' );

        $address    = get_post_meta( $post->ID, '_customer_address', true );
        $email      = get_post_meta( $post->ID, '_customer_email', true );
        $phone      = get_post_meta( $post->ID, '_customer_phone', true );
        $company    = get_post_meta( $post->ID, '_customer_company', true );
        $name       = get_post_meta( $post->ID, '_customer_name', true );
        $url        = get_post_meta( $post->ID, '_customer_url', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="customer_name"><?php _e( 'Customer Name', 'wp-invoice-management' ); ?></label></th>
                <td><input type="text" id="customer_name" name="customer_name" value="<?php echo esc_attr( $name ); ?>" class="regular-text" placeholder="e.g. John Doe" /></td>
            </tr>
            <tr>
                <th><label for="customer_company"><?php _e( 'Company Name', 'wp-invoice-management' ); ?></label></th>
                <td><input type="text" id="customer_company" name="customer_company" value="<?php echo esc_attr( $company ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="customer_email"><?php _e( 'Email', 'wp-invoice-management' ); ?></label></th>
                <td><input type="email" id="customer_email" name="customer_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="customer_phone"><?php _e( 'Phone', 'wp-invoice-management' ); ?></label></th>
                <td><input type="tel" id="customer_phone" name="customer_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="customer_url"><?php _e( 'Website URL', 'wp-invoice-management' ); ?></label></th>
                <td><input type="url" id="customer_url" name="customer_url" value="<?php echo esc_url( $url ); ?>" class="regular-text" placeholder="https://example.com" /></td>
            </tr>
            <tr>
                <th><label for="customer_address"><?php _e( 'Address', 'wp-invoice-management' ); ?></label></th>
                <td><textarea id="customer_address" name="customer_address" rows="4" class="large-text"><?php echo esc_textarea( $address ); ?></textarea></td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['customer_meta_nonce'] ) || ! wp_verify_nonce( $_POST['customer_meta_nonce'], 'customer_save_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $meta_fields = array(
            'customer_name',
            'customer_company',
            'customer_email',
            'customer_phone',
            'customer_url',
            'customer_address',
        );

        foreach ( $meta_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}
