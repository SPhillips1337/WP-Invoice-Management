<?php
namespace Wpim\Invoice\Tests\API {

    use WP_Mock\Tools\TestCase;
    use Wpim\Invoice\API\REST_API;
    use WP_Mock;

    class REST_APITest extends TestCase {
        public function setUp(): void {
            parent::setUp();
            $GLOBALS['mock_current_user_id'] = null;
            $GLOBALS['mock_user_meta'] = null;
        }

        public function tearDown(): void {
            $GLOBALS['mock_current_user_id'] = null;
            $GLOBALS['mock_user_meta'] = null;
            parent::tearDown();
        }

        public function test_register_routes() {
            WP_Mock::userFunction( 'register_rest_route', array(
                'times' => 10,
            ) );

            $rest_api = new REST_API();
            $rest_api->register_routes();
            $this->assertTrue( true );
        }

        public function test_delete_invoice_success() {
            $rest_api = new REST_API();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_param')->with('id')->willReturn(123);

            $mock_post = new \stdClass();
            $mock_post->ID = 123;
            $mock_post->post_type = 'wp_invoice';

            WP_Mock::userFunction( 'get_post', array(
                'args'   => array( 123 ),
                'return' => $mock_post,
            ) );

            WP_Mock::userFunction( 'current_user_can', array(
                'args'   => array( 'delete_post', 123 ),
                'return' => true,
            ) );

            WP_Mock::userFunction( 'wp_delete_post', array(
                'args'   => array( 123, true ),
                'return' => true,
            ) );

            WP_Mock::userFunction( 'rest_ensure_response', array(
                'return' => function( $data ) { return $data; }
            ) );

            $response = $rest_api->delete_invoice( $request );
            $this->assertIsArray( $response );
            $this->assertTrue( $response['deleted'] );
        }

        public function test_delete_invoice_not_found() {
            $rest_api = new REST_API();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_param')->with('id')->willReturn(123);

            WP_Mock::userFunction( 'get_post', array(
                'args'   => array( 123 ),
                'return' => null,
            ) );

            $response = $rest_api->delete_invoice( $request );
            $this->assertInstanceOf( \WP_Error::class, $response );
            $this->assertEquals( 'not_found', $response->get_error_code() );
        }

        public function test_delete_invoice_forbidden() {
            $rest_api = new REST_API();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_param')->with('id')->willReturn(123);

            $mock_post = new \stdClass();
            $mock_post->ID = 123;
            $mock_post->post_type = 'wp_invoice';

            WP_Mock::userFunction( 'get_post', array(
                'args'   => array( 123 ),
                'return' => $mock_post,
            ) );

            WP_Mock::userFunction( 'current_user_can', array(
                'args'   => array( 'delete_post', 123 ),
                'return' => false,
            ) );

            $response = $rest_api->delete_invoice( $request );
            $this->assertInstanceOf( \WP_Error::class, $response );
            $this->assertEquals( 'forbidden', $response->get_error_code() );
        }

        public function test_bulk_delete_invoices_success() {
            $rest_api = new REST_API();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_json_params')->willReturn(array(
                'ids' => array( 101, 102, 103 )
            ));

            $post101 = new \stdClass();
            $post101->ID = 101;
            $post101->post_type = 'wp_invoice';

            $post102 = new \stdClass();
            $post102->ID = 102;
            $post102->post_type = 'wp_invoice';

            // post 103 belongs to another user, so current_user_can will return false
            $post103 = new \stdClass();
            $post103->ID = 103;
            $post103->post_type = 'wp_invoice';

            WP_Mock::userFunction( 'get_post', array(
                'return' => function( $id ) use ( $post101, $post102, $post103 ) {
                    if ( $id === 101 ) return $post101;
                    if ( $id === 102 ) return $post102;
                    if ( $id === 103 ) return $post103;
                    return null;
                }
            ) );

            WP_Mock::userFunction( 'current_user_can', array(
                'return' => function( $capability, $post_id ) {
                    if ( $post_id === 101 || $post_id === 102 ) {
                        return true;
                    }
                    return false;
                }
            ) );

            WP_Mock::userFunction( 'wp_delete_post', array(
                'times' => 2,
            ) );

            WP_Mock::userFunction( 'rest_ensure_response', array(
                'return' => function( $data ) { return $data; }
            ) );

            $response = $rest_api->bulk_delete_invoices( $request );
            $this->assertIsArray( $response );
            $this->assertTrue( $response['success'] );
            $this->assertEquals( 2, $response['deleted'] );
        }

        public function test_bulk_delete_invoices_empty() {
            $rest_api = new REST_API();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_json_params')->willReturn(array());

            $response = $rest_api->bulk_delete_invoices( $request );
            $this->assertInstanceOf( \WP_Error::class, $response );
            $this->assertEquals( 'missing_ids', $response->get_error_code() );
        }

        public function test_update_settings_success() {
            $rest_api = new REST_API();

            $request = $this->createMock(\WP_REST_Request::class);
            $request->method('get_json_params')->willReturn(array(
                'currency_symbol' => '€',
                'currency_code' => 'EUR',
                'tax_label' => 'VAT',
                'default_tax_rate' => '15.5',
                'default_country' => 'France',
                'default_address' => '123 Rue de Rivoli'
            ));

            $GLOBALS['mock_current_user_id'] = 456;
            $GLOBALS['mock_user_meta'] = array(
                456 => array(
                    'wp_invoice_settings' => array()
                )
            );

            WP_Mock::userFunction('sanitize_text_field', array(
                'return' => function($val) { return $val; }
            ));

            WP_Mock::userFunction('sanitize_textarea_field', array(
                'return' => function($val) { return $val; }
            ));

            WP_Mock::userFunction('update_user_meta', array(
                'args' => array(
                    456,
                    'wp_invoice_settings',
                    array(
                        'currency_symbol' => '€',
                        'currency_code' => 'EUR',
                        'tax_label' => 'VAT',
                        'default_tax_rate' => 15.5,
                        'default_country' => 'France',
                        'default_address' => '123 Rue de Rivoli'
                    )
                ),
                'times' => 1,
                'return_callback' => function($user_id, $meta_key, $meta_value) {
                    $GLOBALS['mock_user_meta'][$user_id][$meta_key] = $meta_value;
                    return true;
                }
            ));

            WP_Mock::userFunction('current_user_can', array(
                'args' => array('manage_options'),
                'return' => false
            ));

            WP_Mock::userFunction('get_option', array(
                'args' => array('wp_invoice_settings', array()),
                'return' => array()
            ));

            $response = $rest_api->update_settings($request);
            $this->assertIsArray($response);
            $this->assertEquals(15.5, $response['default_tax_rate']);
            $this->assertEquals('€', $response['currency_symbol']);
        }
    }
}
