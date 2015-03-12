<?php

use Kemist\Http\Server\Server;
use Kemist\Http\Server\ServerRequest;
use Kemist\Http\Server\ServerResponse;
use Kemist\Http\Server\IsNotModifiedMiddleware;

class IsNotModifiedMiddlewareTest extends \PHPUnit_Framework_TestCase {

    public function testIsNotModifiedWithEtag() {
        $etag = md5('test');
        $server_params = array(
            'HTTP_IF_NONE_MATCH' => $etag
        );
        $request = new ServerRequest($server_params);
        $server = new Server($request);
        $server->appendMiddleware(new IsNotModifiedMiddleware());
        $response = new ServerResponse();
        $response = $response->withHeader('etag', $etag);
        $response = $server->listen($response);
        $this->assertEquals(304, $response->getStatusCode());
    }

    public function testIsNotModifiedWithLastModified() {
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone('UTC'));
        $last_modified = $now->format('D, d M Y H:i:s') . ' GMT';
        $server_params = array(
            'HTTP_IF_MODIFIED_SINCE' => $last_modified
        );
        $request = new ServerRequest($server_params);
        $server = new Server($request);
        $server->appendMiddleware(new IsNotModifiedMiddleware());
        $response = new ServerResponse();
        $response = $response->withHeader('last-modified', $last_modified);
        $response = $server->listen($response);
        $this->assertEquals(304, $response->getStatusCode());
    }

    public function testIsNotModifiedStopsPropagation() {
        $middleware = function($request, $next, $server) {
            $response = $next($request, $server);
            if (!$server->isPropagationStopped()) {
                $response->getBody()->write('test content');
                $server->stopPropagation();
            }
            return $response;
        };
        $etag = md5('test');
        $server_params = array(
            'HTTP_IF_NONE_MATCH' => $etag
        );
        $request = new ServerRequest($server_params);
        $server = new Server($request);        
        $server->appendMiddleware(new IsNotModifiedMiddleware());
        $server->appendMiddleware($middleware);
        $response = new ServerResponse();
        $response = $response->withHeader('etag', $etag);
        $response = $server->listen($response);
        $this->assertEquals('', (string) $response->getBody());
    }

}
