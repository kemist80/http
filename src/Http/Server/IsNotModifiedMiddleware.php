<?php

namespace Kemist\Http\Server;

use Kemist\Http\Stream\Stream;

/**
 * IsNotModifiedMiddleware
 *
 * @package Kemist\Http
 * 
 * @version 1.0.1
 */
class IsNotModifiedMiddleware extends AbstractMiddleware {

    /**
     * Detects if content is not modified
     * 
     * @param \Kemist\Http\Server\ServerRequest $request
     * @param \Kemist\Http\Server\ServerResponse $response
     * 
     * @return \Kemist\Http\Server\ServerResponse
     */
    public function process(ServerRequest $request, ServerResponse $response) {
        if (!in_array($request->getMethod(), array('GET', 'HEAD'))) {
            return $response;
        }

        $lastModified = $request->getHeader('If-Modified-Since');
        $notModified = false;

        if ($etags = preg_split('/\s*,\s*/', $request->getHeader('If-None-Match'), null, PREG_SPLIT_NO_EMPTY)) {
            $notModified = (in_array($response->getHeader('ETag'), $etags) ||
                    in_array('*', $etags)
                    ) &&
                    (!$lastModified ||
                    $response->getHeader('Last-Modified') == $lastModified);
        } elseif ($lastModified) {
            $notModified = $lastModified == $response->getHeader('Last-Modified');
        }

        if ($notModified) {
            $response = $response->withStatus(304)->withBody(new Stream('php://memory', 'wb+'));
            foreach (array('allow', 'content-encoding', 'content-language', 'content-Length', 'content-md5', 'content-type', 'last-modified') as $header) {
                if ($response->hasHeader($header)) {
                    $response = $response->withoutHeader($header);
                }
            }
            $this->_server->stopPropagation();
        }

        return $response;
    }

}
