<?php
/**
 * @author Frederick
 *
 *
 */
class Panel_Model_SalesImporter
{
	/**
	 * @var Bts_Model_Tickets
	 */
	private $Tickets = null;
	/**
	 * @var Bts_Model_Attendees
	 */
	private $Attendees = null;
	public function __construct ()
	{
		$this->Tickets = new Bts_Model_Tickets();
		$this->Attendees = new Bts_Model_Attendees();
	}
	public function readCsv ($filename)
	{
		if (! file_exists($filename)) {
			throw new Bts_Exception('File not found');
		}
		$file = @fopen($filename, 'r');
		if ($file === false)
			throw new Bts_Exception('Invalid CSV filename');
		$data = array();
		while (! feof($file)) {
			$row = fgetcsv($file);
			// ticket ID, first name, last name
			if (is_array($row) && count($row) == 3)
				$data[] = $row;
		}
		fclose($file);
		return $data;
	}
	public function activateTickets ($event, $user, array $importedData)
	{
		$log = '';
		if (! is_numeric($event)) {
			throw new Bts_Exception('Event must be a number');
		}
		$event = (int) $event;
		if (! is_numeric($user)) {
			throw new Bts_Exception('User ID must be a number');
		}
		$user = (int) $user;
		foreach ($importedData as $row) {
			if (count($row) != 3) {
				$log .= "Something went wrong with $row.\n";
				continue;
			}
			// query the DB to see if there is an attendee by this name
			$exists = $this->Attendees->findByName($row[1], $row[2]);
			if ($exists instanceof Zend_Db_Table_Rowset_Abstract) {
				if ($exists->count() == 0) {
					// not found; make one
					$attendeeId = $this->Attendees->create(
					$row[1], $row[2], 'bts-gen-' . sha1($row[1] . ' ' . $row[2]));
					if ($attendeeId == - 1) {
						// failed!
						$log .= 'Attendee ' . $row[1] .
						 ' ' . $row[2] . " could not be created.\n";
						$log .= 'Ticket ' . $row[0] . " could not be activated.";
						continue;
					}
				} else
					if ($exists->count() >= 1) {
						// found
						$attendeeId = $exists[0]->attendee_id;
					}
				// now activate tickets
				$activation = $this->Tickets->activate($event,
				(int) $row[0], $attendeeId, $user);
				if ($activation != $this->Tickets->getStatusCode('active')) {
					$log .= 'Ticket ' . $row[0] .
					 " was not activated successfully. Code $activation\n";
				}
			} else {
				$log .= "Something went wrong with {$row[0]} {$row[1]} {$row[2]}.\n";
			}
		}
		return $log;
	}
}