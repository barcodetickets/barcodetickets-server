<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initAutoload()
	{
    	$autoloader = new Zend_Application_Module_Autoloader(array(
    		'namespace' => 'Bts',
       		'basePath' => dirname(__FILE__),
       	));
       	return $autoloader;
    }
	protected function _initDb ()
	{
		if($this->hasPluginResource('db')) {
			$dbResource = $this->getPluginResource('db');
			Zend_Registry::set('db', $dbResource);
		}
	}
}

