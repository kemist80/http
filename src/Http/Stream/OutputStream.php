<?php

namespace Kemist\Http\Stream;

/**
 * OutputStream for php://output
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 */
class OutputStream extends Stream {

    /**
     * Constructor
     * 
     * @param type $stream
     * @param type $mode
     * @param type $context
     */
    public function __construct($stream = 'php://input', $mode = 'r', $context = null) {
        parent::__construct('php://output', 'w', $context);
    }

    /**
     * Output stream is write-only
     * 
     * @return boolean
     */
    public function isReadable() {
        return false;
    }

}
