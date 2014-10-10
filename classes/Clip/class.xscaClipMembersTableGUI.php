<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('./Services/Membership/classes/class.ilParticipants.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilObjScastGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilScastPlugin.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');

/**
 * GUI class for course/group waiting list
 *
 * @author  Fabian Schmid
 * @version $Id$
 *
 */
class xscaClipMembersTableGUI extends ilTable2GUI {

	/**
	 * @param ilObjScast $a_obj_scast
	 * @param string     $command
	 * @param string     $data
	 * @param string     $header
	 */
	public function __construct(ilObjScast $a_obj_scast, $command, $data, $header = '') {
		global $ilCtrl;
		/**
		 * @var  $ilCtrl ilCtrl
		 */
		$this->ctrl = $ilCtrl;
		$this->pl = ilScastPlugin::getInstance();
		$this->objScast = $a_obj_scast;
		$ilObjScastGUI = new ilObjScastGUI($this->objScast->getRefId());
		//		$this->objClip = new xscaClip($this->objScast, $_GET['clip_ext_id']);
		$this->objClip = xscaClip::getInstance($this->objScast, $_GET['clip_ext_id']);
		$this->setPrefix('xsca_members');
		$this->setId('xsca_members');
		parent::__construct($ilObjScastGUI, $command);
		$this->setData($data);
		$this->setShowRowsSelector(true);
		$this->setFormAction($ilCtrl->getFormActionByClass('ilobjscastgui', 'editClipMembers'));
		$this->addColumn($this->pl->txt('fullname'), 'name', 'auto');
		$this->addColumn($this->pl->txt('email'), 'email', 'auto');
		$this->addColumn($this->pl->txt('context'), 'context', 'auto');
		$this->addColumn($this->pl->txt('actions'), 'actions', 'auto');
		$this->setRowTemplate('tpl.clipmembers_table.html', $this->pl->getDirectory());
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$this->tpl->setVariable('NAME', $a_set['name']);
		$this->tpl->setVariable('MAIL', $a_set['mail']);
		$this->tpl->setVariable('CONTEXT', $a_set['context']);
		$alist = new ilAdvancedSelectionListGUI();
		$alist->setId($a_set['user_id']);
		$alist->setListTitle($this->pl->txt('actions'));
		$this->ctrl->setParameterByClass('ilObjScastGUI', 'member_id', $a_set['user_id']);
		if ($a_set['user_id']) {
			$alist->addItem($this->pl->txt('remove'), 'delete', $this->ctrl->getLinkTargetByClass('ilObjScastGUI', 'deleteClipMember'));
		}
		$this->tpl->setVariable('ACTIONS', $alist->getHTML());
	}


	/**
	 * @param $user_id
	 *
	 * @return the html for a new row.
	 */
	public static function getNewRow($user_id) {
		global $ilCtrl;
		/**
		 * @var $ilCtrl ilCtrl
		 */
		$pl = ilScastPlugin::getInstance();
		$tpl = $pl->getTemplate('default/tpl.clipmembers_table.html');
		$user = new ilObjUser($user_id);
		$tpl->setVariable('NAME', $user->getFullname());
		$tpl->setVariable('MAIL', $user->getEmail());
		$tpl->setVariable('CONTEXT', 'Clip Member');
		$alist = new ilAdvancedSelectionListGUI();
		$alist->setId($user_id);
		$alist->setListTitle($pl->txt('actions'));
		$ilCtrl->setParameterByClass('ilObjScastGUI', 'member_id', $user_id);
		if ($user_id) {
			$alist->addItem($pl->txt('remove'), 'delete', $ilCtrl->getLinkTargetByClass('ilObjScastGUI', 'deleteClipMember'));
		}
		$tpl->setVariable('ACTIONS', $alist->getHTML());

		return $tpl->get();
	}
}

?>