<?php
/**
 * BTS users table
 *
 * @author	Frederick Ding
 * @version $Id$
 * @package	Bts
 */
class Bts_Model_DbTable_Users extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name
	 */
	protected $_name = 'bts_users';
	public function __construct ()
	{
		parent::__construct(array(
			'db' => 'db'));
	}
}

