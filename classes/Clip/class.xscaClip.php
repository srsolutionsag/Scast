<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilScastPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/User/class.xscaUser.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Api/class.xscaApi.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Api/class.xscaApiData.php');

/**
 * Class xscaClip
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @author  Martin Studer <ms@studer-raimann.ch>
 * @version $Id:
 *
 */
class xscaClip {

	/**
	 * @var
	 */
	protected $ext_id;
	/**
	 * @var string
	 */
	protected $download_link = '';
	/**
	 * @var string
	 */
	protected $annotation_link = '';
	/**
	 * @var string
	 */
	protected $recordingstation;
	/**
	 * @var string
	 */
	protected $presenter;
	/**
	 * @var int
	 */
	protected $owner;
	/**
	 * @var string
	 */
	protected $streamingonly;
	/**
	 * @var string
	 */
	protected $lecture_date;
	/**
	 * @var string
	 */
	protected $download_links;
	/**
	 * @var string
	 */
	protected $location;
	/**
	 * @var array
	 */
	protected $members = array();
	/**
	 * @var string
	 */
	protected $streaming_html;
	/**
	 * @var int
	 */
	protected $switch_internal_id;
	/**
	 * @var
	 */
	protected $subtitle;
	/**
	 * @var string
	 */
	protected $cover;
	/**
	 * @var xscaLog
	 */
	protected $log;
	/**
	 * @var xscaUser
	 */
	protected $xsca_user;
	/**
	 * @var string
	 */
	protected $channel_ext_id;
	/**
	 * @var string
	 */
	protected $link_box;
	/**
	 * @var string
	 */
	protected $link_cutting_tool;
	/**
	 * @var string
	 */
	protected $link_flash;
	/**
	 * @var string
	 */
	protected $link_mp4;
	/**
	 * @var string
	 */
	protected $link_mov;
	/**
	 * @var string
	 */
	protected $link_m4v;
	/**
	 * @var string
	 */
	protected $status;
        /**
     * @var string
     */
    protected $issued_on;
	/**
	 * @var xscaApiCollection
	 */
	protected $channel_api;
	/**
	 * @var xscaApiCollection
	 */
	protected $clip_api;
	/**
	 * @var xscaClip[]
	 */
	static $cache;


	/**
	 * @param $channel_ext_id
	 * @param $clip_ext_id
	 */
	protected function __construct($channel_ext_id, $clip_ext_id) {
		global $ilDB, $ilUser;
		/**
		 * @var $ilDB ilDB
		 */
		$this->log = xscaLog::getInstance();
		$this->db = $ilDB;
		$this->pl = new ilScastPlugin();
		$this->setChannelExtId($channel_ext_id);
		$this->setExtId($clip_ext_id);
		$this->xsca_user = xscaUser::getInstance($ilUser);
		$this->channel_api = xscaApi::users($this->xsca_user->getExtAccount())->channels($this->getChannelExtId());
		$this->clip_api = $this->channel_api->clips($this->getExtId());
		$this->read();
	}


	/**
	 * @param            $channel_ext_id
	 * @param            $clip_ext_id
	 *
	 * @internal param \ilObjScast $a_obj_scast
	 * @return xscaClip
	 */
	public static function getInstance($channel_ext_id, $clip_ext_id) {
		$id = (string)$clip_ext_id;
		if (! isset(self::$cache[$id])) {
			self::$cache[$id] = new self($channel_ext_id, $clip_ext_id);
		}

		return self::$cache[$id];
	}


	/**
	 * @param ilObjScast $a_obj_scast
	 * @param null       $arr_filter
	 *
	 * @return array
	 */
	public static function getAllInstancesForChannel(ilObjScast $a_obj_scast, $arr_filter = NULL) {
		$suffix = '?conditions=';
		$i = 0;
		foreach ($arr_filter as $key => $filter) {
			if (trim($filter) != '') {
				if ($i > 0) {
					$suffix .= '%20AND%20';
				}
				if ($key == 'withoutowner') {
					$suffix .= 'ivt__owner%20IS%20NULL';
					$i ++;
				} elseif ($key == 'ivt_owner' AND $filter != '') {
					$suffix .= 'ivt__owner%20LIKE\'%25' . $filter . '%25\'';
					$i ++;
				} else {
					$suffix .= $key . '%20LIKE%20\'%25' . str_replace(' ', '%20', $filter) . '%25\'';
					$i ++;
				}
			}
		}
		if (! ilObjScastAccess::checkPermissionOnchannel($a_obj_scast->getRefId(), 'write')) {
			$suffix .= 'state=\'published\'';
		}
		$channel_ext_id = $a_obj_scast->getExtId();
		$ext_account = $a_obj_scast->getSysAccount();
		$obj_clips = xscaApi::users($ext_account)->channels($channel_ext_id)->clips()->setSuffix($suffix)->get();
		$data = array();
		if (count($obj_clips->clip) > 0) {
			foreach ($obj_clips->clip as $clip) {
				$data[] = $clip;
			}
		}

		return $data;
	}


	/**
	 * @param bool $force_reload
	 *
	 * @return bool
	 */
	public function read($force_reload = false) {
		$this->log->write('ReadClip: ' . $this->getExtId(), xscaLog::LEVEL_DEBUG);
		if ($force_reload) {
			$xml = $this->clip_api->get();
		} else {
			$xml = $this->clip_api->getFromCache();
		}
		if ((string)$xml->state != 'published') {
			$xml = $this->clip_api->get();
		}
		$this->setExtId((string)$xml->ext_id);
		$this->setSwitchInternalId((string)$xml->id);
		$this->setOwner($xml->ivt__owner);
		$cut = xscaConfig::get('switchcast_host') . '/clips/' . (string)$xml->id . '/cutting_tool_data/edit';
		$this->setLinkCuttingTool($cut);
		$this->setTitle((string)$xml->title);
		$this->setLocation((string)$xml->location);
		$this->setPresenter((string)$xml->presenter);
		$this->setRecordingStation($xml->ivt__recordingstation);
        $this->setLinkMov(self::getUrlFor($xml, 'Desktop'));
        $this->setLinkM4v(self::getUrlFor($xml, 'Mobile'));
        $this->setCover(self::getUrlFor($xml, 'Cover image'));
        $this->setAnnotationlink(self::getUrlFor($xml, 'Annotate clip'));
        $this->setLinkFlash(self::getUrlFor($xml, 'Streaming'));
        $this->setDownloadLink(self::getUrlFor($xml, 'Download'));
		$this->setStatus((string)$xml->state);
        $this->setIssuedOn((string)$xml->issued_on);
		// Members holen und setzen
		$set = $this->db->query('SELECT * FROM rep_robj_xsca_cmember ' . ' WHERE clip_ext_id = '
			. $this->db->quote($this->getExtId(), 'text'));
		while ($rec = $this->db->fetchObject($set)) {
			$this->members[] = $rec->user_id;
		}
		$this->log->write('ReadClip finished: ' . $this->getExtId(), xscaLog::LEVEL_DEBUG);

		return true;
	}


	/**
	 * @return bool
	 */
	public function update() {
		$data = new xscaApiData('clip');
		$data->addField('ivt__owner', htmlentities($this->getOwner()));
		$data->addField('title', $this->getTitle());
		$data->addField('presenter', $this->getPresenter());
		$data->addField('location', $this->getLocation());
		$this->clip_api->put($data);

		return true;
	}


	public function create() {
	}


	public function delete() {
		$this->clip_api->delete();
		$this->db->manipulate('DELETE FROM rep_robj_xsca_cmember WHERE ' . 'clip_ext_id = '
			. $this->db->quote($this->getExtId(), 'text'));

		return true;
	}


	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function addMember($user_id) {
		$this->db->manipulate('INSERT INTO rep_robj_xsca_cmember ' . '(user_id, clip_ext_id) VALUES ('
			. $this->db->quote($user_id, 'integer') . ',' . $this->db->quote($this->getExtId(), 'text') . ')');

		return true;
	}


	/**
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function deleteMember($user_id) {
		$this->db->manipulate('DELETE FROM rep_robj_xsca_cmember WHERE ' . 'user_id = '
			. $this->db->quote($user_id, 'integer') . ' ' . 'AND clip_ext_id = '
			. $this->db->quote($this->getExtId(), 'text'));

		return true;
	}


	/**
	 * @param $a_userid
	 *
	 * @return bool
	 */
	public function isMember($a_userid) {
		return in_array($a_userid, $this->getMembers());
	}


	/**
	 * @return array
	 */
	public function getMembers() {
		return $this->members;
	}


	/**
	 * @param $a_id
	 */
	public function setMembers($a_id) {
		$this->members = $a_id;
	}



	//
	// Setter/Getter
	//
	/**
	 * @param $a_val
	 */
	public function setExtId($a_val) {
		$this->ext_id = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getExtId() {
		return $this->ext_id;
	}


	/**
	 * @param $a_val
	 */
	public function setTitle($a_val) {
		$this->title = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param $a_val
	 */
	public function setCover($a_val) {
		$this->cover = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getCover() {
		return $this->cover; #Vorschau
	}


	/**
	 * @param $a_val
	 */
	public function setAnnotationlink($a_val) {
		$this->annotation_link = $a_val;
	}


	/**
	 * @return string
	 */
	public function getAnnotationlink() {
		$idP = $_SERVER['Shib-Identity-Provider'];
		if (! $idP) {
			return $this->annotation_link;
		}
		$url = $this->getAnnotationBaseURL();
		$annotation_url = urlencode($this->annotation_link);

		return $url . 'Shibboleth.sso/Login?entityID=' . urlencode($idP) . '&target=' . $annotation_url;
	}


	/**
	 * @return mixed
	 */
	public function getAnnotationBaseURL() {
		$url = $this->annotation_link;
		$url = explode('clips', $url);

		return $url[0];
	}


	/**
	 * @param $a_val
	 */
	public function setStreamingHtml($a_val) {
		$this->streaming_html = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getStreamingHtml() {
		return $this->streaming_html;
	}


	/**
	 * @param $a_val
	 */
	public function setLinkBox($a_val) {
		$this->link_box = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getLinkBox() {
		return $this->link_box;
	}


	/**
	 * @param $a_val
	 */
	public function setLinkFlash($a_val) {
		$this->link_flash = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getLinkFlash() {
		return $this->link_flash;
	}


	/**
	 * @param $a_val
	 */
	public function setLinkMp4($a_val) {
		$this->link_mp4 = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getLinkMp4() {
		return $this->link_mp4;
	}


	/**
	 * @param $a_val
	 */
	public function setLinkMov($a_val) {
		$this->link_mov = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getLinkMov() {
		return $this->link_mov;
	}


	/**
	 * @param $a_val
	 */
	public function setLinkM4v($a_val) {
		$this->link_m4v = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getLinkM4v() {
		return $this->link_m4v;
	}


	/**
	 * @param $a_val
	 */
	public function setSubtitle($a_val) {
		$this->subtitle = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getSubtitle() {
		return $this->subtitle;
	}


	/**
	 * @param $a_val
	 */
	public function setPresenter($a_val) {
		$this->presenter = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getPresenter() {
		return $this->presenter;
	}


	/**
	 * @param $a_val
	 */
	public function setOwner($a_val) {
		$this->owner = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getOwner() {
		return $this->owner;
	}


	/**
	 * @param $a_val
	 */
	public function setLectureDate($a_val) {
		$this->lecture_date = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getLectureDate() {
		return $this->lecture_date;
	}


	/**
	 * @param $a_val
	 */
	public function setLocation($a_val) {
		$this->location = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getLocation() {
		return $this->location;
	}


	/**
	 * @param $a_val
	 */
	public function setDownloadlinks($a_val) {
		$this->download_links = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getDownloadlinks() {
		return $this->download_links;
	}


	/**
	 * @param $a_val
	 */
	public function setStreamingonly($a_val) {
		$this->streamingonly = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getStreamingonly() {
		return $this->streamingonly;
	}


	/**
	 * @param $a_val
	 */
	public function setRecordingStation($a_val) {
		$this->recordingstation = $a_val;
	}


	/**
	 * @return mixed
	 */
	public function getRecordingStation() {
		return $this->recordingstation;
	}


	/**
	 * @param $downloadLink
	 */
	public function setDownloadLink($downloadLink) {
		$this->download_link = $downloadLink;
	}


	/**
	 * @return string
	 */
	public function getDownloadLink() {
		return $this->download_link;
	}


	/**
	 * @param string $channel_ext_id
	 */
	public function setChannelExtId($channel_ext_id) {
		$this->channel_ext_id = $channel_ext_id;
	}


	/**
	 * @return string
	 */
	public function getChannelExtId() {
		return $this->channel_ext_id;
	}


	/**
	 * @param int $switch_internal_id
	 */
	public function setSwitchInternalId($switch_internal_id) {
		$this->switch_internal_id = $switch_internal_id;
	}


	/**
	 * @return int
	 */
	public function getSwitchInternalId() {
		return $this->switch_internal_id;
	}


	/**
	 * @param string $link_cutting_tool
	 */
	public function setLinkCuttingTool($link_cutting_tool) {
		$this->link_cutting_tool = $link_cutting_tool;
	}


	/**
	 * @return string
	 */
	public function getLinkCuttingTool() {
		return $this->link_cutting_tool;
	}


	/**
	 * @param string $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}


	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

    /**
     * @param string $issued_on
     */
    public function setIssuedOn($issued_on) {
        $this->issued_on = $issued_on;
    }


    /**
     * @return string
     */
    public function getIssuedOn() {
        return $this->issued_on;
    }
    
	/**
	 * @return int
	 */
	public function getOwnerILIASId() {
		$id = 0;
		$query = 'select usr_id FROM usr_data WHERE ext_account LIKE ' . $this->db->quote($this->getOwner(), 'text');
		$set = $this->db->query($query);
		if ($set->numRows() > 0) {
			$res = $this->db->fetchObject($set);
			$id = $res->usr_id;
		}

		return $id;
	}


	/**
	 * @param SimpleXMLElement|stdClass $data
	 * @param                           $label
	 *
	 * @return mixed
	 */
	private static function getUrlFor($data, $label) {
		if (count($data->urls->url) > 0) {
			foreach ($data->urls->url as $url) {
				if (((string)$url['label']) == $label) {
					return $url;
				}
			}
		}
	}
}

?>