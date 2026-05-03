<?php
/**
 * PHPUnit bootstrap for WP Invoice Management tests.
 *
 * Loads the Composer autoloader, initialises WP_Mock, and defines
 * stub WordPress constants so tests can run without a full WP install.
 */

// Load Composer autoloader (includes WP_Mock and PHPUnit).
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Initialise WP_Mock — must be called before any test that uses WP functions.
WP_Mock::bootstrap();

// Stub WordPress constants used by the plugin.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
    define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'WPIM_VERSION' ) ) {
    define( 'WPIM_VERSION', '1.0.0' );
}
