<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once 'Auth/Container.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilAuthContainerMSSO extends Auth_Container
{
	private static $force_creation = false;

	private $server;
	
	private $user = '';
	private $user_data = '';
	private $firstname = '';
	private $lastname = '';
	private $email = '';
	private $gender = '';
	private $title = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->server = ilMSSOSetting::getInstance();
		
		$GLOBALS['ilLog']->write(__METHOD__.': MSSO initialized');
		
		$_POST['username'] = 'dummy';
		$_POST['password'] = 'dummy';
		
		parent::__construct();
	}
	
	
	public function forceCreation($a_status)
	{
		self::$force_creation = $a_status;
	}
	
	/**
	 * Set user data (account migration)
	 * @param type $a_data
	 */
	public function setUserData($a_data)
	{
		$this->user_data = $a_data;
	}
	
	public function getUserData()
	{
		return $this->user_data;
	}
	
	public function getRawUserData()
	{
		return base64_decode($_REQUEST['mssso_attrbs']);
	}
	
	public function getRawUserName()
	{
		return base64_decode($_REQUEST['mssso_user']);
	}
	
	/**
	 * Get server configuration
	 * @return ilMSSOSetting
	 */
	public function getServer()
	{
		return $this->server;
	}
	
	public function getSSOUserName()
	{
		return $this->user;
	}
	
	/**
	 * Fetch data
	 * @param type $username
	 * @param type $password
	 * @param type $isChallengeResponse
	 *//**
 * Created by PhpStorm.
 * User: stefan
 * Date: 18.09.18
 * Time: 11:30
 */

	public function fetchData($username, $password, $isChallengeResponse = false)
	{
		$GLOBALS['ilLog']->write(__METHOD__.': Auth container mssso called');
		
		if($this->getUserData())
		{
			$GLOBALS['ilLog']->write(__METHOD__.': Account migration -> new account');
			return true;
		}
		
		#$username = trim($_SERVER[ilMSSOSetting::MSSO_AUTH_USER]);
		$username = trim($this->getRawUserName());
		
		$GLOBALS['ilLog']->write(__METHOD__.': User data is: '.print_r($this->getRawUserData(),true));
		
		if(!$username)
		{
			$GLOBALS['ilLog']->write(__METHOD__.': No sso request.');
			return false;
		}
		
		$GLOBALS['ilLog']->write(__METHOD__.': Current user is '.$username);
		
		$this->user = $username;
		return true;
	}
	
	/**
	 * Login observer
	 * @param type $a_username
	 * @param type $a_auth
	 * @return boolean
	 */
	public function loginObserver($a_username, $a_auth)
	{
		
		$user_data['ilInternalAccount'] = ilObjUser::_checkExternalAuthAccount(
				'mssso_'.$this->getServer()->getServerId(),
				$this->getSSOUserName()
		);
		
		
		$mig = new ilMSSOAuthAttributeToUser($this->getServer());

		if(!$user_data['ilInternalAccount'])
		{
			if($this->getServer()->isSyncEnabled() and !self::$force_creation)
			{
				$a_auth->logout();
				$_SESSION['tmp_auth_mode'] = 'mssso_'.$this->getServer()->getServerId();
				$_SESSION['tmp_usr_name'] = $this->getSSOUserName();
				$_SESSION['tmp_usr_pass'] = 'undefined';
				#$_SESSION['tmp_usr_data'] = $_SERVER[ilMSSOSetting::MSSO_AUTH_ATTIBUTES];
				$_SESSION['tmp_usr_data'] = $this->getRawUserData();
				$_SESSION['tmp_external_account'] = $this->user;
				
				ilUtil::redirect('ilias.php?baseClass=ilStartUpGUI&cmd=showAccountMigration&cmdClass=ilstartupgui');
			}
			elseif($this->getServer()->isSyncEnabled () and self::$force_creation)
			{
				$GLOBALS['ilLog']->write(__METHOD__.': Calling create...');
				$this->parseUserData($mig,$a_username,$this->getUserData());
				$new_name = $mig->create($a_username);
				$a_auth->setAuth($new_name);
				return true;
			}
			else
			{
				// No syncronisation allowed => create Error
				$a_auth->status = AUTH_RADIUS_NO_ILIAS_USER;
				$a_auth->logout();
				return false;
			}
		}
		else
		{
			$GLOBALS['ilLog']->write(__METHOD__.': Calling update...');
			$this->parseUserData($mig,$this->getSSOUserName());
			#$mig->create($user_data['ilInternalAccount'],true);
			$a_auth->setAuth($user_data['ilInternalAccount']);
			return true;
		}
	}
	
	/**
	 * Parse user data
	 * @param ilMSSOAuthAttributeToUser $mig
	 * @param type $a_ext_account
	 * @param type $a_ud
	 */
	protected function parseUserData(ilMSSOAuthAttributeToUser $mig, $a_ext_account ,$a_ud = '')
	{
		
		$ud_str = $a_ud ? $a_ud : $this->getRawUserData();
		
		$remote_attrbs = array();
		foreach(explode(':',$ud_str) as $part)
		{
			list($key,$val) = explode('-',$part,2);
			$remote_attrbs[$key] = rawurldecode($val);
		}
		
		$GLOBALS['ilLog']->write(__METHOD__.': Remote attributes '. print_r($remote_attrbs,true));
		
		#$ud_str = $a_ud ? $a_ud : $_SERVER[ilMSSOSetting::MSSO_AUTH_ATTIBUTES];
		
		$mig->setEmail($remote_attrbs[0]);
		
		switch($remote_attrbs[1])
		{
			case 'H':
				$mig->setGender('m');
				break;
			
			case 'F':
				$mig->setGender('f');
				break;
		}
		$mig->setTitle($remote_attrbs[2]);
		$mig->setFirstname($remote_attrbs[3]);
		$mig->setLastname($remote_attrbs[4]);
		$mig->setExtAccount($a_ext_account);
	}
}
?>
