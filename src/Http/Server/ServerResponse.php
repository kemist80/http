<?php

namespace Kemist\Http\Server;

use Kemist\Http\Response;

/**
 * ServerResponse
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 * 
 */
class ServerResponse extends Response {

    /**
     * Sends headers and body content to the client
     */
    public function send() {
        $this->sendHeaders();
        $this->sendBody();
    }

    /**
     * Sends headers
     */
    public function sendHeaders() {
        // @codeCoverageIgnoreStart
        if (!headers_sent()) {
            header('HTTP/' . $this->getProtocolVersion() . ' ' . $this->getStatusCode() . ' ' . $this->getReasonPhrase());
            foreach ($this->_headers as $name => $value) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
                if ($name == 'Set-Cookie') {
                    $replace = false;
                    foreach ($value as $val) {
                        header($name . ": " . $val, $replace);
                        $replace = false;
                    }
                } else {
                    header($name . ": " . join(',', $value));
                }
            }
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Sends body content to the output
     */
    public function sendBody() {
        $this->getBody()->output();
    }

    /**
     * Magic method
     * 
     * @return string
     */
    public function __toString() {
        $this->send();
        return '';
    }

}
