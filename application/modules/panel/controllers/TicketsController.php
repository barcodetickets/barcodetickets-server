<?php
/**
 * TicketsController
 *
 * @author
 * @version
 */
class Panel_TicketsController extends Zend_Controller_Action
{
	/**
	 * @var Bts_Model_Tickets
	 */
	private $Tickets = null;
	/**
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
			return $this->_helper->redirector->gotoRouteAndExit(
				array(
					'module' => 'panel',
					'controller' => 'login'));
		}
		$this->Tickets = new Bts_Model_Tickets();
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction ()
	{
		$this->view->tickets = $this->Tickets->getEverythingByEvent(
			$this->_getParam('event_id', 1));
		$_statuses = array();
		foreach($this->view->tickets as $t) {
			$_statuses[$t['ticket_id']] = $this->Tickets->getStatusText($t['status']);
		}
		$this->view->assign('ticketStatuses', $_statuses);
	}
}
