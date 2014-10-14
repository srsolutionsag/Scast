<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Services/class.xscaLog.php');
require_once('class.xscaApiCache.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Config/class.xscaConfig.php');
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xscaApiRequest
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id:
 *
 * @extends ...
 */
class xscaApiRequest {

	const GET = 'GET';
	const POST = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';
	/**
	 * @var
	 */
	protected $curl;
	/**
	 * @var string
	 */
	protected $method;
	/**
	 * @var
	 */
	protected $url;
	/**
	 * @var xscaApiData
	 */
	protected $data = NULL;
	/**
	 * @var string
	 */
	protected $output;
	/**
	 * @var SimpleXMLElement
	 */
	protected $return_value;
	/**
	 * @var array
	 */
	protected static $debug_cache = array();


	/**
	 * @param $url
	 * @param $method
	 */
	protected function __construct($url, $method) {
		$this->setUrl($url);
		$this->setMethod($method);
	}


	public function __destruct() {
		if (xscaConfig::get('show_api_debug')) {
			ilUtil::sendInfo(implode('<br>', self::$debug_cache));
		}
	}


	/**
	 * @param $url
	 *
	 * @return SimpleXMLElement
	 */
	public static function get($url) {
		$api = new self($url, self::GET);
		$api->execute();

		return $api->getReturnValue();
	}


	/**
	 * @param $url
	 *
	 * @return SimpleXMLElement
	 */
	public static function getFromCache($url) {
		$api = new self($url, self::GET);
		$api->loadFromCache();

		return $api->getReturnValue();
	}


	/**
	 * @param $url
	 *
	 * @return SimpleXMLElement
	 */
	public static function delete($url) {
		$api = new self($url, self::DELETE);
		$api->execute();

		return $api->getReturnValue();
	}


	/**
	 * @param             $url
	 * @param xscaApiData $data
	 *
	 * @return SimpleXMLElement
	 */
	public static function post($url, xscaApiData $data) {
		$api = new self($url, self::POST);
		$api->setData($data);
		$api->execute();

		return $api->getReturnValue();
	}


	/**
	 * @param             $url
	 * @param xscaApiData $data
	 *
	 * @return SimpleXMLElement
	 */
	public static function put($url, xscaApiData $data = NULL) {
		xscaApiCache::flushEntry(self::GET . $url);
		xscaApiCache::flushEntry(self::PUT . $url);
		$api = new self($url, self::PUT);
		$api->setData($data);
		$api->execute();

		return $api->getReturnValue();
	}


	/**
	 * @return mixed
	 */
	protected function execute() {
		$this->appendDebugCache();
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_CAINFO, xscaConfig::get('cacrt_file'));
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_SSLCERT, xscaConfig::get('crt_file'));
		curl_setopt($this->curl, CURLOPT_SSLKEY, xscaConfig::get('castkey_file'));
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array( "Content-Type: text/xml" ));
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->getMethod());
		curl_setopt($this->curl, CURLOPT_TIMEOUT_MS, 10000);

		if (xscaConfig::get('castkey_password')) {
			curl_setopt($this->curl, CURLOPT_SSLKEYPASSWD, xscaConfig::get('castkey_password'));
		}
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		if ($this->getData()) {
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->getData()->getAsXml());
		}
		curl_setopt($this->curl, CURLOPT_URL, $this->getUrl());
		$this->setOutput(curl_exec($this->curl));
		curl_close($this->curl);
		$this->handleOutput();
	}


	protected function loadFromCache() {
		$cache = xscaApiCache::get($this->getMethod() . $this->getUrl());
		if ($cache) {
			xscaLog::getInstance()->write('ClipCache used', xscaLog::LEVEL_DEBUG);
			$this->setReturnValue($cache);
		} else {
			$this->execute();
		}
	}


	/**
	 * @return string
	 */
	public function getBackTrace() {
		$return = '';
		foreach (debug_backtrace() as $bt) {
			if (!in_array($bt['function'], array( 'getBackTrace', 'executeCommand', 'performCommand' )) AND !in_array($bt['class'], array(
					'xscaApiRequest',
					'ilCtrl',
					'xscaApiCollection',
					'ilObjectPluginGUI',
					'ilObject2GUI',
					'ilObjectFactory',
					'ilObject2'
				))
			) {
				$return .= $bt['class'] . '::' . $bt['function'] . '(' . $bt['line'] . ')<br>';
			}
		}

		return $return;
	}


	/**
	 * @return bool
	 */
	protected function handleOutput() {
		if ($this->getOutput() === false) {
			ilUtil::sendFailure("Switch API is temporarily unavailable. Please do not create or update new Switch-Casts!", true);
		}
		try {
			$this->setReturnValue(new SimpleXMLElement($this->getOutput()));
		} catch (Exception $e) {
			ilUtil::sendFailure($e->getMessage(), true);

			return false;
		}
		if ($this->getReturnValue()->message) {
			if (!preg_match("/Clip\\[([1-9a-zA_Z]*)\\] was successfully deleted/uism", (string)$this->getReturnValue()->message)) {
				ilUtil::sendFailure((string)$this->getReturnValue()->message, true);
			}

			if (xscaConfig::get('show_api_debug')) {
				ilUtil::sendFailure($this->getOutput() . '<br>' . $this->getBackTrace(), true);
//				var_dump($this->getOutput() . '<br>' . $this->getBackTrace()); // FSX
			}

			return false;
		}
		xscaApiCache::flushEntry($this->getMethod() . $this->getUrl());
		xscaApiCache::add($this->getMethod() . $this->getUrl(), $this->getReturnValue());

		return true;
	}


	/**
	 * @return mixed
	 */
	public function getUrl() {
		return $this->url;
	}


	/**
	 * @param mixed $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}


	/**
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}


	/**
	 * @param string $method
	 */
	public function setMethod($method) {
		$this->method = $method;
	}


	/**
	 * @return xscaApiData
	 */
	public function getData() {
		return $this->data;
	}


	/**
	 * @param $data
	 */
	public function setData($data) {
		$this->data = $data;
	}


	/**
	 * @return string
	 */
	public function getOutput() {
		return $this->output;
	}


	/**
	 * @param string $output
	 */
	public function setOutput($output) {
		$this->output = $output;
	}


	/**
	 * @return \SimpleXMLElement
	 */
	public function getReturnValue() {
		return $this->return_value;
	}


	/**
	 * @param \SimpleXMLElement $return_value
	 */
	public function setReturnValue($return_value) {
		$this->return_value = $return_value;
	}


	protected function appendDebugCache() {
		self::$debug_cache[] = '<strong>' . $this->getMethod() . ': ' . $this->getUrl() . '</strong><br>' . $this->getBackTrace();
	}
}

?>