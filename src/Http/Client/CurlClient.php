<?php

namespace Kemist\Http\Client;

use Kemist\Http\Request;
use Kemist\Http\Response;
use Kemist\Http\Stream\Stream;
use Kemist\Http\Exception\ClientException;

/**
 * CurlClient
 *
 * @package Kemist\Http
 * 
 * @version 1.0.2
 * 
 */
class CurlClient extends AbstractClient {

    /**
     * Curl handle
     * @var resource 
     */
    protected $_curl;

    /**
     * Constructor
     * 
     * @param array $options
     * @throws ClientException
     */
    public function __construct(array $options = array()) {
        parent::__construct($options);
        if (!extension_loaded('curl')) {
            throw new ClientException('Curl extension is not available!');
        }
    }

    /**
     * Sends an HTTP request
     * 
     * @param \Kemist\Http\Request $request
     * @return type
     * @throws Exception
     */
    public function send(Request $request) {
        $curl_options = $this->_generateCurlOptions($request);

        $body = (string)$request->getBody();
        if ($body != ''){
            $curl_options[CURLOPT_POSTFIELDS]=$body;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $raw = curl_exec($ch);

        if (curl_errno($ch) > 0) {
            $msg = '( ' . curl_errno($ch) . ') ' . curl_error($ch);
            throw new ClientException('Curl error: ' . $msg);
        }

        $response = new Response();

        $curl_info = curl_getinfo($ch);
        $this->_redirections = $curl_info['redirect_count'];
        $header_size = $curl_info['header_size'];
        $raw_headers = substr($raw, 0, $header_size);
        $content = substr($raw, $header_size);

        // Parse headers
        $allheaders = explode("\r\n\r\n", $raw_headers);
        $last_headers = trim(array_pop($allheaders));
        while (count($allheaders) > 0 && $last_headers == '') {
            $last_headers = trim(array_pop($allheaders));
        }
        $header_lines = explode("\r\n", $last_headers);
        foreach ($header_lines as $header) {
            $header = trim($header);
            if ($header == '') {
                continue;
            }
            // Status line
            if (substr(strtolower($header), 0, 4) == 'http') {
                $temp = explode(' ', $header, 3);
                $temp2 = explode('/', $temp[0], 2);
                $response = $response->withProtocolVersion($temp2[1]);
                continue;
            }
            // Extract header
            $temp = explode(':', $header, 2);
            $header_name = trim(urldecode($temp[0]));
            $header_value = trim(urldecode($temp[1]));
            $response=$this->_addHeaderToResponse($response,$header_name,$header_value);
        }
        // Write content
        $response->getBody()->write($content);

        curl_close($ch);
        return $response;
    }

    /**
     * Generates curl options
     * 
     * @param Request $request
     * 
     * @return array
     */
    protected function _generateCurlOptions(Request $request) {
        $options = isset($this->_options['curl_options']) ? $this->_options['curl_options'] : array();

        $options[CURLOPT_HEADER] = true;
        $options[CURLOPT_RETURNTRANSFER] = true;

        $options[CURLOPT_HTTP_VERSION] = $request->getProtocolVersion();
        $options[CURLOPT_URL] = (string) $request->getUri();
        $options[CURLOPT_CONNECTTIMEOUT] = $this->_options['connection_timeout'];
        $options[CURLOPT_TIMEOUT] = $this->_options['timeout'];
        $options[CURLOPT_FOLLOWLOCATION] = $this->_options['follow_redirections'];
        $options[CURLOPT_MAXREDIRS] = $this->_options['max_redirections'];

        if ($request->hasHeader('accept-encoding') && $this->_options['decode_content']) {
            $options[CURLOPT_ENCODING] = $request->getHeader('accept-encoding');
        }
        if ($request->hasHeader('cookie') && $this->_options['use_cookies']) {
            $options[CURLOPT_COOKIE] = $request->getHeader('cookie');
        }

        switch ($request->getMethod()) {
            case 'GET':
                $options[CURLOPT_HTTPGET] = true;
                break;
            case 'HEAD':
                $options[CURLOPT_NOBODY] = true;
                break;
            case 'POST':
            case 'CONNECT':
            case 'DELETE':
            case 'PATCH':
            case 'PUT':
            case 'TRACE':
                $options[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;
        }

        foreach ($request->getHeaders() as $name => $value) {
            $options[CURLOPT_HTTPHEADER][] = $name . ': ' . $request->getHeader($name);
        }

        if ($request->getUri()->getUserInfo()) {
            $options[CURLOPT_USERPWD] = $request->getUri()->getUserInfo();
        }

        return $options;
    }

}
