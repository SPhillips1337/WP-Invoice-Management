<?php
/**
 * Template for frontend login & registration inside WP Theme
 */
$settings = \Wpim\Invoice\Admin\SettingsPage::get_settings();
$registration_enabled = ! empty( $settings['enable_registration'] );

get_header();
?>
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>WP Invoices</h2>
            <p id="authSubtitle">Log in to manage your invoices</p>
        </div>

        <!-- Toast / Message Box -->
        <div class="auth-message-box" id="authMessageBox" style="display: none;"></div>

        <!-- Login Form -->
        <form id="loginForm" class="auth-form">
            <div class="form-group">
                <label for="loginUsername">Username or Email</label>
                <input type="text" id="loginUsername" name="username" required placeholder="enter your username...">
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" id="loginSubmitBtn">Log In</button>
            
            <?php if ( $registration_enabled ) : ?>
                <p class="auth-toggle-text">Don't have an account? <a href="#" id="showRegisterLink">Register here</a></p>
            <?php endif; ?>
        </form>

        <?php if ( $registration_enabled ) : ?>
            <!-- Register Form -->
            <form id="registerForm" class="auth-form" style="display: none;">
                <div class="form-group">
                    <label for="registerUsername">Username</label>
                    <input type="text" id="registerUsername" name="username" required placeholder="choose username...">
                </div>
                <div class="form-group">
                    <label for="registerEmail">Email Address</label>
                    <input type="email" id="registerEmail" name="email" required placeholder="name@company.com">
                </div>
                <div class="form-group">
                    <label for="registerPassword">Password</label>
                    <input type="password" id="registerPassword" name="password" required placeholder="min. 8 characters">
                </div>
                <div class="form-group">
                    <label for="registerConfirmPassword">Confirm Password</label>
                    <input type="password" id="registerConfirmPassword" name="confirm_password" required placeholder="confirm your password">
                </div>
                <button type="submit" class="btn btn-primary" id="registerSubmitBtn">Register</button>
                
                <p class="auth-toggle-text">Already have an account? <a href="#" id="showLoginLink">Log in here</a></p>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
const WP_INVOICE_API = {
    nonce: '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
    root: '<?php echo rest_url( 'wp-invoice/v1' ); ?>',
    redirectUrl: '<?php echo esc_url( remove_query_arg( 'invoice_action', home_url( add_query_arg( $_GET ) ) ) ); ?>'
};
</script>
<?php
get_footer();
