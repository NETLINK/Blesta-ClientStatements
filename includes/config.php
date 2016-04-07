<?php

### Configuration
#################

## Add salt to the one-way hash verification key for additional security
Configure::set( 'client_statements.salt', "Z6YtP@qPtx8fD34G!-iKXs$5J4Tz8Y#w97nhKbCd" );

## Path to logo
Configure::set( 'client_statements.logo', __DIR__ . DS . "images" . DS . "logo_vector_2.jpg" );

## Company/organistion information

# Tax ID
Configure::set( 'client_statements.org_tax_id', "VAT ID: 12345989M" );

## Invoice footer text
Configure::set( 'client_statements.footer_text', "Please note this is not an official invoice. Errors and omissions excepted. Please refer to your invoice for transaction details and tax information. Please contact us with any questions." );

#### End configuration
######################