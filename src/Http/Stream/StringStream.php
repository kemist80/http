<?php

namespace Kemist\Http\Stream;

/**
 * StringStream
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 */
class StringStream extends Stream{
    
    /**
     * Constructor
     * 
     * @param string|resource $stream
     * @param string $mode Mode with which to open stream
     * @param mixed $context
     * 
     * @throws InvalidArgumentException
     */
    public function __construct($stream, $mode = 'r', $context = null) {       
        if (!is_string($stream)){
            throw new \InvalidArgumentException('Invalid string provided!');
        }
        parent::__construct('php://memory', 'wb+');
        $this->write($stream);
    }
}
