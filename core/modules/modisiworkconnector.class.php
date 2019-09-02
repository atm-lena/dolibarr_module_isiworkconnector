<?php
/* Copyright (C) 2019 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\defgroup   isiworkconnector     Module isiworkconnector
 *  \brief      Example of a module descriptor.
 *				Such a file must be copied into htdocs/isiworkconnector/core/modules directory.
 *  \file       htdocs/isiworkconnector/core/modules/modisiworkconnector.class.php
 *  \ingroup    isiworkconnector
 *  \brief      Description and activation file for module isiworkconnector
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module isiworkconnector
 */
class modisiworkconnector extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		$this->editor_name = 'ATM-Consulting';
		$this->editor_url = 'https://www.atm-consulting.fr';
		
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 104654; // 104000 to 104999 for ATM CONSULTING
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'isiworkconnector';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "ATM";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Description of module isiworkconnector";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0.0';
		// Key used in llx_const table to save module status enabled/disabled (where ISIWORKCONNECTOR is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='isiworkconnector@isiworkconnector';

		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/isiworkconnector/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into isiworkconnector/admin directory, to use to setup module.
		$this->config_page_url = array("isiworkconnector_setup.php@isiworkconnector");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->conflictwith = array();	// List of modules id this module is in conflict with
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("isiworkconnector@isiworkconnector");

		// Constants
		$this->const = array();

        $this->tabs = array();

        // Dictionaries
	    if (! isset($conf->isiworkconnector->enabled))
        {
        	$conf->isiworkconnector=new stdClass();
        	$conf->isiworkconnector->enabled=0;
        }
		$this->dictionaries=array();

	    //Boxes
        $this->boxes = array();			// List of boxes

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus
		 $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=tools',		// Put 0 if this is a single top menu or keep fk_mainmenu to give an entry on left
									'type'=>'left',			                // This is a Top menu entry
									'titre'=>'ISIWork',
									'mainmenu'=>'isiworkconnector',
									'leftmenu'=>'isiworkconnector_left',			// This is the name of left menu for the next entries
									'url'=>'/isiworkconnector/interface.php',
									'langs'=>'isiworkconnector@isiworkconnector',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->isiworkconnector->enabled',	// Define condition to show or hide menu entry. Use '$conf->isiworkconnector->enabled' if entry must be visible if module is enabled.
									'perms'=>'1',			                // Use 'perms'=>'$user->rights->isiworkconnector->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		 $r++;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array();
		
		define('INC_FROM_DOLIBARR', true);

		require dol_buildpath('/isiworkconnector/script/create-maj-base.php');

		$result=$this->_load_tables('/isiworkconnector/sql/');

		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
    public function remove($options = '')
    {
		$sql = array();

		return $this->_remove($sql, $options);
    }

}
