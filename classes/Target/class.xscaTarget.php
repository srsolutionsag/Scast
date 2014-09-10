<?php

/**
 * Class xscaTarget
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaTarget {

	/**
	 * @var string
	 */
	protected $channel_id;
	/**
	 * @var string
	 */
	protected $clip_id;
	/**
	 * @var string
	 */
	protected $token;
	/**
	 * @var string
	 */
	protected $ref_id;
	/**
	 * @var string
	 */
	protected $gui_class;
	/**
	 * @var bool
	 */
	protected $is_switch_redirect = false;
	/**
	 * @var string
	 */
	protected $redirect_url;
	/**
	 * @var string
	 */
	protected $redirect_url_appendix;
	/**
	 * @var string
	 */
	protected $redirect_url_appendix_2;
	/**
	 * @var array
	 */
	protected static $cache = array();


	/**
	 * @param $a_target
	 */
	protected function __construct($a_target) {
		$this->setGuiClass($a_target[1]);
		$t = explode('_', $a_target[0]);
		if (count($t) >= 2) {
			$this->setIsSwitchRedirect(true);
			$this->setChannelId($t[0]);
			$this->setClipId($t[1]);
			$this->setToken($t[2]);
			$this->setRedirectUrl($t[3]);
			$this->setRedirectUrlAppendix($t[4]);
			$this->setRedirectUrlAppendix2($t[5]);
			$ref_ids = ilObjScast::getAllReferences($this->getChannelId());
			$this->setRefId($ref_ids[0]);
		} else {
			$this->setIsSwitchRedirect(false);
			$this->setRefId($a_target[0]);
		}
	}


	/**
	 * @param $a_target
	 *
	 * @return xscaTarget
	 */
	public static function get($a_target) {
		if (! isset(self::$cache[$a_target[0]])) {
			self::$cache[$a_target[0]] = new self($a_target);
		}

		return self::$cache[$a_target[0]];
	}


	/**
	 * @return string
	 */
	public function getFullRedirectUrl() {
		$return = $this->getRedirectUrl();
		if ($this->getRedirectUrlAppendix()) {
			$return .= '_' . $this->getRedirectUrlAppendix();
		}
		if ($this->getRedirectUrlAppendix2()) {
			$return .= '_' . $this->getRedirectUrlAppendix2();
		}

		return $return;
	}


	/**
	 * @param string $token
	 */
	public function setToken($token) {
		$this->token = $token;
	}


	/**
	 * @return string
	 */
	public function getToken() {
		return $this->token;
	}


	/**
	 * @param string $ref_id
	 */
	public function setRefId($ref_id) {
		$this->ref_id = $ref_id;
	}


	/**
	 * @return string
	 */
	public function getRefId() {
		return $this->ref_id;
	}


	/**
	 * @param string $gui_class
	 */
	public function setGuiClass($gui_class) {
		$this->gui_class = $gui_class;
	}


	/**
	 * @return string
	 */
	public function getGuiClass() {
		return $this->gui_class;
	}


	/**
	 * @param string $clip_id
	 */
	public function setClipId($clip_id) {
		$this->clip_id = $clip_id;
	}


	/**
	 * @return string
	 */
	public function getClipId() {
		return $this->clip_id;
	}


	/**
	 * @param string $channel_id
	 */
	public function setChannelId($channel_id) {
		$this->channel_id = $channel_id;
	}


	/**
	 * @return string
	 */
	public function getChannelId() {
		return $this->channel_id;
	}


	/**
	 * @param boolean $is_switch_redirect
	 */
	public function setIsSwitchRedirect($is_switch_redirect) {
		$this->is_switch_redirect = $is_switch_redirect;
	}


	/**
	 * @return boolean
	 */
	public function getIsSwitchRedirect() {
		return $this->is_switch_redirect;
	}


	/**
	 * @param string $redirect_url
	 */
	public function setRedirectUrl($redirect_url) {
		$this->redirect_url = $redirect_url;
	}


	/**
	 * @return string
	 */
	public function getRedirectUrl() {
		return $this->redirect_url;
	}


	/**
	 * @param string $redirect_url_appendix
	 */
	public function setRedirectUrlAppendix($redirect_url_appendix) {
		$this->redirect_url_appendix = $redirect_url_appendix;
	}


	/**
	 * @return string
	 */
	public function getRedirectUrlAppendix() {
		return $this->redirect_url_appendix;
	}


	/**
	 * @param string $redirect_url_appendix_2
	 */
	public function setRedirectUrlAppendix2($redirect_url_appendix_2) {
		$this->redirect_url_appendix_2 = $redirect_url_appendix_2;
	}


	/**
	 * @return string
	 */
	public function getRedirectUrlAppendix2() {
		return $this->redirect_url_appendix_2;
	}
}

?>
