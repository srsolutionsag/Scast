<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('class.ilScastPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Config/class.xscaConfigFormGUI.php');

/**
 * SwitchCast Configuration
 *
 * @author  Alex Killing <alex.killing@gmx.de>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id$
 *
 */
class ilScastConfigGUI extends ilPluginConfigGUI {

	function __construct() {
		global $ilCtrl, $tpl, $ilTabs, $ilToolbar;
		/**
		 * @var $ilCtrl    ilCtrl
		 * @var $tpl       ilTemplate
		 * @var $ilTabs    ilTabsGUI
		 * @var $ilToolbar ilToolbarGUI
		 */
		$this->ctrl = $ilCtrl;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
		$this->toolbar = $ilToolbar;
		$this->pl = ilScastPlugin::getInstance();
	}


	/**
	 * @param $cmd
	 */
	function performCommand($cmd) {
		$this->tabs->addTab('configure', $this->pl->txt('tab_configure'), $this->ctrl->getLinkTarget($this, 'configure'));
		$this->tabs->addTab('user', $this->pl->txt('tab_user'), $this->ctrl->getLinkTarget($this, 'user'));
		switch ($cmd) {
			case 'configure':
			case 'save':
			case 'export':
			case 'import':
			case 'importScreen':
				$this->tabs->setTabActive('configure');
				$this->$cmd();
				break;
			case 'user':
				$this->tabs->setTabActive('user');
				$this->$cmd();
				break;
		}
	}


	public function export() {
		$xObj = simplexml_load_string('<?xml version=\'1.0\' encoding=\'utf-8\'?><scastconfig/>');
		$form = new xscaConfigFormGUI($this);
		if ($form->checkInput()) {
			foreach ($form->getExportItems() as $key => $item) {
				$xObj->addChild($item->getPostvar(), $item->getValue());
				foreach ($item->getSubItems() as $sub_item) {
					$xObj->addChild($item->getPostvar(), $item->getValue());
				}
			}
		}
		file_put_contents('/tmp/scastexport.xml', $xObj->asXML());
		ilUtil::deliverFile('/tmp/scastexport.xml', 'scastexport.xml');
		unlink('/tmp/scastexport.xml');
		$this->ctrl->redirect($this, 'configure');
	}


	/**
	 * @return ilPropertyFormGUI
	 */
	public function importScreen() {
		$form = new ilPropertyFormGUI();
		$file = new ilFileInputGUI($this->pl->txt('import_xml'), 'scastexport');
		$form->addItem($file);
		$form->addCommandButton('import', $this->pl->txt('import_xml'));
		$form->setTitle($this->pl->txt('import_xml'));
		$form->setFormAction($this->ctrl->getFormAction($this));
		$this->tpl->setContent($form->getHTML());

		return $form;
	}


	public function import() {
		$form = $this->importScreen();
		if ($form->checkInput()) {
			$file = $_FILES['scastexport']['tmp_name'];
			$xml = new SimpleXMLElement(file_get_contents($file));
			$form2 = $this->initConfigurationForm();
			if ($form2->checkInput()) {
				foreach ($form2->getItems() as $key => $item) {
					if ($xml->{$item->getPostvar()}) {
						xscaConfig::set($item->getPostvar(), $xml->{$item->getPostvar()});
					}
				}
				$this->ctrl->redirect($this, 'configure');
			}
		}
	}


	public function configure() {
		$this->toolbar->addButton($this->pl->txt('export_xml'), $this->ctrl->getLinkTarget($this, 'export'));
		$this->toolbar->addButton($this->pl->txt('import_xml'), $this->ctrl->getLinkTarget($this, 'importScreen'));
		$new_form = new xscaConfigFormGUI($this);
		$new_form->fillForm();
		$this->tpl->setContent($new_form->getHTML());
	}


	protected function save() {
		$form = new xscaConfigFormGUI($this);
		$form->setValuesByPost();
		if ($form->saveObject()) {
			$this->ctrl->redirect($this, 'configure');
		}
		$this->tpl->setContent($form->getHTML());
	}


	protected function user(){
		
	}
}

?>
