<?php

/**
 * Class xscaApiData
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaApiData {

	/**
	 * @var string
	 */
	protected $base;
	/**
	 * @var array
	 */
	protected $fields = array();


	/**
	 * @param $base
	 */
	public function __construct($base) {
		$this->setBase($base);
	}


	/**
	 * @param $key
	 * @param $value
	 */
	public function addField($key, $value) {
		$this->fields[$key] = $value;
	}


	/**
	 * @return mixed
	 */
	public function getAsXml() {
		$simple_xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><" . $this->getBase() . " />");
		foreach ($this->getFields() as $key => $value) {
			if (is_string($value)) {
				$value = html_entity_decode($value);
			}
			$simple_xml->addChild($key, $value);
		}

		return $simple_xml->asXML();
	}


	/**
	 * @param array $fields
	 */
	public function setFields($fields) {
		$this->fields = $fields;
	}


	/**
	 * @return array
	 */
	public function getFields() {
		return $this->fields;
	}


	/**
	 * @param string $base
	 */
	public function setBase($base) {
		$this->base = $base;
	}


	/**
	 * @return string
	 */
	public function getBase() {
		return $this->base;
	}
}

?>
