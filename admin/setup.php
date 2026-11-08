<?php
/* Copyright (C) 2026	Newe	<lobo@arcafin.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/custom/freemobilesms/admin/setup.php
 * \ingroup freemobilesms
 * \brief   FreeMobileSms setup page.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;  // @phan-suppress-current-line DolibarrForbiddenFunctionPlugin
$j = strlen($tmp2) - 1;  // @phan-suppress-current-line DolibarrForbiddenFunctionPlugin
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
dol_include_once('/freemobilesms/lib/freemobilesms.lib.php');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "sms", "freemobilesms@freemobilesms"));

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (!$user->admin) {
	accessforbidden();
}

$hookmanager->initHooks(array('freemobilesmssetup', 'globalsetup'));


/*
 * Actions
 */

if ($action == 'updatetemplate') {
	$template = GETPOST('FREEMOBILESMS_TEMPLATE', 'nohtml');
	if (dolibarr_set_const($db, 'FREEMOBILESMS_TEMPLATE', $template, 'chaine', 0, '', $conf->entity) > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'addaccount') {
	$number = trim(GETPOST('number', 'alphanohtml'));
	$login = trim(GETPOST('login', 'alphanohtml'));
	$key = trim(GETPOST('apikey', 'none'));
	if ($number === '' || $login === '' || $key === '') {
		setEventMessages($langs->trans('FreeMobileSmsErrorAccountFields'), null, 'errors');
	} elseif (strlen(freemobilesmsNormalizeNumber($number)) < 9) {
		setEventMessages($langs->trans('FreeMobileSmsErrorBadNumber', $number), null, 'errors');
	} elseif (freemobilesmsFindAccount($number) !== null) {
		setEventMessages($langs->trans('FreeMobileSmsErrorAccountExists', $number), null, 'errors');
	} else {
		$accounts = freemobilesmsGetAccounts();
		$accounts[] = array('number' => $number, 'login' => $login, 'key' => dolEncrypt($key));
		if (freemobilesmsSaveAccounts($db, $accounts) > 0) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}

if ($action == 'deleteaccount') {
	$accounts = freemobilesmsGetAccounts();
	$idx = GETPOSTINT('idx');
	if (isset($accounts[$idx])) {
		unset($accounts[$idx]);
		if (freemobilesmsSaveAccounts($db, $accounts) > 0) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * View
 */

$form = new Form($db);

$title = "FreeMobileSmsSetup";
llxHeader('', $langs->trans($title), '', '', 0, 0, '', '', '', 'mod-freemobilesms page-admin_setup');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

$head = freemobilesmsAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, 'phone');

print '<span class="opacitymedium">'.$langs->trans("FreeMobileSmsSetupHelp").'</span><br><br>';

if (getDolGlobalString('MAIN_SMS_SENDMODE') != 'freemobilesms') {
	print '<div class="warning">'.$langs->trans('FreeMobileSmsWarningNotSendMode', '<a href="'.DOL_URL_ROOT.'/admin/sms.php">'.$langs->trans('SmsSetup').'</a>').'</div>';
} else {
	print '<div class="info">'.$langs->trans('FreeMobileSmsInfoTest', '<a href="'.DOL_URL_ROOT.'/admin/sms.php">'.$langs->trans('SmsSetup').'</a>').'</div>';
}

// Lines
print load_fiche_titre($langs->trans('FreeMobileSmsAccounts'), '', '');
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" autocomplete="off">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="addaccount">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$form->textwithpicto($langs->trans("FreeMobileSmsNumber"), $langs->trans("FreeMobileSmsNumberHelp")).'</td>';
print '<td>'.$form->textwithpicto($langs->trans("FreeMobileSmsLogin"), $langs->trans("FreeMobileSmsLoginHelp")).'</td>';
print '<td>'.$form->textwithpicto($langs->trans("FreeMobileSmsApiKey"), $langs->trans("FreeMobileSmsApiKeyHelp")).'</td>';
print '<td></td>';
print '</tr>';
foreach (freemobilesmsGetAccounts() as $idx => $account) {
	print '<tr class="oddeven">';
	print '<td>'.dolPrintHTML($account['number']).'</td>';
	print '<td>'.dolPrintHTML($account['login']).'</td>';
	print '<td>'.img_picto('', 'tick', 'class="pictofixedwidth"').$langs->trans('FreeMobileSmsApiKeyStored').'</td>';
	print '<td class="right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=deleteaccount&idx='.((int) $idx).'&token='.newToken().'">'.img_delete().'</a></td>';
	print '</tr>';
}
print '<tr class="oddeven">';
print '<td><input type="text" class="minwidth150" name="number" placeholder="06 12 34 56 78"></td>';
print '<td><input type="text" class="minwidth100" name="login" placeholder="12345678"></td>';
print '<td><input type="password" class="minwidth200" name="apikey" autocomplete="new-password"></td>';
print '<td class="right"><input type="submit" class="button small" value="'.$langs->trans("Add").'"></td>';
print '</tr>';
print '</table>';
print '</div>';
print '</form>';

// Template
print load_fiche_titre($langs->trans('FreeMobileSmsTemplate'), '', '');
print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updatetemplate">';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
print '<tr class="oddeven"><td class="titlefieldcreate tdtop">'.$form->textwithpicto($langs->trans("FreeMobileSmsTemplate"), $langs->trans("FreeMobileSmsTemplateHelp")).'</td>';
print '<td><textarea name="FREEMOBILESMS_TEMPLATE" class="quatrevingtpercent" rows="4">'.dolPrintText(getDolGlobalString('FREEMOBILESMS_TEMPLATE')).'</textarea>';
print '<br><span class="opacitymedium">'.$langs->trans('Example').': '.dolPrintHTML(freemobilesmsApplyTemplate($langs->transnoentitiesnoconv('FreeMobileSmsExampleMessage'))).'</span></td></tr>';
print '</table>';
print '</div>';
print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></div>';
print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
