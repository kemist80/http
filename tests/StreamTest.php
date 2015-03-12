<?php

use Kemist\Http\Stream\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->_stream = new Stream('php://memory', 'wb+');
    }

    public function testResource() {
        $stream = new Stream(fopen('php://memory', 'wb+'));
        $this->assertTrue(is_resource($stream->getResource()));
    }

    public function testInvalidResource() {
        error_reporting(0);
        $this->setExpectedException('InvalidArgumentException', 'Invalid stream given; must be a string stream identifier or resource');
        $stream = new Stream('invalid');
    }

    public function testToString() {
        $this->_stream->write('Test');
        $this->assertEquals('Test', (string) $this->_stream);
    }

    public function testGetSize() {
        $this->assertNull($this->_stream->getSize());
    }

    public function testFTell() {
        $this->_stream->write('Test');
        $this->assertEquals(4, $this->_stream->tell());
    }

    public function testEof() {
        $this->_stream->read(8192);
        $this->assertEquals(true, $this->_stream->eof());
    }

    public function testIsWritable() {
        $this->assertEquals(true, $this->_stream->isWritable());
    }

    public function testReadLine() {
        $this->_stream->write("first row\n");
        $this->_stream->write("second row\n");
        $this->_stream->rewind();
        $this->assertEquals("first row\n", $this->_stream->readLine());
    }

    public function testGetMetadata() {
        $this->assertTrue(is_array($this->_stream->getMetaData()));
    }

    public function testCopy() {
        $stream = new Stream(fopen('php://memory', 'wb+'));
        $this->_stream->write('Test');
        $this->_stream->rewind();
        $this->_stream->copy($stream);
        $this->assertEquals('Test', (string) $stream);
    }

    public function testOutput() {
        $this->_stream->write('Test');
        ob_start();
        $this->_stream->output();
        $out = ob_get_clean();
        $this->assertEquals('Test', $out);
    }

    public function testCloseInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->close());
    }

    public function testTellInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->tell());
    }

    public function testEofInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->eof());
    }

    public function testSeekInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->seek(0));
    }

    public function testWriteInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->write('d'));
    }

    public function testReadInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->read(4096));
    }

    public function testReadLineInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->readLine());
    }

    public function testNotExistingMetadataKey() {
        $this->assertNull($this->_stream->getMetadata('not_existing'));
    }

    public function testOutputInvalid() {
        $this->_stream->close();
        $this->assertFalse($this->_stream->output());
    }

    public function testCopyInvalid() {
        $this->_stream->close();
        $stream2 = new Stream(fopen('php://memory', 'wb+'));
        $this->assertFalse($this->_stream->copy($stream2));
    }

    public function testReadWhenEof() {
        $this->_stream->write(1);
        $content = $this->_stream->getContents();
        $this->assertEquals('', $this->_stream->read(4096));
    }

    public function testReadLineWhenEof() {
        $this->_stream->write(1);
        $content = $this->_stream->getContents();
        $this->assertEquals('', $this->_stream->readLine());
    }

    public function testGetContentsWhenNotReadable() {
        $this->_stream->close();
        $this->assertEquals('', $this->_stream->getContents());
    }

    public function testStreamContext() {
        $context=array('test'=>'value');
        $stream=new Stream('php://memory','wb+',$context);
        $this->assertTrue(is_resource($stream->getResource()));
    }

}
