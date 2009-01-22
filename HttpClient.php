<?php

interface HttpClientMechanism {
	public function can($feature);
	public function doRequest($request);
}

class FileContentsHttpClient implements HttpClientMechanism {
	public function can($feature) {
		$hasFeature = true;
		switch($feature) {
			case 'GET':
				// TODO: need to check that file_get_contents
				// can accept URLs.
				if (ini_get('allow_url_fopen')) {
					//echo "INFO: file_get_contents allows URLs\n";
					$hasFeature = true;
				}
				$hasFeature = false;
				break;
			default:
				$hasFeature = false;
				break;
		}
		return $feature;
	}
	
	public function doRequest($request) {
		$response = NULL;		
		if ($request->getMethod()=='GET') {
			$url      = $request->getUrl();
			$body     = @file_get_contents($url);

			// Check whether file_get_contents threw an error
			$error = error_get_last();

			if (strpos($error['message'], $url)!==false) {
				$msg = $error['message'];
				
				//echo "file get contents returned an error.\n";
				$httpPattern = '/HTTP\/(\d\.\d) (\d*) (.*)$/';
				if (preg_match($httpPattern, $msg, $matches)) {
					//echo 'HTTP/', $matches[1], ' ', $matches[2], ' ', $matches[3], "\n";

					$response = new HttpResponse();
					$response->setStatus($matches[2]);
					$response->setStatusMsg(trim($matches[3]));
					$response->setVersion($matches[1]);
				} elseif (preg_match('/stream: (.*)$/', $msg, $matches)) {
					$response = new HttpResponse();
					$response->setStatus(502);
					$response->setStatusMsg($matches[1]);
				} else {
					echo "WARN: No valid HTTP reply from file_get_contents:\n",
						$msg, "\n";
				}
			} else {
				// Valid response
				$response = new HttpResponse();
				if (!empty($body)) {
					$response->setStatus('200');
					$response->setStatusMsg('Ok');
					$response->setBody($body);
				}
			}
		}
		return $response;
	}	
	
}



class CurlHttpClient implements HttpClientMechanism {
	public function can($feature) {
		$hasFeature = true;
		switch($feature) {
			case 'GET':
				if (function_exists('curl_init')) {
					$hasFeature = true;
				}
				$hasFeature = false;
				break;
			default:
				$hasFeature = false;
				break;
		}
		return $feature;
	}
	
	public function doRequest($request) {
		$response = NULL;
		switch($request->getMethod()) {
			case 'GET':
				$response = $this->doGet($request);
				break;
			default:
				break;
		}
		return $response;
	}	
	
	public function doGet($request) {
		$response = NULL;

		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_URL, $request->getUrl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Output the headers too
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		$httpOutput = curl_exec($ch);
		
		if ($httpOutput) {
			// Split the response into a header and body
			list ($headers, $body) = $this->parseOutput($httpOutput);
			$response = new HttpResponse();

			if (!empty($headers) && $headers[0]) {
				// Parse the HTTP Status header
				//echo "STATUS LINE:", $headers[0], "\n";
				if (preg_match('/^(HTTP\/\d\.\d) (\d*) (.*)$/', $headers[0], $matches)) {
					$response->setStatus($matches[2]);
					$response->setStatusMsg($matches[3]);
					$response->setVersion($matches[1]);
				}
				unset($headers[0]);
			} else {
				$response->setStatus(curl_getinfo($ch, CURLINFO_HTTP_CODE));	
			}

			$response->setBody($body);
			$response->setHeaders($headers);		

		}
						
		curl_close($ch);

		return $response;
	}
	
	protected function parseOutput($output) {
		$lines = explode("\n", $output);
		$isHeader = true;
		
		$headers = array();
		$buffer  = array();

		foreach($lines as $line) {
			if ($isHeader) {
				if (preg_match('/^\s*$/', $line)) {
					// Header/body separator
					$isHeader = false;
				} else {
					// This is a real HTTP header
					if (preg_match('/^([^:]+)\:(.*)$/', $line, $matches)) {
						//echo "HEADER: [", $matches[1], ']: [', $matches[2], "]\n";
						$headers[trim($matches[1])] = trim($matches[2]);
					} else {
						//echo "HEADER: ", trim($line), "\n";
						$headers[0] = trim($line);
					}					
				}
			} else {
				$buffer[] = $line;
			}
		}		
		$body = implode("\n", $buffer);
		return array($headers, $body);
	}
}




class HttpClient {
	protected $request;
	protected $response;

	// The actual transfer client
	protected $client;


	public function __construct() {
		$this->initHttpMechanism();
	}
	
	public function get($url) {
		$request = new HttpRequest();
		$request->setMethod('GET');
		$request->setUrl($url);	
		$this->doRequest($request);
	}
	
	public function post($url, $body) {
		$request = new HttpRequest();
		$request->setMethod('POST');
		$request->setUrl($url);
		$request->setBody($body);
		$this->doRequest($request);
	}
	
	public function put($url, $body) {
		$request = new HttpRequest();
		$request->setMethod('PUT');
		$request->setUrl($url);
		$request->setBody($body);
		$this->doRequest($request);
	}
	
	public function delete($url) {
		$request = new HttpRequest();
		$request->setMethod('DELETE');
		$request->setUrl($url);	
		$this->doRequest($request);
	}
	
	public function head($url) {
		$request = new HttpRequest();
		$request->setMethod('HEAD');
		$request->setUrl($url);	
		$this->doRequest($request);
	}
	
	public function doRequest($request) {
		$this->response = NULL;
		if ($this->isClientCapable($request)) {
			$this->response = $this->client->doRequest($request);		
		}
		return $this->response;
	}
	
	
	protected function isClientCapable($request) {
		$isCapable = $this->client->can($request->getMethod());
		return $isCapable;	
	}
	
	protected function initHttpMechanism() {
		// Find the best available HTTP mechanism
		//$this->client = new FileContentsHttpClient();
		$this->client = new CurlHttpClient();
	}

}


?>