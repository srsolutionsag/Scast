<?php
/**
 * getChannelInfo
 *
 * @author  Oskar Truffer <ot@studer-raimann.ch>
 * @author  Fabian Schmid <fabian.schmid@ilub.unibe.ch>
 * @version $Id:
 *
 */
chdir(substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], '/Customizing')));
require_once('./include/inc.header.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Api/class.xscaApi.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/User/class.xscaUser.php');
$channel_ext_id = $_REQUEST['ext_id'];
$a = array();
$ch = xscaApi::users(xscaUser::getInstance()->getExtAccount())->channels($channel_ext_id)->getFromCache();
$a = array();
$a['title'] = (string)$ch->name;
$a['discipline'] = (string)$ch->discipline_name;
$a['license'] = (string)$ch->license_name;
$a['estimated_duration'] = (string)$ch->estimated_content_in_hours;
$a['lifetime'] = (string)$ch->lifetime_of_content_in_months;
$a['department'] = (string)$ch->department;
$a['allow_annotations'] = (string)$ch->allow_annotations;
header('Content-type: application/json');
echo json_encode($a);