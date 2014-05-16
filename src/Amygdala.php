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

    function getQuery() {
        return $this->query;
    }

    function getPost() {
        return $this->post;
    }

    function getServer() {
        return $this->server;
    }

    function getData() {
        return $this->data;
    }

    function get( $key = NULL, $index = NULL, $default = NULL, $filter = NULL ) {
        $value = $this->getContext( $key, $index, $default );
        if ( ! is_null( $value ) && $filter !== FALSE ) {
            if ( is_array( $value ) && is_null( $index ) ) {
                return call_user_func( 'filter_var_array', $value, (array) $filter );
            } elseif ( is_array( $filter ) ) {
                $filter = array_values( $filter );
                $f = isset( $filter[0] ) && is_int( $filter[0] ) ?
                    $filter[0] :
                    FILTER_SANITIZE_STRING;
                $fopt = isset( $filter[1] ) ? $filter[1] : NULL;
            } else {
                $f = is_int( $filter ) ? $filter : FILTER_SANITIZE_STRING;
                $fopt = NULL;
            }
            $value = call_user_func( 'filter_var', $value, $f, $fopt );
        } elseif ( is_null( $value ) && ! is_null( $default ) ) {
            $value = $default;
        }
        return $value;
    }

    function getNumeric( $key = NULL, $index = NULL, $default = NULL ) {
        return $this->get( $key, $index, $default, FILTER_SANITIZE_NUMBER_INT );
    }

    function getInt( $key = NULL, $index = NULL, $default = NULL ) {
        return (int) $this->getNumeric( $key, $index, $default );
    }

    function getRegexFiltered( $key = NULL, $index = NULL, $regex = '', $default = NULL ) {
        $value = $this->get( $key, $index, $default, FILTER_SANITIZE_STRING );
        if ( is_string( $value ) ) {
            return preg_filter( '/' . $regex . '/i', '', $value );
        }
    }

    function getAlpha( $key = NULL, $index = NULL, $default = NULL ) {
        return $this->getRegexFiltered( $key, $index, '[^a-z]', $default );
    }

    function getAlphaNum( $key = NULL, $index = NULL, $default = NULL ) {
        return $this->getRegexFiltered( $key, $index, '[^a-z0-9]', $default );
    }

    function getEncoded( $key = NULL, $index = NULL, $default = NULL ) {
        return $this->get( $key, $index, $default, FILTER_SANITIZE_ENCODED );
    }

    function getWithEntities( $key = NULL, $index = NULL, $default = NULL ) {
        $val = $this->get( $key, $index, $default, FILTER_UNSAFE_RAW );
        return is_string( $val ) ? htmlentities( $val, ENT_QUOTES, 'utf-8' ) : NULL;
    }

    function set( $key = NULL, $index = NULL, $value = NULL ) {
        return $this->setContext( $key, $index, $value );
    }

    function query( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->get( 'query', $index, $default, $filter );
    }

    function post( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->get( 'post', $index, $default, $filter );
    }

    function server( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->get( 'server', $index, $default, $filter );
    }

    function data( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->get( 'data', $index, $default, $filter );
    }

    function request( $index = NULL, $default = NULL, $filter = NULL ) {
        $is_post = $this->contextIs( 'server', 'REQUEST_METHOD', 'POST' );
        if ( is_null( $index ) ) {
            return $is_post ? array_merge( $this->query(), $this->post() ) : $this->query();
        } elseif ( $is_post && $this->contextHas( 'post', $index ) ) {
            return $this->post( $index, $default, $filter );
        } else {
            return $this->query( $index, $default, $filter );
        }
    }

    function setQuery( $index = NULL, $value = NULL ) {
        return $this->set( 'query', $index, $value );
    }

    function setPost( $index = NULL, $value = NULL ) {
        return $this->set( 'post', $index, $value );
    }

    function setServer( $index = NULL, $value = NULL ) {
        return $this->set( 'server', $index, $value );
    }

    function setData( $index = NULL, $value = NULL ) {
        return $this->set( 'data', $index, $value );
    }

    function method() {
        return strtoupper( $this->server( 'REQUEST_METHOD', 'GET' ) );
    }

    function userIP() {
        return $this->server( 'REMOTE_ADDR', NULL, FILTER_VALIDATE_IP );
    }

    function port() {
        return $this->getInt( 'server', 'SERVER_PORT', 80 );
    }

    function path() {
        return $this->data( 'path', '/', FILTER_VALIDATE_URL );
    }

    function prototype() {
        return $this->prototype;
    }

    function sniffed() {
        return (bool) $this->sniffed;
    }

    function createBag( $id = '', Array $var = [ ] ) {
        if ( in_array( strtolower( $id ), self::$keys ) && is_null( $this->$id ) ) {
            $bag = clone $this->prototype();
            $bag->exchangeArray( $var );
            $bag->setId( $id )->setRequest( $this );
            $this->$id = $bag;
            return $bag;
        }
    }

    function sniff() {
        if ( $this->sniffed() ) return;
        $this->sniffServer();
        $this->sniffQuery();
        $this->sniffUrl();
        if ( $this->method() === 'POST' ) {
            $this->sniffPost();
        }
    }

    function simulate( $path = '', Array $query = [ ], Array $post = [ ], Array $server = [ ] ) {
        $this->createBag( 'query', $query );
        $this->createBag( 'post', $post );
        $method = isset( $server['method'] ) && in_array( $server['method'], [ 'GET', 'POST' ] ) ?
            $server['method'] :
            'POST';
        $ip = isset( $server['userIP'] ) && filter_var( $server['userIP'], FILTER_VALIDATE_IP ) ?
            $server['userIP'] :
            '127.0.0.1';
        $port = isset( $server['port'] ) && is_numeric( $server['port'] ) ?
            (int) $server['port'] :
            80;
        $server_data = ['REQUEST_METHOD' => $method, 'REMOTE_ADDR' => $ip, 'SERVER_PORT' => $port ];
        $this->createBag( 'server', $server_data );
        if ( ! is_string( $path ) || ! ( $parsed = parse_url( $path, PHP_URL_PATH ) ) ) {
            $parsed = '/';
        }
        $this->createBag( 'data', [ 'path' => $parsed ] );
        return $this;
    }

    private function sniffServer() {
        $definition = [
            'REQUEST_METHOD' => [ 'filter' => FILTER_CALLBACK, 'options' => 'strtoupper' ],
            'QUERY_STRING'   => FILTER_SANITIZE_STRING,
            'REMOTE_ADDR'    => FILTER_VALIDATE_IP,
            'SERVER_PORT'    => FILTER_SANITIZE_NUMBER_INT
        ];
        $server = filter_input_array( INPUT_SERVER, $definition );
        $this->createBag( 'server', $server );
    }

    private function sniffQuery() {
        $vars = [ ];
        parse_str( $this->server( 'QUERY_STRING' ), $vars );
        $this->createBag( 'query', $vars );
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

    private function sniffPost() {
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

}