<?php

class HttpRequest {
	protected $method;
	protected $path;	
	protected $version = 'HTTP/1.1';

	protected $headers;
	protected $body;

	protected $url;
	protected $rawUrl;
	
	public function __construct($url=NULL) {
		$this->headers = array();
		
		if (!is_null($url)) {
			$this->setUrl($url);
		}
	}
	
	public function setUrl($url) {
		$this->rawUrl = $url;
		$this->url    = $this->segmentUrl($url);
		$this->setPath($this->url['path']);
	}
	
	public function getUrl() {
		return $this->rawUrl;
	}
	
	
	public function setPath($path) {
		// TODO: Check that it starts with a /
		$this->path = $path;
	}

	public function setMethod($method) {
		$method = strtoupper($method);
		if ($this->isValidMethod($method)) {
			$this->method = $method;
		}
	}
	
	public function getMethod() {
		return $this->method;
	}
	
	public function setVersion($version) {
		$version = $this->normaliseVersion($version);
		if (!is_null($version)) {
			$this->version = 'HTTP/' . $version;
		}
	}

	public function getHeaders() {
		return $this->headers;
	}
	
	public function getHeader($name) {
		if (!empty($this->headers[$name])) {
			return $this->headers[$name];
		}
		return NULL;
	}
	
	public function addHeader($name, $value) {
		$name = $this->normaliseHeader($name);
		$this->headers[$name] = $value;
	}
	
	public function setHeaders($headers) {
		foreach($headers as $key=>$value) {
			$key = $this->normaliseHeader($key);
			$this->headers[$key] = $value;
		}
	}
		
	public function getBody() {
		return $this->body;
	}	
	
	public function setBody($body) {
		if (is_array($body)) {
			$tmp = array();
			foreach($body as $name=>$val) {
				$tmp[] = $name . '=' . $val;
			}
			$this->body = implode('&', $tmp);
		} else {
			$this->body = $body;
		}
	}


	protected function normaliseHeader($header) {
		$name = str_replace('-', ' ', $header);
		$name = ucwords(strtolower($name));
		$name = str_replace(' ', '-', $name);
		return $name;
	}

	protected function segmentUrl($url) {
		$segments = array();
		if (preg_match('/^(\w+):\/\/([^\/]+)(.+)$/', $url, $matches)) {
			$segments['protocol'] = $matches[1];
			$segments['path']     = $matches[3];
			
			$domain = $matches[2];
			// TODO: Check for username/passwords in the URL
			if (preg_match('/^([^:]+):?(\d*)/', $domain, $matches)) {
				$segments['domain'] = $matches[1];
				if (!empty($matches[2])) {
					$segments['port'] = $matches[2];
				}
			}
		}
		return $segments;
	}	

	protected function normaliseVersion($version) {
		if ($version=='1.1' || $version=1.1) {
			return '1.1';
		} elseif ($version=='1.0' || $version==1.0 || $version==1) {
			return '1.0';
		}
		return NULL;
	}

	protected function isValidMethod($method) {
		$isValid = false;
		
		switch($method) {
			case 'GET':
			case 'POST':
			case 'PUT':			
			case 'DELETE':
				$isValid = true;
				break;
			default:
				$isValid = false;
				break;
		
		}
		return $isValid;
	}
}

?>