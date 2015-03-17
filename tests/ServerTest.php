<?php

use Kemist\Http\Server\Server;

class ServerTest extends \PHPUnit_Framework_TestCase {

    public function testEmptyRequest() {
        $server = new Server();
        $this->assertEquals($_SERVER, $server->getRequest()->getServerParams());
    }

    public function testAppendMiddleWare() {
        $middleware = function($request, $response) {
            $response->getBody()->write('test content');
            return $response;
        };
        $server = new Server();
        $server->appendMiddleware($middleware);
        $response = $server->handle();
        $this->assertEquals('test content', (string) $response->getBody());
    }

    public function testPrependMiddleWare() {
        $middleware1 = function($request, $response) {
            $response->getBody()->write('test content');
            return $response;
        };

        $middleware2 = function($request, $response) {
            $response->getBody()->write('before ');
            return $response;
        };
        $server = new Server();
        $server->appendMiddleware($middleware1);
        $server->prependMiddleware($middleware2);
        $response = $server->handle();
        $this->assertEquals('before test content', (string) $response->getBody());
    }

    public function testInvalidMiddlewareAppended() {
        $server = new Server();
        $this->setExpectedException('\InvalidArgumentException', 'Middleware must be a Closure or an object implementing __invoke method!');
        $server->appendMiddleware(new stdClass());
    }

    public function testInvalidMiddlewarePrepended() {
        $server = new Server();
        $this->setExpectedException('\InvalidArgumentException', 'Middleware must be a Closure or an object implementing __invoke method!');
        $server->prependMiddleware(new stdClass());
    }

    public function testPropagationStopped() {
        $middleware1 = function($request, $response, $server) {
            $response->getBody()->write('test content');
            $server->stopPropagation();
            return $response;
        };

        $middleware2 = function($request, $response, $server) {
            $response->getBody()->write('never happens');
            return $response;
        };
        $server = new Server();
        $server->appendMiddleware($middleware1);
        $server->appendMiddleware($middleware2);
        $response = $server->handle();
        $this->assertEquals('test content', (string) $response->getBody());
    }

}
