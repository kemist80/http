<?php

namespace Kemist\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamableInterface;
use Kemist\Http\Stream\Stream;

/**
 * Response
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 * 
 */
class Response extends AbstractMessage implements ResponseInterface {

    /**
     * HTTP Reason phrases
     * @var array 
     */
    protected $_valid_phrases = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );

    /**
     * Response status code
     * @var int 
     */
    protected $_status_code;

    /**
     * Response reason phrase
     * @var string 
     */
    protected $_reason_phrase;

    /**
     * Constructor
     * 
     * @param \Kemist\Http\StreamableInterface $body
     * @param int $status_code
     * @param array $headers
     * @throws \InvalidArgumentException
     */
    public function __construct($body = 'php://memory', $status_code = 200, $headers = array(), $protocol = '1.1') {
        if (!$this->_isStatusCodeValid($status_code)) {
            throw new \InvalidArgumentException('Invalid status code provided!');
        }

        $this->_status_code = $status_code;
        $this->_reason_phrase = $this->_valid_phrases[$status_code];
        $this->_stream = ($body instanceof StreamableInterface) ? $body : new Stream($body, 'wb+');
        $this->_headers = $this->_buildHeaders($headers);
        $this->_protocol = $protocol;
    }

    /**
     * Gets the response Status-Code.
     *
     * The Status-Code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return integer Status code.
     */
    public function getStatusCode() {
        return $this->_status_code;
    }

    /**
     * Create a new instance with the specified status code, and optionally
     * reason phrase, for the response.
     *
     * If no Reason-Phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * Status-Code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param integer $code The 3-digit integer result code to set.
     * @param null|string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = null) {
        if (!$this->_isStatusCodeValid($code)) {
            throw new \InvalidArgumentException('Invalid status code provided!');
        }
        $clone = clone $this;
        $clone->_status_code = $code;
        $clone->_reason_phrase = $reasonPhrase ? $reasonPhrase : $this->_valid_phrases[$code];
        return $clone;
    }

    /**
     * Gets the response Reason-Phrase, a short textual description of the Status-Code.
     *
     * Because a Reason-Phrase is not a required element in a response
     * Status-Line, the Reason-Phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * Status-Code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string|null Reason phrase, or null if unknown.
     */
    public function getReasonPhrase() {
        return $this->_reason_phrase;
    }

    /**
     * Checks if status code is valid
     * 
     * @param int $code
     * @return bool
     */
    protected function _isStatusCodeValid($code) {
        return array_key_exists($code, $this->_valid_phrases);
    }

    /**
     * Generates raw response message including headers and body
     * 
     * @return string
     */
    public function getRawMessage() {
        $ret = 'HTTP/' . $this->getProtocolVersion() . ' ' . $this->getStatusCode() . ' ' . $this->getReasonPhrase() . "\n";
        foreach ($this->_headers as $name => $value) {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
            if ($name == 'Set-Cookie') {
                foreach ($value as $val) {
                    $ret.=$name . ": " . $val . "\n";
                }
            } else {
                $ret.=$name . ": " . join(',', $value) . "\n";
            }
        }
        $ret.="\n" . (string)$this->getBody();
        return $ret;
    }

    /**
     * Sets a cookie
     * 
     * @param string $key
     * @param string $value
     * @param int|string $expire unix timestamp when cookie expires
     * @param string $path The path on the server in which the cookie will be available on
     * @param string $domain The domain on the server in which the cookie will be available on
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param bool $http_only When TRUE the cookie will be made accessible only through the HTTP protocol
     * 
     * @return self
     */
    public function withCookie($key, $value, $expire = 0, $path = null, $domain = null, $secure = false, $http_only = false) {
        if (is_string($expire) && date_parse($expire)) {
            $exp = new \DateTime($expire);
            $expire = $exp->format('U');
        }

        $header_value = urlencode($key) . '=' . urlencode((string) $value);
        $header_value.=$domain ? '; domain=' . $domain : '';
        $header_value.=$path ? '; path=' . $path : '';
        $header_value.=$expire > 0 ? '; expires=' . gmdate('D, d-M-Y H:i:s e', $expire) : '';
        $header_value.=$secure ? '; secure' : '';
        $header_value.=$http_only ? '; HttpOnly' : '';
        return $this->withAddedHeader('set-cookie', $header_value);
    }

    /**
     * Removes a cookie
     * 
     * @param string $key
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $http_only
     * 
     * @return self
     */
    public function withRemovedCookie($key, $path = null, $domain = null, $secure = false, $http_only = false) {
        return $this->withCookie($key, "", time() - 86400, $path, $domain, $secure, $http_only);
    }

    /**
     * Removes a cookie setting header
     * 
     * @param string $key
     * 
     * @return self
     */
    public function withoutCookie($key) {
        if (!$this->hasHeader('set-cookie') || !$this->hasCookie($key)) {
            return $this;
        }
        $cookies = $this->getHeaderLines('set-cookie');
        foreach ($cookies as $i => $cookie) {
            $temp = explode('=', $cookie);
            if ($temp[0] == $key) {
                unset($cookies[$i]);
            }
        }
        return $this->withHeader('set-cookie', $cookies);
    }

    /**
     * Detects a previously set cookie
     * 
     * @param string $key
     * 
     * @return bool
     */
    public function hasCookie($key) {
        $cookies = $this->getHeaderLines('set-cookie');
        foreach ($cookies as $cookie) {
            $temp = explode('=', $cookie);
            if ($temp[0] == $key) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is response informational
     * 
     * @return bool
     */
    public function isInformational() {
        $status_code = $this->getStatusCode();
        return $status_code >= 100 && $status_code < 200;
    }

    /**
     * Is response OK
     * 
     * @return bool
     */
    public function isOk() {
        return $this->getStatusCode() === 200;
    }

    /**
     * Is response successful
     * 
     * @return bool
     */
    public function isSuccessful() {
        $status_code = $this->getStatusCode();
        return $status_code >= 200 && $status_code < 300;
    }

    /**
     * Is redirect
     * 
     * @return bool
     */
    public function isRedirect() {
        return in_array($this->getStatusCode(), array(301, 302, 303, 307));
    }

    /**
     * Is Redirection
     * 
     * @return bool
     */
    public function isRedirection() {
        $status_code = $this->getStatusCode();
        return $status_code >= 300 && $status_code < 400;
    }

    /**
     * Is Forbidden
     * 
     * @return bool
     */
    public function isForbidden() {
        return $this->getStatusCode() === 403;
    }

    /**
     * Is Not Found
     * 
     * @return bool
     */
    public function isNotFound() {
        return $this->getStatusCode() === 404;
    }

    /**
     * Is Client error
     * 
     * @return bool
     */
    public function isClientError() {
        $status_code = $this->getStatusCode();
        return $status_code >= 400 && $status_code < 500;
    }

    /**
     * Is Server Error
     * 
     * @return bool
     */
    public function isServerError() {
        $status_code = $this->getStatusCode();
        return $status_code >= 500 && $status_code < 600;
    }

}
