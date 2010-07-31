<?php
/**
 * IndexController for the API module
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
require 'ControllerAbstract.php';
class Api_IndexController extends Api_Controller_Abstract
{
	public function testAction ()
	{
		$this->_helper->viewRenderer->setNoRender(false);
		$this->_response->setHeader('Content-Type', 'text/plain', true);
		$this->view->assign('timestamp', $this->clientAuth->validateTimestamp($this->_getParam('timestamp')));
		$this->view->assign('signature', $this->clientAuth->validateSignature($_SERVER['REQUEST_METHOD'], strtok($_SERVER['REQUEST_URI'], '?'), array(
			'sysName' => $this->_getParam('sysName') ,
			'signature' => $this->_getParam('signature') ,
			'timestamp' => $this->_getParam('timestamp'))));
	}
}

