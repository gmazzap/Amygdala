<?php namespace Brain\Amygdala;

/**
 * Wrap request informations and ease the getting of data without having to deal with superglobals.
 *
 * @package Brain\Amygdala
 * @version 0.1
 */
class Amygdala {

    use \Brain\Contextable;

    protected $prototype;

    protected $query;

    protected $post;

    protected $request;

    protected $server;

    protected $data;

    private static $keys = [ 'query', 'post', 'server', 'data', 'request' ];

    private $sniffed = FALSE;

    function __construct( Bag $prototype ) {
        $this->prototype = $prototype;
    }

    /**
     * Get the query bag
     *
     * @return \Brain\Amygdala\Bag
     */
    function getQuery() {
        return $this->query;
    }

    /**
     * Get the post bag
     *
     * @return \Brain\Amygdala\Bag
     */
    function getPost() {
        return $this->post;
    }

    /**
     * Get the server bag
     *
     * @return \Brain\Amygdala\Bag
     */
    function getServer() {
        return $this->server;
    }

    /**
     * Get the data bag
     *
     * @return \Brain\Amygdala\Bag
     */
    function getData() {
        return $this->data;
    }

    /**
     * Get the request bag
     *
     * @return \Brain\Amygdala\Bag
     */
    function getRequest() {
        if ( ! is_null( $this->request ) ) return $this->request;
        $query = $this->getQuery();
        if ( $this->method() === 'POST' ) {
            $request = array_merge( $query->getArrayCopy(), $this->getPost()->getArrayCopy() );
            return $this->createBag( 'request', $request );
        } else {
            return $query;
        }
    }

    /**
     * Get values form one of the parameters bag.
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @param mixed $filter     Filter to use.
     * @return mixed
     */
    function get( $key = NULL, $index = NULL, $default = NULL, $filter = NULL ) {
        $value = $this->getContext( $key, $index );
        if ( $filter !== FALSE ) {
            $filter_def = FILTER_SANITIZE_STRING;
            if ( is_null( $value ) && is_null( $default ) ) {
                $filter = FALSE;
            } elseif ( is_array( $filter ) ) {
                $filter = array_values( $filter );
                $f = isset( $filter[0] ) && is_int( $filter[0] ) ? $filter[0] : $filter_def;
                $fopt = isset( $filter[1] ) ? $filter[1] : NULL;
            } else {
                $f = is_int( $filter ) ? $filter : $filter_def;
                $fopt = NULL;
            }
        }
        if ( $filter && is_array( $value ) ) {
            if ( is_null( $fopt ) ) {
                $fopt = FILTER_REQUIRE_ARRAY;
            } elseif (
                is_array( $fopt )
                && ( ! isset( $fopt['flags'] ) || $fopt['flags'] !== FILTER_REQUIRE_ARRAY )
            ) {
                $fopt['flags'] = isset( $fopt['flags'] ) && is_int( $fopt['flags'] ) ?
                    $fopt['flags'] | FILTER_REQUIRE_ARRAY :
                    FILTER_REQUIRE_ARRAY;
            } elseif ( ! is_array( $fopt ) ) {
                $fopt = is_int( $fopt ) ? [ 'options' => $fopt ] : [ ];
                $fopt['flags'] = FILTER_REQUIRE_ARRAY;
            }
        }
        if ( is_null( $value ) ) $value = $default;
        return $filter === FALSE ? $value : call_user_func( 'filter_var', $value, $f, $fopt );
    }

    /**
     * Get values form one of the parameters bag with no filter or default
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @return mixed
     */
    function getRaw( $key = NULL, $index = NULL ) {
        return $this->getContext( $key, $index );
    }

    /**
     * Similar to get, but force FILTER_SANITIZE_NUMBER_INT as filter
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getNumeric( $key = NULL, $index = NULL, $default = NULL ) {
        $value = $this->get( $key, $index, $default, FILTER_SANITIZE_NUMBER_FLOAT );
        return is_numeric( $value ) ? $value : '';
    }

    /**
     * Similar to getNumeric, also cast result as integer
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return int
     */
    function getInt( $key = NULL, $index = NULL, $default = NULL ) {
        $value = $this->getNumeric( $key, $index, $default );
        return (int) $value ? : 0;
    }

    /**
     * Similar to get, but use a regex to filter the result
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param string $regex     The regex to be used to filter the value
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getRegexFiltered( $key = NULL, $index = NULL, $regex = '', $default = NULL ) {
        $value = $this->get( $key, $index, $default, FILTER_SANITIZE_STRING );
        if ( is_string( $value ) ) {
            return preg_filter( $regex, '', $value );
        }
    }

    /**
     * Similar to get, but return a text-only value
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getAlpha( $key = NULL, $index = NULL, $default = NULL ) {
        return $this->getRegexFiltered( $key, $index, '/[^a-z_]*/i', $default );
    }

    /**
     * Similar to get, but return an alphanumeric only value
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getAlphaNum( $key = NULL, $index = NULL, $default = NULL ) {
        return $this->getRegexFiltered( $key, $index, '/[^a-z0-9_]*/i', $default );
    }

    /**
     * Similar to get, but return an urlencoded only value
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getEncoded( $key = NULL, $index = NULL, $default = NULL ) {
        $val = $this->get( $key, $index, $default, FILTER_SANITIZE_ENCODED );
        return is_string( $val ) ? $val : '';
    }

    /**
     * Similar to get, but return an html-encoded version of the value
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getWithEntities( $key = NULL, $index = NULL, $default = NULL ) {
        $val = $this->get( $key, $index, $default, FILTER_UNSAFE_RAW );
        return is_string( $val ) ? htmlentities( $val, ENT_QUOTES, 'utf-8' ) : '';
    }

    /**
     * Similar to get(), HTML tags are stripped, arrays are serialized, string returned SQL secured.
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getForSql( $key = NULL, $index = NULL, $default = NULL ) {
        $value = $this->get( $key, $index, $default, FILTER_SANITIZE_STRING );
        if ( ! empty( $value ) ) {
            return esc_sql( maybe_serialize( $value ) );
        }
    }

    /**
     * Similar to get(), HTML tags are encoded, arrays are serialized, string returned SQL secured.
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getForSqlEncoded( $key = NULL, $index = NULL, $default = NULL ) {
        $raw = $this->get( $key, $index, $default, FILTER_UNSAFE_RAW );
        if ( ! is_string( $raw ) && ! is_array( $raw ) ) $raw = '';
        $cb = function( $val ) {
            return htmlentities( $val, ENT_QUOTES, 'utf-8' );
        };
        $encoded = is_array( $raw ) ? array_map( $cb, $raw ) : $cb( $raw );
        return esc_sql( maybe_serialize( $encoded ) );
    }

    /**
     * Similar to get(), always return an array, mapped with a given callable.
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param callable $cb      Callback to use to map retrieved value
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return array
     */
    function getMapped( $key = NULL, $index = NULL, $cb = NULL, $default = NULL, $filter = NULL ) {
        $value = [ ];
        if ( is_callable( $cb ) ) {
            if ( is_null( $filter ) ) $filter = FILTER_UNSAFE_RAW;
            $raw = $this->get( $key, $index, $default, $filter );
            if ( is_null( $raw ) ) $raw = $default;
            $value = array_map( $cb, ( is_array( $raw ) ? $raw : [ $raw ] ) );
        }
        return $value;
    }

    /**
     * A short-cut to call get for 'query' bag
     *
     * @param string $index     The key to get
     * @param string $default   Default if the specific key is not available in the bag
     * @param string $filter    The filter to use
     * @return mixed
     */
    function query( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->get( 'query', $index, $default, $filter );
    }

    /**
     * A short-cut to call get for 'post' bag
     *
     * @param string $index     The key to get
     * @param string $default   Default if the specific key is not available in the bag
     * @param string $filter    The filter to use
     * @return mixed
     */
    function post( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->get( 'post', $index, $default, $filter );
    }

    /**
     * A short-cut to call get for 'server' bag
     *
     * @param string $index     The key to get
     * @param string $default   Default if the specific key is not available in the bag
     * @param string $filter    The filter to use
     * @return mixed
     */
    function server( $index = NULL, $default = NULL, $filter = NULL ) {
        if ( is_string( $index ) ) $index = strtoupper( $index );
        return $this->get( 'server', $index, $default, $filter );
    }

    /**
     * A short-cut to call get for 'data' bag
     *
     * @param string $index     The key to get
     * @param string $default   Default if the specific key is not available in the bag
     * @param string $filter    The filter to use
     * @return mixed
     */
    function data( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->get( 'data', $index, $default, $filter );
    }

    /**
     * Get a value from 'request' bag.
     *
     * @param string $index     The key to get
     * @param string $default   Default if the specific key is not available in the bag
     * @param string $filter    The filter to use
     * @return mixed
     */
    function request( $index = NULL, $default = NULL, $filter = NULL ) {
        return $this->getRequest()->get( $index, $default, $filter );
    }

    /**
     * Set a value in one of the bags
     *
     * @param string $key   Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index The key to set
     * @param mixed $value  The value to set
     * @return \Brain\Amygdala\Amygdala
     */
    function set( $key = NULL, $index = NULL, $value = NULL ) {
        return $this->setContext( $key, $index, $value );
    }

    /**
     * A short-cut to call set() for 'query' bag
     *
     * @param string $index The key to set
     * @param string $value The value to set
     * @return mixed
     */
    function setQuery( $index = NULL, $value = NULL ) {
        return $this->set( 'query', $index, $value );
    }

    /**
     * A short-cut to call set() for 'post' bag
     *
     * @param string $index The key to set
     * @param string $value The value to set
     * @return mixed
     */
    function setPost( $index = NULL, $value = NULL ) {
        return $this->set( 'post', $index, $value );
    }

    /**
     * A short-cut to call set() for 'server' bag
     *
     * @param string $index The key to set
     * @param string $value The value to set
     * @return mixed
     */
    function setServer( $index = NULL, $value = NULL ) {
        return $this->set( 'server', $index, $value );
    }

    /**
     * A short-cut to call set() for 'data' bag
     *
     * @param string $index The key to set
     * @param string $value The value to set
     * @return mixed
     */
    function setData( $index = NULL, $value = NULL ) {
        return $this->set( 'data', $index, $value );
    }

    /**
     * Get current request method, 'GET' or 'POST'
     *
     * @return string
     */
    function method() {
        return strtoupper( $this->server( 'REQUEST_METHOD', 'GET' ) );
    }

    /**
     * Get current user IP address
     *
     * @return string
     */
    function userIP() {
        return $this->server( 'REMOTE_ADDR', NULL, FILTER_VALIDATE_IP );
    }

    /**
     * Get current server port (usually 80 for http or 443 https)
     *
     * @return string
     */
    function port() {
        return $this->getInt( 'server', 'SERVER_PORT', 80 );
    }

    /**
     * Get the http referer for current request
     *
     * @return string
     */
    function referer() {
        return $this->server( 'HTTP_REFERER', NULL, FILTER_SANITIZE_URL );
    }

    /**
     * Get the http user agent for current request
     *
     * @return string
     */
    function userAgent() {
        return $this->server( 'HTTP_USER_AGENT' );
    }

    /**
     * Get the current page path, that is the current url, relative to home url
     *
     * @return string
     */
    function path() {
        return $this->data( 'path', '/', FILTER_SANITIZE_URL );
    }

    /**
     * Get the current page path pieces, i.e. the path exploded by '/'
     *
     * @return array
     */
    function pathPieces() {
        $data = $this->data( 'path', '/', FILTER_SANITIZE_URL );
        return $data !== '/' ? explode( '/', $data['path'] ) : [ ];
    }

    /**
     * True if the page is requested from a mobile device
     *
     * @return boolean
     */
    function isMobile() {
        return (bool) $this->data( 'path', NULL, FILTER_UNSAFE_RAW );
    }

    /**
     * True if current request is using SSL
     *
     * @return boolean
     */
    function isSecure() {
        return (bool) $this->data( 'secure', NULL, FILTER_UNSAFE_RAW );
    }

    /**
     * Return a Bag instance used as prototype to created other ones.
     *
     * @return \Brain\Amygdala\Bag
     */
    function prototype() {
        return $this->prototype;
    }

    /**
     * Return true id current request was already sniffed
     *
     * @return boolean
     */
    function sniffed() {
        return (bool) $this->sniffed;
    }

    /**
     * Take an id and an array of data and instanciate and setup a Bag object
     *
     * @param string $id    Bag id
     * @param array $var    Bada data
     * @return \Brain\Amygdala\Bag
     */
    function createBag( $id = '', Array $var = [ ] ) {
        if ( in_array( strtolower( $id ), self::$keys ) && is_null( $this->$id ) ) {
            $bag = clone $this->prototype();
            $bag->exchangeArray( $var );
            $bag->setId( $id )->setRequest( $this );
            $this->$id = $bag;
            return $bag;
        }
    }

    /**
     * Sniff the current request data
     *
     * @return void
     */
    function sniff() {
        if ( $this->sniffed() ) return;
        $this->sniffServer();
        $this->sniffQuery();
        $this->sniffData();
        $this->sniffPost();
    }

    /**
     * Setup bags using fake data instead of request
     *
     * @param string $path  Url path to fake
     * @param array $query  Query data to fake
     * @param array $post   Post data t fake
     * @param array $server Server data to fake
     * @return \Brain\Amygdala\Amygdala
     */
    function simulate( $path = '', Array $query = [ ], Array $post = [ ], Array $server = [ ] ) {
        $this->createBag( 'query', $query );
        $this->createBag( 'post', $post );
        $method = isset( $server['REQUEST_METHOD'] ) ? $server['REQUEST_METHOD'] : NULL;
        if ( ! in_array( $method, ['GET', 'POST' ] ) ) $method = 'GET';
        $ip = isset( $server['REMOTE_ADDR'] ) ? $server['REMOTE_ADDR'] : NULL;
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) $ip = '127.0.0.1';
        $port = isset( $server['SERVER_NAME'] ) ? $server['SERVER_NAME'] : NULL;
        if ( ! is_numeric( $port ) || (int) $port <= 0 ) $port = 80;
        $name = isset( $server['SERVER_NAME'] ) ? $server['SERVER_NAME'] : NULL;
        if ( ! filter_var( $name, FILTER_SANITIZE_URL ) ) $name = 'www.example.com';
        $host = isset( $server['HTTP_HOST'] ) ? $server['HTTP_HOST'] : NULL;
        if ( ! filter_var( $host, FILTER_SANITIZE_URL ) ) $host = 'www.example.com';
        $referer = isset( $server['HTTP_REFERER'] ) ? $server['HTTP_REFERER'] : NULL;
        if ( ! filter_var( $referer, FILTER_SANITIZE_URL ) ) $referer = '';
        $agent = isset( $server['HTTP_USER_AGENT'] ) ? $server['HTTP_USER_AGENT'] : NULL;
        if ( ! filter_var( $agent, FILTER_SANITIZE_STRING ) ) $agent = '';
        $server_data = [
            'REQUEST_METHOD'  => $method,
            'REMOTE_ADDR'     => $ip,
            'SERVER_PORT'     => $port,
            'SERVER_NAME'     => $name,
            'HTTP_HOST'       => $host,
            'HTTP_REFERER'    => $referer,
            'HTTP_USER_AGENT' => $agent
        ];
        $this->createBag( 'server', $server_data );
        if ( ! is_string( $path ) || ! ( $parsed = parse_url( $path, PHP_URL_PATH ) ) ) {
            $parsed = '/';
        }
        $this->createBag( 'data', [ 'path' => $parsed ] );
        return $this;
    }

    private function sniffServer() {
        $definition = [
            'REQUEST_METHOD' => [ 'filter'  => FILTER_CALLBACK, 'options' => function( $method ) {
                return strtoupper( filter_var( $method, FILTER_SANITIZE_STRING ) );
            } ],
            'QUERY_STRING'    => FILTER_UNSAFE_RAW,
            'REMOTE_ADDR'     => FILTER_VALIDATE_IP,
            'SERVER_PORT'     => FILTER_SANITIZE_NUMBER_INT,
            'SERVER_NAME'     => FILTER_SANITIZE_STRING,
            'HTTP_HOST'       => FILTER_SANITIZE_URL,
            'HTTP_REFERER'    => FILTER_SANITIZE_URL,
            'HTTP_USER_AGENT' => FILTER_SANITIZE_STRING
        ];
        $server = filter_input_array( INPUT_SERVER, $definition );
        $this->createBag( 'server', $server );
    }

    private function sniffQuery() {
        $vars = [ ];
        parse_str( $this->server( 'QUERY_STRING' ), $vars );
        $this->createBag( 'query', $vars );
    }

    private function sniffData() {
        $home_path = rtrim( parse_url( home_url(), PHP_URL_PATH ), '/' );
        $path = rtrim( substr( add_query_arg( [ ] ), strlen( $home_path ) ), '/' );
        $qs = array_keys( $this->getQuery()->getArrayCopy() );
        if ( ! empty( $qs ) ) {
            $path = remove_query_arg( $qs, $path );
        }
        $ssl = (bool) is_ssl();
        $data = [
            'path'   => ( $path === '' ) ? '/' : rtrim( $path, '/\\? ' ),
            'mobile' => (bool) wp_is_mobile(),
            'secure' => (bool) $ssl,
            'scheme' => $ssl ? 'https' : 'http'
        ];
        $this->createBag( 'data', $data );
    }

    private function sniffPost() {
        $post = [ ];
        if ( $this->method() === 'POST' ) {
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
        }
        $this->createBag( 'post', $post );
    }

}