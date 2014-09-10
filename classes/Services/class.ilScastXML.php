<?php
require_once('class.ilScastLog.php');
require_once('class.ilScastRequestCache.php');
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
/**
 * Class ilScastXML
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id:
 *
 * @extends ...
 */
class ilScastXML {

	const DEV = false;
	const M_GET = 'GET';
	const M_POST = 'POST';
	const M_PUT = 'PUT';
	const M_DELETE = 'DELETE';
	/**
	 * @var array
	 */
	static $cache = array();
	/**
	 * @var int
	 */
	static $num = 0;


	/**
	 * @param $a_url
	 *
	 * @return int
	 */
	private static function getType($a_url) {
		$type = ilScastRequestCache::TYPE_OTHER;
		if (strpos($a_url, '/channels') > 0) {
			$type = ilScastRequestCache::TYPE_CHANNEL;
		}
		if (strpos($a_url, '/clips') > 0) {
			$type = ilScastRequestCache::TYPE_CLIP;
		}

		return $type;
	}


	/**
	 * @param $a_url
	 *
	 * @return stdClass
	 */
	public static function get($a_url) {
		return self::request($a_url, self::M_GET, self::getType($a_url));
	}


	/**
	 * @param $a_url
	 *
	 * @return stdClass
	 */
	public static function getChannel($a_url) {
		return self::request($a_url, self::M_GET, ilScastRequestCache::TYPE_CHANNEL);
	}


	/**
	 * @param $a_url
	 *
	 * @return stdClass
	 */
	public static function getClip($a_url) {
		return self::request($a_url, self::M_GET, ilScastRequestCache::TYPE_CLIP);
	}


	/**
	 * @param       $a_url
	 * @param       $a_request_method
	 * @param       $a_type
	 * @param array $arr_data
	 *
	 * @return stdClass
	 */
	private static function request($a_url, $a_request_method, $a_type, array $arr_data = NULL) {
		global $ilUser;
		$a_pl = new ilScastPlugin();
		$log = ilScastLog::getInstance();
		$hash = $a_request_method . $a_url;
		//
		// Check for Cache
		if ($a_request_method == self::M_GET AND
			ilScastRequestCache::get($hash, $a_type) AND $a_type == ilScastRequestCache::TYPE_CLIP
		) {
			$log->write('Chache used: ' . $a_url, ilScastLog::LEVEL_DEBUG);

			return ilScastRequestCache::get($hash, $a_type);
		}
		$log->write("SWITCHcast - user: " . $ilUser->getLogin() . " request: " . $a_request_method . " url: "
			. $a_url, ilScastLog::LEVEL_PRODUCTION);
		//
		//cURL-Request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CAINFO, xscaConfig::get('cacrt_file'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSLCERT, xscaConfig::get('crt_file'));
		curl_setopt($ch, CURLOPT_SSLKEY, xscaConfig::get('castkey_file'));
		if (xscaConfig::get('castkey_password')) {
			curl_setopt($ch, CURLOPT_SSLKEYPASSWD, xscaConfig::get('castkey_password'));
		}
		curl_setopt($ch, CURLOPT_URL, $a_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (isset($arr_data['root'])) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, self::arrayToXML($arr_data['root'], $arr_data['data']));
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: text/xml" ));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $a_request_method);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10000);
		$output = curl_exec($ch);
		curl_close($ch);
		//
		// Exception handling
		if ($output === false) {
			ilUtil::sendFailure("Switch API is temporarily unavailable. Please do not create or update new Switch-Casts!", true);
		}
		//
		// Parse Data
		$xml = simplexml_load_string($output);
		ilScastRequestCache::add($hash, $xml, $a_type);

		return $xml;
	}


	/**
	 * @param      $a_url
	 * @param      $a_request
	 * @param null $a_xml
	 * @param bool $as_xml
	 * @param int  $type
	 *
	 * @deprecated
	 *
	 * @return array|bool|mixed|SimpleXMLElement
	 */
	public static function sendRequest($a_url, $a_request, $type, $a_xml = NULL, $as_xml = false) {
		global $ilUser;
		//		ilScastRequestCache::updateDB();
		$log = ilScastLog::getInstance();
		$hash = $a_request . $a_url;
		if ($a_request == self::M_GET AND ilScastRequestCache::get($hash, $type)) {
			$log->write('Chache used: ' . $a_url, ilScastLog::LEVEL_DEBUG);

			return ilScastRequestCache::get($hash, $type);
		}
		//
		$a_pl = new ilScastPlugin();
		if (is_array($a_xml)) {
			$a_xml = self::arrayToXML($a_xml['root'], $a_xml['data']);
		}
		$log->write("REQUEST " . $a_request . " " . $a_url, ilScastLog::LEVEL_DEBUG);
		if (self::DEV) {
			$log->write("INPUT " . $a_xml, ilScastLog::LEVEL_DEBUG);
		}
		$log->write("SWITCHcast - user: " . $ilUser->getLogin() . " request: " . $a_request . " url: "
			. $a_url, ilScastLog::LEVEL_PRODUCTION);
		libxml_use_internal_errors(true);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CAINFO, xscaConfig::get('cacrt_file'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSLCERT, xscaConfig::get('crt_file'));
		curl_setopt($ch, CURLOPT_SSLKEY, xscaConfig::get('castkey_file'));
		if (xscaConfig::get('castkey_password')) {
			curl_setopt($ch, CURLOPT_SSLKEYPASSWD, xscaConfig::get('castkey_password'));
		}
		curl_setopt($ch, CURLOPT_URL, $a_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $a_xml);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array( "Content-Type: text/xml" ));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $a_request);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 10000); //TimeOut nach 10 Sekunden
		$output = curl_exec($ch);
		curl_close($ch);
		if ($as_xml) {
			return $output;
		}
		if (self::DEV) {
			$log->write("OUTPUT " . $output, ilScastLog::LEVEL_DEBUG);
		}
		if ($output === false) {
			ilUtil::sendFailure("Switch API is temporarily unavailable. Please do not create or update new Switch-Casts!", true);

			return false;
		}
		try {
			$return = new SimpleXMLElement($output);
		} catch (Exception $e) {
			ilUtil::sendFailure($e->getMessage(), true);

			return false;
		}
		// Falls das Return-Objekt eine Message enthält so, ist etwas schief gelaufen.
		if ($return->message) {
			ilUtil::sendFailure((string)$return->message, true);
			//			echo '<pre>' . print_r(, 1)
			foreach (debug_backtrace() as $dat) {
				// echo $dat['class'] . '::' . $dat['function'] . '<br>';
			}
			if (self::DEV) {
				ilUtil::sendFailure($output, true);
			}

			return false;
		}
		ilScastRequestCache::add($hash, $return, $type);

		return $return;
	}


	/**
	 * @param       $a_base
	 * @param array $a_data
	 *
	 * @return mixed
	 */
	static function arrayToXML($a_base, array $a_data) {
		$simple_xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><" . $a_base . " />");
		foreach ($a_data as $key => $value) {
			if (is_string($value)) {
				$value = html_entity_decode($value);
			}
			$simple_xml->addChild($key, $value);
		}

		return $simple_xml->asXML();
	}
}

?>