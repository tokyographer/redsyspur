<?php

if(!class_exists('RESTDCCConfirmationService')){
	include_once $GLOBALS["REDSYS_API_PATH"]."/Service/RESTService.php";
	include_once $GLOBALS["REDSYS_API_PATH"]."/Model/message/RESTInitialRequestMessage.php";
	include_once $GLOBALS["REDSYS_API_PATH"]."/Model/message/RESTResponseMessage.php";
	include_once $GLOBALS["REDSYS_API_PATH"]."/Model/message/RESTResponseMessage.php";
	include_once $GLOBALS["REDSYS_API_PATH"]."/Utils/RESTSignatureUtils.php";
	include_once $GLOBALS["REDSYS_API_PATH"]."/Constants/RESTConstants.php";
	
	class RESTDCCConfirmationService extends RESTService{
		function __construct($signatureKey, $env){
			parent::__construct($signatureKey, $env, RESTConstants::$TRATA);
		}

		public function createRequestMessage($message){
			if($message !== NULL){
				$req=new RESTInitialRequestMessage();
				$req->setDatosEntrada($message);
			
				$tagDE=$message->toXml();
				
				$signatureUtils=new RESTSignatureUtils();
				$localSignature= $signatureUtils->createMerchantSignatureHostToHost($this->getSignatureKey(), $tagDE);
				$req->setSignature($localSignature);
				
				return $req->toXml();
			}
			return "";
		}
		
		public function createResponseMessage($trataPeticionResponse, $idLog = null){
			$response=new RESTResponseMessage();
			$response->parseXml($trataPeticionResponse);
			escribirLog("DEBUG", $idLog, "Datos recibidos: " . $response->toXml(), null, __METHOD__);
			
			$acsElem=$response->getTagContent(RESTConstants::$RESPONSE_ACS_URL_TAG, $trataPeticionResponse);

			if($acsElem!==NULL && strlen($acsElem)){
				if($response->getApiCode()!==RESTConstants::$RESP_CODE_OK
					|| !$this->checkSignature($response->getOperation()))//falta un argumento, la firma remota
				{
					$response->setResult(RESTConstants::$RESP_LITERAL_KO);
				}
				else{
					$response->setResult(RESTConstants::$RESP_LITERAL_AUT);
				}
			}
			else{
				$response=new RESTResponseMessage();
				$response->parseXml($trataPeticionResponse);
				$transType = $response->getTransactionType();
				if($response->getApiCode()!==RESTConstants::$RESP_CODE_OK
						|| !$this->checkSignature($response->getOperation()))//falta un argumento, la firma remota
				{
					$response->setResult(RESTConstants::$RESP_LITERAL_KO);
				}
				else{
					switch ((int)$response->getOperation()->getResponseCode()){
						case RESTConstants::$AUTHORIZATION_OK: $response->setResult(($transType==RESTConstants::$AUTHORIZATION || $transType==RESTConstants::$PREAUTHORIZATION || $transType==RESTConstants::$VALIDATION || $transType==RESTConstants::$VALIDATION_CONFIRMATION)?RESTConstants::$RESP_LITERAL_OK:RESTConstants::$RESP_LITERAL_KO); break;
						case RESTConstants::$CONFIRMATION_OK: $response->setResult(($transType==RESTConstants::$CONFIRMATION || $transType==RESTConstants::$REFUND || $transType==RESTConstants::$REFUND_WITHOUT_ORIGINAL)?RESTConstants::$RESP_LITERAL_OK:RESTConstants::$RESP_LITERAL_KO);  break;
						case RESTConstants::$CANCELLATION_OK: $response->setResult($transType==RESTConstants::$CANCELLATION ?RESTConstants::$RESP_LITERAL_OK:RESTConstants::$RESP_LITERAL_KO);  break;
						case RESTConstants::$UNFINISHED: $response->setResult($transType==RESTConstants::$PAYGOLD_REQUEST ?RESTConstants::$RESP_LITERAL_OK:RESTConstants::$RESP_LITERAL_KO);  break;
						default: $response->setResult(RESTConstants::$RESP_LITERAL_KO);
					}
				}
			}				
			
			return $response;
		}
		
		// public function unMarshallResponseMessage($message){
		// 	$response=new RESTResponseMessage();
		// 	$response->parseXml($message);
		// 	return $response;
		// }
	}
}