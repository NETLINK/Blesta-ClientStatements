<?php

### Configuration
#################

## Add salt to the one-way hash verification key for additional security
Configure::set( 'client_statements.salt', "UIUq_8iBcFLOOKBWN_$LOD8VNvGhm$Ht1kV7k4KS" );

## Path to logo
Configure::set( 'client_statements.logo', __DIR__ . DS . "images" . DS . "logo_vector_2.jpg" );

## Company/organistion information

# Tax ID
Configure::set( 'client_statements.org_tax_id', "VAT ID: 12345989M" );

## Invoice footer text
Configure::set( 'client_statements.footer_text', "Please note this is not an official invoice. Errors and omissions excepted. Please refer to your invoice for transaction details and tax information. Please contact us with any questions." );

#### End configuration
######################