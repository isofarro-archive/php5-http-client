<?php

require_once 'HttpRequest.php';
require_once 'HttpResponse.php';
require_once 'HttpClient.php';

$http    = new HttpClient();

$request = new HttpRequest();
$request->setMethod('GET');
$request->setUrl('http://delicious.com:80/isofarro/accessibility/');

$response = $http->doRequest($request);

//print_r($http);
print_r($request);
print_r($response);
?>