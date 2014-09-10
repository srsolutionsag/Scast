<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/AR/class.AR.php');

/**
 * Class xscaConfig
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaConfig extends AR {

	const GOTO_LOGIN = 1;
	const GOTO_REPO = 2;
	const ALLOW_UPLOAD_TOKEN = 'allow_upload_token';
	const F_EULA_TEXT = 'config_scast_eula_text';


	/**
	 * @return string
	 * @description Return the Name of your Database Table
	 */
	static function returnDbTableName() {
		return 'rep_robj_xsca_conf';
	}


	/**
	 * @param $key
	 *
	 * @return array|string
	 */
	public static function get($key) {
		$obj = new self($key);

		return $obj->getValue();
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public static function set($name, $value) {
		$obj = new self($name);
		$obj->setValue($value);
//		var_dump(self::where(array( 'name' => $name ))->hasSets()); // FSX
		if (self::where(array( 'name' => $name ))->hasSets()) {
			$obj->update();
		} else {
			$obj->create();
		}
	}


	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           250
	 */
	protected $name;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_fieldtype        text
	 * @db_length           1000
	 */
	protected $value;


	/**
	 * @param string $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}


	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}


	/**
	 * @param string $name
	 */
	public function setName($name) {
		$this->name = $name;
	}


	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}

?>
