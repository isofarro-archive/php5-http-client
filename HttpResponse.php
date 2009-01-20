<?php

class HttpResponse {
	protected $version;
	protected $status;
	protected $statusMsg;
	protected $headers;
	protected $body;

	public function __construct() {
	
	}
	
	public function setVersion($version) {
		$this->version = $version;
	}
	
	public function setStatus($status) {
		$this->status = $status;
	}
	
	public function getStatus() {
		return $this->status;
	}

	public function setStatusMsg($statusMsg) {
		$this->statusMsg = $statusMsg;
	}
	
	public function getStatusMsg() {
		return $this->statusMsg;
	}

	public function setBody($body) {
		$this->body = $body;
	}
	
	public function getBody() {
		return $this->body;
	}

	public function setHeaders($headers) {
		foreach($headers as $name=>$value) {
			$this->headers[$name] = $value;
		}
	}

	public function addHeader($header, $value) {
		if (empty($this->headers)) {
			$this->headers = array();
		}
		$this->headers[$header] = $value;
	}
}

?>