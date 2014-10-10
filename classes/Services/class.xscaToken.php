<?php
require_once './Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilScastPlugin.php';
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/Config/class.xscaConfig.php');

/**
 * xscaToken
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           $Id$
 */
class xscaToken {

	const DEV = false;


	/**
	 * @return mixed
	 */
	static function _getPath() {
		$path = str_ireplace($_SERVER['DOCUMENT_ROOT'] . '/', '', str_ireplace('/classes/class.xscaToken.php', '', __FILE__));

		return $path;
	}


	/**
	 * @return mixed
	 */
	public static function _getWebUrl() {
		return xscaConfig::get('external_authority_host');
	}


	//
	// Access
	//
	/**
	 * ext_auth_decode_encrypted_token
	 *
	 * @desc Decode public-key-encrypted and base64-encoded token into channel_id/clip_id/plain_token
	 *
	 * @param string $encrypted_token_base64
	 *
	 * @return array
	 */
	public static function ext_auth_decode_encrypted_token($encrypted_token_base64) {

		$encrypted_token = base64_decode($encrypted_token_base64);
		if (! $encrypted_token) {
			$error = true;
		}
		$private_key = openssl_get_privatekey('file://' . xscaConfig::get('localkey_file'));
		if (self::DEV) {
			var_dump($private_key); // FSX
		}
		$dev = openssl_private_decrypt($encrypted_token, $decrypted_token, $private_key);
		if (self::DEV) {
			var_dump($dev); // FSX
		}
		if (self::DEV) {
			echo '<pre>' . print_r($decrypted_token, 1) . '</pre>';
			exit;
		}
		// Token structure: <channel_id>::<clip_id>::<plain_token>
		$parts = explode('::', $decrypted_token);
		if (count($parts) == 3 && ! $error) {
			return array(
				'channel_id' => $parts[0],
				'clip_id' => $parts[1],
				'plain_token' => $parts[2]
			);
		} else {
			echo 'Das Video konnte nicht ausgeliefert werden.';

			return NULL;
		}
	}


	/**
	 * @desc     Redirect back to the SWITCHcast VOD URL with plain token
	 *
	 * @param xscaTarget $target
	 *
	 * @internal param $redirect_url
	 * @internal param $plain_token
	 */
	public static function extAuthRedirectToVodUrl(xscaTarget $target) {
		$redirect_url = $target->getFullRedirectUrl();
		$plain_token = $target->getToken();
		if (strpos($redirect_url, 'token=::plain::') > 0) {
			// URL format: https://cast.switch.ch/vod/clip.url?token=::plain::
			$redirect_url = str_replace('::plain::', urlencode($plain_token), $redirect_url);
		} elseif (strpos($redirect_url, '?') === false) {
			// URL format: https://cast.switch.ch/vod/clip.url
			$redirect_url .= '?token=' . urlencode($plain_token);
		} else {
			// URL format: https://cast.switch.ch/vod/clip.url?param=value
			$redirect_url .= '&token=' . urlencode($plain_token);
		}
		header('Location: ' . $redirect_url);
	}


	/**
	 * @desc Render HTTP status 403 (Forbidden)
	 */
	public static function ext_auth_show_permission_denied_page() {
		header('HTTP/1.1 403 Forbidden');
		echo '<html><head><title>403 Forbidden</title></head><body><h1>Access denied</h1>'
			. '<h2>You have no permission to access this content.</h2></body></html>';
	}


	/**
	 * @desc Simple debug logger into EXT_AUTH_LOGFILE
	 *
	 * @param $message
	 */
	public static function ext_auth_debug($message) {
		if (! EXT_AUTH_DEBUG) {
			return;
		}
		$fh = fopen(EXT_AUTH_LOGFILE, 'a');
		fwrite($fh, date('d/m/y H:i:s:ms ', time()) . $message . '\n');
	}
}

?>