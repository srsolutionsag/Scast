<?php
require_once('class.xscaApiCache.php');
require_once('class.xscaApiRequest.php');
require_once('class.xscaApiCollection.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilScastPlugin.php');

/**
 * Class xscaApi
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaApi {

	/**
	 * @param $ext_account
	 *
	 * @return xscaApiCollection
	 */
	public static function users($ext_account = NULL) {
		$obj = new xscaApiCollection();
		$obj->users($ext_account);

		return $obj;
	}


	/**
	 * @param $ext_account
	 *
	 * @return xscaApiCollection
	 */
	public static function producers($ext_account = NULL) {
		$obj = new xscaApiCollection();
		$obj->producers($ext_account);

		return $obj;
	}


	/**
	 * @param $channel_ext_id
	 *
	 * @return xscaApiCollection
	 */
	public static function channels($channel_ext_id = NULL) {
		$obj = new xscaApiCollection();
		$obj->channels($channel_ext_id);

		return $obj;
	}


	/**
	 * @param $clips_ext_id
	 *
	 * @return xscaApiCollection
	 */
	public static function clips($clips_ext_id = NULL) {
		$obj = new xscaApiCollection();
		$obj->clips($clips_ext_id);

		return $obj;
	}


	/**
	 * @return xscaApiCollection
	 */
	public static function edit() {
		$obj = new xscaApiCollection();
		$obj->edit();

		return $obj;
	}


	/**
	 * @return xscaApiCollection
	 */
	public static function newx() {
		$obj = new xscaApiCollection();
		$obj->newx();

		return $obj;
	}
}

?>
