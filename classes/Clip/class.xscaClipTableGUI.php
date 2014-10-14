<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('class.xscaClipGUI.php');
require_once('./Services/Table/classes/class.ilTable2GUI.php');
require_once('./Services/UIComponent/AdvancedSelectionList/classes/class.ilAdvancedSelectionListGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Services/class.ilWaitGUI.php');
require_once('./Services/Utilities/classes/class.ilCSVWriter.php');
require_once("./Services/Excel/classes/class.ilExcelUtils.php");
require_once("./Services/Excel/classes/class.ilExcelWriterAdapter.php");

/**
 * GUI class for course/group waiting list
 *
 * @author  Fabian Schmid
 * @version $Id$
 *
 */
class xscaClipTableGUI extends ilTable2GUI {

	/**
	 * @param ilObjScastGUI $a_obj_scastgui
	 * @param string        $command
	 * @param string        $header
	 */
	public function __construct(ilObjScastGUI $a_obj_scastgui, $command, $header = '') {
		global $ilCtrl, $ilAccess, $ilUser, $ilToolbar, $tpl;
		/**
		 * @var $ilUser    ilObjUser
		 * @var $ilCtrl    ilCtrl
		 * @var $ilToolbar ilToolbarGUI
		 * @var $ilAccess  ilAccessHandler
		 */
		$this->objScastGui = $a_obj_scastgui;
		$this->objScast = $a_obj_scastgui->object;
		$this->setId('xsca_clips_' . $a_obj_scastgui->getScastObject()->getId());
		$this->setPrefix('xsca_clips');
		$this->pl = ilScastPlugin::getInstance();
		$this->ctrl = $ilCtrl;
		$this->user = $ilUser;
		$this->access = $ilAccess;
		$this->toolbar = $ilToolbar;
		//
		$tpl->addCss($this->pl->getStyleSheetLocation('default/table.css'));
		parent::__construct($a_obj_scastgui, $command);
		$this->setFilterCols(4);
		$this->setFormAction($ilCtrl->getFormAction($this->parent_obj, 'applyFilter'));
		$this->initFilter();
		$this->setFilterCommand('applyFilter');
		$this->setHeaderHTML($header);
		$this->setShowRowsSelector(true);
		$this->setExportFormats(array( self::EXPORT_CSV ));
		// Nur falls Schreibberechtigt und falls External-Account (SWITCHaai vorhanden)
		if (ilObjScastAccess::checkSwitchCastUseage()) {
			$this->toolbar->addButton($this->pl->txt('add_clip'), $this->objScast->getUploadForm(), '_blank');
		}
		$this->toolbar->addButton($this->pl->txt('reload_clips'), $this->ctrl->getLinkTarget($this->objScastGui, 'reloadCache'), '', '', '', 'reloadCache');
		$arrSelectedColumns = $this->getSelectedColumns();
		$this->addColumn($this->pl->txt('preview'), '', '170px');
		$this->addColumn($this->pl->txt('clips'), '', '120px');
		if ($arrSelectedColumns['title']) {
			$this->addColumn($this->pl->txt('title'), 'title', 'auto');
		}
		if ($arrSelectedColumns['presenter']) {
			$this->addColumn($this->pl->txt('presenter'), 'presenter', 'auto');
		}
		if ($arrSelectedColumns['location']) {
			$this->addColumn($this->pl->txt('location'), 'location', 'auto');
		}
		//Recording-Station ivt
		if ($this->objScast->getIvt()) {
			if ($arrSelectedColumns['recordingstation']) {
				$this->addColumn($this->pl->txt('recordingstation'), 'recordingstation', 'auto');
			}
		}
		if ($arrSelectedColumns['date']) {
			$this->addColumn($this->pl->txt('date'), 'date', 'auto');
		}
		//Owner ivt
		if ($this->objScast->getIvt()) {
			if ($arrSelectedColumns['owner']) {
				$this->addColumn($this->pl->txt('owner'), 'owner', 'auto');
			}
		}
		// Action-Menu - sofern iVT oder Schreibberechtigungen auf Cast
		if ($this->objScast->getIvt() OR $this->access->checkAccess('write', '', $this->objScast->getRefId())) {
			$this->addColumn($this->pl->txt('actions'), '', 'auto');
		}
		//		$this->setExternalSegmentation(true);
		//		$this->setExternalSorting(true);
		$this->setRowTemplate('tpl.content_table.html', $this->pl->getDirectory());
		$data = xscaclip::getAllInstancesForChannel($this->objScast, $this->filter);
		$data = $this->checkAccess($data);
		$this->setData($data);
		//ilScastRequestCache::flush(ilObject2::_lookupObjId($_GET['ref_id']));
	}


	/**
	 * @return array
	 */
	public function getSelectableColumns() {
		$cols = array();
		$cols['title'] = array(
			'txt' => $this->pl->txt('title'),
			'default' => true
		);
		$cols['presenter'] = array(
			'txt' => $this->pl->txt('presenter'),
			'default' => true
		);
		$cols['location'] = array(
			'txt' => $this->pl->txt('location'),
			'default' => true
		);
		// Recording-Station ivt
		if ($this->objScast->getIvt()) {
			$cols['recordingstation'] = array(
				'txt' => $this->pl->txt('recordingstation'),
				'default' => true
			);
		}
		$cols['date'] = array(
			'txt' => $this->pl->txt('date'),
			'default' => true
		);
		$cols['owner'] = array(
			'txt' => $this->pl->txt('owner'),
			'default' => true
		);

		return $cols;
	}


	/**
	 * @param array $a_set
	 */
	public function fillRow($a_set) {
		$this->tpl->setCurrentBlock('row');
		$clip = xscaClip::getInstance($this->objScast->getExtId(), $a_set->ext_id);
		$this->tpl->setVariable('CSS_STATUS', 'status_' . $clip->getStatus());
		if ($clip->getStatus() != 'published') {
			$this->tpl->setVariable('CLIP_STATUS', $this->pl->txt('clip_status_' . $clip->getStatus()));
		}
		$sel_cols = $this->getSelectedColumns();
		// Properties
		if ($sel_cols['title']) {
			$this->tpl->setVariable('TITLE', $clip->getTitle());
			$this->tpl->setVariable('SUBTITLE', $clip->getSubtitle());
		}
		if ($sel_cols['presenter']) {
			$this->tpl->setVariable('PRESENTER', $clip->getPresenter());
		}
		if ($sel_cols['location']) {
			$this->tpl->setVariable('LOCATION', $clip->getLocation());
		}
		//Recording-Station ivt
		if ($this->objScast->getIvt()) {
			//Recording-Station ivt
			if ($this->objScast->getIvt()) {
				if ($sel_cols['recordingstation']) {
					$this->tpl->setVariable('RECORDINGSTATION', $clip->getRecordingStation());
				}
			}
		}
		if ($sel_cols['date']) {
			$this->tpl->setVariable('DATE', $clip->getIssuedOn());
		}
		$this->tpl->setVariable('IMAGE', $clip->getCover());
		if ($clip->getLinkFlash()) {
			$this->tpl->setCurrentBlock('flash');
			$this->tpl->setVariable('LinkFlash', $clip->getLinkFlash());
			$this->tpl->setVariable('FLASH', $this->pl->txt('flash'));
			$this->tpl->setVariable('ImgMp4', $this->pl->getImagePath('player_btn_flash.png'));
			$this->tpl->parseCurrentBlock();
		}
		if ($clip->getLinkMov()) {
			$this->tpl->setCurrentBlock('quicktime');
			$this->tpl->setVariable('QUICKTIME', $this->pl->txt('quicktime'));
			$this->tpl->setVariable('LinkMov', $clip->getLinkMov());
			$this->tpl->setVariable('ImgMov', $this->pl->getImagePath('player_btn_quicktime.png'));
			$this->tpl->parseCurrentBlock();
		}
		if ($clip->getLinkM4v()) {
			$this->tpl->setCurrentBlock('ipod');
			$this->tpl->setVariable('IPOD', $this->pl->txt('ipod'));
			$this->tpl->setVariable('LinkM4v', $clip->getLinkM4v());
			$this->tpl->setVariable('ImgM4v', $this->pl->getImagePath('player_btn_ipod.png'));
			$this->tpl->parseCurrentBlock();
		}
		if ($clip->getAnnotationlink()) {
			$this->tpl->setCurrentBlock('annotation');
			$this->tpl->setVariable('ANNOTATION', 'Annotation');
			$this->tpl->setVariable('LinkAnnotation', $clip->getAnnotationlink());
			$this->tpl->setVariable('ImgAnno', $this->pl->getImagePath('player_btn_flash.png'));
			$this->tpl->parseCurrentBlock();
		}
		if ($clip->getDownloadLink()) {
			//			$this->tpl->setCurrentBlock('download');
			//			$this->tpl->setVariable('DOWNLOAD', 'Download');
			//			$this->tpl->setVariable('DownloadLink', $clip->getDownloadLink());
			//			$this->tpl->setVariable('ImgDownload', $this->pl->getImagePath('player_btn_download.png'));
			//			$this->tpl->setVariable('TOOLTIP', $this->pl->txt('download_tooltip'));
			//			$this->tpl->parseCurrentBlock();
		}
		//		$this->tpl->setVariable('LinkBox', $clip->getLinkBox());
		//Owner ivt
		if ($this->objScast->getIvt()) {
			if ($sel_cols['owner']) {
				if ($clip->getOwner()) {
					if ($usr_id = xscaUser::getUsrIdForExtAccount($clip->getOwner())) {
						$obj_user = new ilObjUser($usr_id);

						if ($obj_user->getLastname() != '') {
							$this->tpl->setVariable('OWNER',
								$obj_user->getLastname() . ', ' . $obj_user->getFirstname() . ' (' . $obj_user->getEmail() . ')');
						} else {
							$this->tpl->setVariable('OWNER', $this->pl->txt('owner_unknown'));
						}
					}
				} else {
					$this->tpl->setVariable('OWNER', $this->pl->txt('no_owner'));
				}
			}
		}
		//Action-Menu-Spalte - sofern iVT oder Schreibberechtigungen auf Cast
		if ($this->objScast->getIvt() OR $this->access->checkAccess('write', '', $this->objScast->getRefId())) {
			// Action-Menu-Zeile - sofern Clip-Owner oder Schreibberechtigungen auf Cast
			if (ilObjScastAccess::checkAccessOnClip($clip, 'write')) {
				$alist = new ilAdvancedSelectionListGUI();
				$alist->setId($a_set->id);
				$alist->setListTitle($this->pl->txt('actions'));
				$this->ctrl->setParameterByClass('ilObjScastGUI', 'clip_ext_id', $clip->getExtId());
				if ($this->objScast->getIvt() AND ($this->objScast->getInvitingPossible() AND ($clip->getOwner() == $this->user->getExternalAccount()
							OR $this->access->checkAccess('write', '', $this->objScast->getRefId())))
				) {
					$alist->addItem($this->pl->txt('edit_members'), 'editmembers', $this->ctrl->getLinkTargetByClass('ilObjScastGUI', 'editClipMembers'));
				}
				if ($this->objScast->getIvt() AND $this->access->checkAccess('write', '', $this->objScast->getRefId())
				) {
					$alist->addItem($this->pl->txt('edit_owner'), 'edit', $this->ctrl->getLinkTargetByClass('ilObjScastGUI', 'editClipOwner'));
				}
				if ($this->access->checkAccess('write', '', $this->objScast->getRefId())) {
					// $alist->addItem($this->pl->txt('edit_switchcast_channel'), 'editchannel', $this->objScast->getEditLink(), NULL, NULL, '_blank');
					$alist->addItem($this->pl->txt('cut_switch'), 'cut', $clip->getLinkCuttingTool(), NULL, NULL, '_blank');
				}
				if ($this->access->checkAccess('write', '', $this->objScast->getRefId())) {
					$alist->addItem($this->pl->txt('delete_clip'), 'deleteclip', $this->ctrl->getLinkTargetByClass('ilObjScastGUI', 'confirmDeleteClip'));
					$alist->addItem($this->pl->txt('edit_clip'), 'edit', $this->ctrl->getLinkTargetByClass('xscaClipGUI', 'edit'));
				}
				$this->tpl->setVariable('ACTIONS', $alist->getHTML());
			}
		}
		$this->tpl->parseCurrentBlock();
	}


	/**
	 * @param $data
	 *
	 * @return array
	 */
	public function checkAccess($data) {
		$newData = array();
		if (count($data) > 0) {
			foreach ($data as $clip) {
				$clipGUI = new xscaClipGUI($this->objScastGui, $this->objScast, $clip->ext_id);
				if ($clipGUI->checkPermissionBool('read') OR !$this->objScast->getIvt()) {
					$newData[] = $clip;
				}
			}
		}

		return $newData;
	}


	public function getXscaHTML() {
		$tplsc = $this->pl->getTemplate('default/tpl.player_script.html', false, false);
		$script = $tplsc->get();
		$parent = self::getHTML();

		return $script . $parent;
	}


	public function initFilter() {
		$input = new ilTextInputGUI($this->pl->txt('title'), 'title');
		$this->addFilterItem($input);
		$input->readFromSession();
		$this->filter['title'] = $input->getValue();
		$input = new ilTextInputGUI($this->pl->txt('presenter'), 'presenter');
		$this->addFilterItem($input);
		$input->readFromSession();
		$this->filter['presenter'] = $input->getValue();
		$input = new ilTextInputGUI($this->pl->txt('location'), 'location');
		$this->addFilterItem($input);
		$input->readFromSession();
		$this->filter['location'] = $input->getValue();
		// Recording-Station ivt
		if ($this->objScast->getIvt()) {
			$input = new ilTextInputGUI($this->pl->txt('recordingstation'), 'ivt__recordingstation');
			$this->addFilterItem($input);
			$input->readFromSession();
			$this->filter['ivt__recordingstation'] = $input->getValue();
		}
		if ($this->access->checkAccess('write', '', $this->objScast->getRefId()) AND $this->objScast->getIvt()) {
			$ilParticipants = new ilParticipants($this->objScast->getCourseId());
			$arr_participants = array();
			$arr_participants[''] = '--';
			foreach ($ilParticipants->getParticipants() as $user_id) {
				// Falls AAI-User, so kommt der User in Frage
				if (ilObjUser::    _lookupExternalAccount($user_id)) {
					$ilObjUser = new ilObjUser($user_id);
					$arr_participants[$ilObjUser->getExternalAccount()] =
						$ilObjUser->getLastname() . ' ' . $ilObjUser->getFirstname() . ' (' . $ilObjUser->getEmail() . ')';
				}
			}
			@natcasesort($arr_participants);
			$input = new ilSelectInputGUI($this->pl->txt('owner'), 'ivt_owner');
			$input->setOptions($arr_participants);
			$this->addFilterItem($input);
			$input->readFromSession();
			$this->filter['ivt_owner'] = $input->getValue();
		}
		if ($this->access->checkAccess('write', '', $this->objScast->getRefId()) AND $this->objScast->getIvt()) {
			$input = new ilCheckboxInputGUI($this->pl->txt('without_owner'), 1);
			$this->addFilterItem($input);
			$input->readFromSession();
			$this->filter['withoutowner'] = $input->getChecked();
		}
	}
}

?>