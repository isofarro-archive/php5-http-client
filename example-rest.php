<?php

/**
*	Testing REST methods work by communicating with api.php
*  api.php should be uploaded to a webserver, and the
*  $url below updated with the URL of that script.
**/

require_once 'HttpRequest.php';
require_once 'HttpResponse.php';
require_once 'HttpClient.php';

$url = 'http://localhost/api.php';
$http = new HttpClient();

// GET request
echo "GET request\n";
$request = new HttpRequest();
$request->setUrl($url . '?name1=a&name2=b');
$request->setMethod('GET');
$response = $http->doRequest($request);
echo $response->getStatus(), ': ', $response->getBody(), "\n";
echo "\n";

// POST REQUEST
echo "POST request\n";
$body = "This is a new note to myself.";
$request = new HttpRequest();
$request->setUrl($url);
$request->setMethod('POST');
$request->setBody($body);
$request->addHeader('Content-Type', 'text/plain');
$response = $http->doRequest($request);
//echo "Response: "; print_r($response);
echo $response->getStatus(), ': ', $response->getBody(), "\n";
echo "\n";

// PUT REQUEST
echo "PUT request\n";
$body = "An update to my original post.";
$request = new HttpRequest();
$request->setUrl($url);
$request->setMethod('PUT');
$request->setBody($body);
$request->addHeader('Content-Type', 'text/plain');
$response = $http->doRequest($request);
//echo "Response: "; print_r($response);
echo $response->getStatus(), ': ', $response->getBody(), "\n";
echo "\n";

// DELETE REQUEST
echo "DELETE request\n";
$request = new HttpRequest();
$request->setUrl($url);
$request->setMethod('DELETE');
$response = $http->doRequest($request);
echo $response->getStatus(), ': ', $response->getBody(), "\n";
echo "\n";



?>