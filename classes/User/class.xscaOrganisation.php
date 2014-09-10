<?php

/**
 * Class xscaOrganisation
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaOrganisation {

	/**
	 * @param $email
	 *
	 * @return string
	 */
	public static function getOrganisationByExtAccount($email) {
		$arrOrganizationDomain = explode('@', $email);

		return (string)$arrOrganizationDomain[1];
	}


	/**
	 * @param $organization
	 *
	 * @return string
	 */
	public static function getSysAccountByOrganisation($organization) {
		$sys_account_key = str_replace('.', '_', $organization) . '_sysaccount';
		$sys_account = (string)xscaConfig::get($sys_account_key);

		return ($sys_account ? $sys_account : xscaConfig::get('default_sys_account'));
	}


	/**
	 * @param $ext_account
	 *
	 * @return bool
	 */
	public static function hasSysAccount($ext_account) {
		$organization = self::getOrganisationByExtAccount($ext_account);
		$sys_account_key = str_replace('.', '_', $organization) . '_sysaccount';
		$sys_account = (string)xscaConfig::get($sys_account_key);

		return ($sys_account ? true : false);
	}


	/**
	 * @param $ext_account
	 *
	 * @return mixed|string
	 */
	public static function getSysAccountByExtAccount($ext_account) {
		$organization = self::getOrganisationByExtAccount($ext_account);
		$sys_account_key = str_replace('.', '_', $organization) . '_sysaccount';
		$sys_account = (string)xscaConfig::get($sys_account_key);

		return ($sys_account ? $sys_account : xscaConfig::get('default_sys_account'));
	}
}

?>
