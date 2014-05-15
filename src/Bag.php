<?php namespace Brain\Amygdala;

class Bag extends \ArrayObject {

    use \Brain\Idable,
        \Brain\Fullclonable;

    private $request;

    function setRequest( Amygdala $request ) {
        $this->request = $request;
    }

    function getRequest() {
        return $this->request;
    }

    function get( $index = NULL, $default = NULL ) {
        $value = $this->getRequest()->getContext( $this->getId(), $index );
        if ( is_null( $value ) && ! is_null( $default ) ) {
            $value = $default;
        }
        return $value;
    }

    function getFiltered( $index = NULL, $default = NULL, $filter = NULL ) {
        $value = $this->getRequest()->getContext( $index, $default );
        if ( ! is_null( $value ) && $filter !== FALSE ) {
            $cb = is_array( $value ) ? 'filter_var_array' : 'filter_var';
            if ( is_null( $filter ) ) $filter = FILTER_SANITIZE_STRING;
            $value = call_user_func( $cb, $value, $filter );
        } elseif ( ! is_null( $default ) ) {
            $value = $default;
        }
        return $value;
    }

    function set( $index = NULL, $value = NULL ) {
        $value = $this->getRequest()->setContext( $this->getId(), $index, $value );
        return $this->getRequest();
    }

    function getNumeric( $index = NULL, $default = NULL ) {
        return $this->getFiltered( $index, $default, FILTER_SANITIZE_NUMBER_INT );
    }

    function getInt( $index = NULL, $default = NULL ) {
        return (int) $this->getNumeric( $index, $default );
    }

    function getRegexFiltered( $regex = '', $index = NULL, $default = NULL ) {
        $value = $this->getFiltered( $index, $default, FILTER_SANITIZE_STRING );
        if ( is_string( $value ) ) {
            return preg_filter( '/' . preg_quote( $regex ) . '/i', '', $value );
        }
    }

    function getAlpha( $index = NULL, $default = NULL ) {
        return $this->getRegexFiltered( '[^a-z]', $index, $default );
    }

    function getAlphaNum( $index = NULL, $default = NULL ) {
        return $this->getRegexFiltered( '[^a-z0-9]', $index, $default );
    }

    function getEncoded( $index = NULL, $default = NULL ) {
        return $this->getFiltered( $index, $default, FILTER_SANITIZE_ENCODED );
    }

}