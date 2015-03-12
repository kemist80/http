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
 * @version 1.0.2
 */
class SocketClient extends AbstractClient {

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

        $temporary = new Stream(tmpfile());

        // Dechunk
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
                    if (false !== $data){
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
        $stream->close();

        // Decode
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

        if ($this->_timer_start + $this->_options['timeout'] < time()) {
            throw new ClientException('Timeout exceeded!');
        }

        $response = $response->withBody($temporary);
        $request = $this->setRequestCookies($request, $response);

        $this->_last_request = $request;
        return $this->followRedirection($request, $response);
    }

}
