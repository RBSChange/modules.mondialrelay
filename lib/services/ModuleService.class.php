<?php
/**
 * @package modules.mondialrelay.lib.services
 */
class mondialrelay_ModuleService extends ModuleBaseService
{
	/**
	 * Singleton
	 * @var mondialrelay_ModuleService
	 */
	private static $instance = null;

	/**
	 * @return mondialrelay_ModuleService
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

}