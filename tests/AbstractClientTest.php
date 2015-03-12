<?php

use Kemist\Http\Uri;
use Kemist\Http\Request;
use Kemist\Http\Response;
use Kemist\Http\Stream\StringStream;

class AbstractClientTest extends \PHPUnit_Framework_TestCase {

    protected $_options = array(
        'follow_redirections' => true,
        'max_redirections' => 10,
        'use_cookies' => true,
        'dechunk_content' => true,
        'decode_content' => true,
        'connection_timeout' => 3,
        'timeout' => 10,
    );

    public function setUp() {
        $this->_client = $this->getMockForAbstractClass('Kemist\Http\Client\AbstractClient');
    }

    public function testFollowRedirectionNoNeed() {
        $request = new Request;
        $response = new Response;
        $new_response = $this->_client->followRedirection($request, $response);
        $this->assertEquals($response, $new_response);
    }

    public function testFollowRedirectionWithoutNewLocation() {
        $request = new Request;
        $response = new Response;
        $response = $response->withStatus(303);
        $this->setExpectedException('Kemist\Http\Exception\ClientException', 'Location obsolete by redirection!');
        $new_response = $this->_client->followRedirection($request, $response);
    }

    public function testFollowRedirection() {
        $this->_client->method('send')
                ->will($this->returnArgument(0));
        $request = new Request;
        $response = new Response;
        $response = $response->withStatus(303);
        $response = $response->withHeader('location', 'http://example.com');
        $new_request = $this->_client->followRedirection($request, $response);
        $this->assertEquals((string) $new_request->getUri(), 'http://example.com');
    }

    public function testSetRequestCookiesNoCookies() {
        $request = new Request;
        $response = new Response;
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request, $request);
    }

    public function testSetRequestCookies() {
        $request = new Request;
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), 'cookie1=value1');
    }

    public function testSetRequestCookiesSecure() {
        $request = new Request('https://example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;secure');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), 'cookie1=value1');
    }

    public function testSetRequestCookiesSecureInsecureScheme() {
        $request = new Request('http://example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;secure');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), '');
    }
    
    public function testSetRequestCookiesDomain() {
        $request = new Request('https://example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Domain=example.com');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), 'cookie1=value1');
    }    
    
    public function testSetRequestCookiesSubDomain() {
        $request = new Request('https://sub.example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Domain=.example.com');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), 'cookie1=value1');
    }      
    
    public function testSetRequestCookiesOtherDomain() {
        $request = new Request('https://example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Domain=sub.example.com');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), '');
    } 
    
    public function testSetRequestCookiesNotExpired() {
        $request = new Request('https://example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Expires='.gmdate('D, d-M-Y H:i:s e', time()+3600));
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), 'cookie1=value1');
    }       
    
    public function testSetRequestCookiesExpired() {
        $request = new Request('https://example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Expires='.gmdate('D, d-M-Y H:i:s e', time()-3600));
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), '');
    }         
    
    public function testSetRequestCookiesPath() {
        $request = new Request('https://example.com/path');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Path=/path');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), 'cookie1=value1');
    }        
    
    public function testSetRequestCookiesBasePath() {
        $request = new Request('https://example.com/path');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Path=/');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), 'cookie1=value1');
    }  
    
    
    public function testSetRequestCookiesOtherPath() {
        $request = new Request('https://example.com');
        $response = new Response;
        $response = $response->withHeader('set-cookie', 'cookie1=value1;Path=/path');
        $new_request = $this->_client->setRequestCookies($request, $response);
        $this->assertEquals($new_request->getHeader('cookie'), '');
    }    

}
