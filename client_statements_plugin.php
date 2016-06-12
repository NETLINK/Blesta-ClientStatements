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
			
			/*
			array(
				'action' => "nav_secondary_staff", // The action to tie into
				'uri' => "plugin/client_statements/admin_main/index/", // The URI to fetch when the action is needed
				'name' => "Client Statements", // The name used by Blesta to display for the action (e.g. The name of the link),
				'options' => array(
					'parent' => "billing/",
				),
			),
			*/
			
			array(
				'action' => 'action_staff_client',
				'uri' => "plugin/client_statements/admin/index/",
				'name' => 'Send Account Statement',
				'options' => array(
					'class' => 'statements',
				),
			),
			
			array(
				'action' => "nav_primary_client", // The action to tie into
				'uri' => "plugin/client_statements/client/index/", // The URI to fetch when the action is needed
				'name' => "Account Statements", // The name used by Blesta to display for the action (e.g. The name of the link),
				#'options' => array(
				#	'parent' => "billing/",
				#),
			),
		);
	}
	
	public function install( $plugin_id ) {
		
		Loader::loadModels( $this, array( "EmailGroups", "Emails" ) );
		
		$group = array(
			'action' => "ClientStatements.send_statement",
			'type' => "staff",
			'plugin_dir' => "client_statements",
			'tags' => "first_name,last_name",
		);
		
		// Add the custom group
		$group_id = $this->EmailGroups->add( $group );
		
		$email = array(
			'email_group_id' => $group_id,
			'company_id' => Configure::get( "Blesta.company_id" ),
			'lang' => "en_us",
			'from' => "no-reply@mydomain.com",
			'from_name' => "My Company",
			'subject' => "Subject of the email",
			'text' => "Hi {first_name},
			This is the text version of your email",
			'html' => "<p>Hi {first_name},</p>
			<p>This is the HTML version of your email</p>"
		);
		
		// Add an email to the group
		$this->Emails->add( $email );
		
	}
	
	public function uninstall( $plugin_id, $last_instance ) {
		
		Loader::loadModels( $this, array( "EmailGroups", "Emails" ) );
		
		// Fetch the email template created by this plugin
		$group = $this->EmailGroups->getByAction( "ClientStatements.send_statement" );
		
		// Delete all emails templates belonging to this plugin's email group and company
		if ( $group ) {
			$this->Emails->deleteAll( $group->id, Configure::get( "Blesta.company_id" ) );
		}
		
		if ( $last_instance ) {
			
			try {
				// Remove the email template created by this plugin
				if ( $group ) {
					$this->EmailGroups->delete( $group->id );
				}
			}
			catch ( Exception $e ) {
				// Error dropping... no permission?
				$this->Input->setErrors(
					array(
						'db' => array(
							'create' => $e->getMessage()
						)
					)
				);
				return;
			}
		}
	}
	
}