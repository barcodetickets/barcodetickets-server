<?php
/**
 * Methods for managing the users of the system, used for authentication/
 * authorization and other system uses.
 *
 * @author	Frederick Ding
 * @version	$Id$
 */
class Bts_Model_Users
{
	/**
	 * An instance of the Users table.
	 * @var Bts_Model_DbTable_Users
	 */
	private $UsersTable = null;
	/**
	 * An instance of the phpass library class.
	 * @var BtsX_PasswordHash
	 */
	private $PasswordHash = null;
	const VALID_USERNAME = 1;
	const INVALID_USERNAME = - 200;
	const USERNAME_TOO_LONG = - 201;
	const NO_EMAIL = - 300;
	/**
	 * Constructs this Users model.
	 */
	public function __construct ()
	{
		$this->UsersTable = new Bts_Model_DbTable_Users();
		$this->PasswordHash = new BtsX_PasswordHash(8, TRUE);
	}
	public function _checkUsername ($username)
	{
		if (strlen($username) > 45) return self::USERNAME_TOO_LONG;
		if (! preg_match('/^[[:alnum:]._-]{1,45}$/', $username)) return self::INVALID_USERNAME;
		else return self::VALID_USERNAME;
	}
	/**
	 * Adds a new user to the BTS users table.
	 *
	 * The $meta parameter must contain, at minimum, the e-mail address of the
	 * new user. It may also contain the `status`, `nickname`, and `openid`.
	 *
	 * @param string $username
	 * @param string $password
	 * @param array $meta
	 * @return int
	 * @throws Bts_Exception
	 * @throws Zend_Db_Statement_Exception
	 */
	public function insertUser ($username, $password, array $meta = array())
	{
		if (! isset($meta['email'])) throw new Bts_Exception(
			'E-mail address was not provided', self::NO_EMAIL);
		switch ($this->_checkUsername($username)) {
			case self::VALID_USERNAME:
				break;
			case self::USERNAME_TOO_LONG:
				throw new Bts_Exception('Username is too long',
					self::USERNAME_TOO_LONG);
				break;
			case self::INVALID_USERNAME:
				throw new Bts_Exception(
					'Username contains invalid characters',
					self::INVALID_USERNAME);
		}
		$insertData = array(
			'username' => $username ,
			'password' => $this->PasswordHash->hashPassword($password) ,
			'email' => $meta['email'] ,
			'status' => (is_integer($meta['status'])) ? $meta['status'] : 0,
			'nickname' => ($meta['nickname']) ? $meta['nickname'] : '',
			'openid' => ($meta['openid']) ? $meta['openid'] : ''
		);
		if (isset($meta['nickname'])) $insertData['nickname'] = $meta['nickname'];
		$userId = $this->UsersTable->insert($insertData);
		return $userId;
	}
	public function checkPassword ($username, $password)
	{
		$select = $this->UsersTable->select();
		$select->where('username = ?', $username);
		$row = $this->UsersTable->fetchRow($select);
		if (! $row instanceof Zend_Db_Table_Row_Abstract) {
			return false;
		}
		return $this->PasswordHash->checkPassword($password, $row->password);
	}
	public function changePassword ($username, $old_password, $new_password)
	{
		$select = $this->UsersTable->select();
		$select->where('username = ?', $username);
		$row = $this->UsersTable->fetchRow($select);
		if (! $row instanceof Zend_Db_Table_Row_Abstract) {
			return false;
		}
		$row->password = $this->PasswordHash->hashPassword($new_password);
		return (boolean) $row->save();
	}
	public function userExists ($username)
	{
		$select = $this->UsersTable->select(true);
		$select->where('username = ?', $username);
		$rows = $this->UsersTable->fetchAll($select);
		return (boolean) ($rows->count() === 1);
	}
	public function getUserId ($username)
	{
		$select = $this->UsersTable->select(true);
		$select->where('username = ?', $username);
		$row = $this->UsersTable->fetchRow($select);
		return $row->user_id;
	}
}