<?php
error_reporting(-1);
ini_set('display_errors', true);
define('ROOT_DIR', realpath(dirname(__FILE__)));

require_once ('vendor/autoload.php');

set_time_limit(5);

$request=new Kemist\Http\Request('http://httpbin.org/post?sd=43','POST',array('accept'=>'text/html','connection'=>'close'));
$client=new Kemist\Http\Client\SocketClient(array('timeout'=>5));
$response=$client->send($request);

var_dump(json_decode($response->getBody()));