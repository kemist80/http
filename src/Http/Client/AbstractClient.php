<?php

namespace Kemist\Http\Client;

use Kemist\Http\Request;
use Kemist\Http\Response;
use Kemist\Http\Uri;
use Kemist\Http\Exception\ClientException;

/**
 * AbstractClient class
 *
 * @package Kemist\Http
 * 
 * @version 1.0.0
 */
abstract class AbstractClient {

    /**
     * Count of redirections
     * @var int 
     */
    protected $_redirections = 0;

    /**
     * Client options
     * @var array 
     */
    protected $_options = array(
        'follow_redirections' => true,
        'max_redirections' => 10,
        'use_cookies' => true,
        'dechunk_content' => true,
        'decode_content' => true,
        'connection_timeout' => 30,
        'timeout' => 60,
    );

    /**
     * Last Request object
     * @var Request 
     */
    protected $_last_request;

    /**
     * Constructor
     * 
     * @param array $options
     */
    public function __construct(array $options = array()) {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Sends the request
     * 
     * @param Request $request
     */
    abstract public function send(Request $request);

    /**
     * Handles redirection if follow redirection is enabled
     * 
     * @param Request $request
     * @param Response $response
     */
    public function followRedirection(Request $request, Response $response) {
        if (!$this->_options['follow_redirections'] || !$response->isRedirect()) {
            $this->_timer_start = null;
            return $response;
        }

        if ($this->_redirections >= $this->_options['max_redirections']) {
            throw new ClientException('Redirection limit exceeded!');
        }

        if (!$response->getHeader('location')) {
            throw new ClientException('Location obsolete by redirection!');
        }

        $this->_redirections++;

        $location = $response->getHeader('location');
        $request = $request->withUri(new Uri($location));
        return $this->send($request);
    }

    /**
     * Get redirects number
     * @return type
     */
    public function getRedirections() {
        return $this->_redirections;
    }

    /**
     * Sets request cookies based on response
     * 
     * @param Request $request
     * @param Response $response
     * @return Request
     */
    public function setRequestCookies(Request $request, Response $response) {
        if (!$this->_options['use_cookies']) {
            return $request;
        }
        $cookies = $response->getHeader('set-cookie');
        if (!$cookies) {
            return $request;
        }
        $cookies = $response->getHeaderLines('set-cookie');
        $cookie_headers = array();
        foreach ($cookies as $cookie) {
            $temp = explode(';', $cookie);
            $key_and_value = array_shift($temp);
            list($key, $value) = explode('=', $key_and_value);
            $key = trim($key);
            $value = trim($value);
            if (isset($cookie_headers[$key])) {
                unset($cookie_headers[$key]);
            }
            foreach ($temp as $item) {
                $item = trim($item);

                $item_array = explode('=', $item);
                $param = strtolower(trim($item_array[0]));
                $item_value = isset($item_array[1]) ? trim($item_array[1]) : null;
                switch ($param) {
                    case 'expires':
                        $exp = new \DateTime($item_value);
                        if ($exp->format('U') < time()) {
                            continue 3;
                        }
                        break;
                    case 'domain':
                        
                        if ($item_value != $request->getUri()->getHost()
                            &&
                            !(substr($item_value,0,1)=='.' && strstr($request->getUri()->getHost(),$item_value))
                            ) {
                            continue 3;
                        }
                        break;
                    case 'path':
                        if ($item_value != '/' && $item_value != $request->getUri()->getPath()) {
                            continue 3;
                        }
                        break;
                    case 'secure':
                        if ($request->getUri()->getScheme() != 'https') {
                            continue 3;
                        }
                        break;
                }
            }
            $cookie_headers[$key] = $key . '=' . $value;
        }

        if (count($cookie_headers) > 0) {
            $request = $request->withHeader('cookie', join('; ', $cookie_headers));
        }
        return $request;
    }

    /**
     * Retrieves last Request object if present
     * 
     * @return Request
     */
    public function getLastRequest() {
        return $this->_last_request;
    }

}
