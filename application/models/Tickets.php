<?php
/**
 * Backend data manipulation for dealing with tickets.
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
class Bts_Model_Tickets
{
	/**
	 * An instance of the DbTable for tickets.
	 *
	 * @var Bts_Model_DbTable_Tickets
	 */
	protected $TicketsTable = null;
	public function __construct ()
	{
		$this->TicketsTable = new Bts_Model_DbTable_Tickets();
	}
	public function validate (array $params)
	{
		if (empty($params['event']) || empty($params['ticket'])) {
			throw new Bts_Exception(
				"Missing parameters (event and ticket needed)",
				Bts_Exception::TICKETS_PARAMS_BAD);
		}
		$select = $this->TicketsTable
			->select()
			->where('event_id = ?', $params['event'])
			->where('ticket_id = ?', $params['ticket'])
			->limit(1);
		if(!empty($params['batch'])) {
			$select->where('batch = ?', $params['batch']);
		}
		$row = $this->TicketsTable->fetchRow($select);
		if(!is_null($row) && $row->ticket_id == $params['ticket'])
			return true;
		else return false;
	}
}