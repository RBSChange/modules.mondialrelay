<?php
/**
 * mondialrelay_MondialrelaymodeService
 * @package modules.mondialrelay
 */
class mondialrelay_MondialrelaymodeService extends shipping_RelayModeService
{
	/**
	 * @var mondialrelay_MondialrelaymodeService
	 */
	private static $instance;
	
	/**
	 * @return mondialrelay_MondialrelaymodeService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @return mondialrelay_persistentdocument_mondialrelaymode
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_mondialrelay/mondialrelaymode');
	}
	
	/**
	 * Create a query based on 'modules_mondialrelay/mondialrelaymode' model.
	 * Return document that are instance of modules_mondialrelay/mondialrelaymode,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_mondialrelay/mondialrelaymode');
	}
	
	/**
	 * Create a query based on 'modules_mondialrelay/mondialrelaymode' model.
	 * Only documents that are strictly instance of modules_mondialrelay/mondialrelaymode
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_mondialrelay/mondialrelaymode', false);
	}
	
	/**
	 * @param mondialrelay_persistentdocument_mondialrelaymode $mode
	 * @param order_CartInfo $cart
	 * @return string[]|false
	 */
	public function getConfigurationBlockForCart($mode, $cart)
	{
		return array('mondialrelay', 'MondialRelayModeConfiguration');
	}
	protected function getDetailExpeditionPageTagName()
	{
		return 'contextual_website_website_modules_mondialrelay_mondialrelayexpedition';
	}
	
	/**
	 *
	 * @param mondialrelay_persistentdocument_mondialrelaymode $mode
	 * @param customer_persistentdocument_address $shippingAddress
	 * @param array $extraUrlParams
	 */
	public function getFrameUrl($mode, $shippingAddress, $extraUrlParams = array())
	{
		$baseUrl = Framework::getConfigurationValue('modules/mondialrelay/frameBaseUrl');
		
		$modeId = $mode->getId();
		$vendorcode = $mode->getVendorcode();
		$zipCode = $shippingAddress->getZipCode();
		$country = $shippingAddress->getCountry();
		if ($country instanceof zone_persistentdocument_country)
		{
			$countryCode = $country->getCode();
		}
		else
		{
			$countryCode = 'FR'; // Default
		}
		$crcValue = $this->getFrameCrc($mode, $zipCode, $countryCode);
		
		$returnUrl = LinkHelper::getActionUrl('mondialrelay', 'SelectRelayAction');
		$returnUrl = substr($returnUrl, 7);
		$returnUrl .= '?relayCodeReference={relais}&relayCountryCode={pays}&relayName={relais_nom}&relayAddressLine1={relais_adresse}&relayAddressLine2={relais_adresse2}&relayZipCode={relais_cp}&relayCity={relais_ville}&modeId=' . $modeId;
		$returnUrl = urlencode($returnUrl);
		
		$frameCssUrl = LinkHelper::getActionUrl('mondialrelay', 'GetFrameStylesheet');
		$frameCssUrl = substr($frameCssUrl, 7);
		$frameCssUrl = urlencode($frameCssUrl);
		
		$frameUrl = $baseUrl . '?ens=' . $vendorcode . '&cp=' . $zipCode . '&pays=' . $countryCode . '&crc=' . $crcValue . '&url=' . $returnUrl . '&css=' . $frameCssUrl;
		
		return $frameUrl;
	}
	
	/**
	 * @param mondialrelay_persistentdocument_mondialrelaymode $mode
	 * @param string $zipCode
	 * @param string $countryCode
	 * @return string
	 */
	protected function getFrameCrc($mode, $zipCode, $countryCode)
	{
		//	$raw_hash_string_tpl = "<{vendorcode}>{zipCode}{countryCode}<{vendorprivatekey}>";
		return md5('<' . $mode->getVendorcode() . '>' . $zipCode . $countryCode . '<' . $mode->getVendorprivatekey() . '>');
	}
	
	/**
	 * Construct a shipping_Relay from soap object
	 * @param object $soapObject
	 * @return shipping_Relay
	 */
	public function getRelayFromSoapObject($soapObject)
	{
		$relay = new shipping_Relay();
		
		$relay->setRef($soapObject->Num);
		$relay->setName($soapObject->LgAdr1);
		$relay->setAddressLine1($soapObject->LgAdr3);
		$relay->setZipCode($soapObject->CP);
		$relay->setCity($soapObject->Ville);
		$relay->setCountryCode($soapObject->Pays);
		
		$openingHours = array();
		$openingHours[] = $this->extractOpeningHour($soapObject->Horaires_Lundi->string);
		$openingHours[] = $this->extractOpeningHour($soapObject->Horaires_Mardi->string);
		$openingHours[] = $this->extractOpeningHour($soapObject->Horaires_Mercredi->string);
		$openingHours[] = $this->extractOpeningHour($soapObject->Horaires_Jeudi->string);
		$openingHours[] = $this->extractOpeningHour($soapObject->Horaires_Vendredi->string);
		$openingHours[] = $this->extractOpeningHour($soapObject->Horaires_Samedi->string);
		$openingHours[] = $this->extractOpeningHour($soapObject->Horaires_Dimanche->string);
		$relay->setOpeningHours($openingHours);
		
		$relay->setMapUrl($soapObject->URL_Plan);
		
		$urlPhoto = $soapObject->URL_Photo;
		if ($urlPhoto != null && $urlPhoto != '')
		{
			$relay->setPictureUrl($urlPhoto);
		}
		
		$locationHint1 = $soapObject->Localisation1;
		$locationHint2 = $soapObject->Localisation2;
		$locationHint = null;
		
		if ($locationHint1 != null && $locationHint1 != '')
		{
			$locationHint = $locationHint1;
		}
		if ($locationHint2 != null && $locationHint2 != '')
		{
			$locationHint .= $locationHint2;
		}
		$relay->setLocationHint($locationHint);
		
		return $relay;
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
			$result = $ls->transFO('m.shipping.general.opening-hours', array('ucf'), array(
				'hour1' => $this->formatHour($hours[0]), 'hour2' => $this->formatHour($hours[1])));
			
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
	
	/**
	 * @param string $hour
	 * @return string
	 */
	protected function formatHour($hour)
	{
		$h = substr($hour, 0, 2);
		$m = substr($hour, 2);
		return $h . ':' . $m;
	}
}