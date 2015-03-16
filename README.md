# Http

[![Build Status](https://travis-ci.org/kemist80/http.svg)](https://travis-ci.org/kemist80/http)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kemist80/http/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kemist80/http/?branch=master)
[![Coverage Status](https://img.shields.io/coveralls/kemist80/http.svg)](https://coveralls.io/r/kemist80/http?branch=master)
[![License](https://poser.pugx.org/kemist/http/license.svg)](https://packagist.org/packages/kemist/http) 


HTTP library compliant with proposed [PSR-7](https://github.com/php-fig/fig-standards/blob/master/proposed/http-message-meta.md) message implementation.






## Installation

Via composer:

```json
{
    "require": {
        "kemist/http": "dev-master"
    }
}
```

## HTTP Client
This package contains a cURL- and a socket-based HTTP client class. 
Basic example:
```php
<?php
$request=new Kemist\Http\Request('http://httpbin.org/get?sd=43','GET',array('accept'=>'text/html','connection'=>'close'));

// cURL client
$client=new Kemist\Http\Client\CurlClient();

// OR

// Socket-based client
$client=new Kemist\Http\Client\SocketClient();

$response=$client->send($request);

var_dump($response->getHeaders());
var_dump($response->getBody()->getContents());
```
Both client types have the following options:
```php
<?php

// Client options and their default values
$options=array(
        'follow_redirections' => true,
        'max_redirections' => 10,
        'use_cookies' => true,
        'dechunk_content' => true,
        'decode_content' => true,
        'connection_timeout' => 30,
        'timeout' => 60,
    );
// Options are set through the client constructor
$client=new Kemist\Http\Client\SocketClient($options);
```

## HTTP Server
When using as HTTP server, you can handle incoming request to the server through middleware objects or functions.

...
