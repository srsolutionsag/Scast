<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
require_once('./Services/InfoScreen/classes/class.ilInfoScreenGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Clip/class.xscaClipTableGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Clip/class.xscaClipGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Group/class.xscaGroupGUI.php');
require_once('class.ilScastPlugin.php');
require_once('class.ilObjScast.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Services/class.xscaLog.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Services/class.xscaToken.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Target/class.xscaTarget.php');

/**
 * ilObjScastGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @author            Martin Studer <ms@studer-raimann.ch>
 *
 * $Id$
 *
 * @ilCtrl_isCalledBy ilObjScastGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI, ilStartUpGUI
 * @ilCtrl_Calls      ilObjScastGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, xscaClipGUI, xscaGroupGUI, ilCommonActionDispatcherGUI
 */
class ilObjScastGUI extends ilObjectPluginGUI {

	const F_CHANNEL_ID = 'channel_id';
	const REF_ID = 'ref_id';
	/**
	 * @var ilObjScast
	 */
	public $object;
	/**
	 * @var xscaLog
	 */
	protected $log;
	/**
	 * @var ilScastPlugin
	 */
	protected $pl;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var xscaUser
	 */
	protected $xsca_user;
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs_gui;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;


	/**
	 * @param int $a_ref_id
	 * @param int $a_id_type
	 * @param int $a_parent_node_id
	 */
	public function __construct($a_ref_id = 0, $a_id_type = self::REPOSITORY_NODE_ID, $a_parent_node_id = 0) {
		parent::__construct($a_ref_id, $a_id_type, $a_parent_node_id);
		global $tpl, $ilCtrl, $ilAccess, $ilNavigationHistory, $ilTabs;
		/**
		 * @var $tpl                 ilTemplate
		 * @var $ilCtrl              ilCtrl
		 * @var $ilAccess            ilAccessHandler
		 * @var $ilNavigationHistory ilNavigationHistory
		 * @var $ilTabs              ilTabsGUI
		 */
		$this->tpl = $tpl;
		$this->log = xscaLog::getInstance();
		$this->history = $ilNavigationHistory;
		$this->access = $ilAccess;
		$this->ctrl = $ilCtrl;
		$this->xsca_user = xscaUser::getInstance();
		$this->tabs_gui = $ilTabs;
		$this->pl = ilScastPlugin::getInstance();
		if (exec('hostname') == 'ilias-webt1' OR $_GET['devmode'] OR xscaConfig::get('show_api_debug')) {
			$this->dev = true;
		}
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		if ($_GET['rl']) {
			$this->pl->updateLanguages();
		}
		if ($_GET['hrl']) {
			$this->pl->updateLanguageFiles();
		}
		switch ($this->ctrl->getNextClass()) {
			case 'xscagroupgui':
				$this->initHeader(false);
				$this->setTabs();
				$this->tabs_gui->activateTab('groups');
				$this->setLocator();
				$groups_gui = new xscaGroupGUI($this->object);
				$this->ctrl->forwardCommand($groups_gui);
				break;
			case 'xscaclipgui':
				$this->initHeader();
				$gui = new xscaClipGUI($this, $this->object, $_GET['clip_ext_id']);
				$this->ctrl->forwardCommand($gui);
				break;
			case 'ilcommonactiondispatchergui':
				include_once 'Services/Object/classes/class.ilCommonActionDispatcherGUI.php';
				$gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
				$this->ctrl->forwardCommand($gui);
				break;
			default:
				$this->initHeader(false);
				parent::executeCommand();
				break;
		}

		return true;
	}


	public function infoScreen() {
		$this->tabs_gui->setTabActive('info_short');
		$info = new ilInfoScreenGUI($this);
		$info->enablePrivateNotes();
		$info->getHiddenToggleButton();
		$info->addTagging();
		if ($this->object->getShowUploadToken() AND
			xscaConfig::get(xscaConfig::ALLOW_UPLOAD_TOKEN) AND ilObjScastAccess::checkPermissionOnchannel($_GET['ref_id'], 'read')
		) {
			$info->addSection($this->pl->txt('upload_token'));
			$info->addProperty($this->pl->txt('channel_id'), $this->object->getExtId());
			$info->addProperty($this->pl->txt('daily_upload_token'), $this->object->getDailyToken());
		}
		$this->ctrl->forwardCommand($info);
	}


	/**
	 * @param bool $clear_tabs
	 */
	protected function initHeader($clear_tabs = true) {
		global $lng;
		if (!$this->object instanceof ilObjScast) {
			return false;
		}
		$this->tpl->setTitle($this->object->getTitle());
		$this->tpl->setDescription($this->object->getDescription());
		$this->tpl->setTitleIcon(ilObject::_getIcon(ilObject::_lookupObjId($_GET[self::REF_ID]), "big"), $lng->txt("obj_"
			. ilObject::_lookupType($_GET[self::REF_ID], true)));
		$this->tpl->setLocator();
		if ($clear_tabs) {
			$this->tabs_gui->clearTargets();
			$this->tabs_gui->setBackTarget($this->pl->txt('back_to_list'), $this->ctrl->getLinkTarget($this, 'showContent'));
		}
	}


	/**
	 * @return ilObjScast
	 */
	public function getScastObject() {
		return $this->object;
	}


	/**
	 * @return string
	 */
	final function getType() {
		return 'xsca';
	}


	/**
	 * @param $cmd
	 */
	function performCommand($cmd) {
		switch ($cmd) {
			case 'editProperties': // list all commands that need write permission here
			case 'updateProperties':
				$this->checkPermission('write');
				$this->$cmd();
				break;
			case 'showContent': // list all commands that need read permission here
			case 'applyFilter':
			case 'resetFilter':
			case 'reloadCache':
				$this->checkPermission('read');
				$this->$cmd();
				break;
			case 'editClipOwner': // list all commands that are for clip handling here
			case 'updateClipOwner':
			case 'editClipMembers':
			case 'addClipMember':
			case 'updateClipMember':
			case 'deleteClipMember':
			case 'confirmDeleteClip':
			case 'deleteClip':
				$this->tabs_gui->activateTab('content');
				$clip_gui = new xscaClipGUI($this, $this->object);
				$this->ctrl->forwardCommand($clip_gui);
				break;
		}
	}


	/**
	 * @return string
	 */
	function getAfterCreationCmd() {
		return 'showContent';
	}


	/**
	 * @return string
	 */
	function getStandardCmd() {
		return 'showContent';
	}


	protected function setTabs() {
		if ($this->access->checkAccess('read', '', $this->object->getRefId())) {
			$this->tabs_gui->addTab('content', $this->txt('content'), $this->ctrl->getLinkTarget($this, 'showContent'));
		}
		$this->addInfoTab();
		if ($this->access->checkAccess('write', '', $this->object->getRefId())) {
			$this->tabs_gui->addTab('properties', $this->txt('properties'), $this->ctrl->getLinkTarget($this, 'editProperties'));
		}
		if ($this->access->checkAccess('write', '', $this->object->getRefId()) AND $this->object->getIvt()) {
			$this->tabs_gui->addTab('groups', $this->txt('groups'), $this->ctrl->getLinkTargetByClass('xscaGroupGUI', 'showGroups'));
		}
		$this->addPermissionTab();
	}


	public function create() {
		global $rbacsystem, $lng;
		$this->object = new ilObjScast();
		if (!$rbacsystem->checkAccess('create', $_GET[self::REF_ID], $this->object->getType()) OR !$this->xsca_user->hasSystemAccount()
		) {
			ilUtil::sendFailure($lng->txt('no_permission'));
		} else {
			$this->ctrl->setParameter($this, 'new_type', $this->object->getType());
			$this->xsca_user->create();
			$this->initPropertiesForm('create');
			$this->tpl->setContent($this->form->getHTML());
		}
	}


	public function save() {
		global $rbacsystem;

		if (!$rbacsystem->checkAccess('create', $_GET[self::REF_ID], self::getType())) {
			$this->ilias->raiseError($this->lng->txt('no_create_permission'), $this->ilias->error_obj->MESSAGE);
		}
		$this->ctrl->setParameter($this, 'new_type', self::getType());
		$this->initPropertiesForm('create', self::getType());
		if (xscaConfig::get(xscaConfig::F_USE_EULA)) {
			$eula = $this->form->getInput('accept_eula', false);
		} else {
			$eula = true;
		}
		if ($this->form->checkInput() AND $eula) {
			$newObj = new ilObjScast();
			$newObj->setTitle(ilUtil::stripSlashes($_POST['title']));
			$newObj->setDescription(ilUtil::stripSlashes($_POST['desc']));
			$newObj->setIvt($this->form->getInput('clip_based_rightmanagement'));
			$newObj->setLicense($this->form->getInput('license'));
			$newObj->setDisciplineId($this->form->getInput('discipline_0'));
			$newObj->setEstimatetContentInHours($this->form->getInput('estimated_content_in_hours'));
			$newObj->setLifetimeOfContentinMonth($this->form->getInput('lifetime_of_content_in_months'));
			$newObj->setDepartment($this->form->getInput('department'));
			$newObj->setStreamingOnly($this->form->getInput('streaming_only'));
			$newObj->setInvitingPossible($this->form->getInput('clip_inviting_possible'));
			$newObj->setAllowAnnotations($this->form->getInput('allow_annotations'));
			$newObj->setIntroductionText($this->form->getInput('introduction_text'));
			$newObj->setChannelKind($this->form->getInput('channel_kind'));
			// Falls bestehender Channel
			if ($this->form->getInput('channel_type') == '2') {
				$newObj->setExtId($this->form->getInput(self::F_CHANNEL_ID));
			}
			try {
				$newObj->create();
			} catch (Exception $e) {
				if($e->getMessage()) {
					return false;
				}
			}


			$newObj->createReference();
			$newObj->putInTree($_GET[self::REF_ID]);
			$newObj->setPermissions($_GET[self::REF_ID]);
			ilUtil::sendSuccess($this->pl->txt('msg_obj_modified'), true);
			$this->afterSave($newObj);

			return;
		} elseif (!$eula) {
			global $lng;
			/**
			 * @var $input ilCheckboxInputGUI
			 */
			$input = $this->form->getItemByPostVar('accept_eula');
			$input->setAlert($lng->txt("msg_input_is_required"));
		}
		$this->form->setValuesByPost();
		$this->tpl->setContent($this->form->getHtml());
	}


	function editProperties() {
		$this->tabs_gui->activateTab('properties');
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$this->tpl->setContent($this->form->getHTML());
	}


	/**
	 * @param string $a_mode
	 */
	public function initPropertiesForm($a_mode = 'edit') {
		if ($a_mode == 'edit') {
			$edit_mode = true;
			if (is_object($this->object)) {
				if ($this->object->hasReferencedChannels() > 1) {
					ilUtil::sendInfo($this->txt('has_references'));
				}
			}
		} else {
			$edit_mode = false;
			$this->tpl->addJavaScript($this->plugin->getStyleSheetLocation('default/existing_channel.js'));
			$this->object = new ilObjScast();
			$this->object->setSysAccount($this->xsca_user->getSystemAccount());
		}
		$this->form = new ilPropertyFormGUI();
		// Channel
		if (!$edit_mode) {
			// Channel
			$radio_prop = new ilRadioGroupInputGUI($this->txt('channel'), 'channel_type');
			$op = new ilRadioOption($this->txt('new_channel'), '1', '');
			$radio_prop->addOption($op);
			// Test- oder Produktiv-Channel - wird nur angezeigt, falls diese Funktion in der ILIAS-Administration aktiviert  wird
			if (xscaConfig::get('allow_test_channels')) {
				$cb_prop = new ilSelectInputGUI($this->txt('channel_kind'), 'channel_kind');
				$arr_kind = array(
					'periodic' => $this->txt('channel_kind_productiv'),
					'test' => $this->txt('channel_kind_test')
				);
				$cb_prop->setOptions($arr_kind);
				$op->addSubItem($cb_prop);
			}
			// Existing Channel
			if (!xscaConfig::get('deactivate_get_existing')) {
				$op2 = new ilRadioOption($this->txt('existing_channel'), '2', '');
				$cb_prop = new ilSelectInputGUI($this->txt('select_existing_channel'), self::F_CHANNEL_ID);
				$cb_prop->setOptions($this->xsca_user->getChannelsOfUser(true));
				$op2->addSubItem($cb_prop);
				if (count($this->xsca_user->getChannelsOfUser(true)) == 0) {
					$op2->setDisabled(true);
					$op2->setInfo($this->plugin->txt('no_channels_available'));
				}
				$radio_prop->addOption($op2);
			}
			$radio_prop->setValue('1');
			$radio_prop->setRequired(true);
			$this->form->addItem($radio_prop);
		}
		// title
		$ti = new ilTextInputGUI($this->txt('title'), 'title');
		if ($this->dev) {
			$ti->setValue('TCn_ilias: ' . date(DATE_ISO8601));
		}
		$ti->setRequired(true);
		$this->form->addItem($ti);
		// subtitle
		$ta = new ilTextAreaInputGUI($this->txt('description'), 'desc');
		$this->form->addItem($ta);
		// Online
		if ($edit_mode) {
			$online = new ilCheckboxInputGUI($this->pl->txt('online'), 'online');
			$online->setChecked($this->object->getOnline());
			$this->form->addItem($online);
		}
		//Work Instructions
		$item = new ilTextAreaInputGUI($this->txt('introduction_text'), 'introduction_text');
		$item->setRows(5);
		$this->form->addItem($item);
		// Discipline 1
		// Discipline 2
		// Discipline 3
		$dis = $this->object->getAllDisciplines();
		if (is_array($dis) AND count($dis) > 0) {
			for ($x = 0; $x < 1; $x ++) {
				$discform[$x] = new ilSelectInputGUI($this->txt('discipline_' . $x), 'discipline_' . $x);
				$discform[$x]->setOptions($dis);
				$discform[$x]->setRequired(true);
				$this->form->addItem($discform[$x]);
			}
		}
		// Access
		// not in ILIAS
		// License
		$lic = $this->object->getAllLicenses();
		if (is_array($lic) AND count($lic) > 0) {
			$licform = new ilSelectInputGUI($this->txt('license'), 'license');
			$licform->setOptions($lic);
			$licform->setRequired(false);
			$licform->setInfo($this->txt('license_desc'));
			$this->form->addItem($licform);
		}
		// Estimated Video Content
		$ni = new ilNumberInputGUI($this->txt('estimated_content_in_hours'), 'estimated_content_in_hours');
		if ($this->dev) {
			$ni->setValue(1);
		}
		$ni->setRequired(true);
		$ni->setSize(2);
		$ni->setMinValue(1);
		$ni->setInfo($this->txt('estimated_content_in_hours_desc'));
		$this->form->addItem($ni);
		// Intended Lifetime
		$lt = new ilSelectInputGUI($this->txt('lifetime_of_content_in_months'), 'lifetime_of_content_in_months');
		$month = array(
			6 => '6 month',
			12 => '1 year',
			24 => '2 years',
			36 => '3 years',
			60 => '4 years',
			72 => '5 years',
		);
		$lt->setOptions($month);
		$lt->setRequired(true);
		$lt->setInfo($this->txt('lifetime_of_content_in_months_desc'));
		$this->form->addItem($lt);
		// Department
		$ti = new ilTextInputGUI($this->txt('department'), 'department');
		if ($this->dev) {
			$ti->setValue('iLUB');
		}
		$ti->setRequired(true);
		$ti->setInfo($this->txt('department_desc'));
		$this->form->addItem($ti);
		// Annotations
		$annot_type = new ilRadioGroupInputGUI($this->txt('annotations'), 'allow_annotations');
		$annot_type->setValue($this->object->getAllowAnnotations());
		$opt = new ilRadioOption($this->txt('dont_allow_annotations'), 0);
		$annot_type->addOption($opt);
		$opt = new ilRadioOption($this->txt('allow_annotations'), 1);
		$annot_type->addOption($opt);
		$this->form->addItem($annot_type);
		// Streaming Only: Nur editierbar, falls neue Channels generiert werden. Dies ändert das SWITCHcast-Template. Dies sollte man
		// nicht bei bestehenden Channel tun.
		$cb = new ilCheckboxInputGUI($this->txt('streaming_only'), 'streaming_only');
		if ($edit_mode) {
			$cb->setDisabled(true);
		}
		$cb->setValue(1);
		$this->form->addItem($cb);
		// Type (IVT or Scast)
		if (!xscaConfig::get('deactivate_ivt')) {
			$cb = new ilCheckboxInputGUI($this->txt('clip_based_rightmanagement'), 'clip_based_rightmanagement');
			$cb->setInfo($this->txt('clip_based_rightmanagement_desc'));
			$cb->setValue(1);
			$subcb = new ilCheckboxInputGUI($this->txt('clip_inviting_possible'), 'clip_inviting_possible');
			$subcb->setValue(1);
			$cb->addSubItem($subcb);
			$this->form->addItem($cb);
		}
		// EULA
		if (xscaConfig::get(xscaConfig::F_USE_EULA) AND !$edit_mode) {
			$cb = new ilCheckboxInputGUI($this->pl->txt('accept_eula'), 'accept_eula');
			$cb->setRequired(true);
			$cb->setInfo(xscaConfig::get(xscaConfig::F_EULA_TEXT));
			//			$cb->setValue(1);
			$this->form->addItem($cb);
		}
		//
		if ($edit_mode) {
			// channel_id
			$ne = new ilNonEditableValueGUI($this->txt(self::F_CHANNEL_ID), self::F_CHANNEL_ID);
			$ne->setValue($this->object->getExtId());
			$this->form->addItem($ne);
			$ci = new ilCustomInputGUI($this->txt('edit_switchcast_channel'), 'channel_link');
			$ci->setHtml('<a target=\'_blank\' href=\'' . $this->object->getEditLink() . '\'>' . $this->object->getEditLink() . '</a>');
			$this->form->addItem($ci);
		}
		if (xscaConfig::get(xscaConfig::ALLOW_UPLOAD_TOKEN)) {
			$cb = new ilCheckboxInputGUI($this->pl->txt('show_upload_token'), 'show_upload_token');
			$this->form->addItem($cb);
		}
		if ($edit_mode) {
			$this->form->addCommandButton('updateProperties', $this->txt('update'));
		} else {
			$this->form->addCommandButton('save', $this->txt('save'));
		}
		$this->form->setTitle($this->txt('edit_properties'));
		$this->form->setFormAction($this->ctrl->getFormAction($this));
	}


	public function getPropertiesValues() {
		$values['title'] = $this->object->getTitle();
		$values['desc'] = $this->object->getDescription();
		$values['clip_based_rightmanagement'] = $this->object->getIvt();
		$values['clip_inviting_possible'] = $this->object->getInvitingPossible();
		$values['allow_annotations'] = $this->object->getAllowAnnotations() ? 1 : 0;
		$values['introduction_text'] = $this->object->getIntroductionText();
		$values['license'] = trim($this->object->getLicense());
		$values['discipline_0'] = $this->object->getDisciplineId();
		$values['estimated_content_in_hours'] = $this->object->getEstimatetContentInHours();
		$values['lifetime_of_content_in_months'] = $this->object->getLifetimeOfContentinMonth();
		$values['department'] = $this->object->getDepartment();
		$values['streaming_only'] = $this->object->getStreamingOnly();
		$values['online'] = $this->object->getOnline();
		$values['show_upload_token'] = $this->object->getShowUploadToken();
		$this->form->setValuesByArray($values);
	}


	public function updateProperties() {
		$this->initPropertiesForm();
		if ($this->form->checkInput()) {
			$this->object->setTitle($this->form->getInput('title'));
			$this->object->setDescription($this->form->getInput('desc'));
			$this->object->setOnline($this->form->getInput('online'));
			$this->object->setIvt($this->form->getInput('clip_based_rightmanagement'));
			$this->object->setLicense($this->form->getInput('license'));
			$this->object->setDisciplineId($this->form->getInput('discipline_0'));
			$this->object->setEstimatetContentInHours($this->form->getInput('estimated_content_in_hours'));
			$this->object->setLifetimeOfContentinMonth($this->form->getInput('lifetime_of_content_in_months'));
			$this->object->setDepartment($this->form->getInput('department'));
			$this->object->setStreamingOnly($this->form->getInput('streaming_only'));
			$this->object->setInvitingPossible($this->form->getInput('clip_inviting_possible'));
			$this->object->setAllowAnnotations($this->form->getInput('allow_annotations'));
			$this->object->setIntroductionText($this->form->getInput('introduction_text'));
			$this->object->setShowUploadToken($this->form->getInput('show_upload_token'));
			$this->object->update();
			//			$this->reloadCache();
			//			$this->object->doUpdate();
			ilUtil::sendSuccess($this->pl->txt('msg_obj_modified'), true);
			$this->ctrl->redirect($this, 'editProperties');
		}
		$this->form->setValuesByPost();
		$this->tpl->setContent($this->form->getHtml());
	}


	protected function showContent() {
		$this->tabs_gui->activateTab('content');
		$html_output = $this->renderIntroductionText();
		$table = new xscaClipTableGUI($this, 'showContent');
		$html_output .= $table->getXscaHTML();
		$this->tpl->setContent($html_output . ilWaitGUI::init('#reloadCache', $this->pl->txt('msg_reload_clips'))->getHtml());
		$this->tpl->setPermanentLink($this->getType(), $this->object->getRefId());
	}


	/**
	 * @return string
	 */
	protected function renderIntroductionText() {
		$html_output = '';
		if ($this->object->getIntroductionText()) {
			$introduction_text = $this->pl->getTemplate('default/tpl.introduction_text.html');
			$introduction_text->setVariable('TITLE', $this->plugin->txt('introduction_text'));
			$introduction_text->setVariable('BODY', nl2br($this->object->getIntroductionText()));
			$html_output = $introduction_text->get();
		}

		return $html_output;
	}


	public function applyFilter() {
		$table_gui = new xscaClipTableGUI($this, 'showContent');
		$table_gui->writeFilterToSession(); // writes filter to session
		$table_gui->resetOffset(); // sets record offest to 0 (first page)
		$this->ctrl->redirect($this, 'showContent');
		//		$this->showContent();
	}


	public function resetFilter() {
		$table_gui = new xscaClipTableGUI($this, 'showContent');
		$table_gui->resetOffset(); // sets record offest to 0 (first page)
		$table_gui->resetFilter(); // clears filter
		$this->ctrl->redirect($this, 'showContent');
		//		$this->showContent();
	}


	public function reloadCache() {
		xscaApiCache::flush(ilObject2::_lookupObjId($_GET[self::REF_ID]));
		$data = $this->object->getClips(array());
		foreach ($data as $clip) {
			xscaClip::getInstance($this->object->getExtId(), $clip->ext_id);
		}
		ilUtil::sendSuccess($this->pl->txt('msg_cache_flushed'), true);
	}


	/**
	 * @param $a_target
	 *
	 * @access static
	 */
	public function _goto($a_target) {
		global $lng, $ilAccess;
		/**
		 * @var $ilAccess ilAccessHandler
		 * @var $ilCtrl   ilCtrl
		 */
		$target = xscaTarget::get($a_target);
		$ref_id = $target->getRefId();
		if ($target->getIsSwitchRedirect()) {

			$clip = xscaClip::getInstance($target->getChannelId(), $target->getClipId());
			ilObjScastAccess::checkAccessOnClipForAllReferences($clip);

			if (ilObjScastAccess::checkAccessOnClip($clip, 'read', $ref_id)) {
				xscaToken::extAuthRedirectToVodUrl($target);
			} else {
				ilUtil::sendFailure(sprintf($lng->txt('msg_no_perm_read_item'), ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id))), true);
				ilObjectGUI::_gotoRepositoryRoot();
				exit;
			}
		} else {
			if ($ilAccess->checkAccess('read', '', $ref_id) OR $ilAccess->checkAccess('write', '', $ref_id)) {
				self::redirectToGUI($target);
			} elseif ($ilAccess->checkAccess('visible', '', $ref_id)) {
				self::redirectToGUI($target, 'infoScreen');
			} elseif ($ilAccess->checkAccess('read', '', ROOT_FOLDER_ID)) {
				if (xscaConfig::get('goto') == xscaConfig::GOTO_REPO) {
					ilUtil::sendFailure(sprintf($lng->txt('msg_no_perm_read_item'), ilObject::_lookupTitle(ilObject::_lookupObjId($ref_id))), true);
					ilObjectGUI::_gotoRepositoryRoot();
					exit;
				} elseif (xscaConfig::get('goto') == xscaConfig::GOTO_LOGIN) {
					ilUtil::redirect('login.php?target=' . $_GET['target'] . '&cmd=force_login');
					exit;
				}
			}
		}
		exit;
	}


	/**
	 * @param xscaTarget $target
	 * @param string     $command
	 */
	protected static function redirectToGUI(xscaTarget $target, $command = '') {
		global $ilCtrl;
		/**
		 * @var $ilCtrl ilCtrl
		 */
		$ilCtrl->initBaseClass('ilObjPluginDispatchGUI');
		$ilCtrl->setTargetScript('ilias.php');
		$ilCtrl->getCallStructure(strtolower('ilObjPluginDispatchGUI'));
		$ilCtrl->setParameterByClass($target->getGuiClass(), 'ref_id', $target->getRefId());
		$ilCtrl->redirectByClass(array(
			'ilobjplugindispatchgui',
			$target->getGuiClass()
		), $command);
	}
}


?>
