<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilScastPlugin.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Utilities/classes/class.ilConfirmationGUI.php');
require_once('class.xscaClipMembersTableGUI.php');
require_once('class.xscaClip.php');
require_once('class.xscaClipFormGUI.php');

/**
 * Class xscaClipGUI
 *
 * @author Martin Studer <ms@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaClipGUI {

	/**
	 * @var ilObjScast
	 */
	private $objScast;
	/**
	 * @var xscaClip
	 */
	public $clip;


	/**
	 * @param ilObjScastGUI $a_obj_scast_gui
	 * @param ilObjScast    $a_obj_scast
	 * @param null          $a_clip_ext_id
	 */
	public function  __construct(ilObjScastGUI $a_obj_scast_gui, ilObjScast $a_obj_scast, $a_clip_ext_id = NULL) {
		global $ilCtrl, $tpl, $ilAccess, $ilUser, $ilTabs;
		/**
		 * @var $ilCtrl      ilCtrl
		 * @var $tpl         ilTemplate
		 * @var $ilUser      ilObjUser
		 * @var $ilAccess    ilAccessHandler
		 * @var $ilTabs      ilTabsGUI
		 */
		$this->tpl = $tpl;
		$this->ctrl = $ilCtrl;
		$this->user = $ilUser;
		$this->access = $ilAccess;
		$this->tabs = $ilTabs;
		$this->pl = ilScastPlugin::getInstance();
		$this->objScast = $a_obj_scast;
		$this->parent_gui = $a_obj_scast_gui;
		if (!$a_clip_ext_id) {
			$a_clip_ext_id = $_GET['clip_ext_id'];
		}
		if ($a_clip_ext_id) {
			$this->clip = xscaClip::getInstance($a_obj_scast->getExtId(), $a_clip_ext_id);
		};
		$this->ctrl->setParameterByClass('ilObjScastGUI', 'clip_ext_id', $a_clip_ext_id);
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		switch ($cmd) {
			case 'cancelClipOwner':
			case 'cancelClipMember':
				$this->cancel();
				break;
			case 'edit':
				$this->tpl->getStandardTemplate();
				$this->$cmd();
				$this->tpl->show();
			default:
				$this->$cmd();
				break;
		}

		return true;
	}


	public function cancel() {
		$this->ctrl->redirect($this->parent_gui);
	}


	public function edit() {
		$form = new xscaClipFormGUI($this, $this->clip);
		$form->fillForm();
		$this->tpl->setContent($form->getHTML());
	}


	public function update() {
		$form = new xscaClipFormGUI($this, $this->clip);
		$form->setValuesByPost();

		if ($form->saveObject()) {
			ilUtil::sendSuccess($this->pl->txt('success_edit'), true);
			$this->ctrl->redirect($this->parent_gui, $_GET['fallbackCmd']);
		} else {
			$this->tpl->getStandardTemplate();
			$this->tpl->setContent($form->getHTML());
			$this->tpl->show();
		}
	}


	//
	// Refactor from here
	//
	public function editClipOwner() {
		$this->tpl->addJavaScript($this->pl->getStyleSheetLocation('default/ownerFilter.js'));
		$this->initClipOwnerForm();
		$this->getClipOwnerValues();
		$this->tpl->setContent($this->clip_owner_form->getHTML());
	}


	public function updateClipOwner() {
		$this->initClipOwnerForm();
		if ($this->clip_owner_form->checkInput()) {
			$this->clip->setOwner($this->clip_owner_form->getInput('owner'));
			$this->clip->update();
			xscaApiCache::flush($this->objScast->getId());
			ilUtil::sendSuccess($this->pl->txt('msg_obj_modified'), true);
			$this->ctrl->redirectByClass('ilobjscastgui', 'showContent');
		}
		$this->clip_owner_form->setValuesByPost();
		$this->tpl->setContent($this->clip_owner_form->getHtml());
	}



	/**
	 * @param string $a_mode
	 */
	public function initClipOwnerForm($a_mode = 'edit') {
		$this->ctrl->setParameterByClass('ilObjScastGUI', 'clip_ext_id', $this->clip->getExtId());
		// Form
		$this->clip_owner_form = new ilPropertyFormGUI();
		$this->clip_owner_form->setTitle($this->pl->txt('clip_edit_owner'));
		$ilParticipants = new ilCourseParticipants($this->objScast->getCourseId());
		$arr_participants = array();
		$arr_participants[''] = '--' . $this->pl->txt('not_assigned') . '--';
		foreach ($ilParticipants->getParticipants() as $user_id) {
			$ext_account = xscaUser::getExtAccountForUserId($user_id);
			if ($ext_account) {
				$ilObjUser = new ilObjUser($user_id);
				$arr_participants[$ilObjUser->getExternalAccount()] =
					$ilObjUser->getLastname() . ' ' . $ilObjUser->getFirstname() . ' (' . $ilObjUser->getEmail() . ')';
			}
		}
		@natcasesort($arr_participants);
		$owner = new ilSelectInputGUI($this->pl->txt('owner'), 'owner');
		$owner->setOptions($arr_participants);
		$this->clip_owner_form->addItem($owner);
		$this->clip_owner_form->addCommandButton('updateClipOwner', $this->pl->txt('save'));
		$this->clip_owner_form->addCommandButton('cancelClipOwner', $this->pl->txt('cancel'));
		$this->form->setFormAction($this->ctrl->getFormActionByClass("ilObjScastGUI"));
	}


	function confirmDeleteClip() {
		$this->ctrl->setParameter($this, 'type_id', (int)$_REQUEST['type_id']);
		$conf = new ilConfirmationGUI();
		$conf->setFormAction($this->ctrl->getFormActionByClass('ilObjScastGUI'));
		$conf->setHeaderText($this->pl->txt('clip_confirm_delete'));
		$conf->addItem('', '', $this->clip->getTitle());
		$conf->setConfirm($this->pl->txt('delete_clip'), 'deleteClip');
		$conf->setCancel($this->pl->txt('cancel'), 'showContent');
		$this->tpl->setContent($conf->getHTML());
	}


	function deleteClip() {
		if ($this->access->checkAccess('write', '', $this->objScast->getRefId())) {
			if ($this->clip->delete()) {
				ilUtil::sendSuccess($this->pl->txt('clip_deleted'), true);
				$this->ctrl->setParameterByClass('ilObjScastGUI', 'clip_ext_id', '');
				$this->ctrl->redirectByClass('ilObjScastGUI', 'showContent');
			}
		}
		ilUtil::sendFailure($this->pl->txt('permission_denied'), true);
		$this->ctrl->redirectByClass('ilObjScastGUI', 'showContent');
	}


	function getClipOwnerValues() {
		$values['owner'] = $this->clip->getOwner();
		$this->clip_owner_form->setValuesByArray($values);
	}


	public function editClipMembers() {
		$this->tabs->clearTargets();
		$this->tabs->setBackTarget($this->pl->txt('back_to_list'), $this->ctrl->getLinkTargetByClass('ilObjScastGUI', 'showContent'));
		$clipmembers = array();
		foreach ($this->objScast->getProducers(true) as $key => $value) {
			$castUser = xscaUser::getInstanceByExtAccount($value);
			$clipmembers[$key]['name'] = $castUser->getLastName() . ', ' . $castUser->getFirstName();
			$clipmembers[$key]['mail'] = $castUser->getEmail();
			$clipmembers[$key]['context'] = 'SWITCHcast Producer';
		}
		$owner = xscaUser::getInstanceByExtAccount($this->clip->getOwner());
		if ((string)$this->clip->getOwner()) {
			$entry['name'] = $owner->getLastName() . ', ' . $owner->getFirstName();
			$entry['mail'] = $owner->getEmail();
			$entry['context'] = 'Clip Owner';
			array_push($clipmembers, $entry);
		}
		foreach (xscaGroup::getAllUsersFromGroupForOwner($this->objScast->getId(), $owner->getIliasUserId()) as $member_id) {
			$castUser = new ilObjUser($member_id);
			$entry['name'] = $castUser->getLastName() . ', ' . $castUser->getFirstName();
			$entry['mail'] = $castUser->getEmail();
			$entry['context'] = 'In Group with Owner';
			array_push($clipmembers, $entry);
		}
		foreach ($this->clip->getMembers() as $user_id) {
			$member['name'] = ilObjUser::_lookupFullname($user_id);
			$objMember = new ilObjUser($user_id);
			$member['mail'] = $objMember->getEmail();
			$member['context'] = 'Clip Member';
			$member['user_id'] = $user_id;
			array_push($clipmembers, $member);
		}
		$this->ctrl->setParameterByClass('ilObjScastGUI', 'clip_ext_id', $this->clip->getExtId());
		$table = new xscaClipMembersTableGUI($this->objScast, 'editClipMembers', $clipmembers);
		$this->initClipMemberForm();
		if ($this->access->checkAccess('write', '', $this->objScast->getRefId()) OR
			(string)$this->clip->getOwner() == $this->user->getExternalAccount()
		) {
			$html = $this->clip_member_form->getHTML();
		}
		$html .= $table->getHTML();
		$this->tpl->setContent($html);
	}


	public function addClipMember() {
		$this->initClipMemberForm();
		$this->getClipMemberValues();
		$this->tpl->setContent($this->clip_member_form->getHTML());
	}


	public function updateClipMember() {
		$this->initClipMemberForm();
		if ($this->clip_member_form->checkInput()) {
			$clipmember = $this->clip_member_form->getInput('clipmember');
			if ($clipmember > 0) {
				$this->clip->addMember($clipmember);
				xscaApiCache::flush($this->objScast->getId());
				echo xscaClipMembersTableGUI::getNewRow($clipmember);
				exit;
			}
		}
	}


	public function initClipMemberForm() {
		$this->tpl->addJavaScript($this->pl->getStyleSheetLocation('default/memberJS.js'));
		$this->tpl->addCss($this->pl->getStyleSheetLocation('default/member.css'));
		$this->ctrl->setParameterByClass('ilObjScastGUI', 'clip_ext_id', $this->clip->getExtId());
		// Form
		$this->clip_member_form = new ilPropertyFormGUI();
		$this->clip_member_form->setTitle($this->pl->txt('add_Member'));
		$ilParticipants = new ilCourseParticipants($this->objScast->getCourseId());
		$arr_participants = array();
		$arr_participants[] = '--' . $this->pl->txt('not_assigned') . '--';
		$owner_id = $this->clip->getOwnerILIASId($this->clip->getOwner());
		foreach ($ilParticipants->getParticipants() as $user_id) {
			// Nur Benutzer, welche nicht dem Owner entsprechen und noch nicht Clip-Member und nicht Producer sind anzeigen
			// Hier werden auch lokale Accounts zugelassen!
			if (((string)$this->clip->getOwner() != ilObjUser::_lookupExternalAccount($user_id) OR ilObjUser::_lookupExternalAccount($user_id) == '')
				AND !$this->clip->isMember($user_id) AND !$this->objScast->isProducer(ilObjUser::_lookupExternalAccount($user_id))
				AND !xscaGroup::checkSameGroup($this->objScast->getId(), $owner_id, $user_id)
			) {
				$ilObjUser = new ilObjUser($user_id);
				$arr_participants[$user_id] = $ilObjUser->getLastname() . ' ' . $ilObjUser->getFirstname() . ' (' . $ilObjUser->getEmail() . ')';
			}
		}
		// Sortieren nach Bezeichungen. natcasesort berücksicht Gross-/Kleinschreibung nicht. asort hingegen schon.
		@natcasesort($arr_participants);
		$clipmember = new ilSelectInputGUI($this->pl->txt('member'), 'clipmember');
		$clipmember->setOptions($arr_participants);
		$this->clip_member_form->addItem($clipmember);
		$this->clip_member_form->addCommandButton('updateClipMember', $this->pl->txt('add_member'));
		$this->clip_member_form->addCommandButton('cancelClipMember', $this->pl->txt('cancel'));
		$this->form->setFormAction($this->ctrl->getFormActionByClass("ilObjScastGUI"));

	}


	public function getClipMemberValues() {
		$values['owner'] = $this->clip->getOwner();
		$this->clip_member_form->setValuesByArray($values);
	}


	public function deleteClipMember() {
		$this->clip->deleteMember($_GET['member_id']);
		ilUtil::sendSuccess($this->pl->txt('msg_obj_modified'), true);
		$this->ctrl->redirect($this->parent_gui, 'editClipMembers');
	}


	/**
	 * @param        $a_perm
	 * @param string $a_cmd
	 *
	 * @deprecated
	 * @return bool
	 */
	public function checkPermissionBool($a_perm, $a_cmd = '') {
		global $ilAccess, $ilUser;
		if ($a_perm == 'write') {
			//Write Access to SCastObj? OR ClipOwner?
			If ($ilAccess->checkAccess('write', '', $this->objScast->getRefId()) OR $ilUser->getExternalAccount() == $this->clip->getOwner()
			) {
				return true;
			}
		} elseif ($a_perm == 'read') {
			$arrClipMembers = (array)$this->clip->getMembers();
			if (($ilAccess->checkAccess('write', '', $this->objScast->getRefId())) OR ($ilUser->getExternalAccount() == $this->clip->getOwner() AND
					$ilUser->getExternalAccount() != '' AND $this->clip->getOwner() != '')
				OR is_numeric(array_search($ilUser->getId(), $arrClipMembers))
				OR xscaGroup::checkSameGroup($this->objScast->getId(), $this->clip->getOwnerILIASId(), $ilUser->getId()) OR ($this->objScast->getIvt()
					== false AND $ilAccess->checkAccess('read', '', $this->objScast->getRefId()))
			) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $ref_id
	 */
	public function setRefId($ref_id) {
		$this->ref_id = $ref_id;
	}
}

?>