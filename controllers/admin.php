<?php

class Admin extends ClientStatementsController {
	
	public function index() {
		
		$this->uses( array( "Clients", "Contacts", "Emails", "Logs" ) );
		
		Language::loadLang( "admin_clients" );
		
		# Get client ID
		if ( !isset( $this->get[0] ) || !( $client = $this->Clients->get( (int)$this->get[0] ) ) ) {
			$this->redirect( $this->base_uri . "clients/" );
		}
		
		# Get email template
		$email = $this->Emails->getByType( Configure::get( "Blesta.company_id" ), "ClientStatements.send_statement" );
		
		# Default variables
		$vars = (object)array(
			"email_field" => "email_other",
			"from_name" => $email->from_name,
			"from" => $email->from,
			"subject" => $email->subject,
			"html" => $email->html,
		);
		
		/*
		
			if ( $email && $email->to_address == $client->id ) {
				# Set vars of email to resend
				$vars = (object)array(
					'email_field' => "email_other",
					//'recipient_other' => $email->to_address,
					'from_name' => $email->from_name,
					'from' => $email->from_address,
					'subject' => $email->subject,
					'message' => $email->body_text,
					'html' => $email->body_html,
				);
			}
		//}
		*/
		
		# Send the email
		if ( !empty( $this->post ) ) {
			if ( isset( $this->post['email_field'] ) && $this->post['email_field'] == "email_selected" ) {
				$this->post['to'] = $this->Html->ifSet( $this->post['recipient'] );
			}
			else {
				$this->post['to'] = $this->Html->ifSet( $this->post['recipient_other'] );
			}
			
			# Attempt to send the email
			$this->Emails->send( 'ClientStatements.send_statement', Configure::get( "Blesta.company_id" ), NULL, $to_addresses, $tags );
			
			if ( ( $errors = $this->Emails->errors() ) ) {
				$this->setMessage( "error", $errors );
			}
			else {
				$this->flashMessage( "message", Language::_( "AdminClients.!success.email_sent", true ) );
				$this->redirect( $this->base_uri . "clients/view/" . $client->id . "/" );
			}
			$vars = (object)$this->post;
		}
		
		# Default to use staff email as from address
		if ( !isset( $vars ) ) {
			
			$vars = new stdClass();
			
			$this->uses( array( "Staff" ) );
			$staff = $this->Staff->get( $this->Session->read( "blesta_staff_id" ) );
			
			if ( $staff ) {
				$vars->from_name = $this->Html->concat( " ", $staff->first_name, $staff->last_name );
				$vars->from = $staff->email;
			}
		}
		
		$this->set( "contacts", $this->Form->collapseObjectArray( $this->Contacts->getList( $client->id ), array( "first_name", "last_name", "email" ), "email", " " ) );
		$this->set( "vars", $vars );
		
		# Include WYSIWYG
		$this->Javascript->setFile( "ckeditor/ckeditor.js", "head", VENDORWEBDIR );
		$this->Javascript->setFile( "ckeditor/adapters/jquery.js", "head", VENDORWEBDIR );
		
		$this->view->set( $this->view->fetch( "admin_clients_email" ) );
	}
	
}