<?php

/**
 *
 * @author	Frederick Ding
 * @package	Bts
 */
class Api_Bootstrap extends Zend_Application_Module_Bootstrap
{
	/**
	 * Disables PHP error output
	 * 
	 * It is imperative that no PHP-produced errors or warnings interfere with 
	 * the well-formed XML or JSON response.
	 */
	protected function _initPhp ()
	{
		ini_set('display_errors', '0'); // turn off output
		error_reporting(E_ALL | E_STRICT); // log them anyways
	}
	protected function _initHelpers ()
	{
		Zend_Controller_Action_HelperBroker::addPath(
				dirname(__FILE__) . '/controllers/helpers', 'Api_Action_Helper');
		Zend_Controller_Action_HelperBroker::getStaticHelper('formatResponse');
	}
}

