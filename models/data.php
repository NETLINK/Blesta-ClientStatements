<?php

class Data extends ClientStatementsModel {
	
	private $uid;
	
	public function __construct() {
		parent::__construct();
	}
	
	public function download( $uid, $currency, $inline = false, $months = 6 ) {
		
		$client = $this->getClient( $uid );
		
		if ( ! $client )
			return false;
		
		$this->uid = $uid;
		
		$this->currency = $currency;
		
		$this->date = date( "Y-m-d", strtotime( "-{$months} months" ) );
		
		$this->balforward = $this->getBalanceForward( $uid );
		
		$data = $this->getTransactions( $uid, $this->balforward );
		
		return $this->createPDF( $client, $data, $inline );
		
	}
	
	private function getClient( $uid ) {
		
		$result = $this->Record->query(
			"SELECT *, CONCAT(
				first_name, ' ', last_name, ' (', IFNULL( company, 'n/a' ), ')'
			) AS client
			FROM contacts
			WHERE client_id = '$uid' AND contact_type = 'primary'
			LIMIT 1"
		)->fetch();
		
		return $result;
		
	}
	
	private function getBalanceForward( $uid ) {
		
		$uid = $this->uid;
		
		$result = $this->Record->query(
			"SELECT SUM( total ) AS total
			FROM invoices
			WHERE client_id = '$uid'
			AND DATE_FORMAT( `date_billed`, '%Y-%m-%d' ) < '{$this->date}'
			AND `date_billed` <= CURDATE()
			AND `currency` = '{$this->currency}'
			AND status != 'void'
			AND status != 'draft'"
		)->fetch();
		
		$balforward = (float)$result->total;
		
		$result = $this->Record->query(
			"SELECT SUM( amount ) AS total
			FROM transactions
			WHERE client_id = '$uid'
			AND DATE_FORMAT( `date_added`, '%Y-%m-%d' ) < '{$this->date}'
			AND `currency` = '{$this->currency}'
			AND status = 'approved'"
		)->fetch();
		
		$balforward -= (float)$result->total;
		
		return $balforward;
		
	}
	
	private function getTransactions( $uid, $balance = 0 ) {
		
		$data = $this->Record->query(
			"
			(
				SELECT id,
				total AS `amount`,
				DATE_FORMAT( `date_billed`, '%Y-%m-%d' ) AS `date`,
				DATE_FORMAT( `date_billed`, '%y-%m-%d %H%i%s' ) AS `datetime`,
				CONCAT( SUBSTRING_INDEX( `id_format`, '{num}', 1 ), `id_value` ) AS `txid`,
				'Debit' AS txtype
					FROM `invoices`
					WHERE `client_id` = '$uid'
					AND `currency` = '{$this->currency}'
					AND `date_billed` >= '{$this->date}'
					AND `date_billed` <= CURDATE()
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
					AND `currency` = '{$this->currency}'
					AND `date_added` >= '{$this->date}'
					AND `status` = 'approved'
			)
			
			ORDER BY `datetime` ASC
			"
		)->fetchAll( PDO::FETCH_ASSOC );
		
		$i = array();
		$n = 0;
		
		$this->balance = $balance;
		
		foreach ( $data as $r ) {
			$i[$n] = $r;
			//if ( $r['txtype'] === 'Invoice' || $r['txtype'] === 'Credit' )
			if ( $r['txtype'] === 'Debit' ) {
				$this->balance += $r['amount'];
			}
			else {
				$this->balance -= $r['amount'];
			}
			$i[$n]['balance'] = number_format( $this->balance, 2, '.', ',' );
			$n++;
		}
		
		return $i;
		
	}
	
	private function createPDF( $client, $data, $inline = false ) {
		
		require_once PLUGINDIR . DS . "client_statements" . DS . "includes". DS . "fpdf181" . DS . "fpdf.php";
		
		$pdf = new FPDF();
		
		$company = Configure::get( "Blesta.company" );
		
		## Column titles
		$header = array( 'Date', 'Type', 'ID', 'Amount', 'Balance' );
		
		$pdf->AddPage();
		
		$pdf->Image( Configure::get( 'client_statements.logo' ), 0, 0 );
		
		$pdf->SetFont( 'Arial', 'B', 10 );
		
		$pdf->Cell( 120, 8, '', 0, 0, 'L' );
		$pdf->Cell( 0, 8, $company->name, 0, 0, 'L' );
		$pdf->Ln();
		
		$pdf->SetFont( 'Arial', '', 10 );
		
		$address_lines = explode( PHP_EOL, $company->address );
		
		foreach ( $address_lines as $address_line ) {
			$pdf->Cell( 120, 0, '', 0, 0, 'L' );
			$pdf->Cell( 0, 4, $address_line, 0, 0, 'L' );
			$pdf->Ln();
		}
		
		$pdf->Cell( 120, 6, '', 0, 0, 'L' );
		$pdf->Cell( 0, 7, "Phone: " . $company->phone, 0, 0, 'L' );
		$pdf->Ln();
		$pdf->Cell( 120, 6, '', 0, 0, 'L' );
		$pdf->Cell( 0, 6, Configure::get( 'client_statements.org_tax_id' ), 0, 0, 'L' );
		$pdf->Ln( 18 );
		
		$pdf->SetFont( 'Arial', '', 14 );
		$pdf->Cell( 80, 6, 'Account Statement', 0, 0, 'L' );
		$pdf->Cell( 80, 6, gmdate( 'Y-m-d' ), 0, 0, 'R' );
		$pdf->Ln(10);
		$pdf->SetFont( 'Arial', 'B', 10 );
		$pdf->Cell( 0, 6, $client->client );
		$pdf->Ln();
		$pdf->SetFont( 'Arial', '', 10 );
		$pdf->Cell( 0, 6, $client->address1 . ', '. $client->city . ', ' . $client->state );
		$pdf->Ln( 20 );
		
		## Colors, line width and font
		$default_font_size = $font_size = 9.5;
		$pdf->SetFillColor( 90, 135, 190 );
		$pdf->SetTextColor( 255 );
		$pdf->SetDrawColor( 128, 0, 0 );
		$pdf->SetLineWidth( .3 );
		$pdf->SetFont( '', 'B', $font_size );
		## Header
		$w = array( 30, 40, 30, 40, 40 );
		for ( $i = 0; $i < count( $header ); $i++ ) {
			$pdf->Cell( $w[$i], 7, $header[$i], 1, 0, 'C', true );
		}
		$pdf->Ln();
		## Color and font restoration
		$pdf->SetFillColor( 224, 235, 255 );
		$pdf->SetTextColor( 0 );
		$pdf->SetFont( '' );
		## Data
		$fill = true;
		
		$pdf->Cell( 30, 6, $this->date, 0, 0, 'L', $fill );
		$pdf->Cell( 40, 6, 'Balance Forward', 0, 0, 'C', $fill );
		$pdf->Cell( 30, 6, '-', 0, 0, 'L', $fill );
		$pdf->Cell( 40, 6, number_format( $this->balforward, 2, '.', ',' ) . " {$this->currency}", 0, 0, 'R', $fill );
		$pdf->Cell( 40, 6, number_format( $this->balforward, 2, '.', ',' ) . " {$this->currency}", 0, 0, 'R', $fill );
		$pdf->Ln();
		
		$fill = false;
		
		foreach( $data as $row ) {
			
			//var_dump( $row );
			
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
				$pdf->Cell( 40, 6, '-' . number_format( $row['amount'], 2, '.', ',' ) . " {$this->currency}", 0, 0, 'R', $fill );
			}
			else {
				$pdf->Cell( 40, 6, number_format( $row['amount'], 2, '.', ',' ) . " {$this->currency}", 0, 0, 'R', $fill );
			}
			
			$pdf->Cell( 40, 6, $row['balance'] . " {$this->currency}", 0, 0, 'R', $fill );
			
			$pdf->Ln();
			
			$fill =! $fill;
		}
		
		$pdf->Cell( array_sum( $w ), 0, '', 'T' );
		
		$pdf->Ln( 20 );
		
		$pdf->Cell( 80, 6, 'Your current account balance is:', 0, 0, 'L' );
		$pdf->SetFont( 'Arial', 'B', 13 );
		$pdf->Cell( 80, 6, $this->balance . " {$this->currency}", 0, 0, 'L' );
		
		$pdf->Ln( 8 );
		
		$pdf->SetFont( 'Arial', '', 8 );
		
		$pdf->MultiCell( 150, 6, Configure::get( "client_statements.footer_text" ) );
		
		$pdf->Ln(5);
		$pdf->Ln(1);
		
		## Add HTTP headers
		header( "Cache-Control: no-cache, must-revalidate" ); // No caching
		header( "Expires: 0" ); // Expires header
		header( "Pragma: public" );
		header( "Content-Description: File Transfer" ); // Content description
		header( "Content-type: application/pdf" ); // Content type PDF
		header( "Content-Disposition: " . ( $inline ? "'inline'" : "'attachment'" ) . "; filename='Statement_" . gmdate( 'Y_m_d' ) . ".pdf'" ); // Filename and attachment|inline
		header( "Content-Transfer-Encoding: binary" ); // Encoding
		
		## Send output
		$pdf->Output( 'Statement_' .gmdate( 'Y_m_d' ) . '.pdf', ( $inline ? 'I' : 'D' ) );
		
		return;
		
	}
}