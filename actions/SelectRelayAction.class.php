<?php
/**
 * mondialrelay_SelectRelayAction
 * @package modules.mondialrelay.actions
 */
class mondialrelay_SelectRelayAction extends shipping_SelectRelayAction
{
	/**
	 * @param unknown_type $modeId
	 * @return mondialrelay_persistentdocument_mondialrelaymode
	 */
	protected function getMode($modeId)
	{
		return mondialrelay_persistentdocument_mondialrelaymode::getInstanceById($modeId);
	}
	
	/**
	 * @return string
	 */
	protected function getRelayCodeParamName()
	{
		return 'relayCodeReference';
	}
	
	/**
	 * @return string
	 */
	protected function getRelayCountryCodeParamName()
	{
		return 'relayCountryCode';
	}
	
	/**
	 * @return string
	 */
	protected function getRelayNameParamName()
	{
		return 'relayName';
	}
	
	/**
	 * @return string
	 */
	protected function getRelayAddress1ParamName()
	{
		return 'relayAddressLine1';
	}
	
	/**
	 * @return string
	 */
	protected function getRelayAddress2ParamName()
	{
		return 'relayAddressLine2';
	}
	
	/**
	 * @return string
	 */
	protected function getRelayAddress3ParamName()
	{
		return '';
	}
	
	/**
	 * @return string
	 */
	protected function getRelayZipCodeParamName()
	{
		return 'relayZipCode';
	}
	
	/**
	 * @return string
	 */
	protected function getRelayCityParamName()
	{
		return 'relayCity';
	}
}