<?php
namespace Wpim\Invoice\API;

class AuthAPI {
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'wp-invoice/v1', '/auth/login', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'login' ),
            'permission_callback' => '__return_true', // Public endpoint
        ) );

        register_rest_route( 'wp-invoice/v1', '/auth/register', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'register' ),
            'permission_callback' => '__return_true', // Public endpoint
        ) );
    }

    public function login( $request ) {
        // Enforce REST nonce validation for CSRF
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new \WP_Error( 'csrf_error', 'Invalid security token.', array( 'status' => 403 ) );
        }

        $params = $request->get_json_params();
        $username = isset( $params['username'] ) ? sanitize_text_field( $params['username'] ) : '';
        $password = isset( $params['password'] ) ? $params['password'] : ''; // Plain text for auth check

        if ( empty( $username ) || empty( $password ) ) {
            return new \WP_Error( 'missing_fields', 'Username and password are required.', array( 'status' => 400 ) );
        }

        // Credentials for wp_signon
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        );

        $user = wp_signon( $creds, is_ssl() );

        if ( is_wp_error( $user ) ) {
            // Do NOT log credentials. Return a generic friendly message.
            return new \WP_Error( 'invalid_credentials', 'Invalid username or password.', array( 'status' => 401 ) );
        }

        return rest_ensure_response( array(
            'success' => true,
            'user_id' => $user->ID,
            'username'=> $user->user_login,
            'email'   => $user->user_email,
        ) );
    }

    public function register( $request ) {
        // Enforce REST nonce validation for CSRF
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new \WP_Error( 'csrf_error', 'Invalid security token.', array( 'status' => 403 ) );
        }

        $settings = \Wpim\Invoice\Admin\SettingsPage::get_settings();
        if ( empty( $settings['enable_registration'] ) ) {
            return new \WP_Error( 'registration_disabled', 'Registration is currently disabled.', array( 'status' => 403 ) );
        }

        $params   = $request->get_json_params();
        $username = isset( $params['username'] ) ? sanitize_user( $params['username'] ) : '';
        $email    = isset( $params['email'] ) ? sanitize_email( $params['email'] ) : '';
        $password = isset( $params['password'] ) ? $params['password'] : '';
        $confirm  = isset( $params['confirm_password'] ) ? $params['confirm_password'] : '';

        if ( empty( $username ) || empty( $email ) || empty( $password ) || empty( $confirm ) ) {
            return new \WP_Error( 'missing_fields', 'All registration fields are required.', array( 'status' => 400 ) );
        }

        if ( ! is_email( $email ) ) {
            return new \WP_Error( 'invalid_email', 'Please provide a valid email address.', array( 'status' => 400 ) );
        }

        if ( $password !== $confirm ) {
            return new \WP_Error( 'password_mismatch', 'Passwords do not match.', array( 'status' => 400 ) );
        }

        // Enforce password strength: minimum 8 characters (12+ recommended)
        if ( strlen( $password ) < 8 ) {
            return new \WP_Error( 'weak_password', 'Password must be at least 8 characters long.', array( 'status' => 400 ) );
        }

        if ( username_exists( $username ) ) {
            return new \WP_Error( 'username_exists', 'This username is already taken.', array( 'status' => 400 ) );
        }

        if ( email_exists( $email ) ) {
            return new \WP_Error( 'email_exists', 'This email address is already registered.', array( 'status' => 400 ) );
        }

        // Validate selected role against allowed list (Least privilege principle)
        $allowed_roles = array( 'contributor', 'author', 'editor' );
        $role          = isset( $settings['registration_role'] ) ? $settings['registration_role'] : 'contributor';
        if ( ! in_array( $role, $allowed_roles, true ) ) {
            $role = 'contributor';
        }

        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => $role,
        );

        $user_id = wp_insert_user( $user_data );

        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Automatically log in the user after registration
        $creds = array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true,
        );
        $user = wp_signon( $creds, is_ssl() );

        return rest_ensure_response( array(
            'success' => true,
            'user_id' => $user_id,
            'username'=> $username,
            'email'   => $email,
        ) );
    }
}
