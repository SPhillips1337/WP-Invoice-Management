<?php
namespace Wpim\Invoice;

class Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run() {
        $this->init_cpts();
        $this->init_hooks();
        flush_rewrite_rules();
    }

    private function init_cpts() {
        new CPT\Invoice();
        new CPT\Customer();
        new API\REST_API();
        new Admin\ImportPage();
    }

    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'template_redirect', array( $this, 'handle_invoice_template' ) );
        add_shortcode( 'invoice_editor', array( $this, 'render_frontend_editor' ) );
        add_shortcode( 'invoice_dashboard', array( $this, 'render_invoice_dashboard' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Page Templates
        add_filter( 'theme_templates', array( $this, 'register_page_templates' ), 10, 4 );
        add_filter( 'template_include', array( $this, 'load_page_templates' ) );
    }

    public function handle_invoice_template() {
        global $post;

        if ( is_singular( 'wp_invoice' ) ) {
            $this->render_invoice_template( $post );
            exit;
        }

        if ( isset( $_GET['invoice_dashboard'] ) ) {
            $this->render_full_dashboard();
            exit;
        }

        if ( isset( $_GET['wp-invoice-editor'] ) || isset( $_GET['invoice_editor'] ) ) {
            // Skip auth check for now - add ?invoice_editor=1 to test
            $this->render_frontend_editor();
            exit;
        }

        if ( isset( $_GET['wp_invoice_pdf'] ) ) {
            $invoice_id = intval( $_GET['wp_invoice_pdf'] );
            if ( $invoice_id && current_user_can( 'read' ) ) {
                $this->generate_pdf( $invoice_id );
                exit;
            }
            wp_die( 'Invalid invoice' );
        }
    }

    public function render_frontend_editor( $atts = array() ) {
        $template_path = dirname( dirname( __FILE__ ) ) . '/templates/invoice-editor.php';
        
        if ( ! file_exists( $template_path ) ) {
            echo '<p>Error: Template not found at ' . $template_path . '</p>';
            return;
        }
        
        wp_enqueue_media();
        include $template_path;
    }

    public function render_invoice_dashboard( $atts = array() ) {
        if ( ! is_user_logged_in() ) {
            return '<p>Please <a href="' . wp_login_url( get_permalink() ) . '">log in</a> to view invoices.</p>';
        }

        add_filter( 'body_class', function( $classes ) {
            $classes[] = 'wp-invoice-force-full-width';
            return $classes;
        } );

        wp_enqueue_style( 'wp-invoice-dashboard' );
        wp_enqueue_script( 'wp-invoice-dashboard' );

        ob_start();
        include plugin_dir_path( __DIR__ ) . 'templates/invoice-dashboard.php';
        return ob_get_clean();
    }

    public function render_full_dashboard() {
        if ( ! is_user_logged_in() ) {
            auth_redirect();
        }

        wp_enqueue_style( 'wp-invoice-dashboard' );
        wp_enqueue_script( 'wp-invoice-dashboard' );

        $template_path = dirname( dirname( __FILE__ ) ) . '/templates/dashboard-app.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo $this->render_invoice_dashboard();
        }
    }

    public function register_page_templates( $templates, $theme, $post, $post_type ) {
        $templates['templates/page-invoice-full-width.php'] = __( 'Invoice Full Width', 'wp-invoice-management' );
        return $templates;
    }

    public function load_page_templates( $template ) {
        if ( is_page() ) {
            $template_slug = get_page_template_slug();
            if ( 'templates/page-invoice-full-width.php' === $template_slug ) {
                $file = plugin_dir_path( __DIR__ ) . 'templates/page-invoice-full-width.php';
                if ( file_exists( $file ) ) {
                    return $file;
                }
            }
        }
        return $template;
    }

    public function generate_pdf( $invoice_id ) {
        if ( ! $invoice_id ) {
            wp_die( 'Invalid invoice' );
        }

        $post = get_post( $invoice_id );
        if ( ! $post || $post->post_type !== 'wp_invoice' ) {
            wp_die( 'Invoice not found' );
        }

        // Check permissions - anyone who can read can download their own invoices
        if ( ! current_user_can( 'read_post', $post->ID ) ) {
            wp_die( 'Permission denied' );
        }

        ob_start();
        $this->render_pdf_template( $post );
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page { size: A4; margin: 20mm; }</style></head><body>' . ob_get_clean() . '</body></html>';

        $options = new \Dompdf\Options();
        $options->set( 'isRemoteEnabled', true );
        $options->set( 'defaultFont', 'Helvetica' );

        $dompdf = new \Dompdf\Dompdf( $options );
        $dompdf->loadHtml( $html );
        $dompdf->setPaper( 'A4', 'portrait' );
        $dompdf->render();

        // Fix encoding
        $dompdf->stream( 'invoice-' . $invoice_id . '.pdf', array( 'Attachment' => true ) );
        exit;
    }

    private function render_invoice_template( $post ) {
        return $this->render_invoice_or_pdf_template( $post, false );
    }

    private function render_pdf_template( $post ) {
        return $this->render_invoice_or_pdf_template( $post, true );
    }

    private function render_invoice_or_pdf_template( $post, $is_pdf = false ) {
        $logo_id     = get_post_meta( $post->ID, '_invoice_logo_id', true );
        $from        = get_post_meta( $post->ID, '_invoice_from', true );
        $to          = get_post_meta( $post->ID, '_invoice_to', true );
        $ship_to     = get_post_meta( $post->ID, '_invoice_ship_to', true );
        $date        = get_post_meta( $post->ID, '_invoice_date', true );
        $due_date    = get_post_meta( $post->ID, '_invoice_due_date', true );
        $po_number   = get_post_meta( $post->ID, '_invoice_po_number', true );
        $items       = get_post_meta( $post->ID, '_invoice_items', true );
        $notes       = get_post_meta( $post->ID, '_invoice_notes', true );
        $terms       = get_post_meta( $post->ID, '_invoice_terms', true );
        $subtotal    = get_post_meta( $post->ID, '_invoice_subtotal', true );
        $tax         = get_post_meta( $post->ID, '_invoice_tax', true );
        $discount    = get_post_meta( $post->ID, '_invoice_discount', true );
        $shipping    = get_post_meta( $post->ID, '_invoice_shipping', true );
        $amount_paid = get_post_meta( $post->ID, '_invoice_amount_paid', true );
        $total       = get_post_meta( $post->ID, '_invoice_total', true );
        $status       = get_post_meta( $post->ID, '_invoice_status', true );

        if ( ! is_array( $items ) ) {
            $items = array();
        }
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo esc_html( $post->ID ); ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .invoice-header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .invoice-logo img { max-width: 200px; }
        .invoice-title { font-size: 32px; font-weight: bold; color: #333; }
        .invoice-meta { margin: 20px 0; }
        .invoice-meta table { width: 100%; }
        .invoice-meta th { text-align: left; padding: 5px 10px 5px 0; }
        .invoice-meta td { padding: 5px 0; }
        .invoice-addresses { display: flex; justify-content: space-between; margin: 30px 0; }
        .address-block { width: 45%; }
        .address-block h3 { margin: 0 0 10px; font-size: 14px; color: #666; text-transform: uppercase; }
        .address-block p { margin: 0; white-space: pre-wrap; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.items th { background: #f5f5f5; padding: 12px; text-align: left; border-bottom: 2px solid #ddd; }
        table.items td { padding: 12px; border-bottom: 1px solid #eee; }
        table.items th:nth-child(2), table.items th:nth-child(3), table.items td:nth-child(2), table.items td:nth-child(3) { text-align: right; }
        .totals { margin-top: 20px; text-align: right; }
        .totals table { width: 300px; margin-left: auto; }
        .totals th { padding: 8px; text-align: left; }
        .totals td { padding: 8px; text-align: right; }
        .totals .total-row { font-size: 18px; font-weight: bold; background: #f5f5f5; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 4px; font-weight: bold; text-transform: uppercase; }
        .status-open { background: #e3f2fd; color: #1565c0; }
        .status-paid { background: #e8f5e9; color: #2e7d32; }
        .status-overdue { background: #ffebee; color: #c62828; }
        .project-header td { background: #f9fafb; font-weight: bold; border-bottom: 2px solid #eee; padding-top: 15px; }
        .sub-header td { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #666; background: #fff; padding: 5px 12px; border-bottom: 1px solid #eee; }
        .notes-terms { margin-top: 40px; }
        .notes-terms h3 { font-size: 14px; color: #666; text-transform: uppercase; }
        .notes-terms p { white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="invoice-title">INVOICE</div>
        <div>
            <?php if ( $logo_id ) : 
                $logo_path = get_attached_file( $logo_id );
                $logo_url  = wp_get_attachment_url( $logo_id );
                $logo_src  = $logo_url;

                // For PDF, try to use base64 to avoid path/URL resolution issues
                if ( $is_pdf && $logo_path && file_exists( $logo_path ) ) {
                    $type = pathinfo( $logo_path, PATHINFO_EXTENSION );
                    $data = file_get_contents( $logo_path );
                    $logo_src = 'data:image/' . $type . ';base64,' . base64_encode( $data );
                }
            ?>
                <div class="invoice-logo"><img src="<?php echo $logo_src; ?>" style="max-width: 200px;"></div>
            <?php endif; ?>
            <?php 
            // Only show status in PDF if it's not 'open' (Draft/Open is default and usually redundant on a printed invoice)
            if ( ! $is_pdf || ( $status && ! in_array( $status, array( 'open', 'draft' ) ) ) ) : 
            ?>
                <span class="status-badge status-<?php echo esc_attr( $status ?: 'open' ); ?>">
                    <?php echo esc_html( ucfirst( $status ?: 'Open' ) ); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="invoice-meta">
        <table>
            <tr>
                <th>Invoice Number:</th>
                <td><?php echo esc_html( $post->post_title ); ?></td>
            </tr>
            <?php if ( $date ) : ?>
            <tr>
                <th>Invoice Date:</th>
                <td><?php echo esc_html( $date ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( $due_date ) : ?>
            <tr>
                <th>Due Date:</th>
                <td><?php echo esc_html( $due_date ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( $po_number ) : ?>
            <tr>
                <th>PO Number:</th>
                <td><?php echo esc_html( $po_number ); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="invoice-addresses">
        <div class="address-block">
            <h3>From:</h3>
            <p><?php echo esc_html( $from ); ?></p>
        </div>
        <div class="address-block">
            <h3>Bill To:</h3>
            <p><?php echo esc_html( $to ); ?></p>
        </div>
    </div>

    <?php if ( $ship_to ) : ?>
    <div class="invoice-addresses">
        <div class="address-block">
            <h3>Ship To:</h3>
            <p><?php echo esc_html( $ship_to ); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <table class="items">
        <thead style="display: none;">
            <tr>
                <th style="width: 80px;">Date</th>
                <th>Description</th>
                <th style="width: 50px;">Qty</th>
                <th style="width: 80px;">Rate</th>
                <th style="width: 80px;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $items as $index => $item ) : ?>
                <?php if ( isset( $item['type'] ) && 'section' === $item['type'] ) : ?>
                    <tr class="project-header">
                        <td colspan="5"><?php echo esc_html( $item['description'] ); ?></td>
                    </tr>
                    <tr class="sub-header">
                        <td>Date</td>
                        <td>Description</td>
                        <td>Qty</td>
                        <td>Rate</td>
                        <td>Amount</td>
                    </tr>
                <?php else : ?>
                    <?php if ( $index === 0 ) : ?>
                    <tr class="sub-header">
                        <td>Date</td>
                        <td>Description</td>
                        <td>Qty</td>
                        <td>Rate</td>
                        <td>Amount</td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td style="font-size: 12px; color: #666;"><?php echo esc_html( $item['date'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $item['description'] ); ?></td>
                        <td><?php echo esc_html( $item['quantity'] ); ?></td>
                        <td>$<?php echo number_format( floatval( $item['rate'] ), 2 ); ?></td>
                        <td>$<?php echo number_format( floatval( $item['amount'] ), 2 ); ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <th>Subtotal:</th>
                <td>$<?php echo number_format( $subtotal ?: 0, 2 ); ?></td>
            </tr>
            <?php if ( $tax ) : ?>
            <tr>
                <th>Tax:</th>
                <td>$<?php echo number_format( $tax, 2 ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( $discount ) : ?>
            <tr>
                <th>Discount:</th>
                <td>-$<?php echo number_format( $discount, 2 ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( $shipping ) : ?>
            <tr>
                <th>Shipping:</th>
                <td>$<?php echo number_format( $shipping, 2 ); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ( $amount_paid ) : ?>
            <tr>
                <th>Amount Paid:</th>
                <td>-$<?php echo number_format( $amount_paid, 2 ); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="total-row">
                <th>Total:</th>
                <td>$<?php echo number_format( $total ?: 0, 2 ); ?></td>
            </tr>
        </table>
    </div>

    <?php if ( $notes ) : ?>
    <div class="notes-terms">
        <h3>Notes:</h3>
        <p><?php echo esc_html( $notes ); ?></p>
    </div>
    <?php endif; ?>

    <?php if ( $terms ) : ?>
    <div class="notes-terms">
        <h3>Terms:</h3>
        <p><?php echo esc_html( $terms ); ?></p>
    </div>
    <?php endif; ?>
</body>
</html>
        <?php
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Invoices', 'wp-invoice-management' ),
            __( 'Invoices', 'wp-invoice-management' ),
            'edit_posts',
            'edit.php?post_type=wp_invoice',
            '',
            'dashicons-money',
            50
        );

        add_submenu_page(
            'edit.php?post_type=wp_invoice',
            __( 'Add New Invoice', 'wp-invoice-management' ),
            __( 'Add New', 'wp-invoice-management' ),
            'edit_posts',
            'post-new.php?post_type=wp_invoice'
        );

        add_submenu_page(
            'edit.php?post_type=wp_invoice',
            __( 'Import Invoices', 'wp-invoice-management' ),
            __( 'Import', 'wp-invoice-management' ),
            'edit_posts',
            'wp-invoice-import',
            array( new Admin\ImportPage(), 'render' )
        );
    }

    public function enqueue_frontend_scripts() {
        wp_register_style(
            'wp-invoice-dashboard',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/dashboard.css',
            array(),
            WPIM_VERSION
        );

        wp_register_script(
            'wp-invoice-dashboard',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/dashboard.js',
            array( 'jquery' ),
            WPIM_VERSION,
            true
        );

        wp_localize_script(
            'wp-invoice-dashboard',
            'wpApiSettings',
            array(
                'root'  => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
            )
        );

        wp_enqueue_style(
            'wp-invoice-frontend',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/frontend.css',
            array(),
            WPIM_VERSION
        );

        wp_enqueue_script(
            'wp-invoice-frontend',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/frontend.js',
            array( 'jquery' ),
            WPIM_VERSION,
            true
        );

        wp_localize_script(
            'wp-invoice-frontend',
            'wpApiSettings',
            array(
                'root'  => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
            )
        );
    }

    public function enqueue_admin_scripts( $hook ) {
        global $post_type;

        if ( 'wp_invoice' === $post_type || 'wp_customer' === $post_type ) {
            wp_enqueue_style(
                'wp-invoice-admin',
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/admin.css',
                array(),
                '0.3.0'
            );

            wp_enqueue_script(
                'wp-invoice-admin',
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin.js',
                array( 'jquery', 'underscore' ),
                WPIM_VERSION,
                true
            );
        }
    }
}
