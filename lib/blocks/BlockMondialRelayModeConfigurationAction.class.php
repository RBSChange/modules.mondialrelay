<?php
/**
 * mondialrelay_BlockMondialRelayModeConfigurationAction
 * @package modules.mondialrelay.lib.blocks
 */
class mondialrelay_BlockMondialRelayModeConfigurationAction extends shipping_BlockRelayModeConfigurationAction
{
	
	protected function buildFrameUrl()
	{
		$useFrame = Framework::getConfigurationValue('modules/mondialrelay/useFrame');
		if ($useFrame === 'true')
		{
			return mondialrelay_MondialrelaymodeService::getInstance()->getFrameUrl($this->param['mode'], $this->param['shippingAddress']);
		}
		return null;
	}
	
	/**
	 * Return the list of shipping_Relay
	 * @return array<shipping_Relay>
	 */
	protected function buildRelayList()
	{
		$webserviceUrl = Framework::getConfigurationValue('modules/mondialrelay/webserviceUrl');
		$mode = $this->param['mode'];
		
		$clientOptions = array('encoding' => 'utf-8', 'trace' => true);
		$soapClient = new SoapClient($webserviceUrl . '?wsdl', $clientOptions);
		
		$vendorCode = substr($mode->getVendorcode(), 0, 8);
		
		$crc = strtoupper(md5($vendorCode . $this->param['countryCode'] . $this->param['city'] . $this->param['zipcode'] . $mode->getVendorprivatekey()));
		
		$params = array('Enseigne' => $vendorCode, 'Pays' => $this->param['countryCode'], 'Ville' => $this->param['city'], 
			'CP' => $this->param['zipcode'], 'Security' => $crc);
		
		$relays = array();
		
		$resultSoap = $soapClient->WSI2_RecherchePointRelaisHoraires($params);
		$result = $resultSoap->WSI2_RecherchePointRelaisHorairesResult;
		
		$status = $result->STAT;
		if ($status == '0')
		{
			$list = $result->ListePR->ret_WSI2_sub_PointRelaisHoraires;
			foreach ($list as $item)
			{
				$relay = mondialrelay_MondialrelaymodeService::getInstance()->getRelayFromSoapObject($item);
				
				list($latitude, $longitude) = gmaps_ModuleService::getInstance()->getCoordinatesForAddress($relay->getAddress());
				$relay->setLatitude($latitude);
				$relay->setLongitude($longitude);
				
				$relays[] = $relay;
			}
		}
		
		return $relays;
	
	}

}