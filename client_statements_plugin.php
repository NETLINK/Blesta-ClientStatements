<?php

class ClientStatementsPlugin extends Plugin {
	
	public function __construct() {
		
		$this->loadConfig( dirname( __FILE__ ) . DS . "config.json" );
		
	}
	
	public function getActions() {
		return array(
		
			/*
			array(
				'action' => "widget_client_home",
				'uri' => "plugin/client_statements/client_widget/index/",
				'name' => "Account Statement",
			),
			*/
			
			array(
				'action' => "nav_secondary_staff", // The action to tie into
				'uri' => "plugin/client_statements/admin_main/index/", // The URI to fetch when the action is needed
				'name' => "Client Statements", // The name used by Blesta to display for the action (e.g. The name of the link),
				'options' => array(
					'parent' => "billing/",
				),
			),
			
			array(
				'action' => "nav_primary_client", // The action to tie into
				'uri' => "plugin/client_statements/client/index/", // The URI to fetch when the action is needed
				'name' => "Account Statement", // The name used by Blesta to display for the action (e.g. The name of the link),
				#'options' => array(
				#	'parent' => "billing/",
				#),
			),
		);
	}
}