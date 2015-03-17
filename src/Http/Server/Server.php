<?php

namespace Kemist\Http\Server;

/**
 * Server
 *
 * @package Kemist\Http
 * 
 * @version 1.0.4
 */
class Server {

    /**
     * Middlewares
     * @var array 
     */
    protected $_middlewares = array();

    /**
     * Middleware propagation stopped
     * @var bool 
     */
    protected $_propagation_stopped = false;

    /**
     * ServerRequest
     * @var ServerRequest 
     */
    protected $_request;

    /**
     * Constructor
     * 
     * @param \Kemist\Http\Server\ServerRequest $request
     * @param array $middlewares
     */
    public function __construct(ServerRequest $request = null, array $middlewares = array()) {
        if ($request === null) {
            $request = ServerRequest::createFromGlobals();
        }
        $this->_request = $request;
        $this->_middlewares = $middlewares;
    }

    /**
     * Appends a middleware
     * 
     * @param MiddlewareInterface|Closure $middleware
     */
    public function appendMiddleware($middleware) {
        if (!is_callable($middleware)) {
            throw new \InvalidArgumentException('Middleware must be a Closure or an object implementing __invoke method!');
        }
        $this->_middlewares[] = $this->_prepareMiddleware($middleware);
    }

    /**
     * Prepends a middleware
     * 
     * @param MiddlewareInterface|Closure $middleware
     */
    public function prependMiddleware($middleware) {
        if (!is_callable($middleware)) {
            throw new \InvalidArgumentException('Middleware must be a Closure or an object implementing __invoke method!');
        }
        array_unshift($this->_middlewares, $this->_prepareMiddleware($middleware));
    }

    /**
     * Prepares closure middleware for server
     *  
     * @param MiddlewareInterface|Closure $middleware
     * 
     * @return MiddlewareInterface|Closure
     */
    protected function _prepareMiddleware($middleware) {
        if (!is_object($middleware) || !$middleware instanceof \Closure) {
            return $middleware;
        }
        $middleware = function($request, $next, $server) use ($middleware) {
            $response = $next($request, $server);
            if (!$server->isPropagationStopped()) {
                $response = $middleware($request, $response, $server);
            }
            return $response;
        };
        return $middleware;
    }

    /**
     * Handles current request
     * @param ServerResponse $response
     * 
     * @return ServerResponse
     */
    public function handle(ServerResponse $response = null) {
        if ($response === null) {
            $response = new ServerResponse();
        }

        $initial = function() use ($response) {
            return $response;
        };

        return call_user_func(array_reduce($this->_middlewares, $this->_getMiddlewareCaller(), $initial), $this->_request);
    }

    /**
     * Gets middleware caller callback
     * 
     * @return Closure
     */
    protected function _getMiddlewareCaller() {
        $server = $this;
        return function($middlewares, $middleware) use ($server) {
            return function($request) use ($middlewares, $middleware, $server) {
                return call_user_func($middleware, $request, $middlewares, $server);
            };
        };
    }

    /**
     * Stops middleware propagation
     */
    public function stopPropagation() {
        $this->_propagation_stopped = true;
    }

    /**
     * Detects middleware propagation is stopped
     * 
     * @return bool
     */
    public function isPropagationStopped() {
        return $this->_propagation_stopped;
    }

    /**
     * Retrieves request object
     * 
     * @return ServerRequest
     */
    public function getRequest() {
        return $this->_request;
    }

}
