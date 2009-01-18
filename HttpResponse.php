<?php

class HttpResponse {
	protected $status;
	protected $statusMsg;
	protected $headers;
	protected $body;

	public function __construct() {
	
	}
	
	public function setStatus($status) {
		$this->status = $status;
	}

	public function setStatusMsg($statusMsg) {
		$this->statusMsg = $statusMsg;
	}

	public function setBody($body) {
		$this->body = $body;
	}

}

?>