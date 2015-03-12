<?php

use Kemist\Http\Server\ServerResponse;

class ServerResponseTest extends \PHPUnit_Framework_TestCase {

    public function testSendBody() {
        $response = new ServerResponse();
        $response->getBody()->write('test');
        ob_start();
        $response->sendBody();
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('test', $content);
    }

    public function testToString() {
        $response = new ServerResponse();
        $response->getBody()->write('test');
        ob_start();
        $test = (string) $response;
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('test', $content);
    }

}
