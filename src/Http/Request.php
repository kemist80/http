<?php

namespace Kemist\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamableInterface;
use Kemist\Http\Stream\Stream;

/**
 * Request
 *
 * @package Kemist\Http
 * 
 * @version 1.0.1
 */
class Request extends AbstractMessage implements RequestInterface {

    /**
     * Request target
     * @var string 
     */
    protected $_request_target;

    /**
     * Method
     * @var string 
     */
    protected $_method;

    /**
     * Uri
     * @var UriInterface
     */
    protected $_uri;

    /**
     * Supported HTTP methods
     * @var array
     */
    private $_valid_methods = array(
        'CONNECT',
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'PATCH',
        'POST',
        'PUT',
        'TRACE',
    );

    /**
     * Constructor
     * 
     * @param string|UriInterface $uri
     * @param string $method
     * @param array $headers
     * @param string|StreamableInterface $body
     * @param string $protocol
     * 
     * @throws \InvalidArgumentException
     */
    public function __construct($uri = null, $method = 'GET', $headers = array(), $body = 'php://input', $protocol = '1.1') {
        if (!$this->_isMethodValid($method)) {
            throw new \InvalidArgumentException('Invalid request method given!');
        }

        if (!is_string($uri) && !$uri instanceof UriInterface && $uri !== null) {
            throw new \InvalidArgumentException('Invalid Uri given!');
        }

        $this->_method = $method;
        $this->_uri = is_string($uri) ? new Uri($uri) : $uri;
        $this->_stream = ($body instanceof StreamableInterface) ? $body : new Stream($body, 'r');
        $this->_headers = $this->_buildHeaders($headers);
        $this->_protocol = $protocol;

        if (!$this->hasHeader('host') && $this->_uri !== null) {
            $host = array('host' => array($this->getUri()->getHost()));
            $this->_headers = array_merge($host, $this->_headers);
        }
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget() {
        if ($this->_request_target) {
            return $this->_request_target;
        }
        if (!$this->_uri) {
            return '/';
        }

        $this->_request_target = $this->_uri->getPath() . $this->_uri->getQuery();
        if ($this->_request_target == '') {
            $this->_request_target = '/';
        }
        return $this->_request_target;
    }

    /**
     * Create a new instance with a specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget) {
        $clone = clone $this;
        $clone->_request_target = $requestTarget;
        return $clone;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod() {
        return $this->_method;
    }

    /**
     * Create a new instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * changed request method.
     *
     * @param string $method Case-insensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method) {
        if (!$this->_isMethodValid($method)) {
            throw new \InvalidArgumentException('Invalid request method given!');
        }
        $clone = clone $this;
        $clone->_method = $method;
        return $clone;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request, if any.
     */
    public function getUri() {
        return $this->_uri;
    }

    /**
     * Create a new instance with the provided URI.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @return self
     */
    public function withUri(UriInterface $uri) {
        $clone = clone $this;
        $clone->_uri = $uri;
        $clone = $clone->withHeader('host', $uri->getHost());
        return $clone;
    }

    /**
     * Checks if method is valid
     * 
     * @param string $method
     * @return bool
     */
    protected function _isMethodValid($method) {
        return in_array(strtoupper((string) $method), $this->_valid_methods);
    }

    /**
     * Generates raw request message including headers and body
     * 
     * @return string
     */
    public function getRawMessage() {
        $ret = strtoupper($this->getMethod()) . ' ' . $this->getRequestTarget() . ' HTTP/' . $this->getProtocolVersion() . "\r\n";

        foreach ($this->getHeaders() as $name => $value) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
            $ret.=$name . ': ' . join(',', $value) . "\r\n";
        }        
        $ret.="\r\n" . (string)$this->getBody();
        return $ret;
    }

}
