<?php
/**
 * mondialrelay_BlockExpeditionDetailAction
 * @package modules.mondialrelay.lib.blocks
 */
class mondialrelay_BlockExpeditionDetailAction extends shipping_BlockExpeditionDetailAction
{
	
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
	
	protected function getRelayDetail()
	{
		$result = array();
		$result['openingHours'] = '';
		$result['planUrl'] = null;
		$result['pictureUrl'] = null;
		$result['coordinate'] = null;
		$result['locationHint'] = null;
		
		$crc = strtoupper(md5($this->param['vendorCode'] . $this->param['relayCode'] . $this->param['countryCode'] . $this->param['vendorPrivateKeyCode']));
		
		$soapClient = $this->param['soapClient'];
		
		$params = array('Enseigne' => $this->param['vendorCode'], 'Num' => $this->param['relayCode'], 'Pays' => $this->param['countryCode'], 'Security' => $crc);
		$resultSoap = $soapClient->WSI2_DetailPointRelais($params);
		
		$status = $resultSoap->WSI2_DetailPointRelaisResult->STAT;
		
		if ($status != '0')
		{
			$result['error'] = $this->getStatusLabel($this->param['vendorCode'], $status, $this->param['lang'], $this->param['vendorPrivateKeyCode']);
		}
		else
		{
			$openingHours = array();
			$openingHours[] = $this->extractOpeningHour($resultSoap->WSI2_DetailPointRelaisResult->Horaires_Lundi->string);
			$openingHours[] = $this->extractOpeningHour($resultSoap->WSI2_DetailPointRelaisResult->Horaires_Mardi->string);
			$openingHours[] = $this->extractOpeningHour($resultSoap->WSI2_DetailPointRelaisResult->Horaires_Mercredi->string);
			$openingHours[] = $this->extractOpeningHour($resultSoap->WSI2_DetailPointRelaisResult->Horaires_Jeudi->string);
			$openingHours[] = $this->extractOpeningHour($resultSoap->WSI2_DetailPointRelaisResult->Horaires_Vendredi->string);
			$openingHours[] = $this->extractOpeningHour($resultSoap->WSI2_DetailPointRelaisResult->Horaires_Samedi->string);
			$openingHours[] = $this->extractOpeningHour($resultSoap->WSI2_DetailPointRelaisResult->Horaires_Dimanche->string);
			$result['openingHours'] = $openingHours;
			
			$result['planUrl'] = $resultSoap->WSI2_DetailPointRelaisResult->URL_Plan;
			
			$urlPhoto = $resultSoap->WSI2_DetailPointRelaisResult->URL_Photo;
			if ($urlPhoto != null && $urlPhoto != '')
			{
				$result['pictureUrl'] = $urlPhoto;
			}
			
			$locationHint1 = $resultSoap->WSI2_DetailPointRelaisResult->Localisation1;
			$locationHint2 = $resultSoap->WSI2_DetailPointRelaisResult->Localisation2;
			
			if ($locationHint1 != null && $locationHint1 != '')
			{
				$result['locationHint'] = $locationHint1;
			}
			if ($locationHint2 != null && $locationHint2 != '')
			{
				if ($result['locationHint'] != null)
				{
					$result['locationHint'] .= '<br/>';
				}
				$result['locationHint'] .= $locationHint2;
			}
		}
		
		return $result;
	}
	
	protected function getTrackingDetail($trackingNumber)
	{
		$result = array();
		
		$soapClient = $this->param['soapClient'];
		
		$crc = strtoupper(md5($this->param['vendorCode'] . $trackingNumber . $this->param['lang'] . $this->param['vendorPrivateKeyCode']));
		$params = array('Enseigne' => $this->param['vendorCode'], 'Expedition' => $trackingNumber, 'Langue' => $this->param['lang'], 'Security' => $crc);
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
	 * Extract opening hours from raw hours data
	 * @param array $hours
	 * @return string
	 */
	protected function extractOpeningHour($hours)
	{
		$ls = LocaleService::getInstance();
		$result = '';
		if ($hours[0] == '0000' && $hours[2] == '0000')
		{
			$result = $ls->transFO('m.shipping.general.closed');
		}
		else
		{
			$result = $ls->transFO('m.shipping.general.opening-hours', array('ucf'), array('hour1' => $this->formatHour($hours[0]), 
				'hour2' => $this->formatHour($hours[1])));
			
			if ($hours[2] != '0000')
			{
				$result .= ' ';
				$result .= $ls->transFO('m.shipping.general.and');
				$result .= ' ';
				$result .= $ls->transFO('m.shipping.general.opening-hours', array(), array('hour1' => $this->formatHour($hours[2]), 
					'hour2' => $this->formatHour($hours[3])));
			}
		}
		
		return $result;
	}
	
	protected function formatHour($hour)
	{
		$h = substr($hour, 0, 2);
		$m = substr($hour, 2);
		return $h . ':' . $m;
	}
	
	protected function getStatusLabel($vendorCode, $statusId, $lang, $vendorPrivateKeyCode)
	{
		$soapClient = $this->param['soapClient'];
		
		$crc = strtoupper(md5($vendorCode . $statusId . $lang . $vendorPrivateKeyCode));
		$params = array('Enseigne' => $vendorCode, 'STAT_ID' => $statusId, 'Langue' => $lang, 'Security' => $crc);
		
		$resultSoap = $soapClient->WSI2_STAT_Label($params);
		
		return $resultSoap->WSI2_STAT_LabelResult;
	}
	
	protected function createExpedition($vendorCode, $trackingNumber, $lang, $vendorPrivateKeyCode)
	{
		$soapClient = $this->param['soapClient'];
		
		// 		CCC : Collecte chez le client chargeur / l'enseigne
		// 		CDR : Collecte à domicile pour les expéditions standards
		// 		CDS : Collecte à domicile pour les expéditions lourdes ou volumineuses 
		// 		REL : Collecte en Point Relais
		// 		LCC : Livraison chez le client chargeur / l'enseigne
		// 		LD1 : Livraison à domicile pour les expéditions standards
		// 		LDS : Livraison à domicile pour les expéditions lourdes ou volumineuses 
		// 		24R : Livraison en Point Relais
		// 		DRI : Livraison en Colis Drive
		

		$ModeCol = 'CCC';
		$ModeLiv = '24R';
		$Expe_Langage = 'FR';
		$Expe_Ad1 = 'Mr Couturier Loic'; // (Civilité Nom Prénom)
		$Expe_Ad3 = '11 rue icare'; //(Rue)
		$Expe_Ville = 'entzheim';
		$Expe_CP = '67960';
		$Expe_Pays = 'FR';
		$Expe_Tel1 = '0388764764';
		$Dest_Langage = 'FR';
		$Dest_Ad1 = 'Mr Couturier Loic'; // (Civilité Nom Prénom)
		$Dest_Ad3 = '11 rue icare';
		$Dest_Ville = 'entzheim';
		$Dest_CP = '67960';
		$Dest_Pays = 'FR';
		$Poids = '2000';
		$NbColis = '1';
		$CRT_Valeur = '0';
		$LIV_Rel_Pays = 'FR';
		$LIV_Rel = '026018';
		
		$result = array();
		
		$crc = strtoupper(md5($vendorCode . $ModeCol . $ModeLiv . $Expe_Langage . $Expe_Ad1 . $Expe_Ad3 . $Expe_Ville . $Expe_CP . $Expe_Pays . $Expe_Tel1 . $Dest_Langage . $Dest_Ad1 . $Dest_Ad3 . $Dest_Ville . $Dest_CP . $Dest_Pays . $Poids . $NbColis . $CRT_Valeur . $LIV_Rel_Pays . $LIV_Rel . $vendorPrivateKeyCode));
		$params = array('Enseigne' => $vendorCode, 'ModeCol' => $ModeCol, 'ModeLiv' => $ModeLiv, 'Expe_Langage' => $Expe_Langage, 
			'Expe_Ad1' => $Expe_Ad1, 'Expe_Ad3' => $Expe_Ad3, 'Expe_Ville' => $Expe_Ville, 'Expe_CP' => $Expe_CP, 'Expe_Pays' => $Expe_Pays, 
			'Expe_Tel1' => $Expe_Tel1, 'Dest_Langage' => $Dest_Langage, 'Dest_Ad1' => $Dest_Ad1, 'Dest_Ad3' => $Dest_Ad3, 'Dest_Ville' => $Dest_Ville, 
			'Dest_CP' => $Dest_CP, 'Dest_Pays' => $Dest_Pays, 'Poids' => $Poids, 'NbColis' => $NbColis, 'CRT_Valeur' => $CRT_Valeur, 
			'LIV_Rel_Pays' => $LIV_Rel_Pays, 'LIV_Rel' => $LIV_Rel, 'Security' => $crc);
		$resultSoap = $soapClient->WSI2_CreationExpedition($params);
		
		print_r($resultSoap);
		
		return $result;
	}

}