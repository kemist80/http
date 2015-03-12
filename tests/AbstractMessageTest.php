<?php

use Kemist\Http\AbstractMessage;
use Kemist\Http\Stream\Stream;

class AbstractMessageTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->_stream = new Stream('php://memory', 'wb+');
        $this->_message = $this->getMockForAbstractClass('Kemist\Http\AbstractMessage');
    }

    public function testGetProtocolVersion() {
        $message = $this->_message->withProtocolVersion('1.1');
        $this->assertEquals('1.1', $message->getProtocolVersion());
    }

    public function testGetBody() {
        $message = $this->_message->withBody($this->_stream);
        $this->assertEquals($this->_stream, $message->getBody());
    }

    public function testGetHeader() {
        $message = $this->_message->withHeader('Content-Type', 'text/html');
        $this->assertEquals('text/html', $message->getHeader('content-type'));
    }

    public function testWithoutHeader() {
        $message = $this->_message->withHeader('Content-Type', 'text/html');
        $message = $message->withoutHeader('content-type');
        $this->assertFalse($message->hasHeader('content-type'));
    }

    public function testGetNotExistingHeaderLines() {
        $this->assertEquals(array(), $this->_message->getHeaderLines('content-type'));
    }

    public function testWithAddedHeader() {
        $message = $this->_message->withHeader('Accept', 'text/html');
        $message = $message->withAddedHeader('Accept', 'application/xml');
        $this->assertEquals('text/html,application/xml', $message->getHeader('accept'));
    }

    public function testWithAddedHeaderNotExisting() {
        $message = $this->_message->withAddedHeader('Accept', 'text/html');
        $this->assertEquals('text/html', $message->getHeader('accept'));
    }

    public function testGetHeaders() {
        $message = $this->_message->withHeader('Content-Type', 'text/html');
        $this->assertEquals(array('content-type' => array('text/html')), $message->getHeaders());
    }

    public function testWithoutHeaderNotExisting() {
        $this->assertEquals($this->_message, $this->_message->withoutHeader('accept'));
    }

    public function testWithCookieHeader() {
        $cookie_value = 'test_cookie=test_value; Expires=Wed, 09 Jun 2021 10:18:14 GMT';
        $message = $this->_message->withHeader('Set-Cookie', $cookie_value);
        $lines = $message->getHeaderLines('set-cookie');
        $this->assertEquals(array($cookie_value), $lines);
    }

    public function testInvalidHeaderName() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header name');
        $message = $this->_message->withHeader(new stdClass(), 'test');
    }

    public function testInvalidAddedHeaderName() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header name');
        $message = $this->_message->withAddedHeader(new stdClass(), 'test');
    }
    

}
