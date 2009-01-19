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
		$body = curl_exec($ch);
		
		$response = new HttpResponse();
		$response->setBody($body);
		$response->setStatus(curl_getinfo($ch, CURLINFO_HTTP_CODE));
		
		$response->addHeader('Content-Type',
			curl_getinfo($ch, CURLINFO_CONTENT_TYPE)
		);
		$response->addHeader('Content-Length',
			curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD)
		);
				
		curl_close($ch);

		return $response;
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