<?php

$url = "http://localhost/";

$contents = @file_get_contents($url);

$error = error_get_last();
print_r($error);

if (strpos($error['message'], $url)!==false) {
	//echo "file get contents returned an error.\n";
	if (preg_match('/HTTP\/(\d\.\d) (\d*) (.*)$/', $error['message'], $matches)) {
		echo 'HTTP/', $matches[1], ' ', $matches[2], ' ', $matches[3], "\n";
	}
}


?>