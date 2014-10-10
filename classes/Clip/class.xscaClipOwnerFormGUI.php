<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * GUI-Class xscaClipOwnerFormGUI
 *
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 *
 */
class xscaClipOwnerFormGUI extends ilPropertyFormGUI {

	/**
	 * @var  xscaClip
	 */
	protected $clip;
	/**
	 * @var xscaClipGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;


	/**
	 * @param          $parent_gui
	 * @param xscaClip $clip
	 */
	public function __construct($parent_gui, xscaClip $clip) {
		global $ilCtrl;
		$this->clip = $clip;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilScastPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, 'clip_ext_id');
		$this->initForm();
	}


	private function initForm() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui, $_GET['fallbackCmd']));
		if ($this->clip->getExtId() == 0) {
			$this->setTitle($this->pl->txt('edit_owner'));
		} else {
			$this->setTitle($this->pl->txt('edit_owner'));
		}
		// Form
		$ilParticipants = new ilParticipants($this->clip->getCourseId());
		$arr_participants = array();
		$arr_participants[''] = '--' . $this->pl->txt('not_assigned') . '--';
		foreach ($ilParticipants->getParticipants() as $user_id) {
			if (ilObjUser::_lookupExternalAccount($user_id)) {
				$ilObjUser = new ilObjUser($user_id);
				$arr_participants[$ilObjUser->getExternalAccount()] =
					$ilObjUser->getLastname() . ' ' . $ilObjUser->getFirstname() . ' (' . $ilObjUser->getEmail() . ')';
			}
		}
		@natcasesort($arr_participants);
		$owner = new ilSelectInputGUI($this->pl->txt('owner'), 'owner');
		$owner->setOptions($arr_participants);
		$this->addItem($owner);
		$this->addCommandButton('save', $this->pl->txt('save'));
		$this->addCommandButton('cancel', $this->pl->txt('cancel'));
		$this->addCommandButtons();
	}


	public function fillForm() {
		$array = array(
			'title' => $this->clip->getTitle(),
		);
		$this->setValuesByArray($array);
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		if (! $this->checkInput()) {
			return false;
		}
		$this->clip->setTitle($this->getInput('title'));

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		if ($this->clip->getExtId()) {
			$this->clip->update();
		} else {
			$this->clip->create();
		}

		return true;
	}


	protected function addCommandButtons() {
		if ($this->clip->getExtId() == 0) {
			$this->addCommandButton('create', $this->pl->txt('create_clip'));
		} else {
			$this->addCommandButton('update', $this->pl->txt('save'));
		}
		$this->addCommandButton('cancel', $this->pl->txt('cancel'));
	}
}