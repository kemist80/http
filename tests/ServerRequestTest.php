<?php

use Kemist\Http\Uri;
use Kemist\Http\Server\ServerRequest;
use Kemist\Http\Stream\Stream;
use Kemist\Http\Stream\StringStream;
use Kemist\Http\Exception\RequestException;

class ServerRequestTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->_stream = new Stream('php://memory', 'wb+');
        $this->_uri = new Uri('http://www.example.com/path?param=value');
    }

    public function testGetServerParams() {
        $server_params = array('param' => 'value');
        $sr = new ServerRequest($server_params);
        $this->assertEquals($server_params, $sr->getServerParams());
    }

    public function testGetCookieParams() {
        $server_params = array('HTTP_COOKIE' => 'cookie=value');
        $sr = new ServerRequest($server_params);
        $this->assertEquals(array('cookie' => 'value'), $sr->getCookieParams());
    }

    public function testWithCookieParams() {
        $server_params = array('HTTP_COOKIE' => 'cookie=value');
        $sr = new ServerRequest($server_params);
        $sr = $sr->withCookieParams(array('cookie2' => 'value2'));
        $this->assertEquals(array('cookie2' => 'value2'), $sr->getCookieParams());
    }

    public function testGetQueryParams() {
        $sr = new ServerRequest(array(), array(), $this->_uri);
        $this->assertEquals(array('param' => 'value'), $sr->getQueryParams());
    }

    public function testWithQueryParams() {
        $sr = new ServerRequest();
        $sr = $sr->withQueryParams(array('param' => 'value'));
        $this->assertEquals(array('param' => 'value'), $sr->getQueryParams());
    }

    public function testGetFileParams() {
        $file_params = array('test' => 'value');
        $sr = new ServerRequest(array(), $file_params);
        $this->assertEquals($file_params, $sr->getFileParams());
    }

    public function testGetParsedBody() {
        $input = new StringStream('param1=value1&param2=value2');

        $sr = new ServerRequest(array(), array(), $this->_uri, 'GET', array(), $input);
        $this->assertEquals(array('param1' => 'value1', 'param2' => 'value2'), $sr->getParsedBody());
    }

    public function testGetParsedBodyContentTypeIncludesSemicolon() {
        $input = new StringStream('param1=value1&param2=value2');
        $server_params = array('CONTENT_TYPE' => 'application/x-www-form-urlencoded;encoding=utf8');
        $sr = new ServerRequest($server_params, array(), $this->_uri, 'GET', array(), $input);
        $this->assertEquals(array('param1' => 'value1', 'param2' => 'value2'), $sr->getParsedBody());
    }

    public function testGetParsedBodyJson() {
        $json = new stdClass();
        $json->param1 = 'value1';
        $json->param2 = 'value2';
        $input = new StringStream(json_encode($json));
        $server_params = array('CONTENT_TYPE' => 'application/json');
        $sr = new ServerRequest($server_params, array(), $this->_uri, 'GET', array(), $input);
        $this->assertEquals($json, $sr->getParsedBody());
    }

    public function testGetParsedBodyInvalidJson() {
        $json = new stdClass();
        $json->param1 = 'value1';
        $json->param2 = 'value2';
        $input = new StringStream(json_encode($json) . '?');
        $server_params = array('CONTENT_TYPE' => 'application/json');
        $sr = new ServerRequest($server_params, array(), $this->_uri, 'GET', array(), $input);
        $this->setExpectedException('Kemist\Http\Exception\RequestException', 'Unable to parse json request body! Syntax error, malformed JSON');
        $parsed_body = $sr->getParsedBody();
    }

    public function testGetParsedBodyXml() {
        $xml = file_get_contents('phpunit.xml');
        $input = new StringStream($xml);
        $server_params = array('CONTENT_TYPE' => 'application/xml');
        $sr = new ServerRequest($server_params, array(), $this->_uri, 'GET', array(), $input);
        $parsed_body = $sr->getParsedBody();
        $this->assertTrue(is_object($parsed_body) && $parsed_body instanceof \SimpleXMLElement);
    }

    public function testWithParsedBody() {
        $new_body = 'new_body';
        $sr = new ServerRequest();
        $sr = $sr->withParsedBody($new_body);
        $this->assertEquals($new_body, $sr->getParsedBody());
    }

    public function testGetAttributes() {
        $sr = new ServerRequest();
        $this->assertEmpty($sr->getAttributes());
    }

    public function testGetAttributeDefault() {
        $sr = new ServerRequest();
        $default = 'test';
        $this->assertEquals($default, $sr->getAttribute('not_existing', $default));
    }

    public function testWithAttribute() {
        $sr = new ServerRequest();
        $sr = $sr->withAttribute('attribute1', 'value1');
        $this->assertEquals('value1', $sr->getAttribute('attribute1'));
    }

    public function testWithOutAttribute() {
        $sr = new ServerRequest();
        $sr = $sr->withAttribute('attribute1', 'value1');
        $sr = $sr->withoutAttribute('attribute1');
        $this->assertNull($sr->getAttribute('attribute1'));
    }

    public function testWithCookieHeader() {
        $server_params = array('HTTP_COOKIE' => 'cookie1=value1');
        $sr = new ServerRequest($server_params, array(), $this->_uri, 'GET');
        $sr = $sr->withHeader('cookie', 'cookie2=value2');
        $this->assertEquals(array('cookie2' => 'value2'), $sr->getCookieParams());
    }

    public function testWithUri() {
        $sr = new ServerRequest(array(), array(), $this->_uri);
        $sr = $sr->withUri(new Uri('http://usr:pss@example.com:81/mypath/myfile.html?a=b&b%5B%5D=2&b%5B%5D=3#myfragment'));
        $this->assertEquals(array('a' => 'b', 'b' => array(2, 3)), $sr->getQueryParams());
    }

    public function testExtractUriFromServerParams() {
        $server_params = array(
            'REQUEST_SCHEME' => 'http',
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => 81,
            'REQUEST_URI' => '/mypath/myfile.html?a=b&b%5B%5D=2&b%5B%5D=3',
        );
        $sr = new ServerRequest($server_params);
        $this->assertEquals('http://example.com:81/mypath/myfile.html?a=b&b%5B%5D=2&b%5B%5D=3', $sr->getUri());
    }

    public function testExtractServerProtocol() {
        $server_params = array(
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        );
        $sr = new ServerRequest($server_params);
        $this->assertEquals('1.1', $sr->getProtocolVersion());
    }

    public function testCreateFromGlobals() {
        $server_params = $_SERVER;
        $file_params = $_FILES;
        $sr = ServerRequest::createFromGlobals();
        $this->assertEquals($server_params, $sr->getServerParams());
        $this->assertEquals($file_params, $sr->getFileParams());
    }

}
