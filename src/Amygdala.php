<?php namespace Brain\Amygdala;

class Amygdala {

    use \Brain\Contextable;

    protected $prototype;

    protected $query;

    protected $post;

    protected $server;

    protected $data;

    private static $keys = [ 'query', 'post', 'server', 'data' ];

    private $sniffed = FALSE;

    function __construct( Bag $prototype ) {
        $this->prototype = $prototype;
    }

    function __call( $name, $args ) {
        $object = FALSE;
        if ( strpos( $name, 'set' ) === 0 || strpos( $name, 'get' ) === 0 ) {
            $is_set = strpos( $name, 'set' ) === 0;
            $bag_id = strtolower( substr( $name, 3 ) );
            if ( $this->checkBag( $bag_id, TRUE ) ) {
                $object = $this->$bag_id;
            }
            if ( $object instanceof Bag && ! $is_set ) {
                return $object;
            } elseif ( $object instanceof Bag && isset( $args[0] ) ) {
                $value = isset( $args[1] ) ? $args[1] : NULL;
                return call_user_func( [ $object, 'set' ], $args[0], $value );
            }
        } else {
            if ( in_array( strtolower( $name ), self::$keys ) && $this->checkBag( $name ) ) {
                $object = $this->$name;
            } elseif ( $this->contextHas( 'data', $name ) ) {
                $object = $this->data;
                array_unshift( $args, $name );
            } elseif ( $this->contextHas( 'server', $name ) ) {
                $object = $this->server;
                array_unshift( $args, $name );
            }
            if ( $object instanceof Bag ) {
                return call_user_func_array( [ $object, 'getFiltered' ], $args );
            }
        }
    }

    function sniff() {
        if ( $this->sniffed() ) return;
        $definition = [
            'REQUEST_METHOD' => [ 'filter' => FILTER_CALLBACK, 'options' => 'strtoupper' ],
            'QUERY_STRING'   => FILTER_SANITIZE_STRING,
            'REMOTE_ADDR'    => FILTER_VALIDATE_IP,
            'SERVER_PORT'    => FILTER_SANITIZE_NUMBER_INT
        ];
        $server = filter_input_array( INPUT_SERVER, $definition );
        $server['method'] = $server['REQUEST_METHOD'];
        $server['port'] = $server['SERVER_PORT'];
        $server['userIP'] = $server['REMOTE_ADDR'];
        $vars = [ ];
        parse_str( $server['QUERY_STRING'], $vars );
        $this->createBag( 'query', $vars );
        $this->createBag( 'server', $server );
        $this->sniffUrl();
        if ( $this->method() !== 'POST' ) return $this->createBag( 'post' );
        $post_keys = array_keys( $_POST );
        $filters = [ ];
        $flag = FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH;
        $flag_array = $flag | FILTER_REQUIRE_ARRAY;
        $filter = [ 'filter' => FILTER_UNSAFE_RAW ];
        foreach ( $post_keys as $key ) {
            $filter['flags'] = filter_input( INPUT_POST, $key ) === FALSE ? $flag_array : $flag;
            $filters[$key] = $filter;
        }
        $post = filter_input_array( INPUT_POST, array_combine( $post_keys, $filters ) );
        $this->createBag( 'post', $post );
    }

    function simulate( $path = '', Array $query = [ ], Array $post = [ ], Array $server = [ ] ) {
        $this->createBag( 'query', $query );
        $this->createBag( 'post', $post );
        $method = isset( $server['method'] ) && in_array( $server['method'], ['GET', 'POST' ] ) ?
            $server['method'] :
            'POST';
        $ip = isset( $server['userIP'] ) && filter_var( $server['userIP'], FILTER_VALIDATE_IP ) ?
            $server['userIP'] :
            '127.0.0.1';
        $port = isset( $server['port'] ) && is_numeric( $server['port'] ) ?
            (int) $server['port'] :
            80;
        $server_data = [
            'REQUEST_METHOD' => $method,
            'REMOTE_ADDR'    => $ip,
            'SERVER_PORT'    => $port,
            'method'         => $method,
            'userIP'         => $ip,
            'port'           => $port
        ];
        $this->createBag( 'server', $server_data );
        if ( ! is_string( $path ) || ! ( $parsed = parse_url( $path, PHP_URL_PATH ) ) ) {
            $parsed = '/';
        }
        $this->createBag( 'data', [ 'path' => $parsed ] );
    }

    function request( $index = NULL, $default = NULL, $filter = NULL ) {
        $is_post = $this->contextIs( 'server', 'method', 'POST' );
        if ( is_null( $index ) ) {
            return $is_post ? array_merge( $this->query(), $this->post() ) : $this->query();
        } elseif ( $is_post ) {
            if ( $this->contextHas( 'post', $index ) ) {
                return $this->post( $index, $default, $filter );
            } else {
                $this->query( $index, $default, $filter );
            }
        } else {
            return $this->query( $index, $default, $filter );
        }
    }

    function sniffed() {
        return (bool) $this->sniffed;
    }

    private function checkBag( $type = 'query', $factory = FALSE ) {
        if ( ! $this->sniffed() ) $this->sniff();
        if ( ! in_array( strtolower( $type ), self::$keys ) ) {
            throw new \InvalidArgumentException;
        }
        if ( is_null( $this->$type ) && $factory ) $this->createBag( $type );
        if ( ! $this->$type instanceof Bag ) {
            throw new \DomainException;
        }
        return TRUE;
    }

    private function sniffUrl() {
        $home_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
        $dirty_path = trim( substr( add_query_arg( [ ] ), strlen( $home_path ) ), '/' );
        $qs = array_keys( $this->query() );
        if ( ! empty( $qs ) ) {
            $path = remove_query_arg( $qs, $dirty_path );
        }
        $path = ( $path === '' ) ? '/' : rtrim( $path, '/\\? ' );
        $this->createBag( 'data', [ 'path' => $path ] );
    }

    private function createBag( $id = '', Array $var = [ ] ) {
        if ( in_array( strtolower( $id ), self::$keys ) ) {
            $bag = clone $this->prototype;
            if ( ! empty( $var ) ) $bag->exchangeArray( $var );
            $bag->setId( $id )->setRequest( $this );
            $this->$id = $bag;
            return $bag;
        }
    }

}