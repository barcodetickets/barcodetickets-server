<?php

/**
 * TicketsController
 *
 * @author
 * @version
 */
class Panel_EventsController extends Zend_Controller_Action
{

	/**
	 *
	 * @var Bts_Model_Events
	 */
	private $Events = null;

	/**
	 *
	 * @var Zend_Session_Namespace
	 */
	private $Session = null;

	public function init ()
	{
		$this->Session = new Zend_Session_Namespace('bts-auth');
		$this->_helper->layout()->setLayout('bts');
		// ALWAYS check if authenticated
		if (isset($this->Session->loggedIn) && $this->Session->loggedIn) {
			$this->view->assign('authSession', $this->Session)->assign(
					'isLoggedIn', true);
		} else {
			// redirect to login page if not logged in
			return $this->_helper->redirector->gotoSimpleAndExit('index', 
					'login', 'panel');
		}
	}

	/**
	 * The default action - show the home page
	 */
	public function indexAction ()
	{}

	public function createAction ()
	{
		$this->Events = new Bts_Model_Events();
		$CreateForm = new Panel_Form_CreateEvent(
				array(
						'action' => $this->_helper->url->url()
				));
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $CreateForm->isValid($_POST)) {
			$formValues = $CreateForm->getValues();
			try {
				$id = $this->Events->createEvent($formValues['name'], 
						$formValues['time'], $this->Session->userRow['user_id'], 
						'', $formValues['status']);
				$this->view->assign('formValues', $formValues);
				$this->view->assign('eventId', $id);
				return $this->render('create-successful');
			} catch (Zend_Exception $e) {
				$CreateForm->addError($e->getMessage());
			}
		}
		$this->view->form = $CreateForm;
	}
}