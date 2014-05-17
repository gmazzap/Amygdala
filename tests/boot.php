<?php
if ( ! defined( 'AMYGDALABASEPATH' ) ) define( 'AMYGDALABASEPATH', dirname( dirname( __FILE__ ) ) );

require_once AMYGDALABASEPATH . '/vendor/autoload.php';

if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', TRUE );
}
require_once AMYGDALABASEPATH . '/vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

if ( ! class_exists( 'WP_Error' ) ) require_once __DIR__ . '/class-wp-error.php';

if ( ! function_exists( 'esc_sql' ) ) {

    function esc_sql( $data ) {
        return $data;
    }

}

if ( ! function_exists( 'maybe_serialize' ) ) {

    function maybe_serialize( $data ) {
        if ( is_array( $data ) || is_object( $data ) ) return serialize( $data );
        return $data;
    }

}
