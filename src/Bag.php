<?php namespace Brain\Amygdala;

class Bag extends \ArrayObject {

    private $request;

    use \Brain\Idable,
        \Brain\Fullclonable;

    function getRequest() {
        return $this->request;
    }

    function setRequest( Amygdala $request ) {
        $this->request = $request;
        return $this;
    }

    function __call( $name, $arguments ) {
        if ( strpos( $name, 'get' ) === 0 && method_exists( $this->getRequest(), $name ) ) {
            if ( ! isset( $arguments[0] ) || $arguments[0] !== $this->getId() ) {
                array_unshift( $arguments, $this->getId() );
            }
            return call_user_func_array( [ $this->getRequest(), $name ], $arguments );
        }
    }

}