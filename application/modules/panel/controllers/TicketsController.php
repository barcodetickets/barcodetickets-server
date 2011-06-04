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
	public function manageBarcodesAction ()
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
			$this->_response->setHeader('Content-Disposition',
				'attachment; filename=' . $requestedEvent . '.csv');
			$this->_helper->layout()->disableLayout();
			return $this->render('manage-barcodes-csv');
		}
	}
	public function generateBarcodesAction ()
	{
		$requestedEvent = $this->_getParam('event_id');
		$Events = new Bts_Model_Events();
		if (is_null($requestedEvent)) {
			// no event selected, take us back to barcodes start
			return $this->_helper->redirector->gotoSimpleAndExit(
				'make-barcodes', 'tickets', 'panel');
		} else {
			// TODO: check access level
			$event = $Events->getEvent($requestedEvent);
			if (is_null($event)) {
				return $this->render('bad-event');
			}
			$this->view->eventRow = $event;
		}
		$maxBatch = $this->Tickets->getMaxBatch($requestedEvent);
		$Generate = new Panel_Form_Generate(
			array(
				'action' => $this->_helper->url->url(),
				'batchNumber' => $maxBatch + 1));
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $Generate->isValid($_POST)) {
			$formValues = $Generate->getValues();
			try {
				$Events->generateBatch($requestedEvent, $formValues['count'],
					$this->Session->userRow['user_id'], $this->Tickets);
				$this->view->assign('count', $formValues['count']);
				return $this->render('generate-successful');
			} catch (Zend_Exception $e) {
				$Generate->addError($e->getMessage());
			}
		}
		$this->view->form = $Generate;
	}
	public function generatePdfAction ()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender(true);
		$requestedEvent = $this->_getParam('event_id');
		$requestedBatch = $this->_getParam('batch_id');
		$Events = new Bts_Model_Events();
		if (is_null($requestedEvent)) {
			// no event selected, show selection
			$listEvents = $Events->getEventsForUser(
				$this->Session->userRow['user_id']);
			$this->view->assign('listEvents', $listEvents);
			$this->_helper->layout()->enableLayout();
			return $this->render('pick-event-generate');
		} elseif(is_null($requestedBatch)) {
			// no batch selected, show selection
			$event = $Events->getEvent($requestedEvent);
			$batches = $this->Tickets->getBatches($requestedEvent);
			$this->view->assign('event', $event);
			$this->view->assign('listBatches', $batches);
			$this->_helper->layout()->enableLayout();
			return $this->render('pick-batch-generate');
		} else {
			// TODO: check access level
			$event = $Events->getEvent($requestedEvent);
			if (is_null($event)) {
				$this->_helper->layout()->enableLayout();
				return $this->render('bad-event');
			}
		}
		$tickets = $this->Tickets->getForPdf($requestedEvent, $requestedBatch);
		$_barcodes = array();
		$Barcodes = Bts_Model_Barcodes::getInstance();
		foreach ($tickets as $t) {
			$_barcodes[$t['ticket_id']] = array(
				$Barcodes->encryptBarcode($t['event_id'], $t['batch'],
					$t['ticket_id']), $t['label']);
		}
		$PdfGenerator = new Panel_Model_PdfGenerator($_barcodes);
		$PdfGenerator->render();
	}
}
