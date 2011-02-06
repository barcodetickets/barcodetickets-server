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
			return $this->_helper->redirector->gotoSimpleAndExit(
				'index', 'login', 'panel');
		}
		$this->Tickets = new Bts_Model_Tickets();
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction ()
	{
		$requestedEvent = $this->_getParam('event_id');
		$Events = new Bts_Model_Events();
		if (is_null($requestedEvent)) {
			// no event selected, show selection
			$listEvents = $Events->getEventsForUser(
				$this->Session->userRow['user_id']);
			$this->view->assign('listEvents', $listEvents);
			return $this->render('pick-event');
		} else {
			// TODO: check access level
			$event = $Events->getEvent($requestedEvent);
			if (is_null($event)) {
				return $this->render('bad-event');
			}
			$this->view->eventRow = $event;
		}
		$this->view->tickets = $this->Tickets->getEverythingByEvent(
			$requestedEvent);
		$_statuses = array();
		foreach ($this->view->tickets as $t) {
			$_statuses[$t['ticket_id']] = $this->Tickets->getStatusText(
				$t['status']);
		}
		$this->view->assign('ticketStatuses', $_statuses);
	}
	public function makeBarcodesAction ()
	{
		$requestedEvent = $this->_getParam('event_id');
		$Events = new Bts_Model_Events();
		if (is_null($requestedEvent)) {
			// no event selected, show selection
			$listEvents = $Events->getEventsForUser(
				$this->Session->userRow['user_id']);
			$this->view->assign('listEvents', $listEvents);
			return $this->render('pick-event');
		} else {
			// TODO: check access level
			$event = $Events->getEvent($requestedEvent);
			if (is_null($event)) {
				return $this->render('bad-event');
			}
			$this->view->eventRow = $event;
		}
		$this->view->tickets = $this->Tickets->getEverythingByEvent(
			$requestedEvent);
		$_barcodes = array();
		$Barcodes = Bts_Model_Barcodes::getInstance();
		foreach ($this->view->tickets as $t) {
			$_barcodes[$t['ticket_id']] = $Barcodes->encryptBarcode(
				$t['event_id'], $t['batch'], $t['ticket_id']);
		}
		$this->view->assign('ticketBarcodes', $_barcodes);
		// allow for CSV download
		if ($this->_getParam('format') == 'csv') {
			$this->_response->setHeader('Content-Type', 'text/csv');
			$this->_response->setHeader('Content-Disposition', 'attachment; filename=' . $requestedEvent . '.csv');
			$this->_helper->layout()->disableLayout();
			return $this->render('make-barcodes-csv');
		}
	}
}
