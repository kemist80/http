<?php

namespace Kemist\Http\Server;

/**
 * MiddlewareInterface
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 */
interface MiddlewareInterface {
    
    /**
     * Processes current step and advances to next
     * 
     * @param ServerRequest $request
     * @param \Closure $next
     * @param Server $server
     */
    public function next(ServerRequest $request, \Closure $next, Server $server);
    
    /**
     * 
     * @param ServerRequest $request
     * @param ServerResponse $response
     */
    public function process(ServerRequest $request, ServerResponse $response);
    
    
}
