<?php

/**
 * Class xscaGroupGUI
 *
 * @author             Oskar Truffer <ot@studer-raimann.ch>
 * @author             Martin Studer <ms@studer-raimann.ch>
 * @author             Fabian Schmid <fs@studer-raimann.ch>
 *
 * $Id$
 *
 * @ilCtrl_isCalledBy  xscaGroupGUI: ilObjScastGUI
 * @ilCtrl_Calls       xscaGroupGUI: ilObjScastGUI
 *
 */
class xscaGroupGUI {

	/**
	 * @var
	 */
	protected $ref_id;
	/**
	 * @var ilObjScast
	 */
	protected $scast;
	/**
	 * @var ilParticipants
	 */
	protected $participants;
	/**
	 * @var ilScastPlugin
	 */
	protected $pl;


	/**
	 * @param $scast ilObjScast
	 */
	public function __construct($scast = NULL) {
		global $ilCtrl, $ilAccess, $tpl, $ilUser;
		/**
		 * @var $ilCtrl   ilCtrl
		 * @var $ilAccess ilAccessHandler
		 * @var $tpl      ilTemplate
		 * @var $ilUser   ilObjUser
		 */
		$this->ref_id = $_GET['ref_id'];
		$this->scast = $scast;
		$this->pl = ilScastPlugin::getInstance();
		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->access = $ilAccess;
		$this->user = $ilUser;
		if (! $this->scast) {
			$this->scast = new ilObjScast($this->ref_id);
		}
		$this->participants = new ilCourseParticipants($this->scast->getCourseId());
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$cmd = $this->ctrl->getCmd();
		switch ($cmd) {
			default:
				$this->tpl->getStandardTemplate();
				$this->$cmd();
				$this->tpl->show();
		}

		return true;
	}


	public function showGroups() {
		$this->tpl->addCss($this->pl->getStyleSheetLocation('default/groups.css'));
		$this->ctrl->saveParameter($this, 'ref_id');
		$groups = xscaGroup::getAllForObjId($this->scast->getId());
		$temp = $this->pl->getTemplate('default/tpl.groups.html');
		//SET ajax links
		$temp->setCurrentBlock('javascript');
		$temp->setVariable('NEW_GROUP_LINK', $this->ctrl->getLinkTarget($this, 'newGroup'));
		$temp->setVariable('DELETE_GROUP_LINK', $this->ctrl->getLinkTarget($this, 'deleteGroup'));
		$temp->setVariable('ADD_MEMBER_LINK', $this->ctrl->getLinkTarget($this, 'addToGroup'));
		$temp->setVariable('REMOVE_MEMBER_LINK', $this->ctrl->getLinkTarget($this, 'removeFromGroup'));
		$temp->setVariable('DELETE_GROUP_CONFIRMATION', $this->pl->txt('delete_group_confirmation'));
		$temp->parseCurrentBlock();
		// SET BOX WITH GROUPS
		$temp->setCurrentBlock('group');
		$temp->setVariable('GROUPS', $this->pl->txt('groups'));
		$temp->setVariable('SELECT_A_GROUP', $this->pl->txt('select_a_group'));
		$temp->setVariable('CREATE_A_GROUP', $this->pl->txt('create_a_group'));
		foreach ($groups as $group) {
			$gt = $this->pl->getTemplate('default/tpl.groups.html');
			$this->buildGroupTemplate($gt, $group);
			$temp->setCurrentBlock('groupplace');
			$temp->setVariable('GROUP_PLACE', $gt->get());
			$temp->parseCurrentBlock();
		}
		$temp->parseCurrentBlock();
		// SET BOX WITH PARTICIPANTS
		$temp->setCurrentBlock('participants');
		$temp->setVariable('PARTICIPANTS', $this->pl->txt('available_participants'));
		//Mirglieder sortiert ausgeben.
		foreach ($this->participants->getParticipants() as $participant) {
			$participant = new ilObjUser($participant);
			$arr_participant[$participant->getFullname()]['fullname'] = $participant->getFullname();
			$arr_participant[$participant->getFullname()]['email'] = $participant->getEmail();
			$arr_participant[$participant->getFullname()]['id'] = $participant->getId();
			$arr_participant[$participant->getFullname()]['image'] = $participant->getPersonalPicturePath('xsmall');
		}
		@asort($arr_participant);
		foreach ($arr_participant as $participant) {
			$temp->setCurrentBlock('participant');
			$temp->setVariable('PARTICIPANT', $participant['fullname']);
			$temp->setVariable('PARTICIPANT_EMAIl', $participant['email']);
			$temp->setVariable('PARTICIPANT_ID', $participant['id']);
			$temp->setVariable('PARTICIPANT_ADD', $this->pl->txt('add_member'));
			// GET USER IMAGE
			$temp->setVariable('PARTICIPANT_IMAGE', $participant['image']);
			$temp->parseCurrentBlock();
		}
		$temp->parseCurrentBlock();
		$this->tpl->setContent($temp->get());
	}


	/**
	 * @param $tpl   ilTemplate
	 * @param $group xscaGroup
	 */
	private function buildGroupTemplate(&$tpl, $group) {
		$tpl->setCurrentBlock('group');
		$tpl->setVariable('GROUP', $this->pl->txt('group'));
		$tpl->setVariable('GROUP_NAME', $group->getTitle());
		$tpl->setVariable('GROUP_ID', $group->getId());
		//Gruppenmitglieder sortieren
		if ($group->getMemberIds()) {
			foreach ($group->getMemberIds() as $member_id) {
				$user = new ilObjUser($member_id);
				$arr_members[$user->getFullname()] = $member_id;
			}
		}
		if ($arr_members) {
			@arsort($arr_members);
			foreach ($arr_members as $member_id) {
				$mt = $this->pl->getTemplate('default/tpl.groups.html');
				$this->buildMemberTemplate($mt, $member_id);
				$tpl->setCurrentBlock('memberplace');
				$tpl->setVariable('MEMBER_PLACE', $mt->get());
				$tpl->parseCurrentBlock();
			}
		}
		$tpl->parseCurrentBlock();
	}


	/**
	 * @param $tpl       ilTemplate
	 * @param $member_id int
	 */
	private function buildMemberTemplate(&$tpl, $member_id) {
		$tpl->setCurrentBlock('member');
		$user = new ilObjUser($member_id);
		$tpl->setVariable('PARTICIPANT', $user->getFullname());
		$tpl->setVariable('PARTICIPANT_EMAIl', $user->getEmail());
		$tpl->setVariable('PARTICIPANT_ID', $user->getId());
		$tpl->setVariable('PARTICIPANT_REMOVE', $this->pl->txt('remove'));
		$tpl->parseCurrentBlock();
	}


	//
	// AJAX Methods
	//
	public function newGroup() {
		$name = $_GET['groupName'];
		$group = xscaGroup::getInstance();
		$group->setTitle($name);
		$group->setScastId($this->scast->getId());
		$group->create();
		$tpl = $this->pl->getTemplate('default/tpl.groups.html');
		$this->buildGroupTemplate($tpl, $group);
		echo $tpl->get();
		exit;
	}


	public function deleteGroup() {
		$group = xscaGroup::getInstance($_GET['groupId']);
		$group->delete();
	}


	public function addToGroup() {
		$group_id = $_GET['groupId'];
		$participant_id = $_GET['participantId'];
		$group = xscaGroup::getInstance($group_id);
		$newly_added = $group->addMemberById($participant_id);
		$tpl = $this->pl->getTemplate('default/tpl.groups.html');
		$this->buildMemberTemplate($tpl, $participant_id);
		if ($newly_added) {
			echo $tpl->get();
		}
		exit;
	}


	public function removeFromGroup() {
		$group_id = $_GET['groupId'];
		$member_id = $_GET['memberId'];
		$group = xscaGroup::getInstance($group_id);
		$group->removeMemberById($member_id);
	}
}

?>