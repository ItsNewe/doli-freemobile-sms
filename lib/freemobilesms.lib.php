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
 * \file    htdocs/custom/freemobilesms/lib/freemobilesms.lib.php
 * \ingroup freemobilesms
 * \brief   Common functions for module FreeMobileSms
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function freemobilesmsAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("freemobilesms@freemobilesms");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/freemobilesms/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/freemobilesms/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'freemobilesms@freemobilesms');
	complete_head_from_modules($conf, $langs, null, $head, $h, 'freemobilesms@freemobilesms', 'remove');

	return $head;
}

/**
 * Return the Free Mobile lines of the setup. Keys are still encrypted (dolEncrypt).
 *
 * @return array<int,array{number:string,login:string,key:string}>
 */
function freemobilesmsGetAccounts()
{
	$accounts = json_decode(getDolGlobalString('FREEMOBILESMS_ACCOUNTS'), true);
	return is_array($accounts) ? array_values($accounts) : array();
}

/**
 * Save the Free Mobile lines of the setup
 *
 * @param	DoliDB												$db			Database handler
 * @param	array<int,array{number:string,login:string,key:string}>	$accounts	Lines, keys already encrypted
 * @return	int																	>0 if OK, <0 if KO
 */
function freemobilesmsSaveAccounts($db, $accounts)
{
	global $conf;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
	return dolibarr_set_const($db, 'FREEMOBILESMS_ACCOUNTS', json_encode(array_values($accounts)), 'chaine', 0, '', $conf->entity);
}

/**
 * Return a phone number reduced to its last 9 digits, so +33 6 12 34 56 78, 0033612345678 and 06.12.34.56.78 are equal
 *
 * @param	string	$number		Phone number as typed
 * @return	string				Last 9 digits
 */
function freemobilesmsNormalizeNumber($number)
{
	// ponytail: French numbers only (Free Mobile lines are French), compare the national part without its leading 0
	return substr(preg_replace('/\D/', '', (string) $number), -9);
}

/**
 * Return the account of the setup whose number is the recipient
 *
 * @param	string	$number		Recipient phone number
 * @return	array{number:string,login:string,key:string}|null	Account, null if the number is not one of the setup
 */
function freemobilesmsFindAccount($number)
{
	$number = freemobilesmsNormalizeNumber($number);
	if ($number === '') {
		return null;
	}
	foreach (freemobilesmsGetAccounts() as $account) {
		if (freemobilesmsNormalizeNumber($account['number']) === $number) {
			return $account;
		}
	}
	return null;
}

/**
 * Return the text to send: the template of the setup, with Dolibarr substitution variables, around the message
 *
 * @param	string	$message	Message given by the caller
 * @return	string				Text to send
 */
function freemobilesmsApplyTemplate($message)
{
	global $langs;

	$template = getDolGlobalString('FREEMOBILESMS_TEMPLATE');
	if (strpos($template, '__SMS_MESSAGE__') === false) {
		return $message;
	}
	// Message inserted last, so a message containing __XXX__ is sent as given
	$text = make_substitutions($template, getCommonSubstitutionArray($langs));
	return str_replace('__SMS_MESSAGE__', $message, $text);
}
