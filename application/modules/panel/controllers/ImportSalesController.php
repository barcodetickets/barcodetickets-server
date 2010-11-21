<?php
class Panel_ImportSalesController extends Zend_Controller_Action
{
	/**
	 * @var Panel_Model_SalesImporter
	 */
	private $Importer = null;
	public function init ()
	{
		Zend_Session::start();
		$this->AuthSession = new Zend_Session_Namespace('bts-auth');
		$this->view->assign('serverPublicPath',
		Zend_Registry::get('bts-config')->get('serverPublicPath'));
		// ALWAYS check if authenticated
		if (! $this->AuthSession->loggedIn) {
			return $this->_redirect(
			$this->view->serverUrl() . str_replace($this->view->baseUrl(),
			$this->view->serverPublicPath,
			$this->view->url(
			array(
				'module' => 'panel',
				'controller' => 'login',
				'action' => 'index')) . '?redirect=' . str_replace(
			$this->view->baseUrl(), $this->view->serverPublicPath,
			$this->view->url(
			array(
				'module' => 'panel',
				'controller' => $this->_request->getControllerName(),
				'action' => $this->_request->getActionName())))));
		}
		$this->Importer = new Panel_Model_SalesImporter();
	}
	public function indexAction ()
	{
		$this->view->assign('form', $this->form());
	}
	public function importProcessAction ()
	{
		if (! $this->getRequest()->isPost()) {
			return $this->_redirect(
			$this->view->serverUrl() . str_replace($this->view->baseUrl(),
			$this->view->serverPublicPath,
			$this->view->url(
			array(
				'module' => 'panel',
				'controller' => 'import-sales',
				'action' => 'index'))));
		}
		$form = $this->form();
		if ($form->isValid($_POST)) {
			$form->CSV->receive();
			$filename = $form->getElement('CSV')->getFileName();
			$csv = $this->Importer->readCsv($filename);
			$log = $this->Importer->activateTickets($form->getValue('Event'), $this->AuthSession->userId, $csv);
			$this->view->assign('result', 'ok');
			$this->view->assign('log', $log);
		} else {
			$this->view->assign('result', 'forminvalid');
		}
	}
	public function form ()
	{
		$this->view->doctype('XHTML1_STRICT');
		$form = new Zend_Form();
		$event = new Zend_Form_Element_Select('Event', array(
			'label' => 'Event',
			'required' => true
		));
		$listEvents = $this->Importer->getListEvents($this->AuthSession->userId);
		if(!is_null($listEvents)) {
			foreach($listEvents as $eventRow) {
				$event->addMultiOption($eventRow->event_id, $eventRow->name);
			}
		}
		$upload = new Zend_Form_Element_File('CSV',
		array(
			'label' => 'CSV file',
			'required' => true));
		$submit = new Zend_Form_Element_Submit('Submit',
		array(
			'label' => 'Submit'));
		$form->addElement($event)->addElement($upload)->addElement($submit);
		$form->setAction(
		str_replace($this->view->baseUrl(), $this->view->serverPublicPath,
		$this->view->url(
		array(
			'module' => 'panel',
			'controller' => 'import-sales',
			'action' => 'import-process'))));
		return $form;
	}
}