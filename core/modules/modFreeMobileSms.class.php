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
 * 	\defgroup   freemobilesms     Module FreeMobileSms
 *  \brief      SMS sending engine using the Free Mobile notification API.
 *
 *  \file       htdocs/custom/freemobilesms/core/modules/modFreeMobileSms.class.php
 *  \ingroup    freemobilesms
 *  \brief      Description and activation file for module FreeMobileSms
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module FreeMobileSms
 */
class modFreeMobileSms extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->numero = 517460;
		$this->rights_class = 'freemobilesms';
		$this->family = "interface";
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "FreeMobileSmsDescription";
		$this->descriptionlong = "FreeMobileSmsDescriptionLong";
		$this->editor_name = 'Newe';
		$this->editor_url = '';
		$this->version = '1.0.0';
		$this->const_name = 'MAIN_MODULE_'.dol_strtoupper($this->name);
		$this->picto = 'phone';

		// 'sms' registers this module as an SMS engine selectable in Setup > SMS (MAIN_SMS_SENDMODE).
		// CSMSFile then loads /freemobilesms/class/freemobilesms.class.php.
		$this->module_parts = array(
			'sms' => 'freemobilesms',
		);

		$this->dirs = array();
		$this->config_page_url = array("setup.php@freemobilesms");
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("freemobilesms@freemobilesms");
		$this->phpmin = array(7, 2);
		$this->need_dolibarr_version = array(20, -3);
		$this->need_javascript_ajax = 0;

		$this->const = array(
			1 => array('FREEMOBILESMS_TEMPLATE', 'chaine', '__SMS_MESSAGE__', 'Template of sent SMS, __SMS_MESSAGE__ is replaced by the message', 0, 'current', 0),
		);

		if (!isModEnabled("freemobilesms")) {
			$conf->freemobilesms = new stdClass();
			$conf->freemobilesms->enabled = 0;
		}

		$this->tabs = array();
		$this->dictionaries = array();
		$this->boxes = array();
		$this->cronjobs = array();
		$this->rights = array();
		$this->menu = array();
	}

	/**
	 *  Function called when module is enabled.
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int<-1,1>          	1 if OK, <=0 if KO
	 */
	public function init($options = '')
	{
		$this->remove($options);

		$sql = array();
		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *
	 *	@param	string		$options	Options when enabling module ('', 'noboxes')
	 *	@return	int<-1,1>				1 if OK, <=0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
