<?php

class Client extends ClientStatementsController {
	
	private $user_id;
	public $currencies;
	
	public function preAction() {
		
        parent::preAction();
		
		$this->requireLogin();
		
		## Load Invoices class
		$this->uses( array( "Invoices" ) );
		
		## Load languages
		Language::loadLang( "client_main" );
		
		## Get client ID from session
		$this->user_id = $this->Session->read( "blesta_client_id" );
		
		## Get currency information
		$this->currencies = $this->getCurrencies();
		$this->set( "currencies", $this->currencies );
		$this->set( "debug", $this->currencies );
		
	}

	public function index() {
				
		$this->checkOverdueBalance();
		
	}
	
	public function download() {
		
		$this->view->setView( "client", "default" );
		
		## Get currency and check if valid
		$currency = isset( $this->post['currency'] ) ? $this->post['currency'] : $this->currencies[0]->currency;
		if ( ! $this->isValidCurrency( $currency ) ) $currency = $this->currencies[0]->currency;
				
		if ( isset( $this->post['disposition'] ) && $this->post['disposition'] === "inline" ) {
			$output = 'I';
		}
		else if ( isset( $this->get[0] ) && $this->get[0] === "inline" ) {
			$output = 'I';
		}
		else {
			$output = 'D';
		}
		
		$months = isset( $this->post['time'] ) ? $this->post['time'] : NULL;
		
		$res = $this->Data->getStatement( $this->user_id, $currency, $output, $months );
		
		$this->set( "debug", $res );
	}
	
	private function getCurrencies() {
		
		// Get all client currencies that there may be amounts due in
		$currencies = $this->Invoices->invoicedCurrencies( $this->user_id, 'all' );
		
		return $currencies;
		
	}
	
	private function isValidCurrency( $currency ) {
		foreach ( $this->currencies as $valid_currency ) {
			if ( $currency === $valid_currency->currency ) {
				return true;
			}
		}
		return false;
	}
	
	private function checkOverdueBalance() {
		
		// Get all client currencies that there may be amounts due in
		$currencies = $this->currencies;
		
		// Set a message for all currencies that have an amount due
		$amount_due_message = NULL;
		$max_due = 0;
		$max_due_currency = NULL;
		$currencies_owed = 0;
		
		foreach ( $currencies as $currency ) {
			
			$total_due = $this->Invoices->amountDue( $this->user_id, $currency->currency );
			
			if ( $total_due > $max_due ) {
				$max_due_currency = $currency->currency;
				$max_due = $total_due;
				$amount_due_message = Language::_( "ClientMain.!info.invoice_due_text", true, $this->CurrencyFormat->format( $total_due, $currency->currency ) );
				$currencies_owed++;
			}
			
			if ( $amount_due_message ) {
				$message = array( 'amount_due' => array( $amount_due_message ) );
				if ( $currencies_owed > 1 ) {
					$message['amount_due'][] = Language::_( "ClientMain.!info.invoice_due_other_currencies", true );
				}
				
				$this->setMessage( "notice", $message, false, array(
					'notice_title' => "Outstanding Balance",
					'notice_buttons' => array(
						array(
							'class' => "btn",
							'url' => $this->Html->safe( $this->base_uri . "pay/index/" . $max_due_currency . "/" ),
							'label' => Language::_( "ClientMain.!info.invoice_due_button", true )
						)
					)
				), false );
			}
		}
	}
	
}