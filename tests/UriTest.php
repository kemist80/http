<?php

use Kemist\Http\Uri;

class UriTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->_uri = new Uri('http://usr:pss@example.com:81/mypath/myfile.html?a=b&b%5B%5D=2&b%5B%5D=3#myfragment');
    }

    public function testGetScheme() {
        $this->assertEquals('http', $this->_uri->getScheme());
    }

    public function testGetAuthority() {
        $this->assertEquals('usr:pss@example.com:81', $this->_uri->getAuthority());
    }

    public function testGetUserInfo() {
        $this->assertEquals('usr:pss', $this->_uri->getUserInfo());
    }

    public function testGetHost() {
        $this->assertEquals('example.com', $this->_uri->getHost());
    }

    public function testGetPort() {
        $this->assertEquals(81, $this->_uri->getPort());
    }

    public function testGetPortWithEmptyUri() {
        $uri = new Uri();
        $this->assertNull($uri->getPort());
    }

    public function testGetPath() {
        $this->assertEquals('/mypath/myfile.html', $this->_uri->getPath());
    }

    public function testGetQuery() {
        $this->assertEquals('?a=b&b%5B%5D=2&b%5B%5D=3', $this->_uri->getQuery());
    }

    public function testGetFragment() {
        $this->assertEquals('myfragment', $this->_uri->getFragment());
    }

    public function testWithScheme() {
        $uri = $this->_uri->withScheme('https');
        $this->assertEquals('https', $uri->getScheme());
    }

    public function testWithSchemeHavingDelimiter() {
        $uri = $this->_uri->withScheme('https://');
        $this->assertEquals('https', $uri->getScheme());
    }

    public function testWithInvalidScheme() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid scheme provided!');
        $uri = $this->_uri->withScheme('htttp://');
    }

    public function testWithUserInfo() {
        $uri = $this->_uri->withUserInfo('user', 'password');
        $this->assertEquals('user:password', $uri->getUserInfo());
    }

    public function testWithHost() {
        $uri = $this->_uri->withHost('example2.com');
        $this->assertEquals('example2.com', $uri->getHost());
    }

    public function testWithInvalidHost() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid hostname provided!');
        $uri = $this->_uri->withHost('áésdff:');
    }

    public function testWithPort() {
        $uri = $this->_uri->withPort(96);
        $this->assertEquals(96, $uri->getPort());
    }

    public function testWithInvalidPort() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid port provided!');
        $uri = $this->_uri->withPort(-665);
    }

    public function testWithPath() {
        $uri = $this->_uri->withPath('/folder/subfolder/index.html');
        $this->assertEquals('/folder/subfolder/index.html', $uri->getPath());
    }

    public function testWithPathWithoutLeadingSlash() {
        $uri = $this->_uri->withPath('folder/subfolder/index.html');
        $this->assertEquals('/folder/subfolder/index.html', $uri->getPath());
    }

    public function testWithInvalidPath() {
        $this->setExpectedException('InvalidArgumentException', 'Invalid path provided!');
        $uri = $this->_uri->withPath(array('invalid'));
    }

    public function testWithQuery() {
        $uri = $this->_uri->withQuery('param1=1&param2=2');
        $this->assertEquals('?param1=1&param2=2', $uri->getQuery());
    }

    public function testWithQueryWithLeadingQuestionMark() {
        $uri = $this->_uri->withQuery('?param1=1&param2=2');
        $this->assertEquals('?param1=1&param2=2', $uri->getQuery());
    }
    
    public function testWithFragment() {
        $uri = $this->_uri->withFragment('fragment');
        $this->assertEquals('fragment', $uri->getFragment());
    }    
    
    public function testWithFragmentWithLeadingHashmark() {
        $uri = $this->_uri->withFragment('#fragment');
        $this->assertEquals('fragment', $uri->getFragment());
    }    
    
    public function testToString() {
        $this->assertEquals('http://usr:pss@example.com:81/mypath/myfile.html?a=b&b%5B%5D=2&b%5B%5D=3#myfragment', (string)$this->_uri);
    }      

}
