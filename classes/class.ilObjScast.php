<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/Repository/classes/class.ilObjectPlugin.php');
require_once('class.ilScastPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Group/class.xscaGroup.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Api/class.xscaApiData.php');
require_once('./Modules/Course/classes/class.ilCourseParticipants.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Services/class.xscaLog.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/User/class.xscaUser.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Api/class.xscaApi.php');

/**
 * Class ilObjScast
 *
 * @author Martin Studer <ms@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 *
 * $Id$
 */
class ilObjScast extends ilObjectPlugin {

	const TYPE = 'xsca';
	/**
	 * @var bool
	 */
	protected $online = false;
	/**
	 * @var string
	 */
	protected $upload_form;
	/**
	 * @var string
	 */
	protected $ext_id;
	/**
	 * @var int
	 */
	protected $lifetime_of_content_in_month;
	/**
	 * @var int
	 */
	protected $estimatet_content_in_hours;
	/**
	 * @var string
	 */
	protected $department;
	/**
	 * @var string
	 */
	protected $license;
	/**
	 * @var int
	 */
	protected $discipline_id;
	/**
	 * @var bool
	 */
	protected $inviting_possible = false;
	/**
	 * @var bool
	 */
	protected $streaming_only = true;
	/**
	 * @var bool
	 */
	protected $ivt = false;
	/**
	 * @var array
	 */
	protected $producers = array();
	/**
	 * @var bool
	 */
	protected $allow_annotations = false;
	/**
	 * @var string
	 */
	protected $organisation_domain = '';
	/**
	 * @var string
	 */
	protected $introduction_text = '';
	/**
	 * @var int
	 */
	protected $course_id = 0;
	/**
	 * @var string
	 */
	protected $sys_account;
	/**
	 * @var string
	 */
	protected $channel_kind;
	/**
	 * @var string
	 */
	protected $edit_link;
	/**
	 * @var bool
	 */
	protected $show_upload_token = false;
	/**
	 * @var bool
	 */
	protected static $admin_sync = false;
	/**
	 * @var bool
	 */
	protected static $member_sync = false;
	/**
	 * @var bool
	 */
	protected static $loaded = false;
	//
	// Globals
	//
	/**
	 * @var xscaLog
	 */
	protected $log;


	/**
	 * @param int  $a_ref_id
	 * @param bool $use_cache
	 */
	public function __construct($a_ref_id = 0, $use_cache = false) {
		global $ilDB, $ilUser;
		/**
		 * @var $ilDB   ilDB
		 * @var $ilUser ilObjUser
		 */
		$this->use_cache = $use_cache;
		parent::__construct($a_ref_id);
		$this->xsca_user = xscaUser::getInstance($ilUser);
		$this->organisation_domain = xscaOrganisation::getSysAccountByExtAccount($this->getSysAccount());
		$this->pl = ilScastPlugin::getInstance();
		$this->db = $ilDB;
		$this->log = xscaLog::getInstance();
	}


	/**
	 * @param $ext_id
	 *
	 * @return ilObjScast
	 */
	public static function getInstanceForExtId($ext_id) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$set = $ilDB->query('SELECT * FROM rep_robj_xsca_data ' . ' WHERE ext_id = ' . $ilDB->quote($ext_id, 'text'));
		$rec = $ilDB->fetchObject($set);

		return ilObjectFactory::getInstanceByObjId($rec->id);
	}


	/**
	 * @param $ext_id
	 *
	 * @return array
	 */
	public static function getAllRefIdsForExtId($ext_id) {
		global $ilDB;

		/**
		 * @var $ilDB ilDB
		 */
		$ref_ids = array();
		$set = $ilDB->query('SELECT * FROM rep_robj_xsca_data ' . ' WHERE ext_id = ' . $ilDB->quote($ext_id, 'text'));
		while ($rec = $ilDB->fetchObject($set)) {
			$ref_ids = array_merge($ref_ids, ilObject2::_getAllReferences($rec->id));
		}

		return $ref_ids;
	}


	/**
	 * Get type.
	 */
	final function initType() {
		$this->setType(self::TYPE);
	}


	/**
	 * @return int
	 */
	public function lookupCourseId() {
		/**
		 * @var $tree ilTree
		 */
		global $tree;
		if ($this->getRefId() > 0) {
			$path_ids = $tree->getPathId($this->getRefId());
		}

		if (is_array($path_ids) AND count($path_ids) > 0) {
			foreach ($path_ids as $ref_id) {
				if (ilObject::_lookupType($ref_id, true) == 'crs') {
					$id = ilObject::_lookupObjectId($ref_id);
					break;
				}
			}
		}

		return $id;
	}


	/**
	 * @param null $arr_filter
	 *
	 * @return array
	 */
	public function getClips($arr_filter = NULL) {
		return xscaClip::getAllInstancesForChannel($this, $arr_filter);
	}


	/**
	 * @description To create a channel we need an aai account that is allowed to register a new channel.
	 *              Thus the first choice is the aai account of the current user,
	 *              if he doesn't have an account we use the system account.
	 */
	public function doCreate() {
		$time = microtime();
		$this->log->write('Channel created started' . $time, xscaLog::LEVEL_DEBUG);
		$this->setSysAccount($this->xsca_user->getSystemAccount());
		$this->organisation_domain = $this->xsca_user->getOrganisation();
		$this->xsca_user->create();

		if ($this->getStreamingOnly()) {
			$configuration_id = xscaConfig::get('scast_streaming_configuration_id');
		} else {
			$configuration_id = xscaConfig::get('scast_configuration_id');
		}
		// if there is no system account for the current user
		if ($this->getSysAccount() == '') {
			ilUtil::sendFailure($this->pl->txt('your_organisation_is_not_assigned_on_this_ilias_installation'));
			$this->doDelete();

			return;
		}
		if ($this->getExtId() == '') {

			$data = new xscaApiData('channel');
			$data->setFields(array(
				'name' => (string)$this->getTitle(),
				'discipline_id' => (int)$this->getDisciplineId(),
				'license' => (string)$this->getLicense(),
				'author' => (string)$this->xsca_user->getIliasUserObject()->getFirstName() . ' ' . $this->xsca_user->getIliasUserObject()
						->getLastName(),
				'department' => (string)$this->getDepartment(),
				'organization_domain' => (string)$this->organisation_domain,
				'access' => (string)'external_authority',
				'external_authority_id' => (int)xscaConfig::get('scast_external_authority_id'),
				'export_metadata' => (int)0,
				'template_id' => (int)$configuration_id,
				'estimated_content_in_hours' => (int)$this->getEstimatetContentInHours(),
				'lifetime_of_content_in_months' => (int)$this->getLifetimeOfContentinMonth(),
				'sort_criteria' => (string)'recording_date',
				'auto_chapter' => (int)1,
				'allow_annotations' => $this->getAllowAnnotations() ? 'yes' : 'no',
				'kind' => (string)$this->getChannelKind()
			));
			$obj_channel = xscaApi::users($this->xsca_user->getExtAccount())->channels()->post($data);

			$this->log->write('obj_channel ' . $obj_channel, xscaLog::LEVEL_PRODUCTION);
			if ($obj_channel->ext_id != '') {
				$this->setExtId($obj_channel->ext_id);
			} else {
				throw new Exception('Scast no Ext-ID given');
			}
		} else {
			$this->doUpdate(true);
		}
		$this->addProducerByExtId($this->getSysAccount(), false);
		$this->setOnline(true);
		$this->db->insert('rep_robj_xsca_data', array(
			'id' => array( 'integer', $this->getId() ),
			'ext_id' => array( 'text', $this->getExtId() ),
			'is_online' => array( 'integer', $this->getOnline() ),
			'is_ivt' => array( 'integer', $this->getIvt() ),
			'inviting' => array( 'integer', $this->getInvitingPossible() ),
			'organization_domain' => array( 'text', $this->organisation_domain ),
			'introduction_text' => array( 'clob', $this->getIntroductionText() ),
			'show_upload_token' => array( 'integer', $this->getShowUploadToken() ),
		));
		$time = microtime() - $time;
		$this->log->write('Channel created' . $time, xscaLog::LEVEL_DEBUG);
	}


	public function doRead() {
//		if (!self::$loaded) {
			if (!is_object($this->db)) {
				global $ilDB;
				$this->db = $ilDB;
				$this->pl = ilScastPlugin::getInstance();
			}
			$set = $this->db->query('SELECT * FROM rep_robj_xsca_data ' . ' WHERE id = ' . $this->db->quote($this->getId(), 'integer'));
			while ($rec = $this->db->fetchAssoc($set)) {
				$this->setExtId($rec['ext_id']);
				$this->setIvt($rec['is_ivt']);
				$this->setInvitingPossible($rec['inviting']);
				$this->setSysAccount(xscaOrganisation::getSysAccountByOrganisation($rec['organization_domain']));
				$this->setIntroductionText($rec['introduction_text']);
				$this->setOrganisationDomain($rec['organization_domain']);
				$this->setOnline($rec['is_online']);
				$this->setShowUploadToken($rec['show_upload_token']);
			}
			if (!$this->getExtId()) {
				ilUtil::sendFailure($this->pl->txt('msg_no_ext_id_given'));

				return false;
			}
			// Channel
			if ($this->use_cache) {
				$ch = xscaApi::users($this->getSysAccount())->channels($this->getExtId())->edit()->getFromCache();
			} else {
				$ch = xscaApi::users($this->getSysAccount())->channels($this->getExtId())->edit()->get();
			}
			// Daten aus SwitchCast anpassen
			$this->setEstimatetContentInHours((int)$ch->estimated_content_in_hours);
			$this->setLifetimeOfContentinMonth((int)$ch->lifetime_of_content_in_months);
			$this->setDepartment((string)$ch->department);
			$this->setAllowAnnotations(((string)trim($ch->allow_annotations) == 'yes'));
			$this->setDisciplineId((int)$ch->discipline_id);
			$this->setLicense((string)$ch->license);
			$this->setStreamingOnly((int)$ch->template_id == (int)xscaConfig::get('scast_streaming_configuration_id'));
			// Producers setzen
			if (count($ch->producers->user) > 0) {
				foreach ($ch->producers->user as $value) {
					$this->setProducer((string)$value->login);
				}
			}
			$this->setUploadForm($ch->urls->url[1]);
			$this->setEditLink($ch->urls->url[4]);
			// Course-ID setzen
			$this->setCourseId($this->lookupCourseId());
			if ($this->xsca_user) {
				$this->xsca_user->create();
			}
			$this->syncAdmins();
			$this->syncMembers();
//			self::$loaded = true;
//		}
	}


	/**
	 * @param bool $switch_only
	 *
	 * @return bool|void
	 */
	public function doUpdate($switch_only = false) {
		// Beim Updaten wird der bei der Erstellung gesetzte Sys-Account verwendet
		if ($this->getStreamingOnly()) {
			$configuration_id = xscaConfig::get('scast_streaming_configuration_id');
		} else {
			$configuration_id = xscaConfig::get('scast_configuration_id');
		}
		// Beim Updaten wird keine organization_domain mehr gesetzt. Diese wird nur beim Erstellen gesetzt.
		$data = new xscaApiData('channel');
		$data->setFields(array(
			'name' => (string)$this->getTitle(),
			'discipline_id' => (int)$this->getDisciplineId(),
			'license' => (string)$this->getLicense(),
			'autor' => (string)$this->xsca_user->getIliasUserObject()->getFirstName() . ' ' . $this->xsca_user->getIliasUserObject()->getLastName(),
			'department' => (string)$this->getDepartment(),
			'access' => (string)'external_authority',
			'external_authority_id' => (int)xscaConfig::get('scast_external_authority_id'),
			'export_metadata' => (int)0,
			'template_id' => (int)$configuration_id,
			'estimated_content_in_hours' => (int)$this->getEstimatetContentInHours(),
			'lifetime_of_content_in_months' => (int)$this->getLifetimeOfContentinMonth(),
			'sort_criteria' => (string)'recording_date',
			'auto_chapter' => (int)1,
			'allow_annotations' => $this->getAllowAnnotations() ? 'yes' : 'no'
		));
		xscaApi::users($this->xsca_user->getExtAccount())->channels($this->getExtId())->put($data);
		if (!$switch_only) {
			$this->db->update('rep_robj_xsca_data', array(
				'is_ivt' => array( 'integer', $this->getIvt() ),
				'is_online' => array( 'integer', $this->getOnline() ),
				'inviting' => array( 'integer', $this->getInvitingPossible() ),
				'introduction_text' => array( 'clob', $this->getIntroductionText() ),
				'show_upload_token' => array( 'integer', $this->getShowUploadToken() ),
			), array(
				'id' => array( 'integer', $this->getId() )
			));
		}
		xscaApiCache::flush($this->getId());

		return true;
	}


	public function doDelete() {
		if (!$this->hasReferencedChannels()) {
			xscaApi::users($this->xsca_user->getExtAccount())->channels($this->getExtId())->delete();
		}
		$this->db->manipulate('DELETE FROM rep_robj_xsca_data WHERE id = ' . $this->db->quote($this->getId(), 'integer'));
	}


	/**
	 * @param $a_target_id
	 * @param $a_copy_id
	 * @param $new_obj
	 */
	public function doClone($a_target_id, $a_copy_id, $new_obj) {
		$new_obj->setExtId($this->getExtId());
		$new_obj->setIvt($this->getIvt());
		$new_obj->update();
	}


	//
	// Common Methods
	//	
	/**
	 * @return array
	 */
	public function getAllDisciplines() {
		$new = xscaApi::users($this->xsca_user->getSystemAccount())->channels()->append('new')->get();
		if (count($new->discipline_id[0]) > 0) {
			foreach ($new->discipline_id[0] as $di) {
				$attr = $di->attributes();
				$value = (int)$attr['value'];
				$dis[(int)$value] = (string)$di;
			}

			return $dis;
		} else {
			return array();
		}
	}


	/**
	 * @return array
	 */
	public function getAllLicenses() {
		$new = xscaApi::users($this->xsca_user->getSystemAccount())->channels()->append('new')->get();
		$lic = array();
		if (count($new->license[0]) > 0) {
			foreach ($new->license[0] as $li) {
				$attr = $li->attributes();
				$value = (string)$attr['value'];
				$lic[(string)$value] = (string)$li;
			}

			return $lic;
		} else {
			return array();
		}
	}


	/**
	 * @return int
	 */
	public function hasReferencedChannels() {
		return count(self::getAllReferences($this->getExtId())) > 1;
	}


	/**
	 * @return string
	 */
	public function getDailyToken() {
		return strtoupper(substr(md5($this->getExtId() . date('d-m-Y')), 0, 6));
	}

	//
	// Helpers
	//
	/**
	 * @param bool $ext_id
	 *
	 * @return array
	 */
	public static function getAllReferences($ext_id) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$set = $ilDB->query('SELECT id FROM rep_robj_xsca_data WHERE ext_id = ' . $ilDB->quote($ext_id, 'text'));
		$ref_ids = array();
		while ($rec = $ilDB->fetchObject($set)) {
			$refs = ilObject::_getAllReferences($rec->id);
			$ref_ids = array_merge($ref_ids, array_keys($refs));
		}

		return $ref_ids;
	}


	//
	// Set/Get Methods for our Scast properties
	//
	/**
	 * @return int
	 */
	public function getCourseId() {
		return $this->course_id;
	}


	/**
	 * @param int $course_id
	 */
	public function setCourseId($course_id) {
		$this->course_id = $course_id;
	}


	/**
	 * @param $a_sys_account
	 */
	public function setSysAccount($a_sys_account) {
		$this->sys_account = $a_sys_account;
	}


	/**
	 * @return string
	 */
	public function getSysAccount() {
		return $this->sys_account;
	}


	/**
	 * @param $a_val
	 */
	public function setOnline($a_val) {
		$this->online = $a_val;
	}


	/**
	 * @return bool
	 */
	public function getOnline() {
		return $this->online;
	}


	/**
	 * @param $a_val
	 */
	public function setExtId($a_val) {
		$this->ext_id = $a_val;
	}


	/**
	 * @return string
	 */
	public function getExtId() {
		return $this->ext_id;
	}


	/**
	 * @param $a_val
	 */
	public function setEstimatetContentInHours($a_val) {
		$this->estimatet_content_in_hours = $a_val;
	}


	/**
	 * @return int
	 */
	public function getEstimatetContentInHours() {
		return $this->estimatet_content_in_hours;
	}


	/**
	 * @param $lifetime_of_content_in_month
	 */
	public function setLifetimeOfContentinMonth($lifetime_of_content_in_month) {
		$this->lifetime_of_content_in_month = $lifetime_of_content_in_month;
	}


	/**
	 * @return int
	 */
	public function getLifetimeOfContentinMonth() {
		return $this->lifetime_of_content_in_month;
	}


	/**
	 * @param $a_val
	 */
	public function setDepartment($a_val) {
		$this->department = $a_val;
	}


	/**
	 * @return string
	 */
	public function getDepartment() {
		return $this->department;
	}


	/**
	 * @param $a_val
	 */
	public function setDisciplineId($a_val) {
		$this->discipline_id = $a_val;
	}


	/**
	 * @return int
	 */
	public function getDisciplineId() {
		return $this->discipline_id;
	}


	/**
	 * @param $a_val
	 */
	public function setLicense($a_val) {
		$this->license = $a_val;
	}


	/**
	 * @return string
	 */
	public function getLicense() {
		return $this->license;
	}


	/**
	 * @param $a_val
	 */
	public function setStreamingOnly($a_val) {
		$this->streaming_only = $a_val;
	}


	/**
	 * getStreamingOnly
	 *
	 * @return int
	 */
	public function getStreamingOnly() {
		return $this->streaming_only;
	}


	/**
	 * @param $a_val
	 */
	public function setInvitingPossible($a_val) {
		$this->inviting_possible = $a_val;
	}


	/**
	 * @return bool
	 */
	public function getInvitingPossible() {
		return $this->inviting_possible;
	}


	/**
	 * @param $a_val
	 */
	public function setIvt($a_val) {
		$this->ivt = $a_val;
	}


	/**
	 * @return bool
	 */
	public function getIvt() {
		return $this->ivt;
	}


	/**
	 * setUploadForm
	 *
	 * @param string $a_val
	 */
	public function setUploadForm($a_val) {
		$this->upload_form = $a_val;
	}


	/**
	 * getUploadForm
	 *
	 * @return string
	 */
	public function getUploadForm() {
		return $this->upload_form;
	}


	/**
	 * setEditLink
	 *
	 * @param string $a_val
	 */
	public function setEditLink($a_val) {
		$this->edit_link = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getEditLink() {
		return $this->edit_link;
	}


	/**
	 * @return bool
	 */
	public function getAllowAnnotations() {
		return $this->allow_annotations;
	}


	/**
	 * @param string $organization_domain
	 */
	public function setOrganisationDomain($organization_domain) {
		$this->organisation_domain = $organization_domain;
	}


	/**
	 * @param boolean $allow_annotations
	 */
	public function setAllowAnnotations($allow_annotations) {
		$this->allow_annotations = $allow_annotations;
	}


	/**
	 * @param string $introduction_text
	 */
	public function setIntroductionText($introduction_text) {
		$this->introduction_text = $introduction_text;
	}


	/**
	 * @return string
	 */
	public function getIntroductionText() {
		return $this->introduction_text;
	}


	/**
	 * @param string $channel_kind periodic|test
	 */
	public function setChannelKind($channel_kind) {
		$this->channel_kind = $channel_kind;
	}


	/**
	 * return string $kind
	 */
	public function getChannelKind() {
		return $this->channel_kind;
	}


	/**
	 * @param boolean $show_upload_token
	 */
	public function setShowUploadToken($show_upload_token) {
		$this->show_upload_token = $show_upload_token;
	}


	/**
	 * @return boolean
	 */
	public function getShowUploadToken() {
		return $this->show_upload_token;
	}





	//
	// Refactor
	//
	/**
	 * @param $aai_unique_id
	 *
	 * @return bool
	 */
	public function isProducer($aai_unique_id) {
		$arr_producer = $this->getProducers();

		return in_array($aai_unique_id, $arr_producer);
	}


	/**
	 * @param bool $without_sys_account
	 *
	 * @return array
	 */
	public function getProducers($without_sys_account = false) {
		if ($without_sys_account) {
			$arr_all_sys_accounts = $this->pl->getAllSysAccounts();
			foreach ($this->producers as $key => $value) {
				if (in_array($value, $arr_all_sys_accounts)) {
					unset($this->producers[$key]);
				}
			}
		}

		return $this->producers;
	}


	/**
	 * @param $aai_unique_id
	 */
	public function setProducer($aai_unique_id) {
		$this->producers[] = $aai_unique_id;
	}


	/**
	 * @param xscaUser $user
	 */
	public function removeProducer(xscaUser $user) {
		if (!$user->getIsSystemaccount()) {
			$api = xscaApi::users($this->getSysAccount());
			$api->channels($this->getExtId())->producers($user->getExtAccount())->delete();
			$this->log->write('Producer removed: ' . $user->getExtAccount(), xscaLog::LEVEL_DEBUG);
		}
	}


	/**
	 * @param      $aai_unique_id
	 * @param bool $use_sys_account
	 *
	 * @return SimpleXMLElement
	 */
	public function addProducerByExtId($aai_unique_id, $use_sys_account = true) {
		if (in_array($aai_unique_id, $this->getProducers())) {
//			return false;
		}

		if ($use_sys_account) {
			$ext_account = $this->getSysAccount();
		} else {
			$ext_account = $this->xsca_user->getExtAccount();
		}
		$url = xscaApi::users($ext_account);
		$xml = $url->channels($this->getExtId())->producers($aai_unique_id)->put();
		xscaLog::getInstance()->write('Producer added: ' . $aai_unique_id, xscaLog::LEVEL_DEBUG);

		return $xml;
	}


	/**
	 * @param $usr_id
	 */
	public function addProducerByUsrId($usr_id) {
		global $ilAccess;
		$xscaUser = xscaUser::getInstance(new ilObjUser($usr_id));
		$xscaUser->create();
		if ($xscaUser->isAllowedAsPublisher($this->organisation_domain) AND $ilAccess->checkAccess('write', '', $this->getRefId())
		) {
			$this->addProducerByExtId($xscaUser->getExtAccount(), true);
		}
	}


	/**
	 * @param $email
	 *
	 * @return bool returns true iff the organization of the email-address is the same as the channels organization.
	 */
	public function isAllowedAsPublisher($email) {
		return $this->organisation_domain == xscaOrganisation::getOrganisationByExtAccount($email);
	}


	public function syncMembers() {
		/*if (! self::$member_sync) {
			$ilCourseParticipants = ilCourseParticipants::_getInstanceByObjId($this->getCourseId());
			foreach ($ilCourseParticipants->getMembers() as $member) {
				$xscaUser = xscaUser::getInstance(new ilObjUser($member));
				$xscaUser->create();
//				$this->removeProducer();
			}
			self::$member_sync = true;
		}*/
	}


	protected function syncAdmins() {
		if (!self::$admin_sync) {
			$ilCourseParticipants = ilCourseParticipants::_getInstanceByObjId($this->getCourseId());
			$producers_filtered = $this->getProducers(true);
			foreach ($producers_filtered as $producer_ext_id) {
				$xscaUser = xscaUser::getInstanceByExtAccount($producer_ext_id);
				if ($xscaUser->getIsSystemaccount()) {
					$this->removeProducer($xscaUser);
				}
			}
			foreach ($ilCourseParticipants->getAdmins() as $admin) {
				$this->addProducerByUsrId($admin);
			}
			self::$admin_sync = true;
		}
	}
}

?>
