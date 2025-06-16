<?php
/**
* NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE
* 
* El uso de este software está sujeto a las Condiciones de uso de software que
* se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
* obtener una copia en la siguiente url:
* http://www.redsys.es/wps/portal/redsys/publica/areadeserviciosweb/descargaDeDocumentacionYEjecutables
* 
* Redsys es titular de todos los derechos de propiedad intelectual e industrial
* del software.
* 
* Quedan expresamente prohibidas la reproducción, la distribución y la
* comunicación pública, incluida su modalidad de puesta a disposición con fines
* distintos a los descritos en las Condiciones de uso.
* 
* Redsys se reserva la posibilidad de ejercer las acciones legales que le
* correspondan para hacer valer sus derechos frente a cualquier infracción de
* los derechos de propiedad intelectual y/o industrial.
* 
* Redsys Servicios de Procesamiento, S.L., CIF B85955367
*/

/////////GLOBALES PARA LOG

$logLevel = 0;

$logDISABLED = 0;
$logINFOR = 1;
$logDEBUG = 2;
 
///////////////////// FUNCIONES DE VALIDACION
//Firma
function checkFirma($firma_local, $firma_remota) {
	if ($firma_local == $firma_remota)
		return 1;
	else
		return 0;
}
//Importe

function checkImporte($total) {
	return preg_match("/^\d+$/", $total);
}
 
//Pedido
function checkPedidoNum($pedido) {
	return preg_match("/^\d{1,12}$/", $pedido);
}
function checkPedidoAlfaNum($pedido, $pedidoExtendido = false) {
	if ($pedidoExtendido)
		return preg_match("/^\w{4,256}$/", $pedido);
	else
		return preg_match("/^\w{4,12}$/", $pedido);
}

//Fuc
function checkFuc($codigo) {
	$retVal = preg_match("/^\d{2,9}$/", $codigo);
	if($retVal) {
		$codigo = str_pad($codigo,9,"0",STR_PAD_LEFT);
		$fuc = intval($codigo);
		$check = substr($codigo, -1);
		$fucTemp = substr($codigo, 0, -1);
		$acumulador = 0;
		$tempo = 0;
		
		for ($i = strlen($fucTemp)-1; $i >= 0; $i-=2) {
			$temp = intval(substr($fucTemp, $i, 1)) * 2;
			$acumulador += intval($temp/10) + ($temp%10);
			if($i > 0) {
				$acumulador += intval(substr($fucTemp,$i-1,1));
			}
		}
		$ultimaCifra = $acumulador % 10;
		$resultado = 0;
		if($ultimaCifra != 0) {
			$resultado = 10 - $ultimaCifra;
		}
		$retVal = $resultado == $check;
	}
	return $retVal;
}

//Moneda
function checkMoneda($moneda) {
   return preg_match("/^\d{1,3}$/", $moneda);
}

//Respuesta
function checkRespuesta($respuesta) {
   return preg_match("/^\d{1,4}$/", $respuesta);
}

//Firma
function checkFirmaComposicion($firma) {
   return preg_match("/^[a-zA-Z0-9\/+]{32}$/", $firma);
}

//AutCode
function checkAutCode($id_trans) {
	return preg_match("/^.{0,6}$/", $id_trans);
}

//Nombre del Comecio
function checkNombreComecio($nombre) {
	return preg_match("/^\w*$/", $nombre);
}

//Terminal
function checkTerminal($terminal) {
	return preg_match("/^\d{1,3}$/", $terminal);
}


function getVersionClave() {
	return "HMAC_SHA256_V1";
}

///////////////////// FUNCIONES DE LOG

function make_seed() {

list($usec, $sec) = explode(' ', microtime());
return (float) $sec + ((float) $usec * 100000);

}

function generateIdLog($logLevel, $logString, $idCart = NULL, $force = false) {
	
	($idCart == NULL) ? srand(make_seed()) : srand(intval($idCart));

	$stringLength = strlen ( $logString );
	$idLog = '';

	for($i = 0; $i < 30; $i ++) {

		$idLog .= $logString [rand ( 0, $stringLength - 1 )];
	}
	
	$GLOBALS["logLevel"] = (int)$logLevel;
	return $idLog;

}

function escribirLog($tipo, $idLog, $texto, $logLevel = NULL, $method = NULL) {

	$log = wc_get_logger();
	$context = array( 'source' => 'REDSYS' );
	
	$logfilename = dirname(__FILE__).'/../logs/redsysLog.log';
	$level = $logLevel ?: $GLOBALS["logLevel"];
   
	(is_null($method)) ? ($methodLog = "") : ($methodLog = $method . " -- ");

	$logEntry = $idLog . ' -- ' . $methodLog . $texto;
   
	switch ($level) {
		case 0:
		
			if ($tipo == "ERROR") {
				$log->error($logEntry, $context);
				file_put_contents($logfilename, date('M d Y G:i:s') . ' -- [' . $tipo . ']' . ' -- ' . $logEntry . "\r\n", is_file($logfilename)?FILE_APPEND:0);
			}
			
			break;
   
		case 1:
			
			$logEntry = $idLog . ' -- ' . $methodLog . $texto;

			if ($tipo == "ERROR") {
				$log->error($logEntry, $context);
				file_put_contents($logfilename, date('M d Y G:i:s') . ' -- [' . $tipo . ']' . ' -- ' . $logEntry . "\r\n", is_file($logfilename)?FILE_APPEND:0);
			
			} else if ($tipo == "INFO ") {
				$log->info($logEntry, $context);
				file_put_contents($logfilename, date('M d Y G:i:s') . ' -- [' . $tipo . ']' . ' -- ' . $logEntry . "\r\n", is_file($logfilename)?FILE_APPEND:0);
			}
			
			break;
	
		case 2:
		
			if ($tipo == "ERROR") {

				$log->error($logEntry, $context);
				file_put_contents($logfilename, date('M d Y G:i:s') . ' -- [' . $tipo . ']' . ' -- ' . $logEntry . "\r\n", is_file($logfilename)?FILE_APPEND:0);
			
			} else if ($tipo == "INFO ") {

				$log->info($logEntry, $context);
				file_put_contents($logfilename, date('M d Y G:i:s') . ' -- [' . $tipo . ']' . ' -- ' . $logEntry . "\r\n", is_file($logfilename)?FILE_APPEND:0);
			
			} else if ($tipo == "DEBUG") {

				$log->debug($logEntry, $context);
				file_put_contents($logfilename, date('M d Y G:i:s') . ' -- [' . $tipo . ']' . ' -- ' . $logEntry . "\r\n", is_file($logfilename)?FILE_APPEND:0);
			}
			

			break;
	
		default:
			# Nothing to do here...
	break;
	}
}

/** FUNCIONES COMUNES */

function generaNumeroPedido($idCart, $tipo, $pedidoExtendido = false) {

	switch (intval($tipo)) {
		case 0 : // Hibrido
			$out = str_pad ( $idCart . "z" . time()%1000, 12, "0", STR_PAD_LEFT );
			$outExtended = str_pad ( $idCart . "z" . time()%1000, 4, "0", STR_PAD_LEFT );

			break;
		case 1 : // idCart de la Tienda
			$out = str_pad ( intval($idCart), 12, "0", STR_PAD_LEFT );
			$outExtended = str_pad ( intval($idCart), 4, "0", STR_PAD_LEFT );

			break;
		case 2: // Aleatorio
			mt_srand(time(), MT_RAND_MT19937);
			
			$out = mt_rand (100000000000, 999999999999);
			$outExtended = mt_rand (1000, PHP_INT_MAX);

			break;
	}

	$out = (strlen($out) <= 12) ? $out : substr($out, -12);
	return ($pedidoExtendido) ? $outExtended : $out;
}
   
function createMerchantData($moduleComent, $idCart) {
   
	$data = (object) [
		'moduleComent' => $moduleComent,
		'idCart' => $idCart
	];
	
	return json_encode($data);	
}

function createMerchantTitular($nombre, $apellidos, $email) {
		
	$nombreCompleto  = $nombre . " " . $apellidos;
	$nombreAbreviado = mb_substr($nombre, 0, 1) . ". " . $apellidos;
	
	if (empty($email))
		return $nombreCompleto;

	$nombreEmail = $nombreAbreviado . " | " . $email;

	if (strlen($nombreEmail) > 70)
		return $email;
	else
		return $nombreEmail;

}

/** MONEDA */

function currency_code($currency) {
	$currency_codes = array(
		'ALL' => 8,
		'DZD' => 12,
		'AOK' => 24,
		'MON' => 30,
		'AZM' => 31,
		'ARS' => 32,
		'AUD' => 36,
		'BSD' => 44,
		'BHD' => 48,
		'BDT' => 50,
		'AMD' => 51,
		'BBD' => 52,
		'BMD' => 60,
		'BTN' => 64,
		'BOP' => 68,
		'BAD' => 70,
		'BWP' => 72,
		'BRC' => 76,
		'BZD' => 84,
		'SBD' => 90,
		'BND' => 96,
		'BGL' => 100,
		'BUK' => 104,
		'BIF' => 108,
		'BYB' => 112,
		'KHR' => 116,
		'CAD' => 124,
		'CAD' => 124,
		'CVE' => 132,
		'LKR' => 144,
		'CLP' => 152,
		'CLP' => 152,
		'CNY' => 156,
		'CNH' => 157,
		'COP' => 170,
		'COP' => 170,
		'KMF' => 174,
		'ZRZ' => 180,
		'CRC' => 188,
		'CRC' => 188,
		'CUP' => 192,
		'CYP' => 196,
		'CSK' => 200,
		'CZK' => 203,
		'DKK' => 208,
		'DOP' => 214,
		'ECS' => 218,
		'SVC' => 222,
		'GQE' => 226,
		'ETB' => 230,
		'ERN' => 232,
		'FKP' => 238,
		'FJD' => 242,
		'DJF' => 262,
		'GEL' => 268,
		'GMD' => 270,
		'DDM' => 278,
		'GHC' => 288,
		'GIP' => 292,
		'GTQ' => 320,
		'GNS' => 324,
		'GYD' => 328,
		'HTG' => 332,
		'HNL' => 340,
		'HKD' => 344,
		'HUF' => 348,
		'ISK' => 352,
		'INR' => 356,
		'ISK' => 356,
		'IDR' => 360,
		'IRR' => 364,
		'IRA' => 365,
		'IQD' => 368,
		'ILS' => 376,
		'JMD' => 388,
		'JPY' => 392,
		'JPY' => 392,
		'KZT' => 398,
		'JOD' => 400,
		'KES' => 404,
		'KPW' => 408,
		'KRW' => 410,
		'KWD' => 414,
		'KGS' => 417,
		'LAK' => 418,
		'LBP' => 422,
		'LSM' => 426,
		'LVL' => 428,
		'LRD' => 430,
		'LYD' => 434,
		'LTL' => 440,
		'MOP' => 446,
		'MGF' => 450,
		'MWK' => 454,
		'MYR' => 458,
		'MVR' => 462,
		'MLF' => 466,
		'MTL' => 470,
		'MRO' => 478,
		'MUR' => 480,
		'MXP' => 484,
		'MXN' => 484,
		'MNT' => 496,
		'MDL' => 498,
		'MAD' => 504,
		'MZM' => 508,
		'OMR' => 512,
		'NAD' => 516,
		'NPR' => 524,
		'ANG' => 532,
		'AWG' => 533,
		'NTZ' => 536,
		'VUV' => 548,
		'NZD' => 554,
		'NIC' => 558,
		'NGN' => 566,
		'NOK' => 578,
		'PCI' => 582,
		'PKR' => 586,
		'PAB' => 590,
		'PGK' => 598,
		'PYG' => 600,
		'PEI' => 604,
		'PEI' => 604,
		'PHP' => 608,
		'PLZ' => 616,
		'TPE' => 626,
		'QAR' => 634,
		'ROL' => 642,
		'RUB' => 643,
		'RWF' => 646,
		'SHP' => 654,
		'STD' => 678,
		'SAR' => 682,
		'SCR' => 690,
		'SLL' => 694,
		'SGD' => 702,
		'SKK' => 703,
		'VND' => 704,
		'SIT' => 705,
		'SOS' => 706,
		'ZAR' => 710,
		'ZWD' => 716,
		'YDD' => 720,
		'SSP' => 728,
		'SDP' => 736,
		'SDA' => 737,
		'SRG' => 740,
		'SZL' => 748,
		'SEK' => 752,
		'CHF' => 756,
		'CHF' => 756,
		'SYP' => 760,
		'TJR' => 762,
		'THB' => 764,
		'TOP' => 776,
		'TTD' => 780,
		'AED' => 784,
		'TND' => 788,
		'TRL' => 792,
		'PTL' => 793,
		'TMM' => 795,
		'UGS' => 800,
		'UAK' => 804,
		'MKD' => 807,
		'RUR' => 810,
		'EGP' => 818,
		'GBP' => 826,
		'TZS' => 834,
		'USD' => 840,
		'UYP' => 858,
		'UYP' => 858,
		'UZS' => 860,
		'VEB' => 862,
		'WST' => 882,
		'YER' => 886,
		'YUD' => 890,
		'YUG' => 891,
		'ZMK' => 892,
		'TWD' => 901,
		'TMT' => 934,
		'GHS' => 936,
		'RSD' => 941,
		'MZN' => 943,
		'AZN' => 944,
		'RON' => 946,
		'TRY' => 949,
		'TRY' => 949,
		'XAF' => 950,
		'XCD' => 951,
		'XOF' => 952,
		'XPF' => 953,
		'XEU' => 954,
		'ZMW' => 967,
		'SRD' => 968,
		'MGA' => 969,
		'AFN' => 971,
		'TJS' => 972,
		'AOA' => 973,
		'BYR' => 974,
		'BGN' => 975,
		'CDF' => 976,
		'BAM' => 977,
		'EUR' => 978,
		'UAH' => 980,
		'GEL' => 981,
		'PLN' => 985,
		'BRL' => 986,
		'BRL' => 986,
		'ZAL' => 991,
		'EEK' => 2333,
	);

	return array_key_exists($currency, $currency_codes) ? $currency_codes[$currency] : 0;
}

/** ENCODES ADICIONALES */

function b64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function b64url_decode($data) {
	return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}