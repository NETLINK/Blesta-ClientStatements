<?php

class ClientStatements {
	
	public function __construct() {
		
	}
	
	public function getClient() {
		return $this->Session->read( "blesta_client_id" );
	}
}