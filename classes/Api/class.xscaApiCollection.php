<?php

/**
 * Class xscaApiCollection
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaApiCollection {

	/**
	 * @var string
	 */
	protected $url;
	/**
	 * @var array
	 */
	protected $parts = array();
	/**
	 * @var string
	 */
	protected $suffix;


	public function __construct() {
		$this->url = xscaConfig::get('switch_api_host');
	}


	/**
	 * @return $this
	 */
	public function newx() {
		$this->append('new');

		return $this;
	}


	/**
	 * @return $this
	 */
	public function edit() {
		$this->append('edit');

		return $this;
	}


	/**
	 * @param $ext_account
	 *
	 * @return $this
	 */
	public function users($ext_account = NULL) {
		$this->append('users');
		if ($ext_account) {
			$this->append($ext_account);
		}

		return $this;
	}


	/**
	 * @param null $ext_account
	 *
	 * @return $this
	 */
	public function producers($ext_account = NULL) {
		$this->append('producers');
		if ($ext_account) {
			$this->append($ext_account);
		}

		return $this;
	}


	/**
	 * @param $channel_ext_id
	 *
	 * @return $this
	 */
	public function channels($channel_ext_id = NULL) {
		$this->append('channels');
		if ($channel_ext_id) {
			$this->append($channel_ext_id);
		}

		return $this;
	}


	/**
	 * @param $clip_ext_id
	 *
	 * @return $this
	 */
	public function clips($clip_ext_id = NULL) {
		$this->append('clips');
		if ($clip_ext_id) {
			$this->append($clip_ext_id);
		}

		return $this;
	}


	/**
	 * @param      $key
	 * @param null $value
	 *
	 * @return $this
	 */
	public function append($key, $value = NULL) {
		$this->parts[] = $key;
		if ($value) {
			$this->parts[] = $value;
		}

		return $this;
	}


	/**
	 * @return string
	 */
	public function get() {
		return xscaApiRequest::get($this->getURL());
	}


	/**
	 * @return string
	 */
	public function getFromCache() {
		return xscaApiRequest::getFromCache($this->getURL());
	}


	/**
	 * @return string
	 */
	public function delete() {
		return xscaApiRequest::delete($this->getURL());
	}


	/**
	 * @param xscaApiData $data
	 *
	 * @return SimpleXMLElement
	 */
	public function post(xscaApiData $data) {
		return xscaApiRequest::post($this->getURL(), $data);
	}


	/**
	 * @param xscaApiData $data
	 *
	 * @return SimpleXMLElement
	 */
	public function put(xscaApiData $data = NULL) {
		return xscaApiRequest::put($this->getURL(), $data);
	}


	/**
	 * @param string $suffix
	 *
	 * @return $this
	 */
	public function setSuffix($suffix) {
		$this->suffix = $suffix;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getSuffix() {
		return $this->suffix;
	}


	/**
	 * @return string
	 */
	public function getURL() {
		$url = $this->url . '/' . implode('/', $this->parts) . '.xml' . $this->getSuffix();

		return $url;
	}
}

?>
