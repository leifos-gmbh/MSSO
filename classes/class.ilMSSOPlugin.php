<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
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

include_once './Services/Authentication/classes/class.ilAuthPlugin.php';
include_once './Services/Authentication/interfaces/interface.ilAuthDefinition.php';


/** 
* Base plugin class
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id: class.ilBibAuthPlugin.php 40392 2013-03-06 14:10:25Z smeyer $
* 
*
*/
class ilMSSSOPlugin extends ilAuthPlugin implements ilAuthDefinition
{
	private static $instance = null;

	const CTYPE = 'Services';
	const CNAME = 'Authentication';
	const SLOT_ID = 'authhk';
	const PNAME = 'MSSSO';
	
	const AUTH_ID_BASE = 1300;
	
	
	/**
	 * Get singelton instance
	 * @global ilPluginAdmin $ilPluginAdmin
	 * @return ilBibAuthPlugin
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}

		include_once './Services/Component/classes/class.ilPluginAdmin.php';
		return self::$instance = ilPluginAdmin::getPluginObject(
			self::CTYPE,
			self::CNAME,
			self::SLOT_ID,
			self::PNAME
		);
		
	}
	
	/**
	 * Get name of plugin.
	 */
	public function getPluginName()
	{
		return self::PNAME;
	}

	/**
	 * Init slot
	 */
	protected function slotInit()
	{
		$this->initAutoLoad();
	}

	/**
	 * Get all active auth ids
	 * @return array int
	 */
	public function getAuthIds()
	{
		static $auth_ids = null;
		
		if($auth_ids === null)
		{
			$this->includeClass('class.ilMSSOSetting.php');
			$setting = ilMSSSOSetting::getInstance();
			if($setting->isActive())
			{
				$auth_ids[] = (self::AUTH_ID_BASE + (int) $setting->getServerId());
			}
		}
		return $auth_ids;
	}

	/**
	 * Get auth id by name
	 * @param type $a_auth_name
	 */
	public function getAuthIdByName($a_auth_name)
	{
		if(stristr($a_auth_name, '_'))
		{
			$exploded = explode('_',$a_auth_name);
			return self::AUTH_ID_BASE + $exploded[1];
		}
		return self::AUTH_ID_BASE;
	}
	
	/**
	 * Get auth name by id
	 * @param type $a_auth_id
	 * @return string
	 */
	public function getAuthName($a_auth_id)
	{
		$sid = $a_auth_id - self::AUTH_ID_BASE;
		return 'mssso_'.$sid;
	}

	
	/**
	 * Get container
	 */
	public function getContainer($a_auth_id)
	{
		$sid = $this->extractServerId($a_auth_id);
		if($a_auth_id == (self::AUTH_ID_BASE + $sid))
		{
			$this->includeClass('class.ilAuthContainerMSSO.php');
			$container = new ilAuthContainerMSSSO();
			return $container;
		}
		return null;
	}

	/**
	 * 
	 * @param type $a_auth_id
	 * @return type
	 */
	public function getLocalPasswordValidationType($a_auth_id)
	{
		return ilAuthUtils::LOCAL_PWV_FULL;
	}

	/**
	 * 
	 */
	public function isExternalAccountNameRequired($a_auth_id)
	{
		return true;
	}

	/**
	 * Check if password modification is allowed
	 */
	public function isPasswordModificationAllowed($a_auth_id)
	{
		return false;
	}

	/**
	 * Check multiple auth tries are suported
	 * @param type $a_auth_id
	 * @return boolean
	 */
	public function supportsMultiCheck($a_auth_id)
	{
		return true;
	}

	/**
	 * Get options for mutliple auth mode selection
	 * @param type $a_auth_id
	 */
	public function getMultipleAuthModeOptions($a_auth_id)
	{
		$sid = $this->extractServerId($a_auth_id);
		$this->includeClass('class.ilMSSOSetting.php');
		return array(
			$a_auth_id => array('txt' => ilMSSSOSetting::getInstance()->getTitle())
		);
	}
	
	
	
	/**
	 * Extract auth id
	 * @param type $a_auth_id
	 * @return int 
	 */
	protected function extractServerId($a_auth_id)
	{
		return (int) ($a_auth_id - self::AUTH_ID_BASE);
	}

	/**
	 * Check if auth is active
	 * @param type $a_auth_id
	 */
	public function isAuthActive($a_auth_id)
	{
		$sid = $this->extractServerId($a_auth_id);
		$this->includeClass('class.ilMSSOSetting.php');
		$setting = ilMSSSOSetting::getInstance();
		if($setting->isActive())
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Init auto loader
	 * @return void
	 */
	protected function initAutoLoad()
	{
		$GLOBALS['ilLog']->write(__METHOD__.': ---------------------------------Initialising sso auto load');
		spl_autoload_register(
			array($this,'autoLoad')
		);
	}

	/**
	 * Auto load implementation
	 *
	 * @param string class name
	 */
	private final function autoLoad($a_classname)
	{
		$class_file = $this->getClassesDirectory().'/class.'.$a_classname.'.php';
		@include_once($class_file);
	}
}
?>