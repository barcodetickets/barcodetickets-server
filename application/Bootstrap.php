<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initVersion ()
	{
		defined('BTS_VERSION') || define('BTS_VERSION', '0.0.3-alpha');
		defined('ZF_VERSION') || define('ZF_VERSION', Zend_Version::VERSION);
	}
	protected function _initDb ()
	{
		if ($this->hasPluginResource('db')) {
			$dbResource = $this->getPluginResource('db');
			Zend_Registry::set('db', $dbResource->getDbAdapter());
		}
	}
	protected function _initException ()
	{
		require 'Exception.php';
	}
	protected function _initConfig ()
	{
		$btsConfig = new Zend_Config_Ini(
		APPLICATION_PATH . '/configs/bts.default.ini', 'bts', true);
		if (file_exists(APPLICATION_PATH . '/configs/bts.ini')) {
			$btsConfig->merge(
			new Zend_Config_Ini(APPLICATION_PATH . '/configs/bts.ini', 'bts'));
		}
		Zend_Registry::set('bts-config', $btsConfig);
	}
}

