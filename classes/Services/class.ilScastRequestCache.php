<?php

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/AR/class.AR.php');
/**
 * Class ilScastRequestCache
 */
class ilScastRequestCache extends AR {

	const RANDOM_MINUTES = 1;
	const TYPE_CHANNEL = 1;
	const TYPE_CLIP = 2;
	const TYPE_USER = 3;
	const TYPE_OTHER = 9;
	const DATA_TYPE_XML = 1;
	const DATA_TYPE_STDCLASS = 2;
	const DATA_TYPE_XMLREADER = 3;


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'rep_robj_xsca_cache';
	}


	/**
	 * @var int
	 *
	 * @db_has_field        false
	 */
	protected $id = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           4
	 */
	protected $obj_id = 0;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_unique        true
	 * @db_is_primary       true
	 * @db_is_notnull       true
	 * @db_fieldtype        text
	 * @db_length           256
	 */
	protected $cache_key = 0;
	/**
	 * @var string
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        clob
	 */
	protected $cache_value = '';
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        timestamp
	 */
	protected $cache_time = 0;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $type;
	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           1
	 */
	protected $data_type = self::DATA_TYPE_XML;


	/**
	 * @param $key
	 * @param $value
	 * @param $type
	 */
	public static function add($key, $value, $type) {
		$key = self::key($key);
		if (! self::where(array( 'cache_key' => $key ))->hasSets()) {
			$obj = new self();
			switch (get_class($value)) {
				case 'SimpleXMLElement':
					$obj->setDataType(self::DATA_TYPE_XML);
					$value = $value->asXML();
					break;
				case 'stdClass':
					$obj->setDataType(self::DATA_TYPE_STDCLASS);
					$value = serialize($value);
					break;
				case 'XMLReader':
					$obj->setDataType(self::DATA_TYPE_XMLREADER);
					$value = serialize($value);
					break;
			}
			$obj->setCacheKey($key);
			$obj->setObjId(ilObject2::_lookupObjId($_GET['ref_id']));
			$obj->setCacheValue($value);
			$obj->setType($type);
			$obj->updateCacheTime();
			$obj->create();
		}
	}


	/**
	 * @param $obj_id
	 *
	 * @return mixed
	 */
	public static function getLastUpdate($obj_id) {
		return self::where(array( 'obj_id' => $obj_id, 'type' => self::TYPE_CLIP ))->orderBy('cache_time', 'DESC')
			->limit(0, 1)->first()->getCacheTime();
	}


	/**
	 * @param $obj_id
	 */
	public static function flush($obj_id) {
		if (self::where(array( 'obj_id' => $obj_id ))->hasSets()) {
			foreach (self::where(array( 'obj_id' => $obj_id ))->get() as $obj) {
				$obj->delete();
			}
		}
	}


	/**
	 * @param $key
	 * @param $type
	 *
	 * @return bool|stdClass|SimpleXMLElement
	 */
	public static function get($key, $type) {
		$key = self::key($key);
		$return = false;
		$where['cache_key'] = $key;
		$op['cache_key'] = '=';
		if ($type == self::TYPE_CHANNEL) {
			$where['cache_time'] = self::getDateTime();
			$op['cache_time'] = '>';
		}
		if (self::where($where, $op)->hasSets()) {
			/**
			 * @var $obj ilScastRequestCache
			 */
			$obj = self::where($where, $op)->first();
			switch ($obj->getDataType()) {
				case 0;
				case self::DATA_TYPE_XML:
					$return = @simplexml_load_string($obj->getCacheValue());
					break;
				case self::DATA_TYPE_STDCLASS:
				case self::DATA_TYPE_XMLREADER:
					$return = unserialize($obj->getCacheValue());
					break;
			}
		}
		$where = array(
			'type' => self::TYPE_CHANNEL,
			'cache_key' => $key,
			'cache_time' => self::getDateTime()
		);
		$op = array(
			'type' => '=',
			'cache_key' => '=',
			'cache_time' => '<'
		);
		if (self::where($where, $op)->hasSets()) {
			self::where($where, $op)->first()->delete();
		}

		return $return;
	}


	public function updateCacheTime() {
		$this->setCacheTime(self::getDateTime(rand(1, self::RANDOM_MINUTES) * 60));
	}


	/**
	 * @param int $add
	 *
	 * @return array|bool|int|string
	 */
	private static function getDateTime($add = 0) {
		$date = new ilDateTime(time() + $add, IL_CAL_UNIX);

		return $date->get(IL_CAL_TIMESTAMP);
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	public static function key($key) {
		return md5($key);
	}


	/**
	 * @param $key
	 *
	 * @return ilScastRequestCache
	 */
	private static function getFromSession($key) {
		return $_SESSION['scast'][$key];
	}


	/**
	 * @param                     $key
	 * @param ilScastRequestCache $value
	 */
	private static function setToSession($key, ilScastRequestCache $value) {
		$_SESSION['scast'][$key] = serialize($value);
	}


	//
	// Setter & Getter
	//
	/**
	 * @param string $cache_key
	 */
	public function setCacheKey($cache_key) {
		$this->cache_key = $cache_key;
	}


	/**
	 * @return string
	 */
	public function getCacheKey() {
		return $this->cache_key;
	}


	/**
	 * @param string $cache_value
	 */
	public function setCacheValue($cache_value) {
		$this->cache_value = $cache_value;
	}


	/**
	 * @return string
	 */
	public function getCacheValue() {
		return $this->cache_value;
	}


	/**
	 * @param int $cache_time
	 */
	public function setCacheTime($cache_time) {
		$this->cache_time = $cache_time;
	}


	/**
	 * @return int
	 */
	public function getCacheTime() {
		return $this->cache_time;
	}


	/**
	 * @param int $obj_id
	 */
	public function setObjId($obj_id) {
		$this->obj_id = $obj_id;
	}


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->obj_id;
	}


	/**
	 * @param int $type
	 */
	public function setType($type) {
		$this->type = $type;
	}


	/**
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param int $data_type
	 */
	public function setDataType($data_type) {
		$this->data_type = $data_type;
	}


	/**
	 * @return int
	 */
	public function getDataType() {
		return $this->data_type;
	}
}

?>