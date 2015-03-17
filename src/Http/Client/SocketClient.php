<?php

namespace Kemist\Http\Client;

use Kemist\Http\Request;
use Kemist\Http\Response;
use Kemist\Http\Stream\Stream;
use Kemist\Http\Exception\ClientException;

/**
 * SocketClient
 *
 * @package Kemist\Http
 * 
 * @version 1.0.5
 */
class SocketClient extends AbstractClient {

    /**
     * Constructor
     * 
     * @param array $options
     * @throws ClientException
     */
    public function __construct(array $options = array()) {
        if (ini_get('allow_url_fopen') != '1') {
            throw new ClientException('URL-aware fopen wrappers disabled!');
        }
        parent::__construct($options);
    }

    /**
     * Sends an HTTP request
     * 
     * @param \Kemist\Http\Request $request
     * @return type
     * @throws Exception
     */
    public function send(Request $request) {
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $port = ($scheme == 'https' ? 443 : 80);

        if (!$this->_timer_start) {
            $this->_timer_start = time();
        }

        $f = stream_socket_client(($port == 443 ? 'ssl://' : 'tcp://') . $uri->getHost() . ':' . $port, $errno, $errstr, $this->_options['connection_timeout'], STREAM_CLIENT_CONNECT);
        if ($f === false) {
            throw new ClientException('Socket error: ' . $errno . ' - ' . $errstr);
        }

        if ($request->getMethod() == 'POST') {
            if (!$request->hasHeader('content-length')) {
                $body = $request->getBody();
                $body->rewind();
                $content = $body->getContents();
                $request = $request->withHeader('content-length', (string) strlen($content));
                $body->rewind();
            }
            if (!$request->hasHeader('content-type')){
                $request = $request->withHeader('content-type', 'application/x-www-form-urlencoded');
            }
        }


        $stream = new Stream($f);
        $stream->write($request->getRawMessage());
        $response = new Response();
        $i = 0;
        while (!$stream->eof()) {
            $line = trim($stream->readLine());
            // End of headers
            if ($line == '' || ($i == 0 && !strstr(strtolower($line), 'http'))) {
                break;
            }
            // Status line
            if ($i == 0) {
                $temp = explode(' ', $line, 3);
                $temp2 = explode('/', $temp[0], 2);
                $response = $response->withProtocolVersion($temp2[1])->withStatus($temp[1]);
            } else {
                $temp = explode(':', $line, 2);
                $header_name = trim(urldecode($temp[0]));
                $header_value = trim(urldecode($temp[1]));
                $response = $this->_addHeaderToResponse($response, $header_name, $header_value);
            }
            $i++;
        }

        // Dechunk content
        $body = $this->_dechunk($stream, $response);
        $stream->close();

        // Decode content
        $body = $this->_decode($body, $response);

        if ($this->_timer_start + $this->_options['timeout'] < time()) {
            throw new ClientException('Timeout exceeded!');
        }

        $body->rewind();
        $response = $response->withBody($body);
        $request = $this->setRequestCookies($request, $response);

        $this->_last_request = $request;
        return $this->followRedirection($request, $response);
    }

    /**
     * Dechunks chunked content
     * 
     * @param Stream $stream
     * @param Response $response
     * 
     * @return Stream
     * 
     * @throws ClientException
     */
    protected function _dechunk(Stream $stream, Response $response) {
        $temporary = new Stream(tmpfile());

        if ($this->_options['dechunk_content'] && $response->getHeader('transfer-encoding') == 'chunked') {
            do {
                $line = $stream->readLine();
                if ($line == "\r\n") {
                    continue;
                }

                $length = hexdec($line);
                if (!is_int($length)) {
                    throw new ClientException('Most likely not chunked encoding!');
                }

                if ($line === false || $length < 1 || $stream->eof()) {
                    break;
                }

                do {
                    $data = $stream->read($length);
                    if (false !== $data) {
                        $length -= strlen($data);
                        $temporary->write($data);
                    }
                    if ($length <= 0 || $stream->eof()) {
                        break;
                    }
                } while (true);
            } while (true);
        } else {
            while (!$stream->eof()) {
                if (false !== $data = $stream->read(4096)) {
                    $temporary->write($data);
                }
            }
        }
        return $temporary;
    }

    /**
     * Decodes encoded content
     * 
     * @param Stream $temporary
     * @param Response $response
     * 
     * @return Stream
     * 
     * @throws ClientException
     */
    protected function _decode(Stream $temporary, Response $response) {
        if ($this->_options['decode_content'] && in_array($response->getHeader('content-encoding'), array('gzip', 'deflate'))) {
            if (!$this->_options['dechunk_content'] && $response->getHeader('transfer-encoding') == 'chunked') {
                throw new ClientException('Unable to decode chunked data without dechunking!');
            }
            $temporary2 = new Stream(tmpfile());
            $temporary->seek(10);
            $temporary->appendFilter('zlib.inflate', STREAM_FILTER_READ);
            while (!$temporary->eof()) {
                if (false !== $data2 = $temporary->read(4096)) {
                    $temporary2->write($data2);
                }
            }
            $temporary->close();
            $temporary = $temporary2;
        }
        return $temporary;
    }

}
