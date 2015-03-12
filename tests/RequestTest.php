<?php

use Kemist\Http\Uri;
use Kemist\Http\Request;
use Kemist\Http\Stream\Stream;

class RequestTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->_stream = new Stream('php://memory', 'wb+');
        $this->_uri = new Uri('http://www.example.com/path?param=value');
    }

    public function testInvalidMethod() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid request method given!');
        $request = new Request(null, 'invalid method');
    }

    public function testInvalidUri() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid Uri given!');
        $request = new Request(new stdClass());
    }

    public function testGetRequestTargetWithoutUri() {
        $request = new Request();
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithUriPathAndQuery() {
        $request = new Request($this->_uri);
        $this->assertEquals('/path?param=value', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithEmptyUri() {
        $request = new Request(new Uri());
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function testWithRequestTarget() {
        $request = new Request($this->_uri);
        $request = $request->withRequestTarget('*');
        $this->assertEquals('*', $request->getRequestTarget());
    }

    public function testGetMethod() {
        $request = new Request($this->_uri, 'GET');
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testWithMethod() {
        $request = new Request($this->_uri, 'GET');
        $request = $request->withMethod('POST');
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testWithInvalidMethod() {
        $request = new Request($this->_uri, 'GET');
        $this->setExpectedException('InvalidArgumentException', 'Invalid request method given!');
        $request = $request->withMethod('INVALID');
    }

    public function testWithUri() {
        $request = new Request($this->_uri, 'GET',array('Content-Type'=>'text/html'));
        $new_uri = new Uri('http://example.com');
        $request = $request->withUri($new_uri);
        $this->assertEquals($new_uri, $request->getUri());
    }

    public function testGetRawMessage() {
        $request = new Request($this->_uri);
        $raw = $request->getRawMessage();
        $calc_raw = "GET " . $this->_uri->getPath() . $this->_uri->getQuery() . " HTTP/1.1\r\nHost: " . $this->_uri->getHost();
        $this->assertEquals($calc_raw, trim($raw));
    }

}
