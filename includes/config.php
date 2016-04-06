<?php

### Configuration
#################

## Add salt to the one-way hash verification key for additional security
Configure::set( 'client_statements.salt', "Z6YtP@qPtx8fD34G!-iKXs$5J4Tz8Y#w97nhKbCd" );

## Path to logo
Configure::set( 'client_statements.logo', __DIR__ . DS . "images" . DS . "logo_vector_2.jpg" );

## Company/organistion information

# Name
Configure::set( 'client_statements.org_name', 'Acme Limited' );

# Address
Configure::set( 'client_statements.org_addr_1', "123 Chestnut Drive" );
Configure::set( 'client_statements.$org_addr_2', "Santry, Dublin" );
Configure::set( 'client_statements.$org_addr_3', "Ireland" );

# Contact information 1
Configure::set( 'client_statements.org_contact_1', 'Phone: +353 12 34 5648' );

# Tax ID
Configure::set( 'client_statements.org_tax_id', "VAT ID: 12345989M" );

# Default currency
Configure::set( 'client_statements.default_currency', "EUR" );

# Valid currencies
Configure::set( 'client_statements.valid_currencies', array(
	'EUR',
	'USD',
	'GBP',
) );

## Invoice footer text
Configure::set( 'client_statements.footer_text', "Please note this is not an official invoice. Errors and omissions excepted. Please refer to your invoice for transaction details and tax information. Please contact us with any questions." );

## Force download of PDF boolean true/false
Configure::set( 'client_statements.inline', false );

#### End configuration
######################