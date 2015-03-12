<?php

use Kemist\Http\Client\CurlClient;
use Kemist\Http\Uri;
use Kemist\Http\Request;
use Kemist\Http\Stream\StringStream;

class CurlClientTest extends \PHPUnit_Framework_TestCase {

    public function testCurlError() {
        $uri = new Uri('https://www.example.com');
        $request = new Request($uri);
        $client = new CurlClient();
        $this->setExpectedException('\Kemist\Http\Exception\ClientException');
        $response = $client->send($request);
    }

    public function testDisableSSLVerification() {
        $uri = new Uri('https://www.example.com');
        $request = new Request($uri);
        $client = new CurlClient(array('curl_options' => array(CURLOPT_SSL_VERIFYPEER => false)));
        $response = $client->send($request);
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testHead() {
        $uri = new Uri('http://www.example.com');
        $request = new Request($uri, 'HEAD');
        $client = new CurlClient();
        $response = $client->send($request);
        $this->assertEquals((string) $response->getBody(), '');
    }

    public function testRequestHeaders() {
        $uri = new Uri('http://httpbin.org/headers');
        $headers=[
            'Accept' => 'text/html',
            'Accept-encoding' => 'gzip,deflate',
            'Cookie' => 'cookie1=value1;cookie2=value2'
        ];
        $request = new Request($uri, 'GET', $headers);
        $client = new CurlClient();
        $response = $client->send($request);
        $result=json_decode($response->getBody(),true);
        $this->assertEquals(array_intersect($headers, $result['headers']),$headers);
    }

    public function testPost() {
        $uri = new Uri('http://httpbin.org/post');
        $request = new Request($uri, 'POST', [
            'Accept' => 'text/html',
            'Accept-encoding' => 'gzip,deflate',
        ]);
        $request = $request->withBody(new StringStream('param1=1&param2=2'));
        $client = new CurlClient();
        $response = $client->send($request);
        $result=json_decode($response->getBody(),true);
        $this->assertEquals($result['form'], array('param1'=>1,'param2'=>2));
    }

}
