<?php
/**
 * mondialrelay_GetFrameStylesheetAction
 * @package modules.mondialrelay.actions
 */
class mondialrelay_GetFrameStylesheetAction extends shipping_GetFrameStylesheetAction
{
	/**
	 * @return string
	 */
	protected function getStylesheetName()
	{
		return 'modules.mondialrelay.frame';
	}
}