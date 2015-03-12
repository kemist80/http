<?php

use Kemist\Http\Stream\InputStream;
use Kemist\Http\Stream\StringStream;

class InputStreamTest extends \PHPUnit_Framework_TestCase {

    public function testGetContents() {
        $stream = new StringStream('param1=value1&param2=value2');
        $input = new InputStream($stream->getResource());
        $input->rewind();
        $this->assertEquals('param1=value1&param2=value2', $input->getContents());
    }

    public function testToString() {
        $stream = new StringStream('param1=value1&param2=value2');
        $input = new InputStream($stream->getResource());
        $input->rewind();
        $this->assertEquals('param1=value1&param2=value2', (string) $input);
    }

    public function testToStringBuffered() {
        $stream = new StringStream('param1=value1&param2=value2');
        $input = new InputStream($stream->getResource());
        $input->rewind();
        $str = (string) $input;
        $this->assertEquals('param1=value1&param2=value2', (string) $input);
    }

    public function testRead() {
        $stream = new StringStream('param1=value1&param2=value2');
        $input = new InputStream($stream->getResource());
        $input->rewind();
        $this->assertEquals('param1=value1&param2=value2', $input->read(8192));
    }

    public function testReadAfterEof() {
        $stream = new StringStream('param1=value1&param2=value2');
        $input = new InputStream($stream->getResource());
        $input->rewind();
        $input->read(8192);
        $input->rewind();
        $this->assertFalse($input->read(8192));
    }

}
