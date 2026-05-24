<?php
// Define global WordPress classes and functions needed for unit testing Wpim plugin.
namespace {
    if ( ! class_exists( 'WP_REST_Request' ) ) {
        class WP_REST_Request {
            public function get_header( $key ) {}
            public function get_json_params() {}
            public function get_param( $key ) {}
        }
    }

    if ( ! class_exists( 'WP_Error' ) ) {
        class WP_Error {
            protected $code;
            protected $message;
            protected $data;
            public function __construct( $code = '', $message = '', $data = '' ) {
                $this->code = $code;
                $this->message = $message;
                $this->data = $data;
            }
            public function get_error_code() {
                return $this->code;
            }
            public function get_error_message() {
                return $this->message;
            }
        }
    }

    if ( ! function_exists( 'is_wp_error' ) ) {
        function is_wp_error( $thing ) {
            return ( $thing instanceof WP_Error );
        }
    }

    if ( ! function_exists( 'get_current_user_id' ) ) {
        function get_current_user_id() {
            global $mock_current_user_id;
            return isset( $mock_current_user_id ) ? $mock_current_user_id : 0;
        }
    }

    if ( ! function_exists( 'get_user_meta' ) ) {
        function get_user_meta( $user_id, $key = '', $single = false ) {
            global $mock_user_meta;
            if ( isset( $mock_user_meta ) && isset( $mock_user_meta[ $user_id ] ) && isset( $mock_user_meta[ $user_id ][ $key ] ) ) {
                return $mock_user_meta[ $user_id ][ $key ];
            }
            return array();
        }
    }
}

namespace Wpim\Invoice\Tests\API {

    use WP_Mock\Tools\TestCase;
    use Wpim\Invoice\API\AuthAPI;
    use WP_Mock;

    class AuthAPITest extends TestCase {
        public function setUp(): void {
            parent::setUp();
        }

        public function test_register_routes() {
            $auth_api = new AuthAPI();
            
            WP_Mock::userFunction( 'register_rest_route', array(
                'times' => 2,
            ) );
            
            $auth_api->register_routes();
            $this->assertTrue( true );
        }

        public function test_login_success() {
            $auth_api = new AuthAPI();

            // Mock request object
            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_header')->with('X-WP-Nonce')->willReturn('valid_nonce');
            $request->method('get_json_params')->willReturn(array(
                'username' => 'testuser',
                'password' => 'testpass'
            ));

            // Mock WP functions called inside login()
            WP_Mock::userFunction('wp_verify_nonce', array(
                'args'   => array('valid_nonce', 'wp_rest'),
                'return' => true
            ));

            WP_Mock::userFunction('sanitize_text_field', array(
                'return' => 'testuser'
            ));

            WP_Mock::userFunction('is_ssl', array(
                'return' => false
            ));

            // Mock user object returned by wp_signon
            $mock_user = new \stdClass();
            $mock_user->ID = 42;
            $mock_user->user_login = 'testuser';
            $mock_user->user_email = 'test@example.com';

            WP_Mock::userFunction('wp_signon', array(
                'args'   => array(
                    array(
                        'user_login'    => 'testuser',
                        'user_password' => 'testpass',
                        'remember'      => true
                    ),
                    false
                ),
                'return' => $mock_user
            ));

            WP_Mock::userFunction('rest_ensure_response', array(
                'return' => function($data) { return $data; }
            ));

            $response = $auth_api->login($request);

            $this->assertIsArray($response);
            $this->assertTrue($response['success']);
            $this->assertEquals(42, $response['user_id']);
            $this->assertEquals('testuser', $response['username']);
            $this->assertEquals('test@example.com', $response['email']);
        }

        public function test_login_invalid_nonce() {
            $auth_api = new AuthAPI();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_header')->with('X-WP-Nonce')->willReturn('invalid_nonce');

            WP_Mock::userFunction('wp_verify_nonce', array(
                'args'   => array('invalid_nonce', 'wp_rest'),
                'return' => false
            ));

            $response = $auth_api->login($request);

            $this->assertInstanceOf(\WP_Error::class, $response);
            $this->assertEquals('csrf_error', $response->get_error_code());
        }

        public function test_register_disabled() {
            $auth_api = new AuthAPI();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_header')->with('X-WP-Nonce')->willReturn('valid_nonce');

            WP_Mock::userFunction('wp_verify_nonce', array(
                'args'   => array('valid_nonce', 'wp_rest'),
                'return' => true
            ));

            // Mock registration as disabled
            WP_Mock::userFunction('get_option', array(
                'args'   => array('wp_invoice_settings', array()),
                'return' => array('enable_registration' => 0)
            ));
            WP_Mock::userFunction('wp_parse_args', array(
                'return' => array('enable_registration' => 0)
            ));

            $response = $auth_api->register($request);

            $this->assertInstanceOf(\WP_Error::class, $response);
            $this->assertEquals('registration_disabled', $response->get_error_code());
        }

        public function test_register_success() {
            $auth_api = new AuthAPI();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_header')->with('X-WP-Nonce')->willReturn('valid_nonce');
            $request->method('get_json_params')->willReturn(array(
                'username'         => 'newuser',
                'email'            => 'new@example.com',
                'password'         => 'strong_password_123',
                'confirm_password' => 'strong_password_123'
            ));

            WP_Mock::userFunction('wp_verify_nonce', array(
                'args'   => array('valid_nonce', 'wp_rest'),
                'return' => true
            ));

            // Mock registration as enabled with contributor role
            WP_Mock::userFunction('get_option', array(
                'args'   => array('wp_invoice_settings', array()),
                'return' => array('enable_registration' => 1, 'registration_role' => 'contributor')
            ));
            WP_Mock::userFunction('wp_parse_args', array(
                'return' => array('enable_registration' => 1, 'registration_role' => 'contributor')
            ));

            WP_Mock::userFunction('sanitize_user', array(
                'return' => 'newuser'
            ));
            WP_Mock::userFunction('sanitize_email', array(
                'return' => 'new@example.com'
            ));
            WP_Mock::userFunction('is_email', array(
                'return' => true
            ));
            WP_Mock::userFunction('username_exists', array(
                'return' => false
            ));
            WP_Mock::userFunction('email_exists', array(
                'return' => false
            ));

            WP_Mock::userFunction('wp_insert_user', array(
                'args'   => array(
                    array(
                        'user_login' => 'newuser',
                        'user_email' => 'new@example.com',
                        'user_pass'  => 'strong_password_123',
                        'role'       => 'contributor'
                    )
                ),
                'return' => 99
            ));

            WP_Mock::userFunction('is_ssl', array(
                'return' => false
            ));

            $mock_user = new \stdClass();
            $mock_user->ID = 99;

            WP_Mock::userFunction('wp_signon', array(
                'return' => $mock_user
            ));

            WP_Mock::userFunction('rest_ensure_response', array(
                'return' => function($data) { return $data; }
            ));

            $response = $auth_api->register($request);

            $this->assertIsArray($response);
            $this->assertTrue($response['success']);
            $this->assertEquals(99, $response['user_id']);
        }
    }
}
