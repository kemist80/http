<?php

use Kemist\Http\Response;
use Kemist\Http\Stream\Stream;

class ResponseTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->_stream = new Stream('php://memory', 'wb+');
    }

    public function testInvalidStatus() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid status code provided!');
        $response = new Response($this->_stream, 909);
    }

    public function testGetStatusCode() {
        $response = new Response($this->_stream, 500);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testWithStatus() {
        $response = new Response($this->_stream, 500);
        $response = $response->withStatus(200);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWithInvalidStatus() {
        $response = new Response($this->_stream, 500);
        $this->setExpectedException('InvalidArgumentException', 'Invalid status code provided!');
        $response = $response->withStatus(909);
    }

    public function testGetReasonPhrase() {
        $response = new Response($this->_stream, 500);
        $response = $response->withStatus(200, 'All OK');
        $this->assertEquals('All OK', $response->getReasonPhrase());
    }

    public function testGetRawMessage() {
        $response = new Response($this->_stream);        
        $response = $response->withCookie('test-cookie', 'value');
        $response = $response->withHeader('content-type', 'text/html');
        $raw = $response->getRawMessage();
        $calc_raw = "HTTP/1.1 200 OK\nSet-Cookie: test-cookie=value\nContent-Type: text/html";
        $this->assertEquals($calc_raw, trim($raw));
    }

    public function testWithCookie() {
        $response = new Response($this->_stream);
        $response = $response->withCookie('test-cookie', 'value');
        $header = $response->getHeaderLines('set-cookie');
        $this->assertTrue(in_array('test-cookie=value', $header));
    }

    public function testWithCookieDateString() {
        $response = new Response($this->_stream);
        $response = $response->withCookie('test-cookie', 'value', '1day');
        $header = $response->getHeaderLines('set-cookie');
        $this->assertTrue(in_array('test-cookie=value; expires=' . gmdate('D, d-M-Y H:i:s e', time() + 86400), $header));
    }

    public function testWithoutCookie() {
        $response = new Response($this->_stream);
        $response = $response->withCookie('test-cookie1', 'value1');
        $response = $response->withCookie('test-cookie2', 'value2');
        $response = $response->withoutCookie('test-cookie1');
        $header = $response->getHeaderLines('set-cookie');
        $this->assertFalse(in_array('test-cookie1=value1', $header));
    }

    public function testWithoutCookieNotExisting() {
        $response = new Response($this->_stream);
        $response2 = $response->withoutCookie('test-cookie');
        $this->assertEquals($response, $response2);
    }

    public function testWithRemovedCookie() {
        $response = new Response($this->_stream);
        $response = $response->withRemovedCookie('test-cookie');
        $header = $response->getHeaderLines('set-cookie');
        $this->assertTrue(in_array('test-cookie=; expires=' . gmdate('D, d-M-Y H:i:s e', time() - 86400), $header));
    }

    public function testHasCookieNotExisting() {
        $response = new Response($this->_stream);
        $this->assertFalse($response->hasCookie('test-cookie'));
    }

    public function testIsInformational() {
        $response = new Response($this->_stream, 101);
        $this->assertTrue($response->isInformational());
    }

    public function testIsOk() {
        $response = new Response($this->_stream);
        $this->assertTrue($response->isOk());
    }

    public function testIsSuccessful() {
        $response = new Response($this->_stream, 201);
        $this->assertTrue($response->isSuccessful());
    }

    public function testIsRedirect() {
        $response = new Response($this->_stream, 307);
        $this->assertTrue($response->isRedirect());
    }

    public function testIsRedirection() {
        $response = new Response($this->_stream, 301);
        $this->assertTrue($response->isRedirection());
    }

    public function testIsForbidden() {
        $response = new Response($this->_stream, 403);
        $this->assertTrue($response->isForbidden());
    }

    public function testIsNotFound() {
        $response = new Response($this->_stream, 404);
        $this->assertTrue($response->isNotFound());
    }

    public function testIsClientError() {
        $response = new Response($this->_stream, 410);
        $this->assertTrue($response->isClientError());
    }

    public function testIsServerError() {
        $response = new Response($this->_stream, 502);
        $this->assertTrue($response->isServerError());
    }

}
