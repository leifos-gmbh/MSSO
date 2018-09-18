<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

class ilAuthProviderMSSO extends ilAuthProvider implements ilAuthProviderInterface
{
	/**
	 * @var \ilLogger
	 */
	private $logger = null;

	private $server = null;

	/**
	 * ilAuthProviderMSSO constructor.
	 * @param ilAuthCredentials $credentials
	 */
	public function __construct(ilAuthCredentials $credentials)
	{
		global $DIC;

		$this->logger = $DIC->logger()->auth();
		$this->server = ilMSSOSetting::getInstance();

		parent::__construct($credentials);
	}

	/**
	 * @return ilMSSOSetting|null
	 */
	protected function getServer()
	{
		return $this->server;
	}

	/**
	 * @return bool|string
	 */
	public function getRawUserData()
	{
		return base64_decode($_REQUEST['mssso_attrbs']);
	}

	/**
	 * @return bool|string
	 */
	public function getRawUserName()
	{
		return base64_decode($_REQUEST['mssso_user']);
	}



	/**
	 * Do authentication
	 * @param \ilAuthStatus $status Authentication status
	 * @return bool
	 */
	public function doAuthentication(\ilAuthStatus $status)
	{
		$this->logger->debug('Auth provider sso called.');


		$this->logger->debug('Received user_data: ');
		$this->logger->dump($this->getRawUserData(),ilLogLevel::DEBUG);
		if(!$this->getRawUserData())
		{
			$this->logger->info('No sso request');
			$status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
			$status->setReason('err_wrong_login');
			return false;
		}
		// username available
		$this->getCredentials()->setUsername($this->getRawUserName());
		$ilias_login = ilObjUser::_checkExternalAuthAccount(
			'mssso_'.$this->getServer()->getServerId(),
			$this->getCredentials()->getUsername()
		);

		if(
			!$ilias_login &&
			$this->getServer()->isSyncEnabled()
		)
		{
			// create user
			$this->logger->debug('Starting creation of new user.');
			$mig = new ilMSSOAuthAttributeToUser($this->getServer());
			$this->parseUserData($mig,$this->getCredentials()->getUsername());
			$new_name = $mig->create($this->getCredentials()->getUsername(),false);

			$status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
			$status->setAuthenticatedUserId(ilObjUser::_lookupId($new_name));
			return true;

		}
		elseif(!$this->getServer()->isSyncEnabled())
		{
			$this->logger->notice('New account required. Account migration disabled.');
			$status->setStatus(ilAuthStatus::STATUS_AUTHENTICATION_FAILED);
			$status->setReason('err_auth_ldap_no_ilias_user');
			return false;
		}

		// we have a valid user
		$this->logger->debug('Starting update of user data');
		$mig = new ilMSSOAuthAttributeToUser($this->getServer());
		$this->parseUserData($mig,$this->getCredentials()->getUsername());
		$mig->create($ilias_login,true);

		$status->setStatus(ilAuthStatus::STATUS_AUTHENTICATED);
		$status->setAuthenticatedUserId(ilObjUser::_lookupId($ilias_login));
		return true;
	}


	/**
	 * Parse user data
	 * @param ilMSSOAuthAttributeToUser $mig
	 * @param string $a_ext_account
	 * @param string $a_ud
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

		$this->logger->debug('Remote attibutes: ');
		$this->logger->dump($remote_attrbs, ilLogLevel::DEBUG);

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