<?php
namespace WpInvoiceManagement;

class Plugin {
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run() {
        // TODO: register CPTs, shortcodes, REST routes, admin menus, etc.
    }
}
