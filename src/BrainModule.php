<?php namespace Brain\Amygdala;

use Brain\Container;
use Brain\Module;

class BrainModule implements Module {

    function getBindings( Container $c ) {
        $c["bag"] = $c->factory( function() {
            return new Bag;
        } );
        $c["request"] = function($c) {
            return new Amygdala( $c["bag"] );
        };
    }

    function boot( Container $c ) {
        $c->get( 'request' )->sniff();
    }

    function getPath() {
        return trailingslashit( dirname( dirname( __FILE__ ) ) );
    }

}