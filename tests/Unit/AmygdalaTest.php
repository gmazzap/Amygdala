<?php namespace Brain\Amygdala\Tests\Unit;

use Brain\Amygdala\Tests\TestCase;

class AmygdalaTest extends TestCase {

    private function get() {
        $bag = \Mockery::mock( 'Brain\Amygdala\Bag' );
        $amygdala = \Mockery::mock( 'Brain\Amygdala\Amygdala' )->makePartial();
        $amygdala->shouldReceive( 'prototype' )->withNoArgs()->andReturnNull( $bag );
        return $amygdala;
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testGetFailsIfEmptyKey() {
        $a = $this->get();
        $a->get();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    function testGetFailsIfBadKey() {
        $a = $this->get();
        $a->get( TRUE );
    }

    function testGetDefault() {
        $a = $this->get();
        $a->foo = new \ArrayObject;
        assertEquals( 'Default!', $a->get( 'foo', 'bar', 'Default!' ) );
    }

    function testGetFilterVar() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => 'baz' ] );
        $cb = function( $base ) {
            return $base . ' - filtered';
        };
        $filter_cb = [ FILTER_CALLBACK, [ 'options' => $cb ] ];
        $filter_url = [ FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE ];
        assertEquals( 'baz - filtered', $a->get( 'key', 'bar', 'Default!', $filter_cb ) );
        assertNull( $a->get( 'key', 'bar', 'Default!', $filter_url ) );
    }

    function testGetFilterVarWithArray() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => [ 'a' => 'b', 'c' => 'd' ] ] );
        $cb = function( $base ) {
            return $base . ' - F!';
        };
        $filter_cb = [ FILTER_CALLBACK, [ 'options' => $cb, 'flags' => FILTER_REQUIRE_ARRAY ] ];
        $expected_bar = [ 'a' => 'b - F!', 'c' => 'd - F!' ];
        assertEquals( $expected_bar, $a->get( 'key', 'bar', 'Default!', $filter_cb ) );
        assertEquals( '', $a->get( 'key', 'foo', 'Default!', FILTER_VALIDATE_URL ) );
    }

    function testGetFilterVarArray() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => [ 'a' => 'b', 'c' => 'd' ] ] );
        $cb = function( $base ) {
            return str_repeat( $base, 3 );
        };
        $filters = [
            'foo' => FILTER_SANITIZE_STRING,
            'bar' => [
                'filter'  => FILTER_CALLBACK,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => $cb
            ]
        ];
        $expected = [ 'foo' => 'bar', 'bar' => [ 'a' => 'bbb', 'c' => 'ddd' ] ];
        assertEquals( $expected, $a->get( 'key', NULL, NULL, $filters ) );
    }

    function testGetNumeric() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => [ 'a', 'b' ], 'baz' => '10' ] );
        assertEquals( '', $a->getNumeric( 'key', 'foo' ) );
        assertEquals( '', $a->getNumeric( 'key', 'bar' ) );
        assertTrue( '10' === $a->getNumeric( 'key', 'baz' ) );
    }

    function testGetInt() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => [ 'a', 'b' ], 'baz' => '10' ] );
        assertTrue( 0 === $a->getInt( 'key', 'foo' ) );
        assertTrue( 0 === $a->getInt( 'key', 'bar' ) );
        assertTrue( 10 === $a->getInt( 'key', 'baz' ) );
    }

    function testGetAlpha() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'a+b+c+1+2+3+' ] );
        assertEquals( 'abc', $a->getAlpha( 'key', 'foo' ) );
    }

    function testGetAlphaNum() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'a+b+c+1+2+3+' ] );
        assertEquals( 'abc123', $a->getAlphaNum( 'key', 'foo' ) );
    }

    function testGetEncoded() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => ' a b & c' ] );
        assertEquals( '%20a%20b%20%26%20c', $a->getEncoded( 'key', 'foo' ) );
    }

    function testGetWithEntities() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => '&"a"àà' ] );
        assertEquals( htmlentities( '&"a"àà', ENT_QUOTES ), $a->getWithEntities( 'key', 'foo' ) );
    }

    function testSet() {
        $a = $this->get();
        $a->key = new \ArrayObject();
        $a->set( 'key', 'foo', 'bar' );
        assertEquals( 'bar', $a->key['foo'] );
    }

    function testCreateBag() {
        $amygdala = \Mockery::mock( 'Brain\Amygdala\Amygdala' )->makePartial();
        $bag = \Mockery::mock( 'Brain\Amygdala\Bag' );
        $bag->shouldReceive( 'exchangeArray' )
            ->with( \Mockery::type( 'array' ) )
            ->atLeast( 1 )
            ->andReturnNull();
        $bag->shouldReceive( 'setId' )->with( 'query' )->atLeast( 1 )->andReturnSelf();
        $bag->shouldReceive( 'setRequest' )->with( $amygdala )->atLeast( 1 )->andReturnSelf();
        $amygdala->shouldReceive( 'prototype' )->withNoArgs()->atLeast( 1 )->andReturn( $bag );
        assertEquals( $bag, $amygdala->createBag( 'query', [ ] ) );
        assertEquals( $bag, $amygdala->getQuery() );
    }

    function testSimulate() {
        $amygdala = \Mockery::mock( 'Brain\Amygdala\Amygdala' )->makePartial();
        $bag = \Mockery::mock( 'Brain\Amygdala\Bag' );
        $bag->shouldReceive( 'exchangeArray' )
            ->with( \Mockery::type( 'array' ) )
            ->atLeast( 1 )
            ->andReturnNull();
        $bag->shouldReceive( 'setId' )
            ->with( \Mockery::anyOf( 'query', 'post', 'server', 'data' ) )
            ->atLeast( 1 )
            ->andReturnSelf();
        $bag->shouldReceive( 'setRequest' )->with( $amygdala )->atLeast( 1 )->andReturnSelf();
        $amygdala->shouldReceive( 'prototype' )->withNoArgs()->atLeast( 1 )->andReturn( $bag );
        $path = '/path/to/somewhere';
        $query = ['foo' => 'bar' ];
        $post = ['bar' => 'baz' ];
        $server = ['method' => 'POST' ];
        assertEquals( $amygdala, $amygdala->simulate( $path, $query, $post, $server ) );
    }

}