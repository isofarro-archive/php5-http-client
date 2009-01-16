<?php

require_once 'HttpRequest.php';
require_once 'HttpClient.php';

$http = new HttpClient();
$request = new HttpRequest();


print_r($http);
print_r($request);
?>