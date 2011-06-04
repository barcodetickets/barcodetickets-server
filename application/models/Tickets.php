<?php
/**
 * Backend data manipulation for dealing with tickets.
 *
 * @author	Frederick Ding
 * @version	$Id$
 * @package	Bts
 */
class Bts_Model_Tickets
{
	/**
	 * An instance of the DbTable for tickets.
	 *
	 * @var Bts_Model_DbTable_Tickets
	 */
	protected $TicketsTable = null;
	/**
	 * An instance of the Barcodes helper model.
	 * @var Bts_Model_Barcodes
	 */
	protected $Barcodes = null;
	protected $statuses = array(
		0 => 'inactive',  // unsold
		1 => 'active',  // sold but not checked in
		2 => 'checkedin',  // used
		3 => 'refunded',
		4 => 'lostunsold',  // lost by ticket sellers
		5 => 'lostsold',  // lost by ticket holder
		6 => 'stolen',  // same as #5 but specifies theft
		7 => 'duplicate',  // was misnumbered and had to be reissued
		254 => 'unlimited',  // active permanently
		255 => 'invalidother'); // 255 = unknown status but nevertheless invalid -- must be last
	public function __construct ()
	{
		$this->TicketsTable = new Bts_Model_DbTable_Tickets();
	}
	/**
	 *
	 * @param int $event
	 * @param int $ticket
	 * @param int $attendee
	 * @param int $user
	 * @throws Bts_Exception
	 * @return int
	 */
	public function activate ($event, $ticket, $attendee, $user)
	{
		if (empty($event)) {
			throw new Bts_Exception('Missing parameters (event needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		if (empty($ticket)) {
			throw new Bts_Exception('Missing parameters (ticket needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		if (empty($attendee)) {
			throw new Bts_Exception('Missing parameters (attendee needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		if (empty($user)) {
			throw new Bts_Exception(
				'A user ID is needed to authorize this action',
				Bts_Exception::TICKETS_UNAUTHORIZED);
		}
		// TODO: verify the user is authorized to do so
		// fetch the ticket row from the DB
		$select = $this->TicketsTable->select()
			->where('event_id = ?', $event)
			->where('ticket_id = ?', $ticket)
			->limit(1);
		$row = $this->TicketsTable->fetchRow($select);
		if (! is_null($row)) {
			if ($row->status != $this->getStatusCode('inactive')) {
				if ($row->status == $this->getStatusCode('active'))
					return - 2; // already active
				// integer response that indicates current status
				else
					return $row->status;
			}
			// modify it to active status
			$row->seller_id = (int) $user;
			$row->status = $this->getStatusCode('active');
			$row->attendee_id = (int) $attendee;
			try {
				$row->save();
			} catch (Zend_Db_Statement_Exception $e) {
				// foreign key failed
				return - 3; // bad attendee
			}
			return $this->getStatusCode('active'); // only $this->getStatusCode('active') means success
		} else
			return - 1; // non-existent row
	}
	public function insert ($batch, $event, $status = 0)
	{
		if (empty($event) || empty($batch)) {
			throw new Bts_Exception(
				'Missing parameters (event and batch needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		// check authentication in future
		if (is_int($status) && ! in_array($status, $this->statuses)) {
			throw new Bts_Exception('Bad status code',
				Bts_Exception::TICKETS_PARAMS_BAD);
		} else
			if (is_string($status)) {
				$status = $this->getStatusCode($status);
			}
		// first create this row
		$newTicket = $this->TicketsTable->createRow(
			array(
				'batch' => (int) $batch,
				'event_id' => (int) $event,
				'status' => $status));
		$ticketId = (string) $newTicket->save();
		if (is_null($this->Barcodes)) {
			$this->Barcodes = Bts_Model_Barcodes::getInstance();
		}
		// then update the row with the checksum
		$checksum = $this->Barcodes->generateChecksum($event, $batch,
			$ticketId);
		$newTicket->checksum = $checksum;
		return $newTicket->save();
	}
	/**
	 * @param array $params
	 * @throws Bts_Exception
	 * @return Zend_Db_Table_Row_Abstract|false
	 */
	public function validate (array $params)
	{
		if (empty($params['event']) || empty($params['ticket'])) {
			throw new Bts_Exception(
				'Missing parameters (event and ticket needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		// fetch a row from the DB matching the event ID and ticket ID
		$select = $this->TicketsTable->select()
			->where('event_id = ?', $params['event'])
			->where('ticket_id = ?', $params['ticket'])
			->limit(1);
		// if a batch was provided too, use that to our advantage
		if (! empty($params['batch'])) {
			$select->where('batch = ?', $params['batch']);
		}
		// we don't use the checksum because that's validated separately by
		// the Barcodes model. we COULD.
		$row = $this->TicketsTable->fetchRow($select);
		// send back the object if we got a valid row
		if (! is_null($row) && $row->ticket_id == $params['ticket'])
			return $row;
		else
			return false;
	}
	/**
	 *
	 * @param array $params
	 * @throws Bts_Exception
	 * @return Zend_Db_Table_Row_Abstract|array|false
	 */
	public function checkIn (array $params)
	{
		if (empty($params['event']) || empty($params['ticket'])) {
			throw new Bts_Exception(
				'Missing parameters (event and ticket needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		// fetch a row from the DB matching the event ID and ticket ID
		$select = $this->TicketsTable->select()
			->where('event_id = ?', $params['event'])
			->where('ticket_id = ?', $params['ticket'])
			->limit(1);
		// if a batch was provided too, use that to our advantage
		if (! empty($params['batch'])) {
			$select->where('batch = ?', $params['batch']);
		}
		// we don't use the checksum because that's validated separately by
		// the Barcodes model. we COULD.
		$row = $this->TicketsTable->fetchRow($select);
		// if we got a valid row, mark it as checked in
		if (! is_null($row) && $row->ticket_id == $params['ticket']) {
			if ($row->status != $this->getStatusCode('active') &&
				 $row->status != $this->getStatusCode('unlimited')) {
					// oops, bad ticket -- probably already activated
					$row->setReadOnly(true);
					return array(
						$row);
				}
				if ($row->status != $this->getStatusCode('unlimited')) {
					$row->status = $this->getStatusCode('checkedin');
				}
				$row->checkin_time = new Zend_Db_Expr('UTC_TIMESTAMP()');
				$row->save();
				$row->setReadOnly(true);
				return $row;
			} else
				return false;
		}
		public function invalidate ($event = null, $ticket = null, array $params)
		{
			if (is_null($event)) {
				if (empty($params['event']))
					throw new Bts_Exception('Missing parameters (event needed)',
						Bts_Exception::TICKETS_PARAMS_BAD);
				else
					$event = $params['event'];
			}
			if (is_null($ticket)) {
				if (empty($params['ticket']))
					throw new Bts_Exception('Missing parameters (ticket needed)',
						Bts_Exception::TICKETS_PARAMS_BAD);
				else
					$ticket = $params['ticket'];
			}
			if (empty($params['reasonCode'])) {
				throw new Bts_Exception(
					'A reason must be provided for invalidation',
					Bts_Exception::TICKETS_PARAMS_BAD);
			}
			if (empty($params['userId'])) {
				throw new Bts_Exception(
					'A user ID is needed to authorize this action',
					Bts_Exception::TICKETS_UNAUTHORIZED);
			}
			// ensure that there is at least a key named ['comment'] even if empty
			$params['comment'] = (empty($params['comment'])) ? '' : $params['comment'];
				// TODO
		}
		public function getMaxBatch ($event)
		{
			if (empty($event)) {
				throw new Bts_Exception('Missing parameters (event needed)',
					Bts_Exception::TICKETS_PARAMS_BAD);
			}
			$select = $this->TicketsTable->select(false)
				->from('bts_tickets',
				array(
					new Zend_Db_Expr('MAX(batch)')))
				->where('event_id = ?', $event);
			$result = $select->query()->fetchColumn();
			if (empty($result)) {
				return 0;
			} else
				return (int) $result;
		}
		public function getBatches ($event)
		{
			if (empty($event)) {
				throw new Bts_Exception('Missing parameters (event needed)',
					Bts_Exception::TICKETS_PARAMS_BAD);
			}
			$select = $this->TicketsTable->select(false)
				->from('bts_tickets', 'batch')
				->where('event_id = ?', $event)
				->group('batch');
			$result = $select->query()->fetchAll();
			return $result;
		}
		public function getByAttendee ($attendee)
		{
			if (empty($attendee)) {
				throw new Bts_Exception('Missing parameters (attendee needed)',
					Bts_Exception::TICKETS_PARAMS_BAD);
			}
			$select = $this->TicketsTable->select(true)->where(
				'attendee_id = ?', $attendee);
			$rows = $this->TicketsTable->fetchAll($select);
			foreach ($rows as $row) {
				$row->setReadOnly(true);
			}
			return $rows;
		}
		public function getEverythingByEvent ($event)
		{
			if (empty($event)) {
				throw new Bts_Exception('Missing parameters (event needed)',
					Bts_Exception::TICKETS_PARAMS_BAD);
			}
			$select = $this->TicketsTable->getAdapter()
				->select()
				->from(array(
				't' => 'bts_tickets'))
				->where('t.event_id = ?', (int) $event)
				->joinLeft(array(
				'a' => 'bts_attendees'), 't.attendee_id = a.attendee_id',
				array(
					'first_name',
					'last_name'));
			$rows = $select->query()->fetchAll();
			return $rows;
		}
		public function getForPdf ($event, $batch, $html = true)
		{
			if (empty($event)) {
				throw new Bts_Exception('Missing parameters (event needed)',
					Bts_Exception::TICKETS_PARAMS_BAD);
			}
			if (empty($batch)) {
				throw new Bts_Exception('Missing parameters (batch needed)',
					Bts_Exception::TICKETS_PARAMS_BAD);
			}
			$select = $this->TicketsTable->getAdapter()
				->select()
				->from('bts_tickets',
				array(
					'event_id',
					'batch',
					'ticket_id',
					'checksum' => 'UPPER(checksum)',
					'label' => ($html) ? new Zend_Db_Expr(
						'CONCAT(event_id,"-",batch,"-<b>",ticket_id,"</b>-",UPPER(checksum))') : new Zend_Db_Expr(
						'CONCAT_WS("-",event_id,batch,ticket_id,UPPER(checksum))')))
				->where('event_id = ?', (int) $event)
				->where('batch = ?', (int) $batch);
			$rows = $select->query()->fetchAll();
			return $rows;
		}
		public function getStatusText ($status)
		{
			if (isset($this->statuses[$status])) {
				return $this->statuses[$status];
			} else {
				return end($this->statuses);
			}
		}
		public function getStatusCode ($text)
		{
			$search = array_search($text, $this->statuses);
			if ($search === false) {
				// not found, return the 'other' status code
				return array_search('invalidother',
					$this->statuses);
			} else {
				return $search;
			}
		}
	}