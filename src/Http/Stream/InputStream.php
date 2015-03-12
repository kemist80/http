<?php

namespace Kemist\Http\Stream;

/**
 * InputStream for php://input
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 */
class InputStream extends Stream {

    /**
     * Buffer
     * @var string 
     */
    protected $_buffer = '';

    /**
     * 
     * @var type 
     */
    protected $_all_read = false;

    /**
     * Constructor
     * 
     * @param type $stream
     * @param type $mode
     * @param type $context
     */
    public function __construct($stream = 'php://input', $mode = 'r', $context = null) {
        parent::__construct($stream, 'r', $context);
    }

    /**
     * Gets stream contents
     * 
     * @return string
     */
    public function getContents() {
        if (!$this->_all_read) {
            $this->_buffer = parent::getContents();
            $this->_all_read = true;
        }
        return $this->_buffer;
    }

    /**
     * Reads all data from the input stream
     *
     * @return string
     */
    public function __toString() {
        if ($this->_all_read) {
            return $this->_buffer;
        }

        return $this->getContents();
    }

    /**
     * Reads from stream
     * 
     * @param int $length
     * @return boolean
     */
    public function read($length) {
        if ($this->_all_read) {
            return false;
        }
        $ret = parent::read($length);
        $this->_buffer .= $ret;

        if ($this->eof()) {
            $this->_all_read = true;
        }

        return $ret;
    }

}
