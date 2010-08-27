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
	protected $statuses = array(
		0 => 'inactive' ,  // unsold
		1 => 'active' ,  // sold but not checked in
		2 => 'checkedin' ,  // used
		3 => 'refunded' ,
		4 => 'lostunsold' ,  // lost by ticket sellers
		5 => 'lostsold' ,  // lost by ticket holder
		6 => 'stolen' ,  // same as #5 but specifies theft
		255 => 'invalidother'); // 255 = unknown status but nevertheless invalid -- must be last
	public function __construct ()
	{
		$this->TicketsTable = new Bts_Model_DbTable_Tickets();
	}
	/**

	 * @param Zend_Db_Table_Row_Abstract|array $params
	 * @throws Bts_Exception
	 */
	public function validate (array $params)
	{
		if (empty($params['event']) || empty($params['ticket'])) {
			throw new Bts_Exception(
				'Missing parameters (event and ticket needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		// fetch a row from the DB matching the event ID and ticket ID
		$select = $this->TicketsTable
			->select()
			->where('event_id = ?', $params['event'])
			->where('ticket_id = ?', $params['ticket'])
			->limit(1);
		// if a batch was provided too, use that to our advantage
		if (! empty($params['batch'])) {
			$select->where('batch = ?', $params['batch']);
		}
		$row = $this->TicketsTable
			->fetchRow($select);
		// send back the object if we got a valid row
		if (! is_null($row) && $row->ticket_id == $params['ticket']) return $row;
		else return false;
	}
	public function invalidate ($event = null, $ticket = null, array $params)
	{
		if (is_null($event)) {
			if (empty($params['event'])) throw new Bts_Exception(
				'Missing parameters (event needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
			else $event = $params['event'];
		}
		if (is_null($ticket)) {
			if (empty($params['ticket'])) throw new Bts_Exception(
				'Missing parameters (ticket needed)',
				Bts_Exception::TICKETS_PARAMS_BAD);
			else $ticket = $params['ticket'];
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
		$flipped = array_flip($this->statuses);
		if (isset($flipped[$text])) {
			return $flipped[$text];
		} else {
			return end($flipped); // last item must always be other
		}
	}
}