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
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => [ 'a' => '1', 'c' => 'foo' ] ] );
        $expected_bar_int = [ 'a' => '1', 'c' => '' ];
        $cb = function( $base ) {
            return $base . ' - filtered';
        };
        $flags = FILTER_REQUIRE_ARRAY | FILTER_FLAG_STRIP_LOW;
        $filter_cb = [ FILTER_CALLBACK, ['options' => $cb, 'flags' => $flags ] ];
        $expected_bar_cb = [ 'a' => '1 - filtered', 'c' => 'foo - filtered' ];
        assertEquals( $expected_bar_int, $a->get( 'key', 'bar', '', FILTER_SANITIZE_NUMBER_INT ) );
        assertEquals( $expected_bar_cb, $a->get( 'key', 'bar', '', $filter_cb ) );
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

    function testGetForSql() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => ['a' => '1', 'b' => '<b>foo</b>' ] ] );
        assertEquals( 'bar', $a->getForSql( 'key', 'foo' ) );
        assertEquals( serialize( [ 'a' => '1', 'b' => 'foo' ] ), $a->getForSql( 'key', 'bar' ) );
    }

    function testGetForSqlEncoded() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => [ 'a' => '1', 'b' => '<b>foo</b>' ] ] );
        $b = htmlentities( '<b>foo</b>' );
        assertEquals( 'bar', $a->getForSqlEncoded( 'key', 'foo' ) );
        assertEquals( serialize( [ 'a' => '1', 'b' => $b ] ), $a->getForSqlEncoded( 'key', 'bar' ) );
    }

    function testGetMapped() {
        $a = $this->get();
        $a->key = new \ArrayObject( [ 'foo' => 'bar', 'bar' => [ 'a' => '1', 'b' => 'foo' ] ] );
        $cb = function( $base ) {
            return (string) $base . ' - filtered';
        };
        $expected_bar_1 = [ 'a' => '1 - filtered', 'b' => 'foo - filtered' ];
        $expected_bar_2 = [ 'a' => '1 - filtered', 'b' => ' - filtered' ];
        $expected_bar_3 = [ 'a' => ' - filtered', 'b' => ' - filtered' ];
        assertEquals( $expected_bar_1, $a->getMapped( 'key', 'bar', $cb, '' ) );
        $filter_url = [ FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE ];
        assertEquals( $expected_bar_2, $a->getMapped( 'key', 'bar', $cb, '', FILTER_SANITIZE_NUMBER_INT ) );
        assertEquals( $expected_bar_3, $a->getMapped( 'key', 'bar', $cb, '', $filter_url ) );
    }

    function testRequest() {
        $amygdala = \Mockery::mock( 'Brain\Amygdala\Amygdala' )->makePartial();
        $amygdala->shouldReceive( 'contextIs' )
            ->with( 'server', 'REQUEST_METHOD', 'POST' )
            ->andReturn( TRUE );
        $bag = \Mockery::mock( 'Brain\Amygdala\Bag' );
        $bag->shouldReceive( 'exchangeArray' )
            ->with( \Mockery::type( 'array' ) )
            ->atLeast( 1 )
            ->andReturnNull();
        $bag->shouldReceive( 'setId' )
            ->with( \Mockery::anyOf( 'query', 'post' ) )
            ->atLeast( 1 )
            ->andReturnSelf();
        $amygdala->set( 'query' );
        $amygdala->set( 'post' );
        $amygdala->set( 'query', 'foo', 'bar' );
        $amygdala->set( 'query', 'bar', 'baz' );
        $amygdala->set( 'post', 'foo', 'barbar' );
        $amygdala->set( 'post', 'baz', 'foo' );
        assertEquals( 'bar', $amygdala->query( 'foo' ) );
        assertEquals( 'baz', $amygdala->query( 'bar' ) );
        assertEquals( 'barbar', $amygdala->post( 'foo' ) );
        assertEquals( 'foo', $amygdala->post( 'baz' ) );
        assertEquals( 'barbar', $amygdala->request( 'foo' ) );
        assertEquals( 'baz', $amygdala->request( 'bar' ) );
        assertEquals( 'foo', $amygdala->request( 'baz' ) );
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
        $bag = \Mockery::mock( 'Brain\Amygdala\Bag' )->makePartial();
        $bag->shouldReceive( 'setId' )
            ->with( \Mockery::anyOf( 'query', 'post', 'server', 'data' ) )
            ->atLeast( 1 )
            ->andReturnSelf();
        $bag->shouldReceive( 'setRequest' )->with( $amygdala )->atLeast( 1 )->andReturnSelf();
        $amygdala->shouldReceive( 'prototype' )->withNoArgs()->atLeast( 1 )->andReturn( $bag );
        $path = '/path/to/somewhere';
        $query = ['foo' => 'bar' ];
        $post = ['bar' => 'baz' ];
        $server = [ 'REQUEST_METHOD' => 'POST' ];
        assertEquals( $amygdala, $amygdala->simulate( $path, $query, $post, $server ) );
        assertEquals( $path, $amygdala->path() );
        assertEquals( $query, $amygdala->getRaw( 'query' ) );
        assertEquals( $post, $amygdala->getRaw( 'post' ) );
        assertEquals( 'POST', $amygdala->server( 'REQUEST_METHOD' ) );
    }

}