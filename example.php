<?php

require_once 'HttpRequest.php';
require_once 'HttpClient.php';

$http    = new HttpClient();

$request = new HttpRequest();
$request->setMethod('GET');
$request->setUrl('http://uk.news.yahoo.com/');

//print_r($http);
print_r($request);
?>