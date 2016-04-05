<?php

## Require config file
require_once __DIR__ . "/includes/config.php";

## Get request variables

# User / client ID
$uid = isset( $_REQUEST['uid'] ) ? (int)$_REQUEST['uid'] : NULL;
# Currency
$cur = isset( $_REQUEST['cur'] ) ? $_REQUEST['cur'] : $default_currency;
# Access Key
$key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : NULL;

## Deny access if uid and key are not passed to script
if ( empty( $uid ) || empty( $key ) ) {
	die( 'Error: no user ID or access key supplied. The link you entered may have expired.' );
}

if ( ! in_array( $cur, $valid_currencies ) ) {
	die( 'Error: invalid currency selected. Supported currencies are ' . implode( ', ', $valid_currencies ) . '.' );
}

$mysqli = new mysqli( $dbhost, $dbuser, $dbpass, $dbname );

if ( $mysqli->connect_errno ) {
	echo "Failed to connect to MySQL: ( " . $mysqli->connect_errno . " ) " . $mysqli->connect_error;
	exit;
}

## Get client information from Blesta DB
$result = $mysqli->query(
	"SELECT *, CONCAT(
		first_name, ' ', last_name, ' (', IFNULL( company, 'n/a' ), ')'
	) AS client
	FROM contacts
	WHERE client_id = '$uid' AND contact_type = 'primary'
	LIMIT 1"
);

if ( $result->num_rows !== 1 ) {
	die( 'Error: we could not access any account data with the information that was provided.' );
}

$d = $result->fetch_assoc();
$result->free_result();

$md5 = md5( $d['id'] . $d['client'] . $d['email'] . $salt );

if ( $key !== $md5 ) {
	print_r( $md5 . "<br />" );
	die( 'Error: the access key provided could not be validated. The link you followed may have expired.' );
}

$date = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ), date( 'Y' ) -1 ) );

$result = $mysqli->query(
	"SELECT SUM( total ) AS total
	FROM invoices
	WHERE client_id = '$uid' AND
	DATE_FORMAT(`date_billed`, '%Y-%m-%d') < '$date'
	AND `currency` = '$cur'
	AND status != 'void'
	AND status != 'draft'"
);

$balforward = $result->fetch_array()[0];
$result->free_result();

$result = $mysqli->query(
	"SELECT SUM( amount )
	FROM transactions
	WHERE client_id = '$uid'
	AND DATE_FORMAT( `date_added`, '%Y-%m-%d' ) < '$date'
	AND `currency` = '$cur'
	AND status = 'approved'"
);

if ( ! $result ) printf( "Error: %s\n", $mysqli->error );

$balforward -= $result->fetch_array()[0];
$result->free_result();

/*
$result = $mysqli->query( "SELECT SUM(amount) FROM tblcredit WHERE clientid='$uid' AND DATE_FORMAT(`date`, '%Y-%m-%d') < '$date'" );
$balforward += $result->fetch_array()[0];
$result->free_result();
*/

$result = $mysqli->query( "
	(
				SELECT id,
				total AS `amount`,
				DATE_FORMAT( `date_billed`, '%Y-%m-%d' ) AS `date`,
				DATE_FORMAT( `date_billed`, '%y-%m-%d %H%i%s' ) AS `datetime`,
				CONCAT( SUBSTRING_INDEX( `id_format`, '{num}', 1 ), `id_value` ) AS `txid`,
				'Debit' AS txtype
					FROM `invoices`
					WHERE `client_id` = '$uid'
					AND `currency` = '$cur'
					AND `date_billed` >= '$date'
					AND `status` != 'void'
					AND `status` != 'draft'
	)
	UNION (
				SELECT id,
				amount AS `amount`,
				DATE_FORMAT( `date_added`, '%Y-%m-%d' ) AS `date`,
				DATE_FORMAT( `date_added`, '%y-%m-%d %H%i%s' ) AS `datetime`,
				`transaction_id` AS `txid`,
				'Credit' AS txtype
					FROM `transactions`
					WHERE client_id = '$uid'
					AND `currency` = '$cur'
					AND `date_added` >= '$date'
					AND `status` = 'approved'
			)
				
	#UNION (
	#			SELECT id, amount, DATE_FORMAT( `date`, '%Y-%m-%d' ) AS `date`, 'Credit' AS txtype
	#				FROM tblcredit
	#				WHERE clientid = '$uid'
	#				AND `date` >= '$date'
	#		)
			
	ORDER BY `datetime` ASC
" );

$i = array();

$n = 0;

$balance = $balforward;

if ( ! $result ) printf( "Error: %s\n", $mysqli->error );

while( $r = $result->fetch_assoc() ) {
	$i[$n] = $r;
	//if ( $r['txtype'] === 'Invoice' || $r['txtype'] === 'Credit' )
	if ( $r['txtype'] === 'Debit' ) {
		$balance += $r['amount'];
	}
	else {
		$balance -= $r['amount'];
	}
	$i[$n]['balance'] = number_format( $balance, 2, '.', ',' );
	$n++;
}

$result->free_result();
$mysqli->close();

//define( 'FPDF_FONTPATH', __DIR__ . '/../includes/font/' );
//require( '../includes/fpdf.php' );
require( __DIR__ . '/includes/fpdf181/fpdf.php' );

$pdf = new FPDF();

## Column titles
$header = array( 'Date', 'Type', 'ID', 'Amount', 'Balance' );

$pdf->AddPage();

$pdf->Image( $logo, 0, 0 );

$pdf->SetFont( 'Arial', 'B', 10 );
$pdf->Cell( 120, 6, '', 0, 0, 'L' );
$pdf->Cell( 0, 6, $org_name, 0, 0, 'L' );
$pdf->Ln();

$pdf->SetFont( 'Arial', '', 10 );

$pdf->Cell( 120, 6, '', 0, 0, 'L' );
$pdf->Cell( 0, 6, $org_addr_1, 0, 0, 'L' );
$pdf->Ln();
$pdf->Cell( 120, 6, '', 0, 0, 'L' );
$pdf->Cell( 0, 6, $org_addr_2, 0, 0, 'L' );
$pdf->Ln();
$pdf->Cell( 120, 6, '', 0, 0, 'L' );
$pdf->Cell( 0, 6, $org_addr_3, 0, 0, 'L' );
$pdf->Ln( 8 );
$pdf->Cell( 120, 6, '', 0, 0, 'L' );
$pdf->Cell( 0, 6, $org_contact_1, 0, 0, 'L' );
$pdf->Ln();
$pdf->Cell( 120, 6, '', 0, 0, 'L' );
$pdf->Cell( 0, 6, $org_tax_id, 0, 0, 'L' );
$pdf->Ln( 18 );

$pdf->SetFont( 'Arial', '', 14 );
$pdf->Cell( 80, 6, 'Account Statement', 0, 0, 'L' );
$pdf->Cell( 80, 6, gmdate( 'Y-m-d' ), 0, 0, 'R' );
$pdf->Ln(10);
$pdf->SetFont( 'Arial', 'B', 10 );
$pdf->Cell( 0, 6, $d['client'] );
$pdf->Ln();
$pdf->SetFont( 'Arial', '', 10 );
$pdf->Cell( 0, 6, $d['address1'] . ', '. $d['city'] . ', ' . $d['state'] );
$pdf->Ln( 20 );

$data = $i;

## Colors, line width and font
$default_font_size = $font_size = 9.5;
$pdf->SetFillColor( 90, 135, 190 );
$pdf->SetTextColor( 255 );
$pdf->SetDrawColor( 128, 0, 0 );
$pdf->SetLineWidth( .3 );
$pdf->SetFont( '', 'B', $font_size );
## Header
$w = array( 30, 40, 30, 40, 40 );
for ( $i = 0; $i < count( $header ); $i++ )
$pdf->Cell( $w[$i], 7, $header[$i], 1, 0, 'C', true );
$pdf->Ln();
## Color and font restoration
$pdf->SetFillColor( 224, 235, 255 );
$pdf->SetTextColor( 0 );
$pdf->SetFont( '' );
## Data
$fill = false;

$pdf->Cell( 30, 6, $date, 0, 0, 'L', $fill );
$pdf->Cell( 40, 6, 'Balance Forward', 0, 0, 'C', $fill );
$pdf->Cell( 30, 6, '-', 0, 0, 'L', $fill );
$pdf->Cell( 40, 6, number_format( $balforward, 2, '.', ',' ) . " {$cur}", 0, 0, 'R', $fill );
$pdf->Cell( 40, 6, number_format( $balforward, 2, '.', ',' ) . " {$cur}", 0, 0, 'R', $fill );
$pdf->Ln();


foreach( $data as $row ) {
	
	$pdf->Cell( 30, 6, $row['date'], 0, 0, 'L', $fill );
	$pdf->Cell( 40, 6, $row['txtype'], 0, 0, 'C', $fill );
	
	$decrement_step = 0.1;
	
	## Check if we have enough space for our transaction ID and decrease font size if necessary
	while ( $pdf->GetStringWidth( $row['txid'] ) > 30 ) {
		$pdf->SetFontSize( $font_size -= $decrement_step );
	}
	
	$pdf->Cell( 30, 6, $row['txid'], 0, 0, 'L', $fill );
	
	## Set font size back to default
	$pdf->SetFontSize( $default_font_size );
	
	if ( $row['txtype'] === 'Payment' || $row['txtype'] === 'Credit' ) {
		$pdf->Cell( 40, 6, '-' . number_format( $row['amount'], 2, '.', ',' ) . " {$cur}", 0, 0, 'R', $fill );
	}
	else {
		$pdf->Cell( 40, 6, number_format( $row['amount'], 2, '.', ',' ) . " {$cur}", 0, 0, 'R', $fill );
	}
	$pdf->Cell( 40, 6, $row['balance'] . " {$cur}", 0, 0, 'R', $fill );
	$pdf->Ln();
	$fill =! $fill;
}

$pdf->Cell( array_sum( $w ), 0, '', 'T' );

$pdf->Ln( 20 );

$pdf->Cell( 80, 6, 'Your current account balance is:', 0, 0, 'L' );
$pdf->SetFont( 'Arial', 'B', 13 );
$pdf->Cell( 80, 6, $balance . " {$cur}", 0, 0, 'L' );

$pdf->Ln( 8 );

$pdf->SetFont( 'Arial', '', 8 );

$pdf->MultiCell( 150, 6, $footer_text );

$pdf->Ln(5);
$pdf->Ln(1);

## Add HTTP headers
header( "Cache-Control: no-cache, must-revalidate" ); // No caching
header( "Expires: 0" ); // Expires header
header( "Pragma: public" );
header( "Content-Description: File Transfer" ); // Content description
header( "Content-type: application/pdf" ); // Content type PDF
header( "Content-Disposition: " . ( $force_download ? "'attachment'" : "'inline'" ) . "; filename='Statement_" . gmdate( 'Y_m_d' ) . ".pdf'" ); // Filename and attachment|inline
header( "Content-Transfer-Encoding: binary" ); // Encoding

## Send output
$pdf->Output( 'Statement_' .gmdate( 'Y_m_d' ) . '.pdf', ( $force_download ? 'D' : 'I' ) );