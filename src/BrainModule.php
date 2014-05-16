<?php namespace Brain\Amygdala;

use Brain\Container;
use Brain\Module;

class BrainModule implements Module {

    function getBindings( Container $c ) {
        $c["amygdala.bag"] = $c->factory( function() {
            return new Bag;
        } );
        $c["amygdala"] = function($c) {
            return new Amygdala( $c["amygdala.bag"] );
        };
    }

    function boot( Container $c ) {
        $c->get( 'amygdala' )->sniff();
    }

    function getPath() {
        return trailingslashit( dirname( dirname( __FILE__ ) ) );
    }

}