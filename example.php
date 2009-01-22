<?php
	
require_once 'HttpRequest.php';
require_once 'HttpResponse.php';
require_once 'HttpClient.php';

$http    = new HttpClient();

$request = new HttpRequest();
$request->setMethod('GET');
//$request->setUrl('http://delicious.com:80/isofarro/accessibility/');
$request->setUrl('http://localhost/');

$response = $http->doRequest($request);

//print_r($http);
//print_r($request);
//print_r($response);

echo "Status: ", $response->getStatus(), ' ', $response->getStatusMsg(), "\n";

// Display the title of the page
if (preg_match('/<title>([^<]+)<\/title>/', $response->getBody(), $matches)) {
	echo "Title: {$matches[1]}\n";
}

?>