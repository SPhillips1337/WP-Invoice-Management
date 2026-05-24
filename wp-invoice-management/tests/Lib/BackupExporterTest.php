<?php
namespace {
    if ( ! class_exists( 'WP_Query' ) ) {
        class WP_Query {
            public $posts = array();
            public $max_num_pages = 0;
            public static $last_args = null;

            public function __construct( $args = array() ) {
                self::$last_args = $args;
                $this->max_num_pages = 1;
            }

            public function have_posts() {
                return ! empty( $this->posts );
            }
        }
    }
}

namespace Wpim\Invoice\Tests\Lib {

    use WP_Mock\Tools\TestCase;
    use Wpim\Invoice\Lib\BackupExporter;
    use WP_Mock;

    class BackupExporterTest extends TestCase {
        public function setUp(): void {
            parent::setUp();
            \WP_Query::$last_args = null;
            $GLOBALS['mock_current_user_id'] = null;
        }

        public function test_collect_invoices_filters_by_author_for_non_admins() {
            $exporter = new BackupExporter();

            WP_Mock::userFunction('current_user_can', array(
                'args'   => array('manage_options'),
                'return' => false
            ));

            $GLOBALS['mock_current_user_id'] = 456;

            WP_Mock::userFunction('wp_reset_postdata', array('times' => 2));
            WP_Mock::userFunction('get_bloginfo', array('return' => '6.0'));

            $backup = $exporter->build_backup();

            $this->assertIsArray($backup);
            $this->assertNotNull(\WP_Query::$last_args);
            $this->assertEquals(456, \WP_Query::$last_args['author']);
        }
    }
}
