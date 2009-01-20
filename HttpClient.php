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
					echo "INFO: file_get_contents allows URLs\n";
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
			$body     = @file_get_contents($request->getUrl());
			
			$response = new HttpResponse();
			if (!empty($body)) {
				$response->setStatus('200');
				$response->setStatusMsg('Ok');
				$response->setBody($body);
			} else {
				// TODO: See if there's a readable error from file_get_contents
				// Set a general network error.
				$response->setStatus('500');
				$response->setStatusMsg(
					'file_get_contents unable to retrieve URL'
				);
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
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_URL, $request->getUrl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Output the headers too
		curl_setopt($ch, CURLOPT_HEADER, true);
		
		// TODO: Need to parse out the headers first!
		$httpOutput = curl_exec($ch);
		list ($headers, $body) = $this->parseOutput($httpOutput);
		
		$response = new HttpResponse();

		if ($headers[0]) {
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

/********		
		$response->addHeader('Content-Type',
			curl_getinfo($ch, CURLINFO_CONTENT_TYPE)
		);
		$response->addHeader('Content-Length',
			curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD)
		);
********/
				
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