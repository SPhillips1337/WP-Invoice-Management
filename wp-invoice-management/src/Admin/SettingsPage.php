<?php
namespace Wpim\Invoice\Admin;

class SettingsPage {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_reset' ) );
    }

    public function handle_reset() {
        if ( isset( $_POST['wp_invoice_reset'] ) && current_user_can( 'manage_options' ) ) {
            if ( ! isset( $_POST['confirm_reset'] ) || $_POST['confirm_reset'] !== '1' ) {
                wp_die( __( 'You must confirm the reset by checking the checkbox.', 'wp-invoice-management' ) );
            }

            // Delete all invoices
            $invoices = get_posts( array(
                'post_type'      => 'wp_invoice',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ) );
            foreach ( $invoices as $post_id ) {
                wp_delete_post( $post_id, true );
            }

            // Delete all customers
            $customers = get_posts( array(
                'post_type'      => 'wp_customer',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ) );
            foreach ( $customers as $post_id ) {
                wp_delete_post( $post_id, true );
            }

            wp_redirect( admin_url( 'edit.php?post_type=wp_invoice&page=wp-invoice-settings&reset=success' ) );
            exit;
        }
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=wp_invoice',
            __( 'Invoice Settings', 'wp-invoice-management' ),
            __( 'Settings', 'wp-invoice-management' ),
            'manage_options',
            'wp-invoice-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'wp_invoice_settings_group', 'wp_invoice_settings' );

        add_settings_section(
            'wp_invoice_general_section',
            __( 'General Settings', 'wp-invoice-management' ),
            null,
            'wp-invoice-settings'
        );

        add_settings_field(
            'currency_symbol',
            __( 'Currency Symbol', 'wp-invoice-management' ),
            array( $this, 'render_text_field' ),
            'wp-invoice-settings',
            'wp_invoice_general_section',
            array( 'label_for' => 'currency_symbol', 'default' => '$' )
        );

        add_settings_field(
            'currency_code',
            __( 'Currency Code', 'wp-invoice-management' ),
            array( $this, 'render_text_field' ),
            'wp-invoice-settings',
            'wp_invoice_general_section',
            array( 'label_for' => 'currency_code', 'default' => 'USD' )
        );

        add_settings_field(
            'tax_label',
            __( 'Tax Label', 'wp-invoice-management' ),
            array( $this, 'render_text_field' ),
            'wp-invoice-settings',
            'wp_invoice_general_section',
            array( 'label_for' => 'tax_label', 'default' => 'Tax' )
        );

        add_settings_field(
            'default_country',
            __( 'Default Country', 'wp-invoice-management' ),
            array( $this, 'render_text_field' ),
            'wp-invoice-settings',
            'wp_invoice_general_section',
            array( 'label_for' => 'default_country', 'default' => 'United Kingdom' )
        );

        add_settings_field(
            'default_address',
            __( 'Default Sender Address', 'wp-invoice-management' ),
            array( $this, 'render_textarea' ),
            'wp-invoice-settings',
            'wp_invoice_general_section',
            array( 'label_for' => 'default_address', 'default' => '' )
        );

        add_settings_section(
            'wp_invoice_reset_section',
            __( 'Reset Data', 'wp-invoice-management' ),
            array( $this, 'render_reset_section_description' ),
            'wp-invoice-settings'
        );

        add_settings_field(
            'reset_data',
            __( 'Reset All Data', 'wp-invoice-management' ),
            array( $this, 'render_reset_field' ),
            'wp-invoice-settings',
            'wp_invoice_reset_section'
        );
    }

    public function render_reset_section_description() {
        echo '<p>' . __( 'Danger zone: Permanently delete all invoices and customers. This action cannot be undone.', 'wp-invoice-management' ) . '</p>';
    }

    public function render_reset_field() {
        ?>
        <input type="checkbox" id="confirm_reset" name="confirm_reset" value="1" />
        <label for="confirm_reset"><?php _e( 'I understand this will delete ALL invoices and customers', 'wp-invoice-management' ); ?></label>
        <br><br>
        <input type="submit" name="wp_invoice_reset" class="button button-danger" style="background:#dc3232; color:#fff; border-color:#a00;" value="<?php esc_attr_e( 'Reset All Data', 'wp-invoice-management' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you absolutely sure? This will delete ALL invoices and customers permanently.', 'wp-invoice-management' ) ); ?>');" />
        <?php
    }

    public function render_text_field( $args ) {
        $options = get_option( 'wp_invoice_settings' );
        $value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : $args['default'];
        ?>
        <input type="text" 
               id="<?php echo esc_attr( $args['label_for'] ); ?>" 
               name="wp_invoice_settings[<?php echo esc_attr( $args['label_for'] ); ?>]" 
               value="<?php echo esc_attr( $value ); ?>" 
               class="regular-text">
        <?php
    }

    public function render_textarea( $args ) {
        $options = get_option( 'wp_invoice_settings' );
        $value = isset( $options[ $args['label_for'] ] ) ? $options[ $args['label_for'] ] : $args['default'];
        ?>
        <textarea id="<?php echo esc_attr( $args['label_for'] ); ?>" 
                  name="wp_invoice_settings[<?php echo esc_attr( $args['label_for'] ); ?>]" 
                  rows="5" 
                  class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
        <p class="description"><?php _e( 'This address will be prepopulated in the "From" section of new invoices.', 'wp-invoice-management' ); ?></p>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'wp_invoice_settings_group' );
                do_settings_sections( 'wp-invoice-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function get_settings() {
        $defaults = array(
            'currency_symbol' => '$',
            'currency_code'   => 'USD',
            'tax_label'       => 'Tax',
            'default_country' => 'United Kingdom',
            'default_address' => '',
        );
        $options = get_option( 'wp_invoice_settings', array() );
        return wp_parse_args( $options, $defaults );
    }
}
