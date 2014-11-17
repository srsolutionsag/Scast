<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * GUI-Class xscaClipFormGUI
 *
 * @author            Martin Studer <ms@studer-raimann.ch>
 * @version           $Id:
 *
 */
class xscaClipFormGUI extends ilPropertyFormGUI {

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
			$this->setTitle($this->pl->txt('clip_create'));
		} else {
			$this->setTitle($this->pl->txt('clip_edit'));
		}
		// Title
		$title = new ilTextInputGUI($this->pl->txt('clip_title'), 'title');
		$title->setRequired(true);
		$this->addItem($title);
		// Presenter
		$ti = new ilTextInputGUI($this->pl->txt('clip_presenter'), 'presenter');
		$this->addItem($ti);
		// Ort
		$ti = new ilTextInputGUI($this->pl->txt('clip_location'), 'location');
		$this->addItem($ti);
		$this->addCommandButtons();
	}


	public function fillForm() {
		$array = array(
			'title' => $this->clip->getTitle(),
			'presenter' => $this->clip->getPresenter(),
			'location' => $this->clip->getLocation(),
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
		$this->clip->setPresenter($this->getInput('presenter'));
		$this->clip->setLocation($this->getInput('location'));

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
			$this->addCommandButton('create', $this->pl->txt('clip_create'));
		} else {
			$this->addCommandButton('update', $this->pl->txt('clip_update'));
		}
		$this->addCommandButton('cancel', $this->pl->txt('clip_cancel'));
	}
}