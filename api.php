<?php

header('Content-Type: text/plain');

//echo "API endpoint\n";
$request = (object) NULL;
$headers = array(
	'HTTP_HOST', 'HTTP_USER_AGENT', 'HTTP_ACCEPT', 'HTTP_ACCEPT_LANGUAGE',
	'HTTP_ACCEPT_ENCODING', 'HTTP_ACCEPT_CHARSET', 'HTTP_KEEP_ALIVE',
	'HTTP_CONNECTION', 'HTTP_CACHE_CONTROL', 
);

$request->method   = $_SERVER['REQUEST_METHOD'];
$request->url      = $_SERVER['REQUEST_URI'];
$request->host     = $_SERVER['HTTP_HOST'];
$request->protocol = $_SERVER['SERVER_PROTOCOL'];
//$request->query    = $_SERVER['QUERY_STRING'];
$request->query    = $_GET;
//$request->headers  = processHeaders($headers);
$request->headers  = getallheaders();

switch($request->method) {
	case 'GET':
		doGet($request);
		break;
	case 'POST':
		$request->body = file_get_contents('php://input');
		doPost($request);
		break;
	case 'PUT':
		$request->body = file_get_contents('php://input');
		doPut($request);
		break;
	case 'DELETE':
		doDelete($request);
		break;
	default:
		echo $request->method, " not supported.\n";
		break;
}

function doGet($request) {
	//echo '<pre>'; print_r($request); echo '</pre>';
	echo "GET message received for ", $request->url;
}

function doPost($request) {
	//echo '<pre>'; print_r($request); echo '</pre>';
	echo "POST message received: ", $request->body;
}

function doPut($request) {
	//echo '<pre>'; print_r($request); echo '</pre>';
	echo "PUT message received: ", $request->body;
}

function doDelete($request) {
	//echo '<pre>'; print_r($request); echo '</pre>';
	echo "DELETE message received for ", $request->url;
}


function processHeaders($list) {
	$headers = array();
	foreach($list as $header) {
		if (!empty($_SERVER[$header])) {
			$name = $header;
			if (preg_match('/^HTTP_(.*)$/', $header, $matches)) {
				$name = $matches[1];
			}
			// Tidy up the HTTP header name
			$name = str_replace('_', ' ', $name);
			$name = ucwords(strtolower($name));
			$name = str_replace(' ', '-', $name);

			$headers[$name] = $_SERVER[$header];
		}
	}
	return $headers;
}
?>