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


	private function initForm() {
		$this->setTitle($this->pl->txt('scast_plugin_configuration'));
		$this->setDescription($this->pl->txt('settings_description'));
		//
		$ro = new ilRadioGroupInputGUI($this->pl->txt('admin_config_goto'), 'goto');
		$opt1 = new ilRadioOption($this->pl->txt('admin_config_goto_login'), xscaConfig::GOTO_LOGIN);
		$ro->addOption($opt1);
		$opt1 = new ilRadioOption($this->pl->txt('admin_config_goto_repo'), xscaConfig::GOTO_REPO);
		$ro->addOption($opt1);
		$this->addItem($ro);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_config_show_api_debug'), 'show_api_debug');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_config_deactivate_ivt'), 'deactivate_ivt');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_config_deactivate_get_existing'), 'deactivate_get_existing');
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_config_title_sysaccounts'));
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_config_create_default_sysaccount'), 'create_default_sysaccount');
		$this->addItem($cb);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_default_sysaccount'), 'default_sysaccount');
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
			$ti = new ilTextInputGUI($this->pl->txt('admin_config_sysaccount_' . $domain), $domain . '_sysaccount');
			$this->addItem($ti, false);
		}
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_config_title_certificates'));
		$this->addItem($h);
		//
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_file_crt'), 'crt_file');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_file_castkey'), 'castkey_file');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_castkeypassword'), 'castkey_password');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_file_localkey'), 'localkey_file');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_file_cacrt'), 'cacrt_file');
		$this->addItem($ti);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_config_title_switch'));
		$this->addItem($h);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_host_switch_api'), 'switch_api_host');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_host_switchcast'), 'switchcast_host');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_external_authority_host'), 'external_authority_host');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_external_authority_id'), 'scast_external_authority_id');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_scast_metadata_export'), 'scast_metadata_export');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_scast_configuration_id'), 'scast_configuration_id');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_scast_streaming_configuration_id'), 'scast_streaming_configuration_id');
		$this->addItem($ti);
		//
		$ti = new ilTextInputGUI($this->pl->txt('admin_config_scast_scast_access'), 'scast_access');
		$this->addItem($ti);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_config_allow_test_channels'), 'allow_test_channels');
		$this->addItem($cb);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_config_scast_use_eula'), 'admin_config_scast_use_eula');
		$te = new ilTextareaInputGUI($this->pl->txt('admin_config_scast_eula_text'), xscaConfig::F_EULA_TEXT);
		$te->setUseRte(true);
		$te->setRteTags(array_merge($te->getRteTags(), array( 'a' )));
		$cb->addSubItem($te);
		$this->addItem($cb);
		//
		$h = new ilFormSectionHeaderGUI();
		$h->setTitle($this->pl->txt('admin_config_title_upload_token'));
		$this->addItem($h);
		//
		$cb = new ilCheckboxInputGUI($this->pl->txt('admin_config_allow_upload_token'), xscaConfig::ALLOW_UPLOAD_TOKEN);
		$this->addItem($cb);
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
			$a_item->setInfo($this->pl->txt('admin_config_' . $a_item->getPostVar() . '_info'));
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
		if (! $this->checkInput()) {
			return false;
		}

		return true;
	}


	/**
	 * @return bool
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
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


	protected function addCommandButtons() {
		$this->addCommandButton('save', $this->pl->txt('admin_form_button_save'));
		$this->addCommandButton('cancel', $this->pl->txt('admin_form_button_cancel'));
	}
}