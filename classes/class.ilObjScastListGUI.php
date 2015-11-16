<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Repository/classes/class.ilObjectPluginListGUI.php';
include_once 'class.ilScastPlugin.php';

/**
 * ListGUI implementation for Scast object plugin. This one
 * handles the presentation in container items (categories, courses, ...)
 * together with the corresponfing ...Access class.
 *
 * PLEASE do not create instances of larger classes here. Use the
 * ...Access class to get DB data and keep it small.
 *
 * @author        Alex Killing <alex.killing@gmx.de>
 */
class ilObjScastListGUI extends ilObjectPluginListGUI {

	public function initType() {
		$this->setType('xsca');
	}


	/**
	 * @return string
	 */
	public function getGuiClass() {
		return 'ilObjScastGUI';
	}


	/**
	 * @return array
	 */
	public function initCommands() {
		$this->copy_enabled = false;
        $this->enableTags(true);

		return array(
			array(
				'permission' => 'read',
				'cmd' => 'showContent',
				'default' => true
			),
			array(
				'permission' => 'write',
				'cmd' => 'editProperties',
				'txt' => $this->txt('edit'),
				'default' => false
			),
		);
	}


	/**
	 * @return array
	 */
	public function getProperties() {
		$props = array();
		$this->plugin->includeClass('class.ilObjScastAccess.php');
		if (!ilObjScastAccess::checkOnline($this->obj_id)) {
			$props[] = array(
				'alert' => true,
				'property' => $this->txt('status'),
				'value' => $this->txt('offline')
			);
		}

		return $props;
	}
}

?>
