<?php

/**
 * Class xscaGroup
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xscaGroup {

	/**
	 * @var int
	 */
	protected $id;
	/**
	 * @var ilObjUser[]
	 */
	protected $members;
	/**
	 * @var int[]
	 */
	protected $memberIds;
	/**
	 * @var string
	 */
	protected $title;
	/**
	 * @var int
	 */
	protected $scast_id;
	/**
	 * @var xscaGroup[]
	 */
	static $cache;


	/**
	 * @param int $id
	 */
	protected function __construct($id = 0) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$this->db = $ilDB;
		if ($id != 0) {
			$this->setId($id);
			$this->read();
		}
	}


	/**
	 * @param int $id
	 *
	 * @return xscaGroup
	 */
	public static function getInstance($id = 0) {
		if (! isset(self::$cache[$id])) {
			self::$cache[$id] = new self($id);
		}

		return self::$cache[$id];
	}


	/**
	 * @param $obj_id
	 *
	 * @return xscaGroup[]
	 */
	public static function getAllForObjId($obj_id) {
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$groups = array();
		$query = 'SELECT id FROM rep_robj_xsca_group WHERE scast_id = ' . $ilDB->quote($obj_id, 'integer') . ' ORDER by Title ASC';
		$set = $ilDB->query($query);
		while ($rec = $ilDB->fetchObject($set)) {
			//$rec->id
			$groups[] = xscaGroup::getInstance($rec->id);
		}

		return $groups;
	}


	/**
	 * @param $obj_id
	 * @param $usr_id1
	 * @param $usr_id2
	 *
	 * @return bool
	 */
	public static function checkSameGroup($obj_id, $usr_id1, $usr_id2) {
		$groups = self::getAllForObjId($obj_id);
		foreach ($groups as $group) {
			if ($group->isMember($usr_id1) AND $group->isMember($usr_id2)) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @param $obj_id
	 * @param $owner_id
	 *
	 * @return array
	 */
	public static function getAllUsersFromGroupForOwner($obj_id, $owner_id) {
		$groups = self::getAllForObjId($obj_id);
		$users = array();
		foreach ($groups as $group) {
			if ($group->isMember($owner_id)) {
				foreach ($group->getMemberIds() as $member_id) {
					if (! in_array($member_id, $users) && $member_id != $owner_id) {
						$users[] = $member_id;
					}
				}
			}
		}

		return $users;
	}


	/**
	 * @description attention does not create group to member relations. use add/remove member method.
	 */
	public function create() {
		$next_id = $this->db->nextId('rep_robj_xsca_group');
		$this->setId($next_id);
		$this->db->manipulate('INSERT INTO rep_robj_xsca_group
        (id, title, scast_id) VALUES (' . $this->db->quote($next_id, 'integer') . ', ' . $this->db->quote($this->getTitle(), 'text') . ', '
			. $this->db->quote($this->getScastId(), 'integer') . ')');
	}


	private function read() {
		// build group
		$query = 'SELECT title, scast_id FROM rep_robj_xsca_group WHERE id = ' . $this->db->quote($this->getId(), 'integer');
		$set = $this->db->query($query);
		if ($set->numRows() == 0) {
			throw new Exception('No such group with id ' . $this->getId() . '.');
		}
		$res = $this->db->fetchAssoc($set);
		$this->setTitle($res['title']);
		$this->setScastId($res['scast_id']);
		//build members.
		$this->memberIds = array();
		$query = 'SELECT usr_id FROM rep_robj_xsca_grp_usr WHERE group_id = ' . $this->db->quote($this->getId(), 'integer');
		$set = $this->db->query($query);
		while ($res = $this->db->fetchAssoc($set)) {
			$this->memberIds[] = $res['usr_id'];
		}
	}


	public function update() {
		$this->db->manipulate('UPDATE rep_robj_xsca_group
        SET title = ' . $this->db->quote($this->getTitle(), 'text') . '
        SET scast_id = ' . $this->db->quote($this->getScastId(), 'integer') . '
        WHERE id = ' . $this->db->quote($this->getId(), 'integer'));
	}


	public function delete() {
		//update group
		$this->db->manipulate('DELETE FROM rep_robj_xsca_group
        WHERE id = ' . $this->db->quote($this->getId(), 'integer'));
		//update members.
		$this->db->manipulate('DELETE FROM rep_robj_xsca_grp_usr WHERE group_id = ' . $this->getId());
	}


	/**
	 * @param $usr_id int
	 *
	 * @return bool false iff the member was already in the group.
	 */
	public function addMemberById($usr_id) {
		if (! in_array($usr_id, $this->getMemberIds())) {
			$this->memberIds[] = $usr_id;
			$this->db->manipulate('INSERT INTO rep_robj_xsca_grp_usr (group_id, usr_id) VALUES (' . $this->getId() . ', ' . $usr_id . ')');

			return true;
		}

		return false;
	}


	/**
	 * @param $usr_id
	 */
	public function removeMemberById($usr_id) {
		if (! (($key = array_search($usr_id, $this->getMemberIds())) === false)) {
			unset($this->memberIds[$key]);
			$this->db->manipulate('DELETE FROM rep_robj_xsca_grp_usr WHERE group_id = ' . $this->getId() . ' AND usr_id = ' . $usr_id);
		}
	}


	/**
	 * @param $member_id int user id!
	 *
	 * @return bool
	 */
	public function isMember($member_id) {
		return in_array($member_id, $this->memberIds);
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @return int[]
	 */
	public function getMemberIds() {
		return $this->memberIds;
	}


	/**
	 * @param int $scast_id
	 */
	public function setScastId($scast_id) {
		$this->scast_id = $scast_id;
	}


	/**
	 * @return int
	 */
	public function getScastId() {
		return $this->scast_id;
	}
}

?>