<?php

include_once ('wc-redsys.php');

class WC_Redsys_Ref {

	public static function saveReference($idCustomer, $reference, $cardNumber, $brand, $cardType, $idLog = '0000', $idCart = null){
		
		if(intval($idCustomer) == 0) {
			escribirLog("DEBUG", $idLog, "No se ha guardado la referencia porque el cliente no está registrado");
			return;
		}

		global $wpdb;
		$tableName=$wpdb->prefix."redsys_reference";
		escribirLog("DEBUG", $idLog, "Iniciando proceso de guardado de referencia.");
			
		$supportedBrands=array(1,2,8,9,22);
		if(!in_array($brand, $supportedBrands))
			$brand=null;
	
		if($reference!=null && strlen($reference)>0 && WC_Redsys_Ref::checkRefTable()){
			$oldRef=WC_Redsys_Ref::getCustomerRef($idCustomer, $idLog);
			$maskedCard=WC_Redsys_Ref::maskCardNumber($cardNumber);
			if($oldRef==null){
				$wpdb->insert(
					$tableName,
					array(
						'id_customer' => $idCustomer,
						'reference' => $reference,
						'version' => MODULE_VERSION,
						'cardNumber' => $maskedCard,
						'brand' => $brand,
						'cardType' => $cardType
					)
				);
				escribirLog("DEBUG", $idLog, "Referencia guardada [idCustomer|Reference|cardNumber]: [" . $idCustomer . "|" . $reference . "|" . $cardNumber . "]");
			}
			else{
				$wpdb->update(
					$tableName,
					array(
						'reference' => $reference,
						'version' => MODULE_VERSION,
						'cardNumber' => $maskedCard,
						'brand' => $brand,
						'cardType' => $cardType
					),
					array( 
						'id_customer' => $idCustomer 
					)
				);
				escribirLog("DEBUG", $idLog, "Referencia actualizada [idCustomer|Reference|cardNumber]: [" . $idCustomer . "|" . $reference . "|" . $cardNumber . "]");
			}
		}
	}
	
	public static function getCustomerRef($idCustomer, $idLog = null){
		if(intval($idCustomer) == 0) {
			escribirLog("DEBUG", $idLog, "No se ha servido la referencia porque el cliente no está registrado");
			return;
		}

		if(WC_Redsys_Ref::checkRefTable()){
			global $wpdb;
			$tableName=$wpdb->prefix."redsys_reference";
			
			$ref=$wpdb->get_results( "SELECT * FROM ".$tableName." WHERE id_customer=".$idCustomer.";", ARRAY_A  );
			if(sizeof($ref)>0)
				return array($ref[0]["reference"],$ref[0]["cardNumber"],$ref[0]["brand"],$ref[0]["cardType"]);
		}
		return null;
	}
	public static function checkRefTable(){
		global $wpdb;
		$tableName=$wpdb->prefix."redsys_reference";
		
		$tablas=$wpdb->get_results( "SHOW TABLES LIKE '".$tableName."'" );
		if(sizeof($tablas)<=0)
			WC_Redsys_Ref::createRefTable();

			$tablas=$wpdb->get_results( "SHOW TABLES LIKE '".$tableName."'" );
			return sizeof($tablas)>0;
	}
	public static function createRefTable(){
		global $wpdb;

		$tableName=$wpdb->prefix."redsys_reference";
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS `".$tableName."` (
				`id_customer` INT NOT NULL PRIMARY KEY,
				`version` VARCHAR(10) NOT NULL,
				`reference` VARCHAR(128) NOT NULL,
				`cardNumber` VARCHAR(24),
				`brand` SMALLINT,
				`cardType` VARCHAR(1),
				INDEX (`id_customer`)
			) $charset_collate;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	public static function dropRefTable(){
		global $wpdb;
		
		$tableName=$wpdb->prefix."redsys_reference";
		
		$sql = "'DROP TABLE `".$tableName."`'";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	private static function maskCardNumber($cardNumber) {
		if ($cardNumber == null || strlen($cardNumber) <= 4) 
			return $cardNumber;
		return str_pad(substr($cardNumber, -4, 4), 5, "*", STR_PAD_LEFT);
	}

}