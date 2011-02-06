<?php
class Panel_ImportSalesController extends Zend_Controller_Action
{
	/**
	 * @var Panel_Model_SalesImporter
	 */
	private $Importer = null;
	/**
	 * @var Zend_Session_Namespace
	 */
	private $Session = null;
	public function init ()
	{
		$this->Session = new Zend_Session_Namespace('bts-auth');
		// ALWAYS check if authenticated
		if (isset($this->Session->loggedIn) && $this->Session->loggedIn) {
			$this->view->assign('authSession', $this->Session)->assign(
				'isLoggedIn', true);
		} else {
			// redirect to login page if not logged in
			return $this->_helper->redirector->gotoSimpleAndExit(
				'index', 'login', 'panel');
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
			return $this->_helper->redirector->gotoSimpleAndExit('index',
				'import-sales');
		}
		$form = $this->form();
		if ($form->isValid($_POST)) {
			$form->CSV->receive();
			$filename = $form->getElement('CSV')->getFileName();
			$csv = $this->Importer->readCsv($filename);
			$log = $this->Importer->activateTickets($form->getValue('Event'),
				$this->AuthSession->userId, $csv);
			$this->view->assign('result', 'ok');
			$this->view->assign('log', $log);
		} else {
			$this->view->assign('result', 'forminvalid');
		}
	}
	public function form ()
	{
		$form = new Zend_Form();
		$event = new Zend_Form_Element_Select('Event',
			array(
				'label' => 'Event',
				'required' => true));
		$listEvents = $this->Importer->getListEvents(
			$this->Session->userRow['user_id']);
		if (! is_null($listEvents)) {
			foreach ($listEvents as $eventRow) {
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
		$form->addElement($event)
			->addElement($upload)
			->addElement($submit)
			->addElement('hash', 'loginanticsrf',
			array(
				'decorators' => array(
					'ViewHelper')));
		$form->setAction(
			$this->view->url(
				array(
					'module' => 'panel',
					'controller' => 'import-sales',
					'action' => 'import-process')));
		return $form;
	}
}