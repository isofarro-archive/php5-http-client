<?php

class HttpRequest {
	protected $method;
	protected $path;	
	protected $version = '1.0';

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
			$this->version = $version;
		}
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