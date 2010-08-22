<?php
/**
 * BTS events table
 *
 * @author	Frederick Ding
 * @version $Id$
 */
class Bts_Model_DbTable_Events extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name
	 */
	protected $_name = 'bts_events';
	public function __construct ()
	{
		parent::__construct(array(
			'db' => 'db'));
	}
}

