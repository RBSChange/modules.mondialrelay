<?php
/**
 * mondialrelay_BlockMondialRelayModeConfigurationAction
 * @package modules.mondialrelay.lib.blocks
 */
class mondialrelay_BlockMondialRelayModeConfigurationAction extends shipping_BlockRelayModeConfigurationAction
{

	protected function getRelayModeService()
	{
		return mondialrelay_MondialrelaymodeService::getInstance();
	}
	
}