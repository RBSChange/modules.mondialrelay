<?php
/**
 * mondialrelay_SelectRelayAction
 * @package modules.mondialrelay.actions
 */
class mondialrelay_SelectRelayAction extends shipping_SelectRelayAction
{
	protected function getMode($modeId)
	{
		return mondialrelay_persistentdocument_mondialrelaymode::getInstanceById($modeId);
	}
	
	protected function getRelayCodeParamName()
	{
		return 'relayCodeReference';
	}
	
	protected function getRelayCountryCodeParamName()
	{
		return 'relayCountryCode';
	}
	
	protected function getRelayNameParamName()
	{
		return 'relayName';
	}
	
	protected function getRelayAddress1ParamName()
	{
		return 'relayAddressLine1';
	}
	
	protected function getRelayAddress2ParamName()
	{
		return 'relayAddressLine2';
	}
	
	protected function getRelayAddress3ParamName()
	{
		return '';
	}
	
	protected function getRelayZipCodeParamName()
	{
		return 'relayZipCode';
	}
	
	protected function getRelayCityParamName()
	{
		return 'relayCity';
	}

}