<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once('./Services/Repository/classes/class.ilObjectPluginAccess.php');

/**
 * Access/Condition checking for Scast object
 *
 * Please do not create instances of large application classes (like ilObjScast)
 * Write small methods within this class to determin the status.
 *
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 * @version       $Id$
 */
class ilObjScastAccess extends ilObjectPluginAccess {

	/**
	 * @param string $a_cmd
	 * @param string $a_permission
	 * @param int    $a_ref_id
	 * @param int    $a_obj_id
	 * @param string $a_user_id
	 *
	 * @return bool
	 */
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = '') {
		global $ilUser, $ilAccess;
		if ($a_user_id == '') {
			$a_user_id = $ilUser->getId();
		}
		switch ($a_permission) {
			case 'visible':
			case 'read':
				if (ilObjScastAccess::checkOnline($a_obj_id) AND !$ilAccess->checkAccessOfUser($a_user_id, 'write', '', $a_ref_id)
				) {
					return true;
				}
				break;
			case 'write':
			case 'edit_permission':
				if ($ilAccess->checkAccessOfUser($a_user_id, $a_permission, '', $a_ref_id)) {
					return true;
				}
				break;
		}

		return true;
	}


	/**
	 * @param $a_ref_id
	 * @param $a_permission
	 *
	 * @return bool
	 */
	public static function checkPermissionOnchannel($a_ref_id, $a_permission) {
		global $ilAccess;

		/**
		 * @var $ilAccess ilAccessHandler
		 */

		return $ilAccess->checkAccess($a_permission, '', $a_ref_id);
	}


	/**
	 * @param xscaClip $clip
	 * @param          $a_perm
	 *
	 * @param null     $ref_id
	 *
	 * @return bool
	 */
	public static function checkAccessOnClip(xscaClip $clip, $a_perm, $ref_id = NULL) {
		global $ilAccess, $ilUser;
		$ref_id = $ref_id ? $ref_id : $_GET['ref_id'];
		$ilObjScast = new ilObjScast($ref_id, true);

		if ($a_perm == 'write') {
			if ($ilAccess->checkAccess('write', '', $ref_id) OR $ilUser->getExternalAccount() == $clip->getOwner()
			) {
				return true;
			}
		} elseif ($a_perm == 'read') {

			$write_permission = self::checkAccessOnClipForAllReferences($clip, 'write');
			if ($write_permission) {
				return true;
			}
			$read_permission = self::checkAccessOnClipForAllReferences($clip, 'read');
			if ($read_permission) {
                if($ilObjScast->getIvt()){
                    return true;
                }

				$owner = $ilUser->getExternalAccount() == $clip->getOwner() AND $ilUser->getExternalAccount() != '' AND $clip->getOwner() != '';
				if ($owner) {
					return true;
				}
				$arr_clip_members = $clip->getMembers();
				$clip_member = in_array($ilUser->getId(), $arr_clip_members);
				if ($clip_member) {
					return true;
				}
				if (xscaGroup::checkSameGroup($ilObjScast->getId(), $clip->getOwnerILIASId(), $ilUser->getId())) {
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * @param xscaClip $clip
	 * @param string   $permission
	 *
	 * @return bool
	 */
	public static function checkAccessOnClipForAllReferences(xscaClip $clip, $permission = 'read') {
		global $ilAccess;
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		$access = false;
		foreach (ilObjScast::getAllRefIdsForExtId($clip->getChannelExtId()) as $ref_id) {
			if ($ilAccess->checkAccess($permission, '', $ref_id)) {
				$access = true;
			}
		}

		return $access;
	}


	/**
	 * @param null $ref_id
	 *
	 * @return bool
	 */
	public static function checkSwitchCastUseage($ref_id = NULL) {
		global $ilAccess;
		$ref_id = $ref_id ? $ref_id : $_GET['ref_id'];
		$ilObjScast = new ilObjScast($ref_id, true);

		return $ilAccess->checkAccess('write', '', $ilObjScast->getRefId()) AND xscaUser::getInstance()->isAllowedToUseSwitchCast();
	}


	/**
	 * @param      $a_id
	 * @param bool $as_reference
	 *
	 * @return bool
	 */
	static function checkOnline($a_id, $as_reference = false) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		if ($as_reference) {
			$a_id = ilObject2::_lookupObjId($a_id);
		}
		$set = $ilDB->query('SELECT is_online FROM rep_robj_xsca_data ' . ' WHERE id = ' . $ilDB->quote($a_id, 'integer'));
		$rec = $ilDB->fetchObject($set);

		return (boolean)$rec->is_online;
	}


	/**
	 * @param $sth
	 *
	 * @return bool
	 */
	public function _checkGoto($sth) {
		return true;
	}
}

?>
