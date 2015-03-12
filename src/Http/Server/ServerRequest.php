<?php

namespace Kemist\Http\Server;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Kemist\Http\Stream\InputStream;
use Kemist\Http\Exception\RequestException;
use Kemist\Http\Request;
use Kemist\Http\Uri;

/**
 * ServerRequest
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 * @todo detect body encoding and parse it
 */
class ServerRequest extends Request implements ServerRequestInterface {

    /**
     * Server parameters
     * @var array 
     */
    protected $_server_params;

    /**
     * Cookie parameters
     * @var array 
     */
    protected $_cookie_params;

    /**
     * Query parameters
     * @var array 
     */
    protected $_query_params;

    /**
     * File parameters
     * @var array 
     */
    protected $_file_params;

    /**
     * Parsed body
     * @var array|object
     */
    protected $_parsed_body;

    /**
     * Attributes
     * @var array 
     */
    protected $_attributes;

    /**
     * Cosntructor
     * 
     * @param array $server_params
     * @param array $file_params
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @param \Kemist\Http\InputStream|string $body
     */
    public function __construct($server_params = array(), $file_params = array(), $uri = null, $method = null, $headers = array(), $body = 'php://input') {
        $this->_server_params = $server_params;
        $this->_file_params = $file_params;
        if (count($headers) == 0) {
            $headers = $this->_extractHeadersFromServerParams();
        }
        if ($body == 'php://input') {
            $body = new InputStream('php://input');
        }
        if (count($this->_server_params) > 0) {
            if ($uri === null) {
                $uri = $this->_extractUri();
            }
            if ($method === null) {
                $method = isset($this->_server_params['REQUEST_METHOD']) ? $this->_server_params['REQUEST_METHOD'] : 'GET';
            }
        } else {
            if ($method === null) {
                $method = 'GET';
            }
            if ($uri === null) {
                $uri = new Uri();
            }
        }

        parent::__construct($uri, $method, $headers, $body);
        $this->_extractQueryParams();
        $this->_extractCookiesHeader();
    }

    /**
     * Attempts to extract Uri from server parameters
     * 
     * @return Uri
     */
    protected function _extractUri() {
        $uri = new Uri('');
        if (isset($this->_server_params['REQUEST_SCHEME'])) {
            $uri = $uri->withScheme($this->_server_params['REQUEST_SCHEME']);
        }
        if (isset($this->_server_params['HTTP_HOST'])) {
            $uri = $uri->withHost($this->_server_params['HTTP_HOST']);
        }
        if (isset($this->_server_params['SERVER_PORT'])) {
            $uri = $uri->withPort($this->_server_params['SERVER_PORT']);
        }
        if (isset($this->_server_params['REQUEST_URI'])) {
            $temp = explode('?', $this->_server_params['REQUEST_URI']);
            $uri = $uri->withPath($temp[0]);
            if (isset($temp[1])) {
                $uri = $uri->withQuery($temp[1]);
            }
        }
        return $uri;
    }

    /**
     * Extract request headers from server parameters
     */
    protected function _extractHeadersFromServerParams() {
        $headers = array();
        foreach ($this->_server_params as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        if (isset($this->_server_params['SERVER_PROTOCOL'])) {
            $this->_protocol = substr($this->_server_params['SERVER_PROTOCOL'], strpos($this->_server_params['SERVER_PROTOCOL'], '/') + 1);
        }

        return $headers;
    }

    /**
     * Extract cookies from header
     */
    protected function _extractCookiesHeader() {
        if (!$this->hasHeader('cookie')) {
            return array();
        }
        $cookie = trim($this->getHeader('cookie'));
        $temp = explode(';', $cookie);
        $this->_cookie_params = array();

        foreach ($temp as $cookie_item) {
            $cookie_temp = explode('=', $cookie_item);
            $key = urldecode(trim($cookie_temp[0]));
            $value = urldecode($cookie_temp[1]);
            if (!isset($this->_cookie_params[$key])) {
                $this->_cookie_params[$key] = $value;
            }
        }
    }

    /**
     * Extract query parameters to array
     */
    protected function _extractQueryParams() {
        $query = $this->getUri()->getQuery();
        if ($query) {
            $this->_query_params = array();
            parse_str(substr($query, 1), $this->_query_params);
        }
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams() {
        return $this->_server_params;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams() {
        return $this->_cookie_params;
    }

    /**
     * Create a new instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return self
     */
    public function withCookieParams(array $cookies) {
        $clone = clone $this;
        $clone->_cookie_params = $cookies;
        return $clone;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URL or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the composed URL or the `QUERY_STRING`
     * composed in the server params.
     *
     * @return array
     */
    public function getQueryParams() {
        return $this->_query_params;
    }

    /**
     * Create a new instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URL stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return self
     */
    public function withQueryParams(array $query) {
        $clone = clone $this;
        $clone->_query_params = $query;
        return $clone;
    }

    /**
     * Retrieve the upload file metadata.
     *
     * This method MUST return file upload metadata in the same structure
     * as PHP's $_FILES superglobal.
     *
     * These values MUST remain immutable over the course of the incoming
     * request. They SHOULD be injected during instantiation, such as from PHP's
     * $_FILES superglobal, but MAY be derived from other sources.
     *
     * @return array Upload file(s) metadata, if any.
     */
    public function getFileParams() {
        return $this->_file_params;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is application/x-www-form-urlencoded and the
     * request method is POST, this method MUST return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody() {
        if ($this->_parsed_body) {
            return $this->_parsed_body;
        }
        $content_type = isset($this->_server_params['CONTENT_TYPE']) ? $this->_server_params['CONTENT_TYPE'] : 'application/x-www-form-urlencoded';
        $this->getBody()->rewind();
        $content = urldecode($this->getBody()->getContents());
        if (strstr($content_type, ';')) {
            $temp = explode(';', $content_type);
            $content_type = array_shift($temp);
        }

        switch ($content_type) {
            case 'application/json':
                $this->_parsed_body = json_decode($content);
                $this->_detectJsonError();
                break;
            case 'text/xml':
            case 'application/xml':
                $this->_parsed_body = simplexml_load_string($content);
                break;
            case 'application/x-www-form-urlencoded':
            default:
                parse_str($content, $this->_parsed_body);
                break;
        }
        return $this->_parsed_body;
    }

    /**
     * Detects json parsing errors
     * 
     * @return type
     * @throws RequestException
     */
    protected function _detectJsonError() {
        $error = 'Unable to parse json request body! ';
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return;
            case JSON_ERROR_DEPTH:
                $error.= 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error.= 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error.= 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error.= 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $error.= 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $error.= 'Unknown error';
                break;
        }
        throw new RequestException($error);
    }

    /**
     * Create a new instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is application/x-www-form-urlencoded and the
     * request method is POST, use this method ONLY to inject the contents of
     * $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return self
     */
    public function withParsedBody($data) {
        $clone = clone $this;
        $clone->_parsed_body = $data;
        return $clone;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes() {
        return $this->_attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null) {
        return isset($this->_attributes[$name]) ? $this->_attributes[$name] : $default;
    }

    /**
     * Create a new instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value) {
        $clone = clone $this;
        $clone->_attributes[$name] = $value;
        return $clone;
    }

    /**
     * Create a new instance that removes the specified derived request
     * attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name) {
        $clone = clone $this;
        if (isset($clone->_attributes[$name])) {
            unset($clone->_attributes[$name]);
        }
        return $clone;
    }

    /**
     * Create Server Request from global objects
     */
    public static function createFromGlobals() {
        return new self($_SERVER, $_FILES);
    }

    /**
     * After changing the cookie header cookies must be reparsed
     * 
     * @param string $name
     * @param string $value
     */
    public function withHeader($name, $value) {
        $clone = parent::withHeader($name, $value);
        if (strtolower($name) == 'cookie') {
            $clone->_extractCookiesHeader();
        }
        return $clone;
    }

    /**
     * Overriddes withUri as query params must be reparsed
     * 
     * @param UriInterface $uri
     */
    public function withUri(UriInterface $uri) {
        $clone = parent::withUri($uri);
        $clone->_extractQueryParams();
        return $clone;
    }

}
