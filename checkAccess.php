<?php
/*
  +-----------------------------------------------------------------------------+
  | ILIAS open source                                                           |
  +-----------------------------------------------------------------------------+
  | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
  |                                                                             |
  | This program is free software; you can redistribute it and/or               |
  | modify it under the terms of the GNU General Public License                 |
  | as published by the Free Software Foundation; either version 2              |
  | of the License, or (at your option) any later version.                      |
  |                                                                             |
  | This program is distributed in the hope that it will be useful,             |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
  | GNU General Public License for more details.                                |
  |                                                                             |
  | You should have received a copy of the GNU General Public License           |
  | along with this program; if not, write to the Free Software                 |
  | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
  +-----------------------------------------------------------------------------+
*/
/**
 * Access/Condition checking for Scast object
 *
 *
 * @author  Fabian Schmid <fabian.schmid@ilub.unibe.ch>
 * @author  Oskar Truffer <ot@studer-raimann.ch>
 * @author  Martin Studer <mr@studer-raimann.ch>
 * @author  Fabian Schmid <fabian.schmid@ilub.unibe.ch>
 * @version $Id$
 *
 */
chdir(substr($_SERVER['SCRIPT_FILENAME'], 0, strpos($_SERVER['SCRIPT_FILENAME'], '/Customizing')));
require_once('include/inc.ilias_version.php');
require_once('Services/Component/classes/class.ilComponent.php');
if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.2.999')) {
	require_once 'Services/Context/classes/class.ilContext.php';
	ilContext::init(ilContext::CONTEXT_SOAP);
	include_once 'Services/Init/classes/class.ilInitialisation.php';
	$ilInit = new ilInitialisation();
	$GLOBALS['ilInit'] = $ilInit;
} else {
	$_GET['baseClass'] = 'ilStartUpGUI';
	require_once('./include/inc.get_pear.php');
	require_once('./include/inc.header.php');
}
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Services/class.xscaToken.php');
try {
	$ilInit->initILIAS();
} catch (Exception $e) {
	if (isset($_REQUEST['enc_token'])) {
		$dec_token_arr = xscaToken::ext_auth_decode_encrypted_token($_REQUEST['enc_token']);
		$channel_id = $dec_token_arr['channel_id'];
		$clip_id = $dec_token_arr['clip_id'];
		$token = $dec_token_arr['plain_token'];
		$client = $_GET['client_id'];
		header('Location: ' . xscaToken::_getWebUrl() . 'login.php?client_id=' . $client . '&target=xsca_'
		. $channel_id . '_' . $clip_id . '_' . $token . '_' . $_GET['redirect']);
	} else {
		header('Location: ' . xscaToken::_getWebUrl());
	}
}
if (isset($_REQUEST['enc_token'])) {
	$dec_token_arr = xscaToken::ext_auth_decode_encrypted_token($_REQUEST['enc_token']);
	$channel_id = $dec_token_arr['channel_id'];
	$clip_id = $dec_token_arr['clip_id'];
	$token = $dec_token_arr['plain_token'];
	$client = $_GET['client_id'];
	header('Location: ' . xscaToken::_getWebUrl() . 'goto.php?client_id=' . $client . '&target=xsca_' . $channel_id
	. '_' . $clip_id . '_' . $token . '_' . $_GET['redirect']);
} else {
	header('Location: ' . xscaToken::_getWebUrl());
}

?>