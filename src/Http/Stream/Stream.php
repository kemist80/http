<?php

namespace Kemist\Http\Stream;

use Psr\Http\Message\StreamableInterface;

/**
 * Stream
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 */
class Stream implements StreamableInterface {

    /**
     * Stream resource
     * @var resource 
     */
    protected $_resource;

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
        if (is_resource($stream)) {
            $this->_resource = $stream;
        } elseif (is_string($stream)) {
            if ($context === null) {
                $this->_resource = fopen($stream, $mode);
            } else {
                $context = is_resource($context) ? $context : stream_context_create($context);
                $this->_resource = fopen($stream, $mode, false, $context);
            }
        }
        if (!is_resource($this->_resource)) {
            throw new \InvalidArgumentException('Invalid stream given; must be a string stream identifier or resource');
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Checks if resource is valid
     * 
     * @return boolean
     */
    public function isValid() {
        return $this->_resource && is_resource($this->_resource);
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @return string
     */
    public function __toString() {
        $this->rewind();
        return $this->getContents();
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close() {
        if (!$this->isValid()) {
            return false;
        }
        $resource = $this->detach();
        fclose($resource);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach() {
        $resource = $this->_resource;
        $this->_resource = null;
        return $resource;
    }

    /**
     * Get the size of the stream if known
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize() {
        return null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int|bool Position of the file pointer or false on error.
     */
    public function tell() {
        if (!$this->isValid()) {
            return false;
        }
        return ftell($this->_resource);
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof() {
        if (!$this->isValid()) {
            return false;
        }
        return feof($this->_resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable() {
        return $this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function seek($offset, $whence = SEEK_SET) {
        if (!$this->isSeekable()) {
            return false;
        }
        $result = fseek($this->_resource, $offset, $whence);
        return ($result === 0);
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will return FALSE, indicating
     * failure; otherwise, it will perform a seek(0), and return the status of
     * that operation.
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function rewind() {
        return $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable() {
        $mode = $this->getMetaData('mode');
        return $mode && (strstr($mode, 'w') || strstr($mode, '+'));
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int|bool Returns the number of bytes written to the stream on
     *     success or FALSE on failure.
     */
    public function write($string) {
        if (!$this->isValid()) {
            return false;
        }
        return fwrite($this->_resource, $string);
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable() {
        $mode = $this->getMetaData('mode');
        return $mode && (strstr($mode, 'r') || strstr($mode, '+'));
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string|false Returns the data read from the stream, false if
     *     unable to read or if an error occurs.
     */
    public function read($length) {
        if (!$this->isValid() || !$this->isReadable()) {
            return false;
        }
        if ($this->eof()) {
            return '';
        }
        return fread($this->_resource, $length);
    }

    /**
     * Reads a line from stream
     * 
     * @return string|boolean
     */
    public function readLine() {
        if (!$this->isValid() || !$this->isReadable()) {
            return false;
        }
        if ($this->eof()) {
            return '';
        }
        return fgets($this->_resource, 8192);
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     */
    public function getContents() {
        if (!$this->isReadable()) {
            return '';
        }
        return stream_get_contents($this->_resource);
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null) {
        if (!$this->isValid()) {
            return false;
        }
        if ($key === null) {
            return stream_get_meta_data($this->_resource);
        }
        $metadata = stream_get_meta_data($this->_resource);
        if (!array_key_exists($key, $metadata)) {
            return null;
        }
        return $metadata[$key];
    }

    /**
     * Gets resource
     * @return resource
     */
    public function getResource() {
        return $this->_resource;
    }

    /**
     * Copies from one stream to another
     * 
     * @param Stream $dest
     * @param int $maxlength
     * @param int $offset
     * 
     * @return boolean|int
     */
    public function copy(Stream $dest, $maxlength = -1, $offset = 0) {
        if (!$dest->isValid() || !$this->isValid()) {
            return false;
        }
        return stream_copy_to_stream($this->_resource, $dest->getResource(), $maxlength, $offset);
    }

    /**
     * Sends stream contents directly to the output
     * 
     * @return boolean
     */
    public function output() {
        if (!$this->isValid()) {
            return false;
        }
        $this->rewind();
        return fpassthru($this->_resource);
    }

    /**
     * Appends filter to stream
     * 
     * @param string $filtername
     * @param int $read_write
     * @param mixed $params
     * 
     * @return resource
     */
    public function appendFilter($filtername, $read_write, $params = null) {
        return stream_filter_append($this->_resource, $filtername, $read_write, $params);
    }

    /**
     * Prepends filter to stream
     * 
     * @param string $filtername
     * @param int $read_write
     * @param mixed $params
     * 
     * @return resource
     */
    public function prependFilter($filtername, $read_write, $params = null) {
        return stream_filter_prepend($this->_resource, $filtername, $read_write, $params);
    }

}
