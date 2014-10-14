<?php

require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.xscaConfig.php');

/**
 * GUI-Class xscaClipFormGUI
 *
 * @author            Martin Studer <ms@studer-raimann_ch>
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           $Id:
 *
 */
class xscaConfigFormGUI extends ilPropertyFormGUI {

	/**
	 * @var ilScastConfigGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;


	/**
	 * @param $parent_gui
	 */
	public function __construct($parent_gui) {
		global $ilCtrl;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilScastPlugin::getInstance();
		if ($_GET['hrl'] == 'true') {
			$this->pl->updateLanguageFiles();
		}
		$this->ctrl->saveParameter($parent_gui, 'clip_ext_id');
		$this->setFormAction($this->ctrl->getFormAction($parent_gui));
		$this->initForm();
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function txt($key) {
		return $this->pl->txt('admin_config_' . $key);
	}


	private function initForm() {
		$this->setTitle($this->txt('form_title'));
		$this->setDescription($this->txt('form_description'));
		//
		$ro = new ilRadioGroupInputGUI($this->txt(xscaConfig::F_GOTO), xscaConfig::F_GOTO);
		$opt1 = new ilRadioOption($this->txt('goto_login'), xscaConfig::GOTO_LOGIN);
		$ro->addOption($opt1);
		$opt1 = new ilRadioOption($this->txt('goto_repo'), xscaConfig::GOTO_REPO);
		$ro->addOption($opt1);
		$this->addItem($ro);
		//
		$cb = new ilCheckboxInputGUI($this->txt(xscaConfig::F_SHOW_API_DEBUG), xscaConfig::F_SHOW_API_DEBUG);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->txt(xscaConfig::F_DISABLE_CACHE), xscaConfig::F_DISABLE_CACHE);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->txt(xscaConfig::F_DEACTIVATE_IVT), xscaConfig::F_DEACTIVATE_IVT);
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->txt(xscaConfig::F_DEACTIVATE_GET_EXISTING), xscaConfig::F_DEACTIVATE_GET_EXISTING);
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->txt('title_sysaccounts'));
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->txt(xscaConfig::F_CREATE_BY_SYS), xscaConfig::F_CREATE_BY_SYS);
		//		$this->addItem($cb);
		//
		$ti = new ilTextInputGUI($this->txt(xscaConfig::F_DEFAULT_SYSACCOUNT), xscaConfig::F_DEFAULT_SYSACCOUNT);
		$this->addItem($ti);
		//
		$domains = array(
			'unibe_ch',
			'unil_ch',
			'switch_ch',
			'phtg_ch',
			'unifr_ch',
			'phz_ch',
			'phzh_ch',
			'unibas_ch',
			'eth_ch',
		);
		foreach ($domains as $domain) {
			$ti = new ilTextInputGUI($this->txt('sysaccount_' . $domain), $domain . '_sysaccount');
			$this->addItem($ti, false);
		}
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->txt('title_certificates'));
		$this->addItem($h);
		//
		//
		$ti = new ilTextInputGUI($this->txt('file_crt'), 'crt_file');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('file_castkey'), 'castkey_file');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('castkeypassword'), 'castkey_password');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('file_localkey'), 'localkey_file');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('file_cacrt'), 'cacrt_file');
		$this->addItem($ti);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->txt('title_switch'));
		$this->addItem($h);
		//
		$ti = new ilTextInputGUI($this->txt('host_switch_api'), 'switch_api_host');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('host_switchcast'), 'switchcast_host');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('external_authority_host'), 'external_authority_host');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('external_authority_id'), 'scast_external_authority_id');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('scast_metadata_export'), 'scast_metadata_export');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('scast_configuration_id'), 'scast_configuration_id');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('scast_streaming_configuration_id'), 'scast_streaming_configuration_id');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->txt('scast_scast_access'), 'scast_access');
		$this->addItem($ti);
		//
		$cb = new ilCheckboxInputGUI($this->txt('allow_test_channels'), 'allow_test_channels');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->txt(xscaConfig::F_USE_EULA), xscaConfig::F_USE_EULA);
		$te = new ilTextareaInputGUI($this->txt(xscaConfig::F_EULA_TEXT), xscaConfig::F_EULA_TEXT);
		$te->setUseRte(true);
		$te->setRteTags(array_merge($te->getRteTags(), array( 'a' )));
		$cb->addSubItem($te);
		$this->addItem($cb);
		//
		//		$h = new ilFormSectionHeaderGUI();
		//		$h->setTitle($this->txt('title_upload_token'));
		//		$this->addItem($h);
		//		//
		//		$cb = new ilCheckboxInputGUI($this->txt('allow_upload_token'), xscaConfig::ALLOW_UPLOAD_TOKEN);
		//		$this->addItem($cb);
		//
		$this->addCommandButtons();
	}


	/**
	 * @param      $a_item
	 *
	 * @param bool $add_info
	 *
	 * @return mixed
	 */
	public function addItem($a_item, $add_info = true) {
		if (get_class($a_item) != 'ilFormSectionHeaderGUI' AND $add_info) {
			$a_item->setInfo($this->txt('' . $a_item->getPostVar() . '_info'));
		}

		return parent::addItem($a_item);
	}


	public function fillForm() {
		$array = array();
		foreach ($this->getItems() as $item) {
			if (get_class($item) != 'ilFormSectionHeaderGUI') {
				$key = $item->getPostVar();
				$array[$key] = xscaConfig::get($key);
				foreach ($item->getSubItems() as $sub_item) {
					$key = $sub_item->getPostVar();
					$array[$key] = xscaConfig::get($key);
				}
			}
		}
		$this->setValuesByArray($array);
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		if (!$this->checkInput()) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (!$this->fillObject()) {
			return false;
		}
		foreach ($this->getItems() as $item) {
			if (get_class($item) != 'ilFormSectionHeaderGUI') {
				/**
				 * @var $item ilCheckboxInputGUI
				 */
				$key = $item->getPostVar();
				xscaConfig::set($key, $this->getInput($key));
				foreach ($item->getSubItems() as $subitem) {
					$key = $subitem->getPostVar();
					xscaConfig::set($key, $this->getInput($key));
				}
			}
		}

		return true;
	}


	/**
	 * @return mixed
	 */
	public function getExportItems() {
		$protected = array(
			'ilFormSectionHeaderGUI',
		);
		$return = array();
		foreach ($this->getItems() as $item) {
			if (!in_array(get_class($item), $protected)) {
				$return[] = $item;
			}
		}

		return $return;
	}


	protected function addCommandButtons() {
		$this->addCommandButton('save', $this->txt('form_button_save'));
		$this->addCommandButton('cancel', $this->txt('form_button_cancel'));
	}
}