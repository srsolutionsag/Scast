<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/User/class.xscaOrganisation.php');

/**
 * Class xscaUser
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaUser {

	/**
	 * @var array
	 */
	protected static $existing_cache = array();
	/**
	 * @var string
	 */
	protected $ext_account;
	/**
	 * @var int
	 */
	protected $ilias_user_id;
	/**
	 * @var ilObjUser
	 */
	protected $ilias_user_object = NULL;
	/**
	 * @var string
	 */
	protected $first_name = '';
	/**
	 * @var string
	 */
	protected $last_name = '';
	/**
	 * @var string
	 */
	protected $email = '';
	/**
	 * @var bool
	 */
	protected $is_system_account = false;
	/**
	 * @var xscaUser[]
	 */
	protected static $cache = array();
	//
	// Factory
	//
	/**
	 * @param ilObjUser $ilUser
	 *
	 * @internal param $ext_account
	 */
	protected function __construct(ilObjUser $ilUser) {
		if ($ilUser->getExternalAccount()) {
			$ext_account = $ilUser->getExternalAccount();
		} else {
			$ext_account = xscaConfig::get('default_sysaccount');
			$this->setIsSystemaccount(true);
		}
		$this->setExtAccount($ext_account);
		$this->setIliasUserId($ilUser->getId());
		$this->setIliasUserObject($ilUser);
	}


	/**
	 * @param ilObjUser $ilUser
	 *
	 * @internal param $ext_account
	 *
	 * @return xscaUser
	 */
	public static function getInstance(ilObjUser $ilUser = NULL) {
		if (!$ilUser) {
			global $ilUser;
		}
		$usr_id = $ilUser->getId();
		if (!isset(self::$cache[$usr_id])) {
			self::$cache[$usr_id] = new self($ilUser);
		}

		return self::$cache[$usr_id];
	}


	/**
	 * @param $ext_account
	 *
	 * @return xscaUser
	 */
	public static function getInstanceByExtAccount($ext_account) {
		$usr_id = self::getUsrIdForExtAccount($ext_account);
		if ($usr_id) {
			return self::getInstance(new ilObjUser($usr_id));
		} else {
			return self::getInstance();
		}
	}


	/**
	 * @param $ext_account
	 *
	 * @return int
	 */
	public static function getUsrIdForExtAccount($ext_account) {
		global $ilDB;
		$id = 0;
		$query = 'select usr_id FROM usr_data WHERE ext_account LIKE ' . $ilDB->quote($ext_account, 'text');
		$set = $ilDB->query($query);
		if ($set->numRows() > 0) {
			$res = $ilDB->fetchObject($set);
			$id = $res->usr_id;
		}

		return $id;
	}


	/**
	 * @return bool
	 */
	public function isAllowedToUseSwitchCast() {
		return xscaOrganisation::hasSysAccount($this->getExtAccount());
	}


	/**
	 * @return bool
	 */
	public function hasSystemAccount() {
		return xscaOrganisation::hasSysAccount($this->getExtAccount());
	}


	/**
	 * @return mixed|string
	 */
	public function getSystemAccount() {
		return xscaOrganisation::getSysAccountByExtAccount($this->getExtAccount());
	}


	/**
	 * @return string
	 */
	public function getOrganisation() {
		return xscaOrganisation::getOrganisationByExtAccount($this->getExtAccount());
	}


	/**
	 * @param $organization_domain
	 *
	 * @return bool
	 */
	public function isAllowedAsPublisher($organization_domain) {
		return $organization_domain == xscaOrganisation::getOrganisationByExtAccount($this->getExtAccount());
	}


	/**
	 * @param bool $sort
	 * @param bool $from_same_origin
	 *
	 * @return array
	 */
	public function getChannelsOfUser($sort = false, $from_same_origin = false) {
		$channels = xscaApi::users($this->getExtAccount())->channels()->get();
		$flds = array();
		foreach ($channels->channel as $ch) {
			$ch = (array)$ch;

			if ($from_same_origin) {
				//				$channel = xscaApi::users($this->getExtAccount())->channels($ch['ext_id'])->get();
			}
			$id = $ch['ext_id'];
			$name = $ch['name'];
			$flds[$id] = $name;
		}
		if ($sort) {
			natcasesort($flds);
		}

		return $flds;
	}


	/**
	 * @param ilObjUser $user
	 *
	 * @deprecated
	 */
	public static function registerUser(ilObjUser $user) {
		$xscaUser = self::getInstance($user);
		$xscaUser->create();
	}

	//
	// CRUD
	//
	/**
	 * @return bool
	 */
	public function create() {
		if ($this->exists()) {
			return false;
		}

		$api = xscaApi::users();
		$data = new xscaApiData('user');
		$data->setFields(array(
			'login' => (string)$this->getExtAccount(),
			'lastname' => (string)$this->getLastname(),
			'firstname' => (string)$this->getFirstname(),
			'email' => (string)$this->getEmail(),
			'organization_domain' => (string)$this->getOrganisation()
		));
		$api->post($data);
		xscaLog::getInstance()->write('User registered: ' . $this->getExtAccount(), xscaLog::LEVEL_DEBUG);

		return true;
	}


	/**
	 * @return bool
	 */
	public function exists() {
		if (!isset(self::$existing_cache[$this->getExtAccount()])) {
			$api = xscaApi::users($this->getExtAccount())->get();
			self::$existing_cache[$this->getExtAccount()] = ((string)$api->request_status != '400');
		}

		return self::$existing_cache[$this->getExtAccount()];
	}


	/**
	 * @deprecated
	 */
	public function update() {
		// http://help.switch.ch/cast/integration/api_specification.html
		// Update/delete users is currently not supported
		$this->create();
	}



	//
	// Setter & Getter
	//
	/**
	 * @param string $ext_account
	 */
	public function setExtAccount($ext_account) {
		$this->ext_account = $ext_account;
	}


	/**
	 * @return string
	 */
	public function getExtAccount() {
		return $this->ext_account;
	}


	/**
	 * @param int $ilias_user_id
	 */
	public function setIliasUserId($ilias_user_id) {
		$this->ilias_user_id = $ilias_user_id;
	}


	/**
	 * @return int
	 */
	public function getIliasUserId() {
		return $this->ilias_user_id;
	}


	/**
	 * @param \ilObjUser $ilias_user_object
	 */
	public function setIliasUserObject($ilias_user_object) {
		$this->ilias_user_object = $ilias_user_object;
	}


	/**
	 * @return \ilObjUser
	 */
	public function getIliasUserObject() {
		return $this->ilias_user_object;
	}


	/**
	 * @return ilObjUser
	 */
	public function il() {
		return $this->getIliasUserObject();
	}


	/**
	 * @return string
	 */
	public function getLastName() {
		return $this->il()->getLastname();
	}


	/**
	 * @return string
	 */
	public function getFirstname() {
		return $this->il()->getFirstname();
	}


	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->il()->getEmail();
	}


	/**
	 * @param boolean $is_systemaccount
	 */
	public function setIsSystemaccount($is_systemaccount) {
		$this->is_system_account = $is_systemaccount;
	}


	/**
	 * @return boolean
	 */
	public function getIsSystemaccount() {
		return $this->is_system_account;
	}
}

?>
