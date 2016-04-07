<?php

class Admin extends ClientStatementsController {
	
	public function index() {
		
		$this->uses( array( "Emails", "EmailGroups" ) );
		
		$group = array(
			'action' => "MyPlugin.custom_action",
			'type' => "staff",
			'plugin_dir' => "client_statements",
			'tags' => "first_name,last_name",
		);
		
		// Add the custom group
		$group_id = $this->EmailGroups->add( $group );
		
		$email = array(
			'email_group_id' => $group_id,
			'company_id' => $this->company_id,
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
}