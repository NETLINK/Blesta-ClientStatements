<?php

class Client extends ClientStatementsController {
	
	private $user_id;
	
	public function preAction() {
		
        parent::preAction();
		
		$this->user_id = $this->Session->read( "blesta_client_id" );
		
	}

	public function index() {
		
		// Set some variables to the view
		$this->set( "var1", "value1" );
		// Set variables all at once
		$var2 = "hello";
		$var3 = "world";
		$this->set( compact( "var2", "var3" ) );
		// Automatically renders the view in /plugins/my_plugin/views/default/client_main.pdt
	}
	
	public function download() {
		
		$this->view->setView( "client", "default" );
		
		if ( isset( $this->get[0] ) && $this->get[0] === "inline" ) {
			$inline = true;
		}
		else {
			$inline = true;
		}
		
		$res = $this->Data->download( $this->user_id, 'EUR', $inline );
		
		$this->set( "debug", $res );
	}
	
}