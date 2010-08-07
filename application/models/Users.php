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
	/**
	 * Checks whether the username is a valid one by checking its length (max.
	 * in database is 45) and looking for permitted characters (alphanumeric,
	 * period, hyphen and underscore).
	 * @param string $username
	 * @return int
	 */
	public function _checkUsername ($username)
	{
		if (strlen($username) > 45) return self::USERNAME_TOO_LONG;
		if (! preg_match('/^[[:alnum:]._-]{1,45}$/', $username)) return self::INVALID_USERNAME;
		else return self::VALID_USERNAME;
	}
	/**
	 * Changes a given user's password in the database.
	 *
	 * @param string $username
	 * @param string $old_password
	 * @param string $new_password
	 * @return boolean true on success and false on failure
	 */
	public function changePassword ($username, $old_password, $new_password)
	{
		$select = $this->UsersTable->select()->where('username = ?', $username);
		$row = $this->UsersTable->fetchRow($select);
		if (! $row instanceof Zend_Db_Table_Row_Abstract) {
			return false;
		}
		if (! $this->PasswordHash->checkPassword($old_password, $row->password)) {
			return false;
		}
		$row->password = $this->PasswordHash->hashPassword($new_password);
		return (boolean) $row->save();
	}
	/**
	 * Checks whether a given username and password combination is valid.
	 *
	 * @param string $username
	 * @param string $password
	 * @return boolean
	 */
	public function checkPassword ($username, $password)
	{
		$select = $this->UsersTable->select()->where('username = ?', $username);
		$row = $this->UsersTable->fetchRow($select);
		if (! $row instanceof Zend_Db_Table_Row_Abstract) {
			return false;
		}
		return $this->PasswordHash->checkPassword($password, $row->password);
	}
	/**
	 * Gets the user ID of the user associated with the given username.
	 *
	 * @param string $username
	 * @return int ID if successful or -1 if not found
	 */
	public function getUserId ($username)
	{
		$select = $this->UsersTable->select(false)->from($this->UsersTable, 'user_id')->where('username = ?', $username);
		$row = $this->UsersTable->fetchRow($select);
		if (is_null($row)) {
			return - 1;
		}
		return (int) $row->user_id;
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
			'status' => (is_integer($meta['status'])) ? $meta['status'] : 0 ,
			'nickname' => ($meta['nickname']) ? $meta['nickname'] : '' ,
			'openid' => ($meta['openid']) ? $meta['openid'] : '');
		if (isset($meta['nickname'])) $insertData['nickname'] = $meta['nickname'];
		$userId = $this->UsersTable->insert($insertData);
		return $userId;
	}
	/**
	 * Queries the database to check whether a given username exists.
	 *
	 * @param string $username
	 * @return boolean
	 */
	public function userExists ($username)
	{
		$select = $this->UsersTable->select(true)->where('username = ?', $username);
		$rows = $this->UsersTable->fetchAll($select);
		return ($rows->count() === 1);
	}
}