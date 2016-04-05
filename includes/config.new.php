<?php

### Configuration
#################

## Debugging
ini_set( 'display_errors', 1 );

## Add salt to the one-way hash verification key for additional security
$salt = "UIUq_8iBcFLOOKBWN_$LOD8VNvGhm$Ht1kV7k4KS";

## Path to logo
$logo = 'includes/logo_vector_2.jpg';

## Company/organistion information

# Name
$org_name = 'Acme Ltd.';

# Address
$org_addr_1 = "123 Chestnut Drive";
$org_addr_2 = "Santry, Dublin";
$org_addr_3 = "Ireland";

# Contact information 1
$org_contact_1 = 'Phone: +353 12 34 5648';

# Tax ID
$org_tax_id = "VAT ID: 12345989M";

# Default currency
$default_currency = "EUR";

# Valid currencies
$valid_currencies = array(
	'EUR',
	'USD',
	'GBP',
);

## Invoice footer text
$footer_text = "Please note this is not an official invoice. Errors and omissions excepted. Please refer to your invoice for transaction details and tax information. Please contact us with any questions.";

## Force download of PDF boolean true/false
$force_download = false;


## Database information
$dbhost = "localhost";
$dbuser = "[dbuser]";
$dbpass = "[dbpass]";
$dbname = "[dbname]";

#### End configuration
######################