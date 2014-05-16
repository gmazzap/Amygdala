<?php namespace Brain;

/**
 * Wrap request informations and ease the getting of data without having to deal with superglobals.
 *
 * This class is a sort of *proxy* to ease the package API calls.
 * All the API functions are defined in the class Brain\Striatum\API and can be called using this
 * class static methods, like:
 *
 *     Brain\Request::getInt( 'query', 'page' );
 *
 * Same methods can be also called using dynamic methods:
 *
 *     $api = new Brain\Request;
 *     $api->getInt( 'query', 'page' );
 *
 * This is useful when the package is used inside OOP plugins, making use of dependency injection.
 *
 * @package Brain\Amygdala
 * @version 0.1
 */
class Request extends Facade {

    public static function getBindId() {
        return 'amygdala';
    }

}