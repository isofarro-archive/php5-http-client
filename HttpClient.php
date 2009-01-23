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
			case 'POST':
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
			case 'POST':
				$response = $this->doPost($request);
				break;
			case 'PUT':
				$response = $this->doPut($request);
				break;
			case 'DELETE':
				$response = $this->doDelete($request);
				break;
			default:
				break;
		}
		return $response;
	}	
	
	public function doGet($request) {
		$response = NULL;
		$ch = curl_init();
		
		curl_setopt_array($ch, array(
			CURLOPT_URL            => $request->getUrl(),
			CURLOPT_HTTPGET        => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true
		));
		
		// Convert to raw CURL headers and add to request
		$headers = $this->processRequestHeaders($request->getHeaders());
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);            
		}

		$httpOutput = curl_exec($ch);
		$response = $this->parseResponse($httpOutput);
								
		curl_close($ch);
		return $response;
	}

	public function doPost($request) {
		$ch = curl_init();
		
		$data = $request->getBody();

		if ($data) {
			curl_setopt_array($ch, array(
				CURLOPT_URL            => $request->getUrl(),
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => $data,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => true
			));

			// Convert to raw CURL headers and add to request
			$headers = $this->processRequestHeaders($request->getHeaders());
			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);            
			}

			$httpOutput = curl_exec($ch);
			$response = $this->parseResponse($httpOutput);
		} else {
			echo "ERROR: No body to send.\n";
		}
		
		curl_close($ch);
		return $response;
	}

	public function doPut($request) {
		$ch = curl_init();

		$data = $request->getBody();
		
		if ($data) {
			curl_setopt_array($ch, array(
				CURLOPT_URL            => $request->getUrl(),
				CURLOPT_CUSTOMREQUEST  => 'PUT',
				CURLOPT_POSTFIELDS     => $data,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => true
			));

			// Convert to raw CURL headers and add to request
			$headers = $this->processRequestHeaders($request->getHeaders());
			if (!empty($headers)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);            
			}

			$httpOutput = curl_exec($ch);
			$response = $this->parseResponse($httpOutput);
		} else {
			echo "ERROR: No body to send.\n";
		}
		
		curl_close($ch);
		return $response;
	}

	public function doDelete($request) {
		$response = NULL;
		$ch = curl_init();
		
		curl_setopt_array($ch, array(
			CURLOPT_URL            => $request->getUrl(),
			CURLOPT_CUSTOMREQUEST  => 'DELETE',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true
		));
		
		// Convert to raw CURL headers and add to request
		$headers = $this->processRequestHeaders($request->getHeaders());
		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);            
		}

		$httpOutput = curl_exec($ch);
		$response = $this->parseResponse($httpOutput);
								
		curl_close($ch);
		return $response;
	}

	/**
	*	Parses the raw HTTP response and returns a response object
	**/
	protected function parseResponse($output) {
		$response = new HttpResponse();
		
		if ($output) {
			$lines    = explode("\n", $output);
			$isHeader = true;
			$buffer   = array();
			
			foreach($lines as $line) {
				if ($isHeader) {
					if (preg_match('/^\s*$/', $line)) {
						// Header/body separator
						$isHeader = false;
					} else {
						// This is a real HTTP header
						if (preg_match('/^([^:]+)\:(.*)$/', $line, $matches)) {
							//echo "HEADER: [", $matches[1], ']: [', $matches[2], "]\n";
							$name  = trim($matches[1]);
							$value = trim($matches[2]);						
							$response->addHeader($name, $value);
						} else {
							// This is the status response
							//echo "HEADER: ", trim($line), "\n";
							if (preg_match(
										'/^(HTTP\/\d\.\d) (\d*) (.*)$/', 
										trim($line), $matches)
									) {
								$response->setStatus($matches[2]);
								$response->setStatusMsg($matches[3]);
								$response->setVersion($matches[1]);
							}
						}					
					}
				} else {
					$buffer[] = $line;
				}
			}
			// The buffer is the HTTP Entity Body
			$response->setBody(implode("\n", $buffer));
		} else {
			$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			if ($statusCode==0) {
				$response->setStatus(502);
				$response->setStatusMsg('CURL Error');
			} else {
				$response->setStatus($statusCode);
				$response->setStatusMsg('CURL Response');
			}	
		}

		return $response;
	}	

	protected function processRequestHeaders($headers) {
		$rawHeaders = array();
		if (is_array($headers)) {
			foreach ($headers as $key=>$value) {
				$rawHeaders[] = $key . ': ' . $value;
			}
		}
		return $rawHeaders;
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
			$isReady = $this->isRequestReady($request);
			if ($isReady) {
				$this->response = $this->client->doRequest($request);		
			} else {
				echo "WARN: Request isn't ready.\n";
			}
		} else {
			echo "WARN: HTTP Client isn't capable of performing this request.\n";
		}
		return $this->response;
	}
	
	
	protected function isRequestReady($request) {
		$isReady = true;
		
		if (!$request->getUrl()) {
			$isReady = false;
		}		
		switch($request->getMethod()) {
			case 'GET':
			case 'DELETE':
				break;
			case 'POST':
			case 'PUT':
				if (!$request->getBody()) {
					$isReady = false;
				}
				break;
			default:
				$isReady = false;
				break;
		}
		
		return $isReady;
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