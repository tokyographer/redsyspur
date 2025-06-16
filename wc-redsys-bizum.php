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
if(!class_exists("WC_Redsys_Refund")) {
	require_once('wc-redsys-refund.php');
}

class WC_Redsys_Bizum extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'redsys_bizum';
        $this->logString          = str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

        //$this->icon               = REDSYSPUR_URL . '/pages/assets/images/Redsys.png';
        $this->method_title       = __( 'Bizum · Pasarela Unificada de Redsys para WooCommerce', 'woocommerce' );
        $this->method_description = __( 'Permita a sus clientes pagar con Bizum redirigiéndoles a los servicios de Redsys.', 'woocommerce' );
        $this->notify_url         = add_query_arg( 'wc-api', 'WC_redsys_bizum', home_url( '/' ) );
        $this->redirect_url       = add_query_arg( 'wc-api', 'WC_redsys_bizum_redirect', home_url( '/' ) );
        $this->log                =  new WC_Logger();

        $this->has_fields         = false;

        // Load the settings
        $this->init_settings();
        $this->init_form_fields();

        $this->supports           = array( 'refunds' );

        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );
        $this->buttonLabel        = "Continuar en Bizum";

        // Get settings
        $this->entorno            = $this->get_option( 'entorno' );
        $this->nombre             = $this->get_option( 'name' );
        $this->fuc                = $this->get_option( 'fuc' );
        $this->tipopago           = $this->get_option( 'tipopago' );
        $this->clave256           = $this->get_option( 'clave256' );
        $this->terminal           = $this->get_option( 'terminal' );
        $this->activar_log	      = $this->get_option( 'activar_log' );
        $this->notificacion_get	  = $this->get_option( 'notificacion_get' );
        $this->estado             = $this->get_option( 'estado' );
        $this->genPedido	      = $this->get_option( 'genPedido' );
        $this->pedidoExtendido	  = $this->get_option( 'pedidoExtendido' );
        $this->mantener_carrito   = $this->get_option( 'mantener_carrito' );
        $this->activar_anulaciones= $this->get_option( 'activar_anulaciones' );
        $this->moneda_manual      = $this->get_option( 'moneda_manual' );
        $this->decimales_moneda   = $this->get_option( 'decimales_moneda' );
        $this->urlok              = $this->get_option( 'urlok' );
		$this->urlko              = $this->get_option( 'urlko' );

        $this->moduleComent = "Pasarela Unificada de Redsys para WooCommerce";

        //moneda a usar
        $this->moneda = currency_code(get_option('woocommerce_currency'));

        //idLog
        $this->logString          = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Actions
        add_action( 'woocommerce_receipt_redsys_bizum', array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        //Payment listener/API hook
        add_action( 'woocommerce_api_wc_redsys_bizum', array( $this, 'check_rds_response' ) );
        add_action( 'woocommerce_api_wc_redsys_bizum_redirect', array( $this, 'redirect' ) );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'advertencia_sandbox' ) );
    }

    function init_form_fields() {
        global $woocommerce;

        $this->form_fields = array(
                'enabled' => array(
                        'title'       => __( 'Activación del Módulo', 'woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Activa o desactiva el Módulo de BIZUM', 'woocommerce' ),
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
                        'default'     => __( 'Pagar con BIZUM', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'description' => array(
                        'title'       => __( 'Descripción del método de Pago', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Descripción del método de Pago que el cliente verá en la página de compra.', 'woocommerce' ),
                        'default'     => __( 'Pague con BIZUM usando los servicios de Redsys.', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'entorno' => array(
                        'title'       => __( 'Entorno de Operación', 'woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Entorno donde procesar el pago. <br>Recuerde no activar el modo "Sandbox" en su entorno de producción, de lo contrario podrían producirse ventas no deseadas. Dispone de más información sobre cómo realizar pruebas <a href=https://pagosonline.redsys.es/entornosPruebas.html target="_blank" rel="noopener noreferrer">aquí</a>.', 'woocommerce' ),
                        'default'     => 'Sis-t',
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
                        'default'     => __( '', 'woocommerce' ),
                ),
                'mantener_carrito' => array(
                    'title'       => __( 'Redirigir al checkout en caso de error para reintentar la operación', 'woocommerce'),
                    'type'        => 'select', //checkbox
    //                'label'       => 'Con esta opción activa, el carrito no se borrará si se produce un error durante el proceso de pago y el cliente será redirigido al checkout para poder intentarlo de nuevo. No se creará un pedido con esta opción activa.',
                    'description' => __( 'Esta función está desactivada temporalmente', 'woocommerce' ),
                    'default'     => 'no',
                    'options'     => array(
                            'no' => __( 'Desactivado', 'woocommerce' ),
                            'si' => __( 'Activado', 'woocommerce' )
                    ),
                    'desc_tip'    => true,
                    'disabled'    => true,
               ),
               'activar_anulaciones' => array(
                    'title'       => __( 'Realizar una anulación automática en caso de error fatal', 'woocommerce' ),
                    'description' => __( 'Con esta opción activada, si el cliente realiza un pago en la pasarela pero la validación del pedido falla, se emitirá una anulación automática.', 'woocommerce' ),
                    'type'        => 'select',
                    'default'     => '1',
                    'options'     => array(
                        '0' => __( 'No', 'woocommerce' ),
                        '1' => __( 'Si', 'woocommerce' )
                    ),
                    'disabled'    => false,
                    'desc_tip'    => false,
                ),
                'estado' => array(
                        'title'       => __( 'Estado del pedido al verificarse el pago', 'redsys_wc' ),
                        'type'        => 'select',
                        'description' => __( 'Aquí puede configurar el estado en el que se mostrará el pedido en el apartado "Pedidos" de su backoffice una vez el módulo reciba la notificación de que el pago ha sido correcto.', 'redsys_wc' ),
                        'default'     => 'processing',
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
                'activar_log' => array(
                        'title'       => __( 'Guardar registros de comportamiento', 'woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Si activa esta opción, se guardarán registros (logs) de los procesos que realice el módulo. <br> A la hora de notificar cualquier incidencia, los logs completos son de gran utilidad para poder detectar el problema.', 'woocommerce' ),
                        'default'     => 'no',
                        'options'     => array(
                                '0' => __( 'No', 'woocommerce' ),
                                '1' => __( 'Sí, sólo informativos', 'woocommerce' ),
                                '2' => __( 'Sí, todos los registros', 'woocommerce' )
                        ),
                        'desc_tip'    => true,
                ),
                'notificacion_get' => array(
                    'title'       => __( 'Validar los pedidos usando los parámetros incluidos en el retorno de navegación del cliente', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( '<span style="color:#fa7878; font-weight:bold;">( ! )</span> Ten en cuenta que para que esta opción funcione, no debes tener ninguna URL personalizada configurada en URL para operaciones correctas ni incorrectas.<br>Para un correcto funcionamiento, debes configurar en el Portal de Administración del TPV Virtual la opción "Enviar parámetros en las URLs" a "Sí".', 'woocommerce' ),
                    'default'     => 0,
                    'options'     => array(
                            0 => __( 'No', 'woocommerce' ),
                            1 => __( 'Sí', 'woocommerce' ),
                    ),
                    'desc_tip'    => false,
                ), 
                'tipopago' => array(
                    'title'       => __( 'Tipo de transacción', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Esta opción permite enviar información adicional del cliente que está realizando la compra, proporcionando más seguirdad a la hora de autenticar la operación<br><span style="color:#fa7878; font-weight:bold;">( ! )</span> Si selecciona "preautorización", deberá realizar las confirmaciones desde el Portal de Administración del TPV Virtual.', 'woocommerce' ),
                    'default'     => '0',
                    'options'     => array(
                            '0' => __( 'Autorización', 'woocommerce' ),
                            '7' => __( 'Autenticación', 'woocommerce' )
                    ),
                ),
                'genPedido' => array(
                    'title' => __( 'Método de generación del número de pedido', 'redsys_wc' ),
                    'type' => 'select',
                    'description' => __( 'Esta opción no modifica la forma en la que se identifica la orden en su Backoffice, sino el número de pedido (adaptado para que siempre ocupe doce dígitos) que se envía a Redsys para identificar la operación.<br>Recuerde que en los detalles de cada orden puede ver el número de pedido que identifica la operación en el Portal de Administración del TPV Virtual.', 'redsys_wc' ),
                    'default' => '0',
                    'options' => array(
                            '0' => __( 'Híbrido (recomendado)', 'woocommerce' ),
                            '1' => __( 'Sólo ID del carrito', 'woocommerce' ),
                            '2' => __( 'Aleatorio', 'woocommerce' )
                    ),
                ),
                'pedidoExtendido' => array(
                    'title'       => __( 'El terminal permite número de pedido extendido', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Marque esta opción si su terminal está configurado para admitir números de pedidos extendidos. Esto es útil para tiendas cuyos número de pedidos podrían exceder las doce posiciones que tiene como máximo un número de pedido estándar.<br>Recuerde que debe solicitar a su entidad bancaria que activen esta configuración en su terminal antes de marcar esta opción.', 'woocommerce' ),
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
                'urlok' => array(
                    'title'       => __( 'URL para operaciones correctas', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Este campo, denominado URL_OK, establece a qué página se redirigirá al cliente al volver de Redsys una vez la operación haya finalizado y esta sea correcta. Si este campo se rellena, se ignorará la configuración del parámetro establecida en el Portal de Administración del TPV Virtual.', 'woocommerce' ),
                    'default'     => __( '', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'urlko' => array(
                    'title'       => __( 'URL para operaciones erróneas', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Este campo, denominado URL_KO, establece a qué página se redirigirá al cliente al volver de Redsys una vez la operación haya finalizado y esta haya tenido algún error. Si este campo se rellena, se ignorará la configuración del parámetro establecida en el Portal de Administración del TPV Virtual.', 'woocommerce' ),
                    'default'     => __( '', 'woocommerce' ),
                    'desc_tip'    => true,
                ));
				
				$tmp_estados=wc_get_order_statuses();
				foreach($tmp_estados as $est_id=>$est_na){
					$this->form_fields['estado']['options'][substr($est_id,3)]=$est_na;
                    $this->form_fields['estado_autenticacion']['options'][substr($est_id,3)]=$est_na;
				}
    }

    function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order($order_id);

        $orderIdLog = $order_id . $this->fuc;
        $idLog = generateIdLog($this->activar_log, $this->logString, $orderIdLog);

        $isLogged = is_user_logged_in();
		$userId = $order->get_customer_id();
        
        escribirLog("DEBUG", $idLog, "**************************");
		escribirLog("INFO ", $idLog, "****** NUEVO PEDIDO ******");
		escribirLog("DEBUG", $idLog, "**************************");

		escribirLog("INFO ", $idLog, "Pago con Bizum", null, __METHOD__);
		escribirLog("INFO ", $idLog, "ID del usuario cargado: " . $userId, null, __METHOD__);

        if ($isLogged == true)
			escribirLog("INFO ", $idLog, "El usuario que hace el pedido está logueado en la página", null, __METHOD__);
		else
			escribirLog("INFO ", $idLog, "El usuario que hace el pedido no está logueado en la página", null, __METHOD__);

        $redirectUrl = $this->redirect_url . "&order_id=" . $order_id;

        escribirLog("DEBUG", $idLog, "Redireccionando a " . $redirectUrl . " para continuar...", null, __METHOD__);

        // Return redirect page
        return array(
            'result' 	=> 'success',
            'redirect'	=> $redirectUrl
        );
    }

    function generate_redsys_form( $order_id ) {
        //Objeto tipo pedido
        $order = new WC_Order($order_id);

        $orderIdLog = $order_id . $this->fuc;
        $idLog = generateIdLog($this->activar_log, $this->logString, $orderIdLog);
        escribirLog("DEBUG", $idLog, "Generando formulario para el pedido " . $order_id);
    
        $merchantModule = 'WO-PUR v' . MODULE_VERSION;

        escribirLog("DEBUG", $idLog, "Versión del módulo: " . $merchantModule, null, __METHOD__);
        escribirLog("DEBUG", $idLog, "Versión de Wordpress: " . $GLOBALS['wp_version'], null, __METHOD__);
		escribirLog("DEBUG", $idLog, "Versión de WooCommerce: " . WC_VERSION, null, __METHOD__);
		escribirLog("DEBUG", $idLog, "Versión de PHP: " . phpversion(), null, __METHOD__);

        //Recuperamos los datos de config.
        $logActivo=$this->get_option('activar_log');
        $nombre=$this->get_option('name');
        $codigo=$this->get_option('fuc');
        $terminal=$this->get_option('terminal');
        $moneda = currency_code(get_option('woocommerce_currency'));
        $clave256=$this->get_option('clave256');
        $tipopago=intval($this->get_option('tipopago'));
        $entorno=$this->get_option('entorno');

        if ( !empty( $this->get_option('moneda_manual') ) )
            $moneda = $this->get_option('moneda_manual');

        //Callback
        $urltienda = $this -> notify_url;
        $urlok = $this->get_option('urlOK') ? $this->get_option('urlOK') : $this -> notify_url;
		$urlko = $this->get_option('urlKO') ? $this->get_option('urlKO') : $this -> notify_url;

		if(!substr($urlok, 0, 4) === "http"){
			$urlok = home_url( '/checkout' ) . $urlok;
		}

		if(!substr($urlko, 0, 4) === "http"){
			$urlko = home_url( '/checkout' ) . $urlko;
		}

        //Calculo del precio total del pedido
        $currency_decimals = intval($this->get_option('decimales_moneda')) /*get_option('woocommerce_price_num_decimals')*/;

        $transaction_amount = number_format( (float) ($order->get_total()), intval($currency_decimals), '.', '' );
        $transaction_amount = str_replace('.','',$transaction_amount);
        $transaction_amount = floatval($transaction_amount);

        // Descripción de los productos
        $productos="";
        $products = WC()->cart->cart_contents;
        foreach ($products as $product) {
            $productos .= $product['quantity'].'x'.$product['data']->get_title().'/';
        }

        $numpedido = generaNumeroPedido($order_id, $this->get_option('genPedido'), $this->get_option('pedidoExtendido') == 1);
        escribirLog("INFO ", $idLog, "Numero de pedido enviado a Redsys ─ [Ds_Merchant_Order]: " . $numpedido, null, __METHOD__); 

        $idioma_web = substr(get_locale(),0,2);
        switch ($idioma_web) {
            case 'es':
            $idiomaFinal='001';
            break;
            case 'en':
            $idiomaFinal='002';
            break;
            case 'ca':
            $idiomaFinal='003';
            break;
            case 'fr':
            $idiomaFinal='004';
            break;
            case 'de':
            $idiomaFinal='005';
            break;
            case 'nl':
            $idiomaFinal='006';
            break;
            case 'it':
            $idiomaFinal='007';
            break;
            case 'sv':
            $idiomaFinal='008';
            break;
            case 'pt':
            $idiomaFinal='009';
            break;
            case 'pl':
            $idiomaFinal='011';
            break;
            case 'gl':
            $idiomaFinal='012';
            break;
            case 'eu':
            $idiomaFinal='013';
            break;
            default:
            $idiomaFinal='002';
        }

        $merchantTitular = createMerchantTitular($order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_billing_email());

        // Generamos la firma	
        $miObj = new RedsyspurAPI;
        $miObj->setParameter("DS_MERCHANT_AMOUNT",$transaction_amount);
        $miObj->setParameter("DS_MERCHANT_ORDER",$numpedido);
        $miObj->setParameter("DS_MERCHANT_MERCHANTCODE",$codigo);
        $miObj->setParameter("DS_MERCHANT_CURRENCY", $moneda);
        $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE", $tipopago);
        $miObj->setParameter("DS_MERCHANT_TERMINAL",$terminal);
        $miObj->setParameter("DS_MERCHANT_MERCHANTURL",$urltienda);
        $miObj->setParameter("DS_MERCHANT_URLOK",$urlok);
        $miObj->setParameter("DS_MERCHANT_URLKO",$urlko);
        $miObj->setParameter("Ds_Merchant_ConsumerLanguage",$idiomaFinal);
        $miObj->setParameter("Ds_Merchant_ProductDescription",$productos);
        $miObj->setParameter("Ds_Merchant_Titular",$merchantTitular);
        $miObj->setParameter("Ds_Merchant_MerchantName",$nombre);
        $miObj->setParameter("Ds_Merchant_PayMethods", "z");
        $miObj->setParameter("Ds_Merchant_Module",$merchantModule);

        $merchantData = createMerchantData($this->moduleComent, $order_id);
        $miObj->setParameter ( "Ds_Merchant_MerchantData", b64url_encode($merchantData) );
        
        //Datos de configuración
        $version = getVersionClave();

        //Clave del comercio que se extrae de la configuración del comercio
        // Se generan los parámetros de la petición
        $request = "";
        $paramsBase64 = $miObj->createMerchantParameters();
        $signatureMac = $miObj->createMerchantSignature($this->clave256);

        $resys_args = array(
            'Ds_SignatureVersion' => $version,
            'Ds_MerchantParameters' => $paramsBase64,
            'Ds_Signature' => $signatureMac
            //, 'this_path' => $this->_path
        );

        escribirLog("DEBUG", $idLog, "Parámetros de la solicitud: " . $resys_args['Ds_MerchantParameters'], null, __METHOD__ );
        escribirLog("DEBUG", $idLog, "Firma calculada y enviada : " . $resys_args['Ds_Signature'], null, __METHOD__ );

          //Se establecen los input del formulario con los datos del pedido y la redirección
        $resys_args_array = array();
        foreach($resys_args as $key => $value){
          $resys_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
        }

        //Se establece el entorno del SIS
        if($entorno==0) {
            $env="https://sis-t.redsys.es:25443/sis/realizarPago/utf-8";
        }
        else{
            $env="https://sis.redsys.es/sis/realizarPago/utf-8";
        }	

        //Formulario que envía los datos del pedido y la redirección al formulario de acceso al TPV

        return '<form action="'.$env.'" method="post" id="redsys_payment_form">'. 
                    implode('', $resys_args_array) . 
                    //'<input type="submit" class="button-alt" id="submit_redsys_payment_form" value="'.__('Pagar con Bizum', 'redsys').'" />'.
                    //'<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancelar Pedido', 'redsys').'</a>
                '</form>
                <script  type="text/javascript">
	                document.getElementById("redsys_payment_form").submit();
                </script>';
    }

    function redirect(){
        header("Content-Type: text/html");

        if ( isset( $_GET['order_id'] ) ) {
            $order_id = $_GET['order_id'];
            echo $this->generate_redsys_form($order_id);
        }
        exit;
    }

    function check_rds_response() {

        try {

            /** Identificamos que la petición ha llegado hasta el validador. */
            http_response_code(100);
            /** Se crea el objeto principal de la clase. */
            $miObj = new RedsyspurAPI;
            $redsys = new WC_Redsys_Bizum;

            /** Se recogen los datos de entrada. **/
            $dsSignatureVersion   = $_POST["Ds_SignatureVersion"] ?? $_GET["Ds_SignatureVersion"] ?? false;
            $dsMerchantParameters = $_POST["Ds_MerchantParameters"] ?? $_GET["Ds_MerchantParameters"] ?? false;
            $dsSignature          = $_POST["Ds_Signature"] ?? $_GET["Ds_Signature"] ?? false;

            /** Se comprueba si la URL ha entrado con parámetros. */
            if (!$dsMerchantParameters or !$dsSignature) {

                http_response_code(400);
                die ('La URL de notificación o del retorno de navegación no contiene parámetros válidos, por lo que no se puede redireccionar de nuevo a la tienda. Revisa tu historial de pedidos accediendo a la tienda de nuevo y en caso de duda contacta con el comercio.');
            }
            
            /** Se decodifican los datos enviados y se carga el array de datos **/
            $decodec = $miObj->decodeMerchantParameters($dsMerchantParameters);
            $miObj->stringToArray($decodec);

            /** Se inicializan los objetos necesarios para crear los registros de log. **/
            $merchantData = b64url_decode($miObj->getParameter('Ds_MerchantData'));
            $merchantData = json_decode( $merchantData ); 

            $idCart = $merchantData->idCart;
            $pedido = $miObj->getParameter('Ds_Order'); 

            $orderIdLog = $idCart . $redsys->fuc;
            $idLog = generateIdLog($redsys->activar_log, $redsys->logString, $orderIdLog);

            /** Se identifica la operacion en el registro. */
            if (!empty($_POST))
                escribirLog("INFO ", $idLog, "***** VALIDACIÓN DE LA NOTIFICACIÓN  ──  PEDIDO " . $miObj->getParameter('Ds_Order') . " *****");
            else
                escribirLog("INFO ", $idLog, "***** RETORNO DE NAVEGACIÓN  ──  PEDIDO " . $miObj->getParameter('Ds_Order') . " *****");

            /** Obtenemos la clave local. **/
            $claveComercio = $redsys->get_option( 'clave256' );

            escribirLog("DEBUG", $idLog, "Parámetros de la notificación : " . $dsMerchantParameters);
            escribirLog("DEBUG", $idLog, "Firma recibida del TPV Virtual: " . $dsSignature);

            /** Comprobacion de la firma y rechazo del procesamiento si no coinciden. */
            if (!WC_Redsys::validarFirma($miObj, $dsMerchantParameters, $dsSignature, $claveComercio, $dsSignatureVersion, $idLog)) {
                
                http_response_code(403);
                escribirLog("ERROR", $idLog, "Las firmas no coinciden, la notificación se rechazará con error HTTP 403.");
                die ('La petición no puede ser atendida porque las firmas no coinciden.');
            }

            /** Se obtiene cuál es el estado configurado como "estado final" en la configuración del módulo. */
            $estadoFinal = $redsys->estado;

            // if(!wc_get_order($idCart)) {

            //     $idCart++;
            //     escribirLog("INFO ", $idLog, "Se ha actualizado el idCart a $idCart al haber fallado la obtención de orden con el número enviado a Redsys.", null, __METHOD__);
            // }
            
            /** Se crea el objeto order para poder procesar la notificación. */
            $order = new WC_Order($idCart);
                        
            /** Control de navegación en caso de que el cliente sea redirigido al validation. */
            /** Se accede sólo si el POST está vacío pero sí que tenemos merchantParameters. */
            if (empty($_POST) and $dsMerchantParameters) {
                                
                escribirLog("INFO ", $idLog, "Cliente redirigido al validador a través del retorno de navegación.");

                /** URL adonde redirigiremos al cliente. */
                $urlRedirect = $order->get_checkout_order_received_url();

                /** Se evalúa si se necesita procesar la notificación usando parámetros GET comprobando si getOrderByCartId nos devuelve un pedido. Si lo hiciera, el pedido existe y no hay que validar. */
                if (!($order->get_status() == $estadoFinal or $order->get_status() == "completed" or $order->get_status() == "cancelled") and $redsys->get_option( 'notificacion_get' )) {

                    escribirLog("INFO ", $idLog, "Se van utilizar los datos recibidos vía GET para validar el pedido " . $pedido . " porque notificacion_get es " . $redsys->get_option( 'notificacion_get' ));

                    /** Si la validación sale mal, fijamos el checkout como la URL a la que redirigir. */
                    if (!WC_Redsys::confirmarPedido($miObj, $redsys, $order, $merchantData, $pedido, $estadoFinal, $idLog))
                        $urlRedirect = $order->get_cancel_order_url();    
                                    
                    $order->add_order_note( __('[REDSYS] El pedido se ha registrado usando los parámetros incluidos en el retorno de navegación.', 'woocommerce') . $metodoOrder);
                }
                
                escribirLog("DEBUG", $idLog, "Redireccionando cliente a: " . $urlRedirect);

                http_response_code(308);
                wp_redirect($urlRedirect);

                exit();
            }

            /** Evaluamos si el pedido ya está creado, y si es así, registramos que no lo tenemos que tocar. */
            if (($order->get_status() == $estadoFinal or $order->get_status() == "completed" or $order->get_status() == "cancelled") and $redsys->get_option( 'notificacion_get' )) {
				
				http_response_code(422);
				escribirLog("ERROR", $idLog, "Se ha recibido una notificación pero la orden ya está creada.");
				
				die("Se ha recibido una notificación pero la orden ya está creada.");
            
			} else {

				/** Ejectuamos la lógica de confirmación del pedido. */
                WC_Redsys::confirmarPedido($miObj, $redsys, $order, $merchantData, $pedido, $estadoFinal, $idLog);
                				
                exit();
			}

        } catch (Exception $e) {
            
            http_response_code(500);
            escribirLog("ERROR", "0000000000000000000000000ERROR", "Excepción en la validación: ".$e->getMessage());

            die("Excepcion en la validacion.");
        }
    }

    public static function confirmarPedido($miObj, $redsys, $order, $merchantData, $pedido, $estadoFinal, $idLog = null) {

        /** Se extraen todos los datos de la notificación. **/
        $total            = (int)$miObj->getParameter('Ds_Amount');  
        $idCart           = $merchantData->idCart;
        $codigo           = (int)$miObj->getParameter('Ds_MerchantCode');
        $terminal         = (int)$miObj->getParameter('Ds_Terminal');
        $moneda           = (int)$miObj->getParameter('Ds_Currency');
        $respuesta        = $miObj->getParameter('Ds_Response');
        $authCode         = $miObj->getParameter('Ds_AuthorisationCode');
        $tipoTransaccion  = (int)$miObj->getParameter('Ds_TransactionType');
        $metodo           = (int)$miObj->getParameter('Ds_ProcessedPayMethod');

        $metodoOrder = "N/A";

        if ($respuesta < 101)
            $metodoOrder = "Autorizada " . $authCode;    
        else if ($respuesta >= 101)
            $metodoOrder = "Denegada " . $respuesta;

        /** Se escriben en el registro los datos recibidos. */
        escribirLog("DEBUG", $idLog, "ID del Carrito: " . $idCart);
        escribirLog("DEBUG", $idLog, "Codigo Comercio FUC: " . $codigo);
        escribirLog("DEBUG", $idLog, "Terminal: " . $terminal);
        escribirLog("DEBUG", $idLog, "Moneda: " . $moneda);
        escribirLog("DEBUG", $idLog, "Codigo de respuesta del SIS: " . $respuesta);
        escribirLog("DEBUG", $idLog, "Método de Pago: " . $metodo);
        escribirLog("DEBUG", $idLog, "Información adicional del módulo: " . $merchantData->moduleComent);

        switch($tipoTransaccion) {

            case 0:
                $estadoFinal = $redsys->get_option( 'estado' );
                break;

            case 1:
                $estadoFinal = $redsys->get_option( 'estado_preautorizacion' );
                break;

            case 7:
                $estadoFinal = $redsys->get_option( 'estado_autenticacion' );
                break;
        }

        /** Análisis de respuesta del SIS. */
        $erroresSIS = array();
        $errorBackofficeSIS = "";

        include 'erroresSIS.php';

        if (array_key_exists($respuesta, $erroresSIS)) {
            
            $errorBackofficeSIS  = $respuesta;
            $errorBackofficeSIS .= ' - '.$erroresSIS[$respuesta].'.';
        
        } else {

            $errorBackofficeSIS = "La operación ha finalizado con errores. Consulte el módulo de administración del TPV Virtual.";
        }
        
        $authCode = str_replace("+", "", $authCode);
        escribirLog("DEBUG", $idLog, "Código de Autorización: " . $authCode);

        /** Advertimos si es una preautorización que el pedido aún no tiene valor contable. */
        if ($tipoTransaccion == 1)
            $order->add_order_note( __('[REDSYS] Esta orden se ha validado usando una PREAUTORIZACIÓN. Recuerde que para realizar la confirmación, deberá hacerlo desde el Portal de Administración del TPV Virtual', 'woocommerce'));

        /** Se valida el pedido cuando la operación es genuina y válida. */    
        if((int)$respuesta < 101){

            /** Añadimos los mensajes de información a la orden. */
            $order->add_order_note( __('[REDSYS] ', 'woocommerce') . $metodoOrder);
            $order->add_order_note( __('[REDSYS] Respuesta del SIS: ', 'woocommerce') . $errorBackofficeSIS);
            $order->update_status($estadoFinal,__( '[REDSYS] El pedido es válido y se ha registrado correctamente. Número de pedido enviado a Redsys: ', 'woocommerce' ) . $pedido);

            /** Se marca como completada para WooCommerce y se reduce el stock. */
            $order->payment_complete();

            wc_reduce_stock_levels($order->id);
            WC()->cart->empty_cart();
            
            /** Guardamos la reeferencia si en la notificación está incluida. */
            $merchantIdentifier = $miObj->getParameter('Ds_Merchant_Identifier');
            
            if($redsys->get_option('withref') == 1 and $merchantIdentifier!=null and is_user_logged_in()) {
                
                $idCustomer = $order->get_customer_id();
                $cardNumber=$miObj->getParameter('Ds_Card_Number');
                $brand=$miObj->getParameter('Ds_Card_Brand');
                $cardType=$miObj->getParameter('Ds_Card_Type');
                
                WC_Redsys_Ref::saveReference($idCustomer, $merchantIdentifier, $cardNumber, $brand, $cardType, $idLog, $idCart);
            }
            
            /** Se guarda el ID para posteriores operaciones sobre la orden. */
            WC_Redsys_Refund::saveOrderId($idCart, $pedido, $total);

            /** E imprimimos el resultado en el registro. */
            escribirLog("INFO ", $idLog, "El pedido con ID de carrito " . $idCart . " (" . $pedido . "), y número de orden $order->id es válido y se ha registrado correctamente.", null, __METHOD__);
            
            echo "Pedido validado con éxito ── " . $errorBackofficeSIS;
            http_response_code(200);

            return(1);
        
        } else {

            /** Si se ha producido un error, mostramos que ha habido un error. */

            $order = new WC_Order($idCart);
            $order->add_order_note( __('[REDSYS] ', 'woocommerce') . $metodoOrder);
            $order->add_order_note( __('[REDSYS] Respuesta del SIS: ', 'woocommerce') . $errorBackofficeSIS);
            $order->update_status('cancelled',__( '[REDSYS] El pedido ha finalizado con errores. Número de pedido enviado a Redsys: ', 'woocommerce' ) . $pedido);
            WC()->cart->empty_cart();

            escribirLog("ERROR", $idLog, "El pedido con ID de carrito " . $idCart . " (" . $pedido . "), y número de orden $order->id ha finalizado con errores.");
            escribirLog("ERROR", $idLog, $errorBackofficeSIS);
           
            echo "El pedido ha finalizado con errores ── " . $errorBackofficeSIS;
            http_response_code(412);

            return(0);
        }
    }

    public static function validarFirma($miObj, $dsMerchantParameters, $dsSignature, $claveComercio, $dsSignatureVersion, $idLog = false) {
        
        $dsSignatureLOCAL = $miObj->createMerchantSignatureNotif($claveComercio,$dsMerchantParameters);
        
        escribirLog("DEBUG", $idLog, "Firma calculada notificación  : " . $dsSignatureLOCAL);
        escribirLog("DEBUG", $idLog, "Firma calculada usando la clave de encriptación [" . $dsSignatureVersion . "] " . substr($claveComercio, 0, 3) . "*");

        if ($dsSignature === $dsSignatureLOCAL)
            return true;
        else
            return false;
    }

    function receipt_page( $order ) {
    }

    function advertencia_sandbox() {
        if ( $this->entorno == 0 && $this->enabled == 'yes' ) {
            wc_print_notice( sprintf(
                __("%s El método de pago '%s' está configurado para operar en entorno de pruebas, por lo que los %s de esta orden no tendrán efecto contable si este método de pago es utilizado.", "woocommerce"),
                '<strong>' . __("Advertencia:", "woocommerce") . '</strong>',
                $this->title,
                strip_tags( wc_price( WC()->cart->get_subtotal() ) )
            ), 'notice' );
        }
    }

    function escribirLog_wc($texto,$activo) {
        if($activo=="si"){
            // Log
            $this->log->add( 'redsys', $texto."\r\n");
        }
    }

	public function process_refund($order_id, $amount = 0, $reason = '', $idLog = null){
		$idLog = generateIdLog($this->activar_log, $this->logString, $order_id);

		return WC_Redsys_Refund::refund($this, $order_id, $amount, $reason, $idLog);
    }
}