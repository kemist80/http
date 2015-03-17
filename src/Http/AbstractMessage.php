<?php

namespace Kemist\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * Message
 *
 * @package Kemist\Http
 * 
 * @version 1.0.1
 */
abstract class AbstractMessage implements MessageInterface {

    /**
     * HTTP protocol version 
     * @var string 
     */
    protected $_protocol;

    /**
     * Headers
     * @var array 
     */
    protected $_headers;

    /**
     * Stream
     * @var StreamableInterface 
     */
    protected $_stream;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion() {
        return $this->_protocol;
    }

    /**
     * Create a new instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version) {
        $clone = clone $this;
        $clone->_protocol = $version;
        return $clone;
    }

    /**
     * Retrieves all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings.
     */
    public function getHeaders() {
        return $this->_headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name) {
        return isset($this->_headers[strtolower($name)]);
    }

    /**
     * Retrieve a header by the given case-insensitive name, as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeaderLines() instead
     * and supply your own delimiter when concatenating.
     *
     * @param string $name Case-insensitive header field name.
     * @return string
     */
    public function getHeader($name) {
        $lines = $this->getHeaderLines($name);
        return join(',', $lines);
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[]
     */
    public function getHeaderLines($name) {
        $name = strtolower($name);
        if (!$this->hasHeader($name)) {
            return array();
        }
        return is_array($this->_headers[$name]) ? $this->_headers[$name] : array($this->_headers[$name]);
    }

    /**
     * Create a new instance with the provided header, replacing any existing
     * values of any headers with the same case-insensitive name.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value) {
        $this->_checkHeader($name, $value);
        $clone = clone $this;
        if (strtolower($name) == 'set-cookie' && !is_array($value)) {
            $value = array($value);
        }
        $clone->_headers[strtolower($name)] = is_array($value) ? $value : explode(',', $value);
        return $clone;
    }

    /**
     * Checks header name and value validity
     * 
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).

     * @throws \InvalidArgumentException for invalid header names or values.
     */
    protected function _checkHeader($name, $value) {
        if (!is_string($name) ||
                (!is_string($value) && !is_array($value))
        ) {
            throw new \InvalidArgumentException('Invalid header name or header value given!');
        }
    }

    /**
     * Creates a new instance, with the specified header appended with the
     * given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value) {
        $this->_checkHeader($name, $value);
        $name = strtolower($name);

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }
        $clone = clone $this;
        if (is_array($value)) {
            $clone->_headers[$name] = array_merge($clone->_headers[$name], $value);
        } else {
            $clone->_headers[$name][] = $value;
        }

        return $clone;
    }

    /**
     * Creates a new instance, without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    public function withoutHeader($name) {
        if (!$this->hasHeader($name)) {
            return $this;
        }
        $clone = clone $this;
        unset($clone->_headers[strtolower($name)]);
        return $clone;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface Returns the body as a stream.
     */
    public function getBody() {
        return $this->_stream;
    }

    /**
     * Create a new instance, with the specified message body.
     *
     * The body MUST be a StreamableInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamableInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamableInterface $body) {
        $clone = clone $this;
        $clone->_stream = $body;
        return $clone;
    }

    /**
     * Converts headers to proper format
     * 
     * @param array $headers
     * @return array
     */
    protected function _buildHeaders(array $headers) {
        $processed = array();
        foreach ($headers as $header => $value) {
            if (!is_string($header)) {
                continue;
            }
            $header = strtolower($header);
            if (!is_array($value) && !is_string($value)) {
                continue;
            }

            if (!is_array($value)) {
                $value = explode(',', $value);
            }

            if (isset($processed[$header])) {
                $processed[$header] = array_merge($processed[$header], $value);
            } else {
                $processed[$header] = $value;
            }
        }

        return $processed;
    }

}
