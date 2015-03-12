<?php

namespace Kemist\Http\Server;

/**
 * Server
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
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
     * @param mixed $middleware
     */
    public function appendMiddleware($middleware) {
        if (!is_callable($middleware)) {
            throw new \InvalidArgumentException('Middleware must be a Closure or an object implementing __invoke method!');
        }
        $this->_middlewares[] = $middleware;
    }

    /**
     * Prepends a middleware
     * 
     * @param mixed $middleware
     */
    public function prependMiddleware($middleware) {
        if (!is_callable($middleware)) {
            throw new \InvalidArgumentException('Middleware must be a Closure or an object implementing __invoke method!');
        }
        array_unshift($this->_middlewares, $middleware);
    }

    /**
     * Listens to incoming requests
     * @param \Kemist\Http\Server\ServerResponse $response
     * 
     * @return type
     */
    public function listen(ServerResponse $response = null) {
        if ($response === null) {
            $response = new \Kemist\Http\Server\ServerResponse();
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
        return function($middlewares, $middleware) {
            return function($request) use ($middlewares, $middleware) {
                return call_user_func($middleware, $request, $middlewares, $this);
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
