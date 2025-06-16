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

if(!function_exists("escribirLog")) {
	require_once('apiRedsys/redsysLibrary.php');
}
if(!class_exists("RedsyspurAPI")) {
	require_once('apiRedsys/apiRedsysFinal.php');
}
if(!class_exists("WC_Redsys_Ref")) {
	require_once('wc-redsys-ref.php');
}
if(!class_exists("WC_Redsys")) {
	require_once('wc-redsys.php');
}
if(!class_exists("WC_Redsys_Refund")) {
	require_once('wc-redsys-refund.php');
}

include_once REDSYSPUR_PATH.'/ApiRedsysREST/initRedsysApi.php';

class WC_Redsys_Insite extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'redsys_insite';
        //$this->icon               = REDSYSPUR_URL . '/pages/assets/images/Redsys.png';
        $this->method_title       = __( 'inSite · Pasarela Unificada de Redsys para WooCommerce', 'woocommerce' );
        $this->method_description = __( 'Permita a sus clientes pagar con tarjeta sin salir de su web usando los servicios de Redsys.', 'woocommerce' );
        
		$this->process_url         = add_query_arg( 'wc-api', 'WC_redsys_process', home_url( '/' ) );
        $this->secure_redir_url    = add_query_arg( 'wc-api', 'WC_redsys_secure_redir', home_url( '/' ) );
		$this->secure_redir_v2_url = add_query_arg( 'wc-api', 'WC_redsys_secure_redir_v2', home_url( '/' ) );
        $this->secure_back_url     = add_query_arg( 'wc-api', 'WC_redsys_secure_back', home_url( '/' ) );
		$this->secure_back_v2_url  = add_query_arg( 'wc-api', 'WC_redsys_secure_back_v2', home_url( '/' ) );
		$this->payment_fields_url  = add_query_arg( 'wc-api', 'WC_redsys_insite_payment_fields', home_url( '/' ) );

        $this->has_fields         = false;
        $this->version			  = MODULE_VERSION;
        
        // Load the settings
        $this->init_settings();
        $this->init_form_fields();

		$this->supports           = array( 'refunds' );

        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
		$this->buttonLabel 		  = "Use el botón situado donde ha introducido sus datos de tarjeta";

        // Get settings
        $this->entorno            = $this->get_option( 'entorno' );
        $this->nombre             = $this->get_option( 'name' );
        $this->fuc                = $this->get_option( 'fuc' );
        $this->tipopago           = $this->get_option( 'tipopago' );
        $this->clave256           = $this->get_option( 'clave256' );
        $this->terminal           = $this->get_option( 'terminal' );
        $this->activar_log	  	  = $this->get_option( 'activar_log' );
        $this->estado             = $this->get_option( 'estado' );
		$this->genPedido	      = $this->get_option( 'genPedido' );
//         $this->withdcc            = $this->get_option( 'withdcc' );
        $this->with3ds            = $this->get_option( 'with3ds' );
        $this->idioma_tpv         = $this->get_option( 'idioma_tpv' );
        $this->withref            = $this->get_option( 'withref' );
        $this->tabla_ordenes      = $this->get_option( 'tabla_ordenes' );
		$this->moneda_manual      = $this->get_option( 'moneda_manual' );
		$this->decimales_moneda   = $this->get_option( 'decimales_moneda' );

        $this->button_text        = $this->get_option( 'button_text' );
        $this->button_style       = $this->get_option( 'button_style' );
        $this->body_style         = $this->get_option( 'body_style' );
        $this->form_style         = $this->get_option( 'form_style' );
        $this->form_text_style    = $this->get_option( 'form_text_style' );
        $this->sustituir_idioma   = $this->get_option( 'sustituir_idioma' );
		$this->urlOK              = $this->get_option( 'urlOK' );
		$this->urlKO              = $this->get_option( 'urlKO' );

		//moneda a usar
        $this->moneda = currency_code(get_option('woocommerce_currency'));

		if ( !empty( $this->get_option('moneda_manual') ) )
			$this->moneda = $this->get_option('moneda_manual');

		//idLog
        $this->logString          = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Actions
		add_action( 'wp_head', array( $this, 'add_redsys_insite' ) );
        add_action( 'woocommerce_receipt_redsys_insite', array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        //Payment listener/API hook
        add_action( 'woocommerce_api_wc_redsys_process', array( $this, 'process_order' ) );
        add_action( 'woocommerce_api_wc_redsys_secure_redir', array( $this, 'redirect_to_tdsecure_v1' ) );
		add_action( 'woocommerce_api_wc_redsys_secure_redir_v2', array( $this, 'redirect_to_tdsecure_v2' ) );
        add_action( 'woocommerce_api_wc_redsys_secure_back', array( $this, 'back_from_tdsecure' ) );
		add_action( 'woocommerce_api_wc_redsys_secure_back_v2', array( $this, 'back_from_tdsecure_v2' ) );
		add_action( 'woocommerce_api_wc_redsys_insite_payment_fields', array( $this, 'payment_fields_api' ) );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_update_order_meta' ) );
    }

	function checkout_fields( $fields ) {
		foreach($fields['billing'] as &$field){
			$field['class'][] = 'update_totals_on_change';
		}
		foreach($fields['shipping'] as &$field){
			$field['class'][] = 'update_totals_on_change';
		}			
		return $fields;
	}

	function checkout_update_order_meta($order_id){
		if ( ! empty( $_POST['redsysOrder'] ) ) {
			update_post_meta($order_id, 'redsysOrder', $_POST['redsysOrder']);
		}
	}

	function add_redsys_insite(){

		if (is_checkout() && $this->enabled == 'yes') {

			echo '<!-- Script de procesamiento de pago inSite de la Pasarela Unificada de Redsys. -->';
			echo('<script src="'.RESTConstants::getJSPath($this->entorno).'"></script>');
		}

	}

	public static function createEndpointParams($endpoint, $object, $idCart, $protocolVersion = null, $idLog = null) {

		$endpoint .= "&order=".$object->getOrder();
		$endpoint .= "&currency=".$object -> getCurrency();
		$endpoint .= "&amount=".$object -> getAmount();
		$endpoint .= "&merchant=".$object -> getMerchant();
		$endpoint .= "&terminal=".$object -> getTerminal();
		$endpoint .= "&transactionType=".$object -> getTransactionType();
		$endpoint .= "&idCart=".$idCart;
	   
		if (!empty($protocolVersion))
			$endpoint .= "&protocolVersion=".$protocolVersion;
		
		if (!empty($idLog))
			$endpoint .= "&idLog=".$idLog;
	   
		return $endpoint;
	}
    
    function process_order() {

		$insiteData = array(
			'idCart' => $_POST['idCart'],
			'orderId' => $_POST['orderId'],
			'orderTotal' => $_POST['orderTotal'],
			'billingFirstName' => $_POST['billingFirstName'],
			'billingLastName' => $_POST['billingLastName'],
			'email' => $_POST['email'],
			'valores3DS' => $_POST['valores3DS'],
			'operId' => $_POST['operId'],
			'useReference' => $_POST['useReference'] === "true",
			'saveReference' => $_POST['saveReference'] === "true",
		);
		
		WC()->session->set('REDSYS_insite_data', json_encode($insiteData));

		die();
    }
    
    function redirect_to_tdsecure_v1() {

    	$form='<iframe name="redsys_iframe_acs" name="redsys_iframe_acs" src=""
	    		id="redsys_iframe_acs"
	    		sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
	    		height="100%" width="100%" style="border: none; display: none;"></iframe>
	    	
    	<form name="redsysAcsForm" id="redsysAcsForm"
    		action="'.WC()->session->get( "REDSYS_urlacs" ).'" method="POST"
    		target="_parent" style="border: none;">
    		<table name="dataTable" border="0" cellpadding="0">
    			<input type="hidden" name="PaReq"
    				value="'.WC()->session->get( "REDSYS_pareq" ).'">
    			<input type="hidden" name="TermUrl"
    				value="'.WC()->session->get( "REDSYS_termURL" ).'">
    			<input type="hidden" name="MD"
    				value="'.WC()->session->get( "REDSYS_md" ).'">
    			<br>
    			<p
    				style="font-family: Arial; font-size: 16; font-weight: bold; color: black; align: center;">
    				Conectando con el emisor...</p>
    		</table>
    	</form>
	    	
    	<script>
    		window.onload = function () {
    		    document.getElementById("redsys_iframe_acs").onload = function() {
    		    	document.getElementById("redsysAcsForm").style.display="none";
    		    	document.getElementById("redsys_iframe_acs").style.display="inline";
    		    }
    			document.redsysAcsForm.submit();
    		}
    	</script>';


    	WC()->session->set('REDSYS_pareq', null);
    	WC()->session->set('REDSYS_urlacs', null);
    	WC()->session->set('REDSYS_md', null);
		WC()->session->set('REDSYS_termURL', null);

    	die($form);
    }

	function redirect_to_tdsecure_v2() {

    	$form='<iframe name="redsys_iframe_acs" name="redsys_iframe_acs" src=""
		id="redsys_iframe_acs" target="_parent" referrerpolicy="origin"
		sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
		height="100%" width="100%" style="border: none; display: none;"></iframe>

		<form name="redsysAcsForm" id="redsysAcsForm"
			action="'.WC()->session->get( "REDSYS_urlacs" ).'" method="POST"
			target="_parent" style="border: none;">
			<table name="dataTable" border="0" cellpadding="0">
				<input type="hidden" name="creq"
					value="'.WC()->session->get( "REDSYS_creq" ).'">
				<br>
				<p
					style="font-family: Arial; font-size: 16; font-weight: bold; color: black; align: center;">
					Conectando con el emisor...</p>
			</table>
		</form>
				
		<script>
			window.onload = function () {
				document.getElementById("redsys_iframe_acs").onload = function() {
					document.getElementById("redsysAcsForm").style.display="none";
					document.getElementById("redsys_iframe_acs").style.display="inline";
				}
				document.redsysAcsForm.submit();
			}
		</script>';

    	WC()->session->set('REDSYS_creq', null);
    	WC()->session->set('REDSYS_urlacs', null);
		WC()->session->set('REDSYS_idCart', null);

    	die($form);
    }
    
    function back_from_tdsecure() {

		$origIdCart = $_GET["idCart"];

		$orderIdLog = $origIdCart . $this->fuc;
    	$idLog = generateIdLog($this->activar_log, $this->logString, $orderIdLog);

		$order = new WC_Order($origIdCart);

		$request = new RESTAuthenticationRequestMessage ();

		$request->setOrder ( $_GET['order'] );
		$request->setAmount ( $_GET['amount'] );
		$request->setCurrency ( $_GET['currency'] );
		$request->setMerchant ( $_GET['merchant'] );
		$request->setTerminal ( $_GET['terminal'] );
		$request->setTransactionType ( $_GET['transactionType'] );
		$request->addEmvParameter ( RESTConstants::$RESPONSE_JSON_THREEDSINFO_ENTRY , RESTConstants::$RESPONSE_3DS_CHALLENGE_RESPONSE );
		$request->addEmvParameter ( RESTConstants::$RESPONSE_JSON_PROTOCOL_VERSION_ENTRY , RESTConstants::$RESPONSE_3DS_VERSION_1 );
		$request->addEmvParameter ( RESTConstants::$RESPONSE_JSON_PARES_ENTRY , $_POST ["PaRes"] );
		$request->addEmvParameter ( RESTConstants::$RESPONSE_JSON_MD_ENTRY , $_POST ["MD"] );
			
		$service = new RESTOperationService ( $this->clave256, $this->entorno );
		$result = $service->sendOperation ( $request, $idLog );
		
		$paymentResult = [
			'idCart' => $origIdCart,
			'order' => $_GET['order'],
			'transactionType' => $result->getOperation()->getTransactionType(),
			'result' => $result->getResult(),
			'apiCode' => $result->getApiCode(),
			'authCode' => $result->getAuthCode(),
			'merchantIdentifier' => $result->getOperation()->getMerchantIdentifier(),
			'cardNumber' => $result->getOperation()->getCardNumber(),
			'cardBrand' => $result->getOperation()->getCardBrand(),
			'cardType' => $result->getOperation()->getCardType(),
		];

		set_transient('REDSYS_payment_result_' . $origIdCart, json_encode($paymentResult), 600);
			
		escribirLog("DEBUG", $idLog, "Fijado transient con paymentResult para la orden " . $origIdCart, null, __METHOD__);
		escribirLog("DEBUG", $idLog, "Valor de paymentResult: " . json_encode($paymentResult), null, __METHOD__);

		$form = '<p style="font-family: Arial; font-size: 16; font-weight: bold; color: black; align: center;">
					Procesando operación...
				</p>
				<script>parent.location.href = "' . $order->get_checkout_payment_url(true) . '";</script>';

		die($form);
	}

	function back_from_tdsecure_v2() {

		$origIdCart=$_GET["idCart"];

		$orderIdLog = $origIdCart . $this->fuc;
    	$idLog = generateIdLog($this->activar_log, $this->logString, $orderIdLog);

		$order = new WC_Order($origIdCart);

		$request = new RESTAuthenticationRequestMessage ();

		$request->setOrder ( $_GET['order'] );
		$request->setAmount ( $_GET['amount'] );
		$request->setCurrency ( $_GET['currency'] );
		$request->setMerchant ( $_GET['merchant'] );
		$request->setTerminal ( $_GET['terminal'] );
		$request->setTransactionType ( $_GET['transactionType'] ); 
		$request->addEmvParameter ( RESTConstants::$RESPONSE_JSON_THREEDSINFO_ENTRY , RESTConstants::$RESPONSE_3DS_CHALLENGE_RESPONSE );
		$request->addEmvParameter ( RESTConstants::$RESPONSE_JSON_PROTOCOL_VERSION_ENTRY , $_GET['protocolVersion'] );
		$request->addEmvParameter ( RESTConstants::$RESPONSE_MERCHANT_EMV3DS_CRES , $_POST ["cres"] );
			
		$service = new RESTOperationService ( $this->clave256, $this->entorno );
		$result = $service->sendOperation ( $request, $idLog );

		$controlCode = generateIdLog($this->activar_log, $this->logString . $result->getOperation()->getCardNumber(), $orderIdLog);

		$paymentResult = [
			'idCart' => $origIdCart,
			'order' => $_GET['order'],
			'amount' => $_GET['amount'],
			'transactionType' => $result->getOperation()->getTransactionType(),
			'result' => $result->getResult(),
			'apiCode' => $result->getApiCode(),
			'authCode' => $result->getAuthCode(),
			'merchantIdentifier' => $result->getOperation()->getMerchantIdentifier(),
			'cardNumber' => $result->getOperation()->getCardNumber(),
			'cardBrand' => $result->getOperation()->getCardBrand(),
			'cardType' => $result->getOperation()->getCardType(),
			'controlCode' => $controlCode,
		];

		set_transient('REDSYS_payment_result_' . $origIdCart, json_encode($paymentResult), 600);

		escribirLog("DEBUG", $idLog, "Fijado transient con paymentResult para la orden " . $origIdCart, null, __METHOD__);
		escribirLog("DEBUG", $idLog, "Valor de paymentResult: " . json_encode($paymentResult), null, __METHOD__);
		escribirLog("DEBUG", $idLog, "Valor de result: " . $result, null, __METHOD__);


		$form = '<p style="font-family: Arial; font-size: 16; font-weight: bold; color: black; align: center;">
					Procesando operación...
				</p>
				<script>parent.location.href = "' . $order->get_checkout_payment_url(true) . '";</script>';

		die($form);
	}

    function process_payment( $order_id ) {

		$orderIdLog = $order_id . $this->fuc;
    	$idLog = generateIdLog($this->activar_log, $this->logString, $orderIdLog);

		$order = new WC_Order($order_id);

		$insiteData = WC()->session->get('REDSYS_insite_data');
		$insiteData = json_decode($insiteData, true);

		if(!$insiteData){
			return array(
				'result' 	=> 'failure',
			);
		}

		$idCart = $insiteData['idCart'];
		$orderId = $insiteData['orderId'];
		$orderTotal = $insiteData['orderTotal'];
		$billingFirstName = $insiteData['billingFirstName'];
		$billingLastName = $insiteData['billingLastName'];
		$email = $insiteData['email'];
		$valores3DS = $insiteData['valores3DS'];
		$operId = $insiteData['operId'];
		$useReference = $insiteData['useReference'];
		$saveReference = $insiteData['saveReference'];

		$merchantModule = 'WO-PUR v' . MODULE_VERSION;

		escribirLog("DEBUG", $idLog, "Versión del módulo: " . $merchantModule, null, __METHOD__);
        escribirLog("DEBUG", $idLog, "Versión de Wordpress: " . $GLOBALS['wp_version'], null, __METHOD__);
		escribirLog("DEBUG", $idLog, "Versión de WooCommerce: " . WC_VERSION, null, __METHOD__);
		escribirLog("DEBUG", $idLog, "Versión de PHP: " . phpversion(), null, __METHOD__);

        //Calculo del precio total del pedido
        $currency_decimals = intval($this->get_option('decimales_moneda')) /*get_option('woocommerce_price_num_decimals')*/;

        $transaction_amount = number_format( (float) ($orderTotal), intval($currency_decimals), '.', '' );
        $transaction_amount = str_replace('.','',$transaction_amount);
        $transaction_amount = floatval($transaction_amount);
        
        $productos="";
        $products = WC()->cart->cart_contents;
        foreach ($products as $product) {
            $productos .= $product['quantity'].'x'.$product['data']->get_title().'/';
        }
		
		//Peticion de datos de tarjeta. (IniciaPeticion)
		$initialRequest = new RESTInitialRequestMessage();
		$initialRequest->setAmount ( $transaction_amount );
    	$initialRequest->setCurrency ( $this->moneda );
    	$initialRequest->setMerchant ( $this->fuc  );
    	$initialRequest->setTerminal ( $this->terminal  );
    	$initialRequest->setOrder ( $idCart );
		if($useReference){
			$userId=get_current_user_id();
			$ref=WC_Redsys_Ref::getCustomerRef($userId, $idLog);

			$initialRequest->useReference($ref[0]);
		}else{
			$initialRequest->setOperID ( $operId );
		}
    	$initialRequest->setTransactionType ( $this->tipopago );
		$initialRequest->demandCardData();

		$service = new RESTInitialRequestService ( $this->clave256, $this->entorno );
		$initialResult = $service -> sendOperation($initialRequest, $idLog);

		$merchantTitular = createMerchantTitular($billingFirstName, $billingLastName, $email);

		escribirLog("DEBUG", $idLog, "initialResult: " . $initialResult, null, __METHOD__);
    	
		//Creación de objeto para la petición.
    	$request = new RESTOperationMessage ();
    	$request->setAmount ( $transaction_amount );
    	$request->setCurrency ( $this->moneda );
    	$request->setMerchant ( $this->fuc  );
    	$request->setTerminal ( $this->terminal  );
    	$request->setOrder ( $idCart );
		if($useReference){
			$request->useReference($ref[0]);
		}else{
			$request->setOperID ( $operId );
		}
    	$request->setTransactionType ( $this->tipopago );
		$request->addParameter ( "DS_MERCHANT_TITULAR", $merchantTitular );
		$request->addParameter ( "DS_MERCHANT_PRODUCTDESCRIPTION", $productos );
		$request->addParameter ( "DS_MERCHANT_MODULE", $merchantModule );
		$ip = $_SERVER['REMOTE_ADDR'] == "::1" ? "127.0.0.1" : $_SERVER['REMOTE_ADDR'];
		$request->addParameter ( "DS_MERCHANT_CLIENTIP", $ip );
		$ThreeDSParams = $valores3DS;
		$ThreeDSInfo = $initialResult->protocolVersionAnalysis();

		if ($this->with3ds) {

			$version = explode( '.', $ThreeDSInfo);			
			if ($version[0] == "1") {

				escribirLog("DEBUG", $idLog, "Versión de 3DSecure: " . $ThreeDSInfo, null, __METHOD__);
				$request -> setEMV3DSParamsV1();
	
			} else {

				escribirLog("DEBUG", $idLog, "Versión de 3DSecure: " . $ThreeDSInfo, null, __METHOD__);
				$decoded3DS = json_decode(str_replace("\\","",$ThreeDSParams));

				$browserAcceptHeader = "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8,application/json";
				$browserUserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36";
				$browserJavaEnable = $decoded3DS->browserJavaEnabled;
				$browserJavaScriptEnabled = $decoded3DS->browserJavascriptEnabled;
				$browserLanguage = $decoded3DS->browserLanguage;
				$browserColorDepth = $decoded3DS->browserColorDepth;
				$browserScreenHeight = $decoded3DS->browserScreenHeight;
				$browserScreenWidth = $decoded3DS->browserScreenWidth;
				$browserTZ = $decoded3DS->browserTZ;
				$threeDSCompInd = "N";
				$threeDSServerTransID = $initialResult -> getThreeDSServerTransID();
				$notificationURL = WC_Redsys_Insite::createEndpointParams($this->secure_back_v2_url, $request, $order_id, $ThreeDSInfo, $idLog);
				
				$request -> setEMV3DSParamsV2($ThreeDSInfo, $browserAcceptHeader, $browserUserAgent, $browserJavaEnable, $browserJavaScriptEnabled, $browserLanguage, $browserColorDepth, $browserScreenHeight, $browserScreenWidth, $browserTZ, $threeDSServerTransID, $notificationURL, $threeDSCompInd);
	
			}

		} else {

			$request->useDirectPayment ();
		}
		
		if($this->withref && !$useReference && $saveReference && is_user_logged_in()) {
			$request->createReference ();
			escribirLog("INFO ", $idLog, "Se ha recibido una petición para guardar la referencia del cliente.", null, __METHOD__);
		}

		$service = new RESTOperationService ( $this->clave256, $this->entorno );
		$result = $service->sendOperation ( $request, $idLog );

		$ThreeDSInfo = $result->protocolVersionAnalysis();

		if ($result->getResult () == RESTConstants::$RESP_LITERAL_AUT) {

			if ($ThreeDSInfo == "1.0.2") {

				$termURL = WC_Redsys_Insite::createEndpointParams($this->secure_back_url, $result->getOperation (), $order_id, null, $idLog);

				WC()->session->set('REDSYS_pareq', $result->getPAReqParameter ());
				WC()->session->set('REDSYS_urlacs', $result->getAcsURLParameter ());
				WC()->session->set('REDSYS_md', $result->getMDParameter ());
				WC()->session->set('REDSYS_termURL', $termURL);

				escribirLog("DEBUG", $idLog, "URL con parámetros: " . $termURL, null, __METHOD__);

				$redirectUrl = $this->secure_redir_url;
			} else {

				WC()->session->set('REDSYS_creq', $result->getCreqParameter ());
				WC()->session->set('REDSYS_urlacs', $result->getAcsURLParameter ());

				$redirectUrl = $this->secure_redir_v2_url;
			}
			
		}else{
			$paymentResult = [
				'idCart' => $orderId,
				'order' => $idCart,
				'transactionType' => $result->getOperation()->getTransactionType(),
				'result' => $result->getResult(),
				'apiCode' => $result->getApiCode(),
				'authCode' => $result->getAuthCode(),
				'merchantIdentifier' => $result->getOperation()->getMerchantIdentifier(),
				'cardNumber' => $result->getOperation()->getCardNumber(),
				'cardBrand' => $result->getOperation()->getCardBrand(),
				'cardType' => $result->getOperation()->getCardType(),
			];

			$redirectUrl = $order->get_checkout_payment_url(true);
	
			set_transient('REDSYS_payment_result_' . $order_id, json_encode($paymentResult), 600);
			
			escribirLog("DEBUG", $idLog, "Fijado transient con paymentResult para orderId " . $order_id, null, __METHOD__);
			escribirLog("DEBUG", $idLog, "Valor de paymentResult: " . json_encode($paymentResult), null, __METHOD__);
		}

		return array(
			'result' 	=> 'success',
			'redirect'	=> $redirectUrl
		);
    }

    function get_next_order_id(){
        global $wpdb;
        global $woocommerce;

		$statuses = array_keys(wc_get_order_statuses());
		$statuses = implode( "','", $statuses );
	
		// Getting last Order ID (max value)

		// $results = $wpdb->get_col( "
		// 	SELECT MAX(id)+1 FROM {$wpdb->prefix}wc_orders
		// " );

		switch ($this->get_option('tabla_ordenes')) {

			case 0:
				$results = $wpdb->get_col( "
					SELECT MAX(ID)+1 FROM {$wpdb->prefix}posts
					WHERE post_type LIKE 'shop_order'
					AND post_status IN ('$statuses')
				" );
				break;
				
			case 1:
				$results = $wpdb->get_col( "
					SELECT MAX(id)+1 FROM {$wpdb->prefix}wc_orders
				" );
				break;

			default:
				return;

		}

		return reset($results);
	}

	function payment_fields_api(){
		$this->payment_fields();
		exit;
	}

    function payment_fields(){
		if(isset($_POST['post_data'])){
			parse_str($_POST['post_data'], $post_data);
			$post_data = array_filter($post_data, function($v){return !is_null($v) && $v !== '';});
		}

		//Create fake order
		$order = new WC_Order();
		
		if (isset($post_data)) {
			$order_id = (is_array($post_data) && array_key_exists('order_id', $post_data)) ? $post_data['order_id'] : $this->get_next_order_id();
			$order->set_id($order_id);
			$order->set_total(WC()->cart->total);

			$order->set_billing_email($post_data['billing_email']);
			$order->set_billing_first_name($post_data['billing_first_name']);
			$order->set_billing_last_name($post_data['billing_last_name']);
			$order->set_billing_address_1($post_data['billing_address_1']);
			$order->set_billing_address_2($post_data['billing_address_2']);
			$order->set_billing_country($post_data['billing_country']);
			$order->set_billing_postcode($post_data['billing_postcode']);
			$order->set_billing_city($post_data['billing_city']);
			$order->set_billing_state($post_data['billing_state']);
			$order->set_billing_phone($post_data['billing_phone']);
		}

		$orderId = $order->get_id();
		$orderTotal = $order->get_total();
		$billingFirstName = $order->get_billing_first_name();
		$billingLastName = $order->get_billing_last_name();
		$email = $order->get_billing_email();

		$orderIdLog = $orderId . $this->fuc;
		$idLog = generateIdLog($this->activar_log, $this->logString, $orderIdLog);

    	$allowReference=$this->withref=="1" && is_user_logged_in();
    	$body_style=$this->body_style;
    	$form_style=$this->form_style;
    	$form_text_style=$this->form_text_style;
    	$btnStyle=$this->button_style;
    	$btnText=$this->button_text;

    	//Objeto tipo pedido
		if ($orderId == 0)
			$numpedido = generaNumeroPedido($orderId, 2); //Forzar aleatorio si el orderID es 0.
		else
			$numpedido = generaNumeroPedido($orderId, $this->get_option('genPedido'));

		$htmlPath = REDSYSPUR_PATH.'/pages/templates/paymentform.html';
    	$staticPath = REDSYSPUR_URL.'/pages/assets';

		$isLogged = is_user_logged_in();
		$userId = get_current_user_id();

		escribirLog("DEBUG", $idLog, "**************************");
		escribirLog("INFO ", $idLog, "****** NUEVO PEDIDO ******");
		escribirLog("DEBUG", $idLog, "**************************");

		escribirLog("INFO ", $idLog, "Pago con Tarjeta inSite", null, __METHOD__);
		escribirLog("INFO ", $idLog, "ID del usuario cargado: " . $userId, null, __METHOD__);

		if ($isLogged == true)
			escribirLog("INFO ", $idLog, "El usuario que hace el pedido está logueado en la página", null, __METHOD__);
		else
			escribirLog("INFO ", $idLog, "El usuario que hace el pedido no está logueado en la página", null, __METHOD__);	
		
		$brandImg="";
		$refTitle="";
		$ref = null;

		if($allowReference && $isLogged){
			$ref=WC_Redsys_Ref::getCustomerRef($userId, $idLog);

			if($ref!=null && $ref[2]!=null)
				$brandImg='<img src="'.REDSYSPUR_URL.'/pages/assets/images/brands/'.$ref[2].'.jpg" style="display: inline;"/>';
			
			if($ref!=null){
				$refTitle="Usar tarjeta ";
				if($ref[3]=="C")
					$refTitle="Usar tarjeta de crédito ";
				else
					$refTitle="Usar tarjeta de débito ";
				
				if($ref[1]!=null)
					$refTitle.=$ref[1];
			}
		}

		if ($this->get_option( 'idioma_tpv' )) {
			$idioma_web = substr ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'], 0, 2 );
				
			switch ($idioma_web) {
				case 'es' :
					$idioma_tpv = '001';
					break;
				case 'en' :
					$idioma_tpv = '002';
					break;
				case 'ca' :
					$idioma_tpv = '003';
					break;
				case 'fr' :
					$idioma_tpv = '004';
					break;
				case 'de' :
					$idioma_tpv = '005';
					break;
				case 'nl' :
					$idioma_tpv = '006';
					break;
				case 'it' :
					$idioma_tpv = '007';
					break;
				case 'sv' :
					$idioma_tpv = '008';
					break;
				case 'pt' :
					$idioma_tpv = '009';
					break;
				case 'pl' :
					$idioma_tpv = '011';
					break;
				case 'gl' :
					$idioma_tpv = '012';
					break;
				case 'eu' :
					$idioma_tpv = '013';
					break;
				default :
					$idioma_tpv = '002';
			}
		} else
			$idioma_tpv = '001';


		if ( $this->get_option( 'sustituir_idioma' ) ) {

			$htmlPath   = preg_replace('/(\.[a-z]{2,3})\/[a-z]{2}\//', '$1/', $htmlPath);
			$staticPath = preg_replace('/(\.[a-z]{2,3})\/[a-z]{2}\//', '$1/', $staticPath);
			$brandImg   = preg_replace('/(\.[a-z]{2,3})\/[a-z]{2}\//', '$1/', $brandImg);

			escribirLog("INFO ", $idLog, "Opción para eliminar el idioma de las URL de los assets activada", null, __METHOD__);
			escribirLog("DEBUG", $idLog, "Nueva URL   htmlPath: $htmlPath", null, __METHOD__);
			escribirLog("DEBUG", $idLog, "Nueva URL staticPath: $staticPath", null, __METHOD__);
			escribirLog("DEBUG", $idLog, "Nueva URL   brandImg: $brandImg", null, __METHOD__);
		}

		$html = file_get_contents($htmlPath);

		$html = str_replace("{merchantCode}",$this->fuc,$html);
		$html = str_replace("{merchantTerminal}",$this->terminal,$html);
    	$html = str_replace("{staticPath}",$staticPath,$html);
    	$html = str_replace("{orderId}",$orderId,$html);
		$html = str_replace("{orderTotal}",$orderTotal,$html);
		$html = str_replace("{billingFirstName}",$billingFirstName,$html);
		$html = str_replace("{billingLastName}",$billingLastName,$html);
		$html = str_replace("{email}",$email,$html);
    	$html = str_replace("{idCart}",$numpedido,$html);

    	$html = str_replace("{allowReference}",$allowReference?"true":"false",$html);
		$html = str_replace("{hasReference}", $ref?"true":"false",$html);
    	$html = str_replace("{referenceTitle}",$refTitle,$html);
    	$html = str_replace("{cardBrandLogo}",$brandImg,$html);

    	$html = str_replace("{procUrl}",$this->process_url,$html);
    	$html = str_replace("{body_style}",$body_style,$html);
    	$html = str_replace("{form_style}",$form_style,$html);
    	$html = str_replace("{form_text_style}",$form_text_style,$html);
    	$html = str_replace("{btnStyle}",$btnStyle,$html);
    	$html = str_replace("{btnText}",$btnText,$html);
    	$html = str_replace("{idioma_tpv}",$idioma_tpv,$html);

		echo($html);
    }
    
    function receipt_page( $order_id ) {

		$orderIdLog = $order_id . $this->fuc;
    	$idLog = generateIdLog($this->activar_log, $this->logString, $orderIdLog);

		$paymentResult = get_transient('REDSYS_payment_result_' . $order_id);
		$paymentResult = json_decode($paymentResult, true);

		escribirLog("INFO ", $idLog, "Procesando respuesta REST para el pedido " . $order_id, null, __METHOD__);

		if(!wc_get_order($order_id)) {
			$order_id ++;
			escribirLog("INFO ", $idLog, "Se ha actualizado el idCart a $order_id al haber fallado la obtención de orden con el número enviado a Redsys.", null, __METHOD__);
		}

        $order = new WC_Order($order_id);
		
		$urlOK = $this->get_option('urlOK');
		$urlKO = $this->get_option('urlKO');

		if($paymentResult){
			$estadoFinal = $this->get_option( 'estado' );
			switch($paymentResult['transactionType']){
				case 0:
					$estadoFinal = $this->get_option( 'estado' );
					break;
				case 1:
					$estadoFinal = $this->get_option( 'estado_preautorizacion' );
					break;
				case 7:
					$estadoFinal = $this->get_option( 'estado_autenticacion' );
					break;
			}
	
			$respuestaSIS = WC_Redsys_Insite::checkRespuestaSIS($paymentResult['apiCode'], $paymentResult['authCode']);
	
			if ($paymentResult['result'] == RESTConstants::$RESP_LITERAL_OK) {
				$order->add_order_note( __('[REDSYS] Respuesta del SIS: ', 'woocommerce') . $respuestaSIS[0]);
				$order->update_status($estadoFinal,__( '[REDSYS] El pedido es válido y se ha registrado correctamente. Número de pedido enviado a Redsys: ', 'woocommerce' ) . $paymentResult['order']);
	
				$order->payment_complete();

//				escribirLog("INFO ", $idLog, "Pedido que debería ser el ORDER ID: " . (new WC_Redsys)->get_next_order_id() - 1, null, __METHOD__);
//				WC_Redsys_Refund::saveOrderId((new WC_Redsys)->get_next_order_id() - 1, $paymentResult['order'], $paymentResult['amount']);
				WC_Redsys_Refund::saveOrderId($order->id, $paymentResult['order'], $paymentResult['amount']);
				escribirLog("INFO ", $idLog, "Pedido " . $paymentResult['idCart'] . " registrado con éxito.", null, __METHOD__);
				
				$reference=$paymentResult['merchantIdentifier'];
				if($reference!=null){
					$idCustomer = get_post_meta( $order_id, '_customer_user', true );
					$cardNumber=$paymentResult['cardNumber'];
					$cardBrand=$paymentResult['cardBrand'];
					$cardType=$paymentResult['cardType'];
					
					WC_Redsys_Ref::saveReference($idCustomer, $reference, $cardNumber, $cardBrand, $cardType);
				}
	
				$redirectUrl = $urlOK ? $urlOK : $order->get_checkout_order_received_url();
			} else {
				$order->add_order_note( __('[REDSYS] Respuesta del SIS: ', 'woocommerce') . $respuestaSIS[0]);
				$order->update_status('cancelled',__( '[REDSYS] El pedido ha finalizado con errores. Número de pedido enviado a Redsys: ', 'woocommerce' ) . $paymentResult['order']);

				escribirLog("ERROR", $idLog, "Pedido " . $paymentResult['apiCode'] . " finalizado con errores.", null, __METHOD__);
	
				$redirectUrl = $urlKO ? $urlKO : $order->get_cancel_order_url();
			}
		}else{
			$order->update_status('cancelled',__( '[REDSYS] El pedido ha finalizado con errores. Número de pedido enviado a Redsys: ', 'woocommerce' ) . $paymentResult['order']);
			escribirLog("ERROR", $idLog, "Pedido " . $paymentResult['apiCode'] . " finalizado con errores, marcado como Cancelado.", null, __METHOD__);

			$redirectUrl = $urlKO ? $urlKO : $order->get_cancel_order_url();
		}

		if(!substr($redirectUrl, 0, 4) === "http"){
			$redirectUrl = home_url( '/checkout' ) . $redirectUrl;
		}

		escribirLog("DEBUG", $idLog, "Redirigiendo a " . $redirectUrl, null, __METHOD__);

		header("Location: " . $redirectUrl);
		
		exit();
    }

	function checkRespuestaSIS($codigo_respuesta, $authCode) {

		$erroresSIS = array();
		$errorBackofficeSIS = "";
	   
		include 'erroresSIS.php';
	   
		if (array_key_exists($codigo_respuesta, $erroresSIS)) {
		
			$errorBackofficeSIS = $codigo_respuesta;
			$errorBackofficeSIS .= ' - '.$erroresSIS[$codigo_respuesta].'.';
		
		} else {
	   
			$errorBackofficeSIS = "La operación ha finalizado con errores. Consulte el módulo de administración del TPV Virtual.";
		}
	   
		$metodoOrder = "N/A";
	   
		if (($codigo_respuesta < 101) && (strpos($codigo_respuesta, "SIS") === false))
			$metodoOrder = "Autorizada " . $authCode; 

		else {

			if (strpos($codigo_respuesta, "SIS") !== false)
				$metodoOrder = "Error " . $codigo_respuesta;
			else 
				$metodoOrder = "Denegada " . $codigo_respuesta;
		}

		return array($errorBackofficeSIS, $metodoOrder);
	}

    function init_form_fields() {
    	global $woocommerce;
    
    	$this->form_fields = array(
                'enabled' => array(
                    'title'       => __( 'Activación del Módulo', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Activa o desactiva el Módulo de pago con Tarjeta', 'woocommerce' ),
                    'default'     => 'no',
                    'options'     => array(
                            'yes' => __( 'Activado', 'woocommerce' ),
                            'no'  => __( 'Desactivado', 'woocommerce' )
                    ),
                    'desc_tip'    => true,
                ),
                'title' => array(
                        'title'       => __( 'Título del método de Pago', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Título del método de Pago que el cliente verá en la página de compra.', 'woocommerce' ),
                        'default'     => __( 'Pagar con Tarjeta', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'description' => array(
                        'title'       => __( 'Descripción del método de Pago', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Descripción del método de Pago que el cliente verá en la página de compra.', 'woocommerce' ),
                        'default'     => __( 'Pague con tarjeta usando los servicios de Redsys.', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'entorno' => array(
                        'title'       => __( 'Entorno de Operación', 'woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Entorno donde procesar el pago. <br>Recuerde no activar el modo "Sandbox" en su entorno de producción, de lo contrario podrían producirse ventas no deseadas. Dispone de más información sobre cómo realizar pruebas <a href=https://pagosonline.redsys.es/entornosPruebas.html target="_blank" rel="noopener noreferrer">aquí</a>.', 'woocommerce' ),
                        'default'     => 0,
                        'options'     => array(
                            0 => __( 'Sandbox', 'woocommerce' ),
                            1 => __( 'Producción', 'woocommerce' )
                        )
                ),
				'name' => array(
						'title'       => __( 'Nombre del Comercio', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'Nombre de su comercio que se establecerá a la hora de enviar las operaciones.', 'woocommerce' ),
						'default'     => __( '', 'woocommerce' ),
						'desc_tip'    => true,
				),
                'fuc' => array(
                        'title'       => __( 'Número de Comercio (FUC)', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'El número de comercio, también denominado FUC, es un número que identifica a su comercio y debe habérselo provisto su Entidad Bancaria.', 'woocommerce' ),
                        'default'     => __( '', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'terminal' => array(
                        'title'       => __( 'Número de Terminal', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'El número de terminal es el número que identifica el terminal dentro de su comercio y debe habérselo provisto su Entidad Bancaria.', 'woocommerce' ),
                        'default'     => __( '', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'clave256' => array(
                        'title'       => __( 'Clave de Encriptación SHA-256', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Esta clave permite firmar todas las operaciones enviadas por el módulo y ha debido ser provista de ella por su Entidad Bancaria. Recuerde guardarla en un lugar seguro. <br> Para realizar pruebas en el entorno Sandbox, puede usar: sq7HjrUOBfKmC576ILgskD5srU870gJ7 o la provista por su Entidad Bancaria.', 'woocommerce' ),
                        'default'     => __( 'sq7HjrUOBfKmC576ILgskD5srU870gJ7', 'woocommerce' ),
                ),
    			'activar_log' => array(
                    'title'       => __( 'Guardar registros de comportamiento', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Si activa esta opción, se guardarán registros (logs) de los procesos que realice el módulo. <br> A la hora de notificar cualquier incidencia, los logs completos son de gran utilidad para poder detectar el problema.', 'woocommerce' ),
                    'default'     => '1',
                    'options'     => array(
                            '0' => __( 'No', 'woocommerce' ),
                            '1' => __( 'Sí, sólo informativos', 'woocommerce' ),
                            '2' => __( 'Sí, todos los registros', 'woocommerce' )
                    ),
                    'desc_tip'    => true,
                ),
				'tipopago' => array(
                    'title'       => __( 'Tipo de transacción', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( '<b>Autorización:</b> Es la operación estándar para que tus clientes realicen un pago.<br><b>Preautorización:</b> Esta operación retiene el cargo en la tarjeta del cliente, pero debe ser confirmada por ti en el Portal de Administración del TPV Virtual para que tenga efecto contable.<br><b>Autenticación:</b> Confirma los datos de la tarjeta del cliente pero no retiene el dinero en su cuenta. Para que tenga valor contable, debes confirmar la operación en el Portal de Administración del TPV Virtual, al igual que con la preautorización.', 'woocommerce' ),
                    'default'     => '0',
                    'options'     => array(
                            '0' => __( 'Autorización', 'woocommerce' ),
                            '1' => __( 'Preautorización', 'woocommerce' ),
							'7' => __( 'Autenticación', 'woocommerce' )
                	),
				), 
                'estado' => array(
                    'title'       => __( 'Estado del pedido al verificarse el pago para las autorizaciones', 'redsys_wc' ),
                    'type'        => 'select',
                    'description' => __( 'Aquí puede configurar el estado en el que se mostrará el pedido en el apartado "Pedidos" de su backoffice una vez el módulo reciba la notificación de que el pago ha sido correcto.', 'redsys_wc' ),
                    'default'     => 'processing',
                    'options'     => array(),
                    'desc_tip'    => true,
                ),
                'estado_preautorizacion' => array(
                    'title'       => __( 'Estado del pedido al verificarse el proceso de preautorización', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Aquí puede configurar el estado en el que se mostrará el pedido en el apartado "Pedidos" de su backoffice al realizar una preautorizacion.', 'woocommerce' ),
                    'default'     => 'on-hold',
                    'options'     => array(),
                    'desc_tip'    => true,
                ),
                'estado_autenticacion' => array(
                    'title'       => __( 'Estado del pedido al verificarse el proceso de autenticación', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Aquí puede configurar el estado en el que se mostrará el pedido en el apartado "Pedidos" de su backoffice al realizar una autenticación.', 'woocommerce' ),
                    'default'     => 'on-hold',
                    'options'     => array(),
                    'desc_tip'    => true,
                ),
				'genPedido' => array(
                    'title'       => __( 'Método de generación del número de pedido', 'redsys_wc' ),
                    'type'        => 'select',
                    'description' => __( 'Esta opción no modifica la forma en la que se identifica la orden en su Backoffice, sino el número de pedido (adaptado para que siempre ocupe doce dígitos) que se envía a Redsys para identificar la operación.<br>En algunos casos, si falla la obtención del identificador del carrito, se forzará que se utilice un número aleatorio.<br>Recuerde que en los detalles de cada orden puede ver el número de pedido que identifica la operación en el Portal de Administración del TPV Virtual.', 'redsys_wc' ),
                    'default'     => '0',
                    'options'     => array(
                        '0' => __( 'Híbrido (recomendado)', 'woocommerce' ),
                        '1' => __( 'Sólo ID del carrito', 'woocommerce' ),
                        '2' => __( 'Aleatorio', 'woocommerce' )
                    ),
                ),
    			'with3ds' => array(
    					'title'       => __( 'Pago seguro usando 3D Secure', 'woocommerce' ),
    					'type'        => 'select',
						'description' => __( 'Esta opción permite enviar información adicional del cliente que está realizando la compra, proporcionando más seguirdad a la hora de autenticar la operación. Se recomienda el envío de esta información en los datos de la operación.', 'woocommerce' ),
    					'default'     => '1',
    					'desc_tip'    => true,
    					'options'     => array(
    						'0' => __( 'No', 'woocommerce' ),
    						'1' => __( 'Si', 'woocommerce' )
    					)
    			),
    			'idioma_tpv' => array(
						'title'       => __( 'Permitir al TPV usar el idioma configurado en el navegador del cliente', 'woocommerce' ),
						'type'        => 'select',
						'description' => __( 'Con esta opción activada, la pasarela se mostrará en el idioma de visualización que el cliente haya configurado en los ajustes de su navegador.', 'woocommerce' ),
						'default'     => '0',
    					'desc_tip'    => false,
    					'options'     => array(
    						'0' => __( 'No', 'woocommerce' ),
    						'1' => __( 'Si', 'woocommerce' )
    					)
    			),
    			'withref' => array(
    					'title'       => __( 'Habilitar pago por referencia', 'woocommerce' ),
    					'type'        => 'select',
    					'description' => __( 'Habilita el pago por referencia .', 'woocommerce' ),
    					'default'     => '0',
    					'desc_tip'    => true,
    					'options'     => array(
    						'0' => __( 'No', 'woocommerce' ),
    						'1' => __( 'Si', 'woocommerce' )
    					)
    			),
    			'button_text' => array(
    					'title'       => __( 'Texto del botón de pago', 'woocommerce' ),
    					'type'        => 'text',
    					'description' => __( 'Texto del botón de pago.', 'woocommerce' ),
    					'default'     => 'REALIZAR PAGO',
    					'desc_tip'    => true
    			),
    			'button_style' => array(
    					'title'       => __( 'Estilo del botón de pago', 'woocommerce' ),
    					'type'        => 'text',
    					'description' => __( 'Estilo del botón de pago.', 'woocommerce' ),
    					'default'     => 'background-color:orange;color:black;',
    					'desc_tip'    => true
    			),
    			'body_style' => array(
    					'title'       => __( 'Estilo del iframe', 'woocommerce' ),
    					'type'        => 'text',
    					'description' => __( 'Estilo del iframe.', 'woocommerce' ),
    					'default'     => 'color:black;',
    					'desc_tip'    => true
    			),
    			'form_style' => array(
    					'title'       => __( 'Estilo del formulario', 'woocommerce' ),
    					'type'        => 'text',
    					'description' => __( 'Estilo del formulario.', 'woocommerce' ),
    					'default'     => 'color:grey;',
    					'desc_tip'    => true
    			),
    			'form_text_style' => array(
    					'title'       => __( 'Estilo del texto de formulario', 'woocommerce' ),
    					'type'        => 'text',
    					'description' => __( 'Estilo del texto de formulario.', 'woocommerce' ),
    					'default'     => ';',
    					'desc_tip'    => true
				),
				'sustituir_idioma' => array(
					'title'       => __( 'Eliminar el idioma en la ruta de las URLs', 'woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Esta opción elimina los idiomas (por ejemplo /en/) de la ruta de las URL. Esto es útil si tu plataforma no es capaz de encontrar las rutas de plantillas o archivos requeridos para el funcionamiento del módulo.', 'woocommerce' ),
					'default'     => '0',
					'options'     => array(
						'0' => __( 'No', 'woocommerce' ),
						'1' => __( 'Si', 'woocommerce' )
					)
				),
				'moneda_manual' => array(
                    'title'       => __( 'Moneda personalizada para operaciones', 'woocommerce' ),
                    'type'        => 'text',
                    'placeholder' => __( 'Introduzca el código ISO de la moneda.', ' woocommerce '),
                    'description' => __( '<span style="color:#fa7878; font-weight:bold;">( ! )</span> Esta configuración sobreescribirá la detección automática de moneda, su terminal deberá estar configurado para usar la moneda que aquí establezca si es distinta al Euro.<br>Deje en blanco para usar la detección automática. Use esta configuración sí y sólo sí su comercio está recibiendo errores SIS0015 o SIS0027.', 'woocommerce' ),
                    'default'     => '',
                ),
				'decimales_moneda' => array(
                    'title'       => __( 'Número de decimales de la moneda utilizada', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( '<span style="color:#fa7878; font-weight:bold;">( ! )</span> Esta configuración sobreescribe la detección automática de decimales de la moneda.<br>Al instalar el módulo se obtiene la configuración de Woocommerce, salvo que se hayan configurado cuatro o más decimales, que entonces se fija por defecto en 2 decimales (para el Euro, por ejemplo); pero si está usando monedas con distinto número de decimales o tiene problemas con el importe enviado al TPV Virtual, pruebe a cambiar esta opción.', 'woocommerce' ),
                    'default' => (intval(get_option('woocommerce_price_num_decimals')) > 3) ? 2 : intval(get_option('woocommerce_price_num_decimals')),
                    'options' => array(
                            0 => __( '0 (JPY, KRW, VND, ...)', 'woocommerce' ),
                            1 => __( '1 (JOD, TND, LYD, ...)', 'woocommerce' ),
                            2 => __( '2 (EUR, USD, GBP, ...)', 'woocommerce' ),
                            3 => __( '3 (KWD, OMR, BHD, ...)', 'woocommerce' )
                    ),
                ),
				'tabla_ordenes' => array(
                    'title'       => __( 'Tabla de Wordpress donde se guardan las órdenes de Woocommerce', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Configura aquí la tabla donde se guardan las órdenes de Woocommerce. Por defecto, se usa la tabla de entradas de Wordpress, pero en versiones más nuevas de Woocommerce, es posible que tengas configurado que se haga en una tabla propia de Woocommerce.<br>Puedes consultar tu configuración en Ajustes de Woocommerce > Avanzado > Almacenes de datos personalizado.', 'woocommerce' ),
                    'default'     => '0',
                    'options'     => array(
                            '0' => __( 'Tabla de Post/Entradas wp_posts (por defecto)', 'woocommerce' ),
                            '1' => __( 'Tabla de órdenes de WooCommerce wc_orders', 'woocommerce' )
                    ),
                ),
				'urlOK' => array(
                    'title'       => __( 'URL para operaciones correctas', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Este campo, denominado URL_OK, establece a qué página se redirigirá al cliente al volver de Redsys una vez la operación haya finalizado y esta sea correcta. Si este campo se rellena, se ignorará la configuración del parámetro establecida en el Portal de Administración del TPV Virtual.', 'woocommerce' ),
                    'default'     => __( '', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'urlKO' => array(
                    'title'       => __( 'URL para operaciones erróneas', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Este campo, denominado URL_KO, establece a qué página se redirigirá al cliente al volver de Redsys una vez la operación haya finalizado y esta haya tenido algún error. Si este campo se rellena, se ignorará la configuración del parámetro establecida en el Portal de Administración del TPV Virtual.', 'woocommerce' ),
                    'default'     => __( '', 'woocommerce' ),
                    'desc_tip'    => true,
                )
    	);
    		
    	$tmp_estados=wc_get_order_statuses();
    	foreach($tmp_estados as $est_id=>$est_na){
			$this->form_fields['estado']['options'][substr($est_id,3)]=$est_na;
			$this->form_fields['estado_preautorizacion']['options'][substr($est_id,3)]=$est_na;
			$this->form_fields['estado_autenticacion']['options'][substr($est_id,3)]=$est_na;
    	}
    }

	public function process_refund($order_id, $amount = 0, $reason = '', $idLog = null){
		$idLog = generateIdLog($this->activar_log, $this->logString, $order_id);

		return WC_Redsys_Refund::refund($this, $order_id, $amount, $reason, $idLog);
    }
}