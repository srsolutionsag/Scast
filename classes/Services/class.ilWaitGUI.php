<?php

/**
 * Class ilWaitGUI
 */
class ilWaitGUI {

	/**
	 * @var string
	 */
	protected $message;
	/**
	 * @var string
	 */
	protected $onclick_dom_id;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;
	/**
	 * @var string
	 */
	protected $html = '';


	/**
	 * @param        $onclick_dom_id
	 * @param string $message
	 *
	 * @return ilWaitGUI
	 */
	public static function init($onclick_dom_id, $message = '') {
		$obj = new ilWaitGUI($onclick_dom_id, $message);

		return $obj;
	}


	/**
	 * @param        $onclick_dom_id
	 * @param string $message
	 */
	protected function __construct($onclick_dom_id, $message = '') {
		$this->setOnclickDomId($onclick_dom_id);
		$this->setMessage($message);
		$this->initTemplate();
	}


	private function initTemplate() {
		$this->tpl = new ilTemplate('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/templates/default/Services/tpl.wait.html', false, false);
		$this->tpl->setVariable('DOM', $this->getOnclickDomId());
		if ($this->getMessage()) {
			$this->tpl->setCurrentBlock('message');
			$this->tpl->setVariable('MESSAGE', $this->getMessage());
			$this->tpl->parseCurrentBlock();
		}
		$this->setHtml($this->tpl->get());
	}


	//
	// Setter & Getter
	//
	/**
	 * @param mixed $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}


	/**
	 * @return mixed
	 */
	public function getMessage() {
		return $this->message;
	}


	/**
	 * @param mixed $onclick_dom_id
	 */
	public function setOnclickDomId($onclick_dom_id) {
		$this->onclick_dom_id = $onclick_dom_id;
	}


	/**
	 * @return mixed
	 */
	public function getOnclickDomId() {
		return $this->onclick_dom_id;
	}


	/**
	 * @param string $html
	 */
	public function setHtml($html) {
		$this->html = $html;
	}


	/**
	 * @return string
	 */
	public function getHtml() {
		return $this->html;
	}
}

?>
