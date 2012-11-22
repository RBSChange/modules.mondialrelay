<?php
/**
 * mondialrelay_BlockExpeditionDetailAction
 * @package modules.mondialrelay.lib.blocks
 */
class mondialrelay_BlockExpeditionDetailAction extends shipping_BlockExpeditionDetailAction
{
	/**
	 * Initialize $this->param
	 */
	protected function init()
	{
		$shippingAdress = $this->expedition->getAddress();
		$shippingMode = $this->expedition->getShippingMode();
		
		$this->param['relayCode'] = $shippingAdress->getLabel();
		$this->param['countryCode'] = $shippingAdress->getCountryCode();
		$vendorCode = $shippingMode->getVendorcode();
		$this->param['vendorCode'] = substr($vendorCode, 0, 8);
		$this->param['vendorPrivateKeyCode'] = $shippingMode->getVendorprivatekey();
		$this->param['lang'] = strtoupper($this->getContext()->getLang());
		
		$webserviceUrl = Framework::getConfigurationValue('modules/mondialrelay/webserviceUrl');
		
		$clientOptions = array('encoding' => 'utf-8', 'trace' => true);
		$this->param['soapClient'] = new SoapClient($webserviceUrl . '?wsdl', $clientOptions);
	}
	
	/**
	 * @return shipping_Relay
	 */
	protected function getRelayDetail()
	{
		$relay = null;
		
		$crc = strtoupper(md5($this->param['vendorCode'] . $this->param['relayCode'] . $this->param['countryCode'] . $this->param['vendorPrivateKeyCode']));
		
		$soapClient = $this->param['soapClient'];
		
		$params = array('Enseigne' => $this->param['vendorCode'], 'Num' => $this->param['relayCode'], 
			'Pays' => $this->param['countryCode'], 'Security' => $crc);
		$resultSoap = $soapClient->WSI2_DetailPointRelais($params);
		$result = $resultSoap->WSI2_DetailPointRelaisResult;
		
		$status = $result->STAT;
		
		if ($status == '0')
		{
			$relay = mondialrelay_MondialrelaymodeService::getInstance()->getRelayFromSoapObject($result);
		}
		
		return $relay;
	}
	
	/**
	 * @param string $trackingNumber
	 * @return array
	 */
	protected function getTrackingDetail($trackingNumber)
	{
		$result = array();
		
		$soapClient = $this->param['soapClient'];
		
		$crc = strtoupper(md5($this->param['vendorCode'] . $trackingNumber . $this->param['lang'] . $this->param['vendorPrivateKeyCode']));
		$params = array('Enseigne' => $this->param['vendorCode'], 'Expedition' => $trackingNumber, 
			'Langue' => $this->param['lang'], 'Security' => $crc);
		$resultSoap = $soapClient->WSI2_TracingColisDetaille($params);
		
		$status = $resultSoap->WSI2_TracingColisDetailleResult->STAT;
		
		if ($status != '0' && $status != '80' && $status != '81' && $status != '82' && $status != '83')
		{
			$result['error'] = $this->getStatusLabel($this->param['vendorCode'], $status, $this->param['lang'], $this->param['vendorPrivateKeyCode']);
		}
		else
		{
			$result['steps'] = array();
			
			$trackingLines = $resultSoap->WSI2_TracingColisDetailleResult->Tracing->ret_WSI2_sub_TracingColisDetaille;
			
			foreach ($trackingLines as $trackingLine)
			{
				
				$label = $trackingLine->Libelle;
				if ($label != null)
				{
					$step = array();
					$step['label'] = $label;
					$step['date'] = $trackingLine->Date;
					$step['hour'] = $trackingLine->Heure;
					$step['place'] = $trackingLine->Emplacement;
					$result['steps'][] = $step;
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * @param string $vendorCode
	 * @param string $statusId
	 * @param string $lang
	 * @param string $vendorPrivateKeyCode
	 * @return string
	 */
	protected function getStatusLabel($vendorCode, $statusId, $lang, $vendorPrivateKeyCode)
	{
		$soapClient = $this->param['soapClient'];
		
		$crc = strtoupper(md5($vendorCode . $statusId . $lang . $vendorPrivateKeyCode));
		$params = array('Enseigne' => $vendorCode, 'STAT_ID' => $statusId, 'Langue' => $lang, 'Security' => $crc);
		
		$resultSoap = $soapClient->WSI2_STAT_Label($params);
		
		return $resultSoap->WSI2_STAT_LabelResult;
	}
}