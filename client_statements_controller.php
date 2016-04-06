<?php

class ClientStatementsController extends AppController {
	
	public function preAction() {
		
		$this->structure->setDefaultView( APPDIR );
		
		parent::preAction();
		
		Configure::load( "config", __DIR__ . DS . "includes" . DS );
		
		$this->uses( array( "ClientStatements.Data" ) );
		
		// Override default view directory
		$this->view->view = "default";
		//$this->structure->view = "default";
	}
	
}