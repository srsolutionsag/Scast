<?php
require_once('./Services/Logging/classes/class.ilLog.php');

/**
 * Class xscaLog
 */
class xscaLog {

	const LEVEL_DEBUG = 1;
	const LEVEL_WARN = 2;
	const LEVEL_BLOCK = 3;
	const LEVEL_PRODUCTION = 5;
	/**
	 * @var ilLog
	 */
	protected $scast_log;
	/**
	 * @var ilLog
	 */
	protected $il_log;
	/**
	 * @var xscaLog
	 */
	static $cache;
	/**
	 * @var xscaLogMessage[]
	 */
	static $messages = array();


	protected function __construct() {
		global $ilLog;
		$this->il_log = $ilLog;
	}


	public function __destruct() {
		if (is_writable(ILIAS_ABSOLUTE_PATH . '/logs/scast.log')) {
			$this->scast_log = new ilLog(ILIAS_ABSOLUTE_PATH . '/logs/', 'scast.log', 'SCAST');
			$this->scast_log->write('New Request ++++++++++++++++++++++++++++++++++++++++', self::LEVEL_WARN);
			foreach (self::$messages as $m) {
				if ($m->getLevel() === self::LEVEL_PRODUCTION) {
					@$this->il_log->write($m->getMessage());
				}
				$this->scast_log->write($m->getMessage(), self::getLevel($m->getLevel()));
			}
			$this->scast_log->write('Request ended ++++++++++++++++++++++++++++++++++++++', self::LEVEL_WARN);
		}
	}


	/**
	 * @return xscaLog
	 */
	public static function getInstance() {
		if (! isset(self::$cache)) {
			self::$cache = new self();
		}
		$obj =& self::$cache;

		return $obj;
	}


	/**
	 * @param $message
	 * @param $level
	 */
	public function write($message, $level) {
		array_push(self::$messages, xscaLogMessage::get($message, $level));
	}


	/**
	 * @param $level
	 *
	 * @return string
	 */
	private static function getLevel($level) {
		switch ($level) {
			case self::LEVEL_DEBUG:
				return 'DEBUG';
			case self::LEVEL_WARN:
				return 'WARN';
			case self::LEVEL_BLOCK:
				return 'BLOCK';
			case self::LEVEL_PRODUCTION:
				return '';
		}
	}
}

class xscaLogMessage {

	/**
	 * @var int
	 */
	protected $level = 0;
	/**
	 * @var string
	 */
	protected $message = '';


	/**
	 * @param $message
	 * @param $level
	 */
	protected function __construct($message, $level) {
		$this->setLevel($level);
		$this->setMessage(($message));
	}


	/**
	 * @param $message
	 * @param $level
	 *
	 * @return xscaLogMessage
	 */
	public static function get($message, $level) {
		$obj = new self($message, $level);

		return $obj;
	}


	/**
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}


	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}


	/**
	 * @param int $level
	 */
	public function setLevel($level) {
		$this->level = $level;
	}


	/**
	 * @return int
	 */
	public function getLevel() {
		return $this->level;
	}
}

?>