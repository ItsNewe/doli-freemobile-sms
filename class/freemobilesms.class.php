<?php
/* Copyright (C) 2026	Newe	<lobo@arcafin.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/custom/freemobilesms/class/freemobilesms.class.php
 * \ingroup freemobilesms
 * \brief   SMS engine loaded by CSMSFile when MAIN_SMS_SENDMODE is freemobilesms
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
dol_include_once('/freemobilesms/lib/freemobilesms.lib.php');

/**
 * Send SMS with the Free Mobile notification API. The API only texts the line owning the login,
 * so the recipient must be one of the lines of the setup.
 */
class FreeMobileSms
{
	const API_URL = 'https://smsapi.free-mobile.fr/sendmsg';

	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Sender (ignored by Free Mobile)
	 */
	public $expe;

	/**
	 * @var string Recipient phone number
	 */
	public $dest;

	/**
	 * @var string Message
	 */
	public $message;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $deferred;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $priority;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $class;

	/**
	 * @var bool Unused, set by CSMSFile
	 */
	public $nostop;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $deliveryreceipt;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $socid;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $contact_id;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $member_id;

	/**
	 * @var int Unused, set by CSMSFile
	 */
	public $fk_project;

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string[] Error messages
	 */
	public $errors = array();

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Send the SMS
	 *
	 * @return	int		1 if sent, -1 if error
	 */
	public function SmsSend()	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- name called by CSMSFile
	{
		global $langs;
		$langs->load("freemobilesms@freemobilesms");

		$account = freemobilesmsFindAccount((string) $this->dest);
		if ($account === null) {
			return $this->setError($langs->transnoentitiesnoconv('FreeMobileSmsErrorUnknownNumber', (string) $this->dest));
		}

		// POST keeps the key out of the URL logged by getURLContent(). Free only reads a JSON body (form fields give 400).
		$param = json_encode(array(
			'user' => $account['login'],
			'pass' => dolDecrypt($account['key']),
			'msg' => freemobilesmsApplyTemplate((string) $this->message),
		));
		$headers = array('Content-Type: application/json');
		$result = getURLContent(self::API_URL, 'POSTALREADYFORMATED', $param, 1, $headers, array('https'), 0, -1, 10, 30);

		$code = (int) $result['http_code'];
		if ($code == 200) {
			return 1;
		}
		if (in_array($code, array(400, 402, 403, 500))) {
			return $this->setError($langs->transnoentitiesnoconv('FreeMobileSmsError'.$code));
		}
		return $this->setError($langs->transnoentitiesnoconv('FreeMobileSmsErrorHttp', $code, $result['curl_error_msg']));
	}

	/**
	 * Return the possible senders, used by the SMS form. Free Mobile has no sender: list the lines of the setup.
	 *
	 * @return	stdClass[]	Objects with property number
	 */
	public function SmsSenderList()	// phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- name called by FormSms
	{
		$senders = array();
		foreach (freemobilesmsGetAccounts() as $account) {
			$sender = new stdClass();
			$sender->number = $account['number'];
			$senders[] = $sender;
		}
		return $senders;
	}

	/**
	 * Set the error and log it
	 *
	 * @param	string	$message	Error message
	 * @return	int					-1
	 */
	private function setError($message)
	{
		$this->error = $message;
		$this->errors = array($message);
		dol_syslog(__METHOD__.' '.$message, LOG_ERR);
		return -1;
	}
}
