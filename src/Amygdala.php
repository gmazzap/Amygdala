<?php namespace Brain\Amygdala;

/**
 * Wrap request informations and ease the getting of data withouh havin gto deal with superglobals.
 */
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

    /**
     * Similar to get, but force FILTER_SANITIZE_NUMBER_INT as filter
     *
     * @param string $key       Id of the bag, 'query', 'post', 'server' or 'data'
     * @param string $index     The key to get
     * @param mixed $default    Default if the specific key is not available in the bag
     * @return string
     */
    function getNumeric( $key = NULL, $index = NULL, $default = NULL ) {
        return $this->get( $key, $index, $default, FILTER_SANITIZE_NUMBER_FLOAT );
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
        return (int) $this->getNumeric( $key, $index, $default );
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
            return preg_filter( '/' . $regex . '/i', '', $value );
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
        return $this->getRegexFiltered( $key, $index, '[^a-z]', $default );
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
        return $this->getRegexFiltered( $key, $index, '[^a-z0-9]', $default );
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
        return $this->get( $key, $index, $default, FILTER_SANITIZE_ENCODED );
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
        return is_string( $val ) ? htmlentities( $val, ENT_QUOTES, 'utf-8' ) : NULL;
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
     * Get a value from 'query' or 'post' bags.
     *
     * @param string $index     The key to get
     * @param string $default   Default if the specific key is not available in the bag
     * @param string $filter    The filter to use
     * @return mixed
     */
    function request( $index = NULL, $default = NULL, $filter = NULL ) {
        $is_post = $this->contextIs( 'server', 'REQUEST_METHOD', 'POST' );
        if ( is_null( $index ) ) {
            $query = $this->getQuery()->getArrayCopy();
            $post = $this->getPost()->getArrayCopy();
            return $is_post ? array_merge( $query, $post ) : $query;
        } elseif ( $is_post && $this->contextHas( 'post', $index ) ) {
            return $this->post( $index, $default, $filter );
        } else {
            return $this->query( $index, $default, $filter );
        }
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
        if ( $this->method() === 'POST' ) {
            $this->sniffPost();
        }
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
        if ( ! in_numeric( $port ) || (int) $port <= 0 ) $port = 80;
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
            'REQUEST_METHOD'  => [ 'filter' => FILTER_CALLBACK, 'options' => 'strtoupper' ],
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
        $data = parse_url( home_url() );
        $data['path'] = ( $path === '' ) ? '/' : rtrim( $path, '/\\? ' );
        $data['url_pieces'] = explode( '/', $data['path'] );
        $data['mobile'] = (bool) wp_is_mobile();
        $data['secure'] = (bool) is_ssl();
        $this->createBag( 'data', $data );
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