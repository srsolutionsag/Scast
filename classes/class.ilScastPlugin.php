<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Services/class.xscaLog.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/lib/simplexlsx.class.php');

/**
 * Class ilScastPlugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version $Id$
 */
class ilScastPlugin extends ilRepositoryObjectPlugin {

	const PLUGIN_NAME = 'Scast';
	/**
	 * @var ilDB
	 */
	protected $db;
	/**
	 * @var array
	 */
	protected static $cache = array();
	/**
	 * @var
	 */
	protected static $instance;


	/**
	 * @return ilScastPlugin
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	final function init() {
		global $ilDB;
		$this->db = $ilDB;
	}


	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::PLUGIN_NAME;
	}


	/**
	 * @return array
	 * @deprecated
	 */
	public function getAllSysAccounts() {
		if (!isset(self::$cache['all_sys_accounts'])) {
			$set = $this->db->query('SELECT value FROM rep_robj_xsca_conf WHERE name LIKE' . $this->db->quote('%sysaccount', 'text'));
			while ($rec = $this->db->fetchObject($set)) {
				$return[] = $rec->value;
			}
			self::$cache['all_sys_accounts'] = $return;
		} else {
			xscaLog::getInstance()->write('Config-Cache used: all_sys_accounts', xscaLog::LEVEL_DEBUG);
		}

		return self::$cache['all_sys_accounts'];
	}


	public function updateLanguageFiles() {
		setlocale(LC_ALL, 'de_DE.utf8');
		ini_set('auto_detect_line_endings', true);
		$path = substr(__FILE__, 0, strpos(__FILE__, 'classes')) . 'lang/';
		if (file_exists($path . 'lang_custom.csv')) {
			$file = $path . 'lang_custom.csv';
		} else {
			$file = $path . 'lang.csv';
		}
		$keys = array();
		$new_lines = array();

		foreach (file($file) as $n => $row) {
			//			$row = utf8_encode($row);
			if ($n == 0) {
				$keys = str_getcsv($row, ";");
				continue;
			}
			$data = str_getcsv($row, ";");;
			foreach ($keys as $i => $k) {
				if ($k != 'var' AND $k != 'part') {
					if ($data[1] != '') {
						$new_lines[$k][] = $data[0] . '_' . $data[1] . '#:#' . $data[$i];
					} else {
						$new_lines[$k][] = $data[0] . '#:#' . $data[$i];
					}
				}
			}
		}
		$start = '<!-- language file start -->' . PHP_EOL;
		$status = true;

		foreach ($new_lines as $lng_key => $lang) {
			$status = file_put_contents($path . 'ilias_' . $lng_key . '.lang', $start . implode(PHP_EOL, $lang));
		}

		if (!$status) {
			ilUtil::sendFailure('Language-Files coul\'d not be written');
		}
		$this->updateLanguages();
	}
}

?>
