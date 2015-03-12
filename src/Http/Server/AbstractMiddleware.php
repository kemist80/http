<?php

namespace Kemist\Http\Server;

/**
 * AbstractMiddleware
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 */
abstract class AbstractMiddleware implements MiddlewareInterface {

    protected $_server;
    
    /**
     * Processing current step and advances to next
     * 
     * @param \Kemist\Http\Server\ServerRequest $request
     * @param \Closure $next
     * @param Server $server
     */
    public function next(ServerRequest $request, \Closure $next, Server $server) {
        $response = $next($request);
        $this->_server=$server;
        if ($this->_server->isPropagationStopped()) {
            return $response;
        }
        return $this->process($request, $response);
    }

    /**
     * Invoke object
     * 
     * @param \Kemist\Http\Server\ServerRequest $request
     * @param \Closure $next
     * @param Server $server
     * 
     * @return type
     */
    public function __invoke(ServerRequest $request, \Closure $next, Server $server) {
        return $this->next($request, $next, $server);
    }

    /**
     * Processes current step
     * 
     * @param ServerRequest $request
     * @param ServerResponse $response
     */
    abstract public function process(ServerRequest $request, ServerResponse $response);
}
