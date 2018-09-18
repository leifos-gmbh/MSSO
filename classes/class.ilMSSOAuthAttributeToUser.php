<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/** 
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ingroup ServicesRadius 
*/
class ilMSSSOAuthAttributeToUser
{
	
	private $server;
	
	private $firstname = '';
	private $lastname = '';
	private $additional_roles;
	private $ext_account = '';
	
	/**
	 * Constructor
	 *
	 * @access public
	 * 
	 */
	public function __construct(ilMSSSOSetting $server)
	{
		global $ilLog;
		
		$this->log = $ilLog;
		
		$this->server = $server;
		
		include_once('./Services/Xml/classes/class.ilXmlWriter.php');
	 	$this->writer = new ilXmlWriter();
	}
	
	/**
	 * Get server setting
	 * @return ilBibAuthSetting
	 */
	public function getServer()
	{
		return $this->server;
	}
	
	public function setFirstname($a_name)
	{
		$this->firstname = $a_name;
	}

	public function setLastname($a_name)
	{
		$this->lastname = $a_name;
	}
	
	public function setEmail($a_email)
	{
		$this->email = $a_email;
	}
	
	public function setGender($a_gender)
	{
		$this->gender = $a_gender;
	}
	
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}


	public function setAdditionalRoles($a_roles)
	{
		$this->additional_roles = $a_roles;
	}
	
	public function getAdditionalRoles()
	{
		return (array) $this->additional_roles;
	}
	
	public function setExtAccount($a_ext_account)
	{
		$this->ext_account = $a_ext_account;
	}
	
	public function getExtAccount()
	{
		return $this->ext_account;
	}

	/**
	 * Create new ILIAS account
	 *
	 * @access public
	 * 
	 * @param string internal username
	 */
	public function create($a_username, $a_update = false)
	{
		global $rbacreview;
		
		$this->writer->xmlStartTag('Users');
		
		// Single users
		// Required fields
		// Create user
		if($a_update)
		{
			$this->writer->xmlStartTag('User',array('Action' => 'Update'));
			$this->writer->xmlElement('Login',array(),$a_username);
			
		}
		else
		{
			$this->writer->xmlStartTag('User',array('Action' => 'Insert'));
			$this->writer->xmlElement('Login',array(),$new_name = ilAuthUtils::_generateLogin($a_username));
		}
				
		// Assign to role only for new users
		$this->writer->xmlElement(
				'Role',
				array(
					'Id' => $this->server->getRole(),
					'Type' => 'Global',
					'Action' => 'Assign'),''
		);
		
		foreach($this->getAdditionalRoles() as $role)
		{
			if($rbacreview->isGlobalRole((int) $role))
			{
				$GLOBALS['ilLog']->write(__METHOD__.' Assigning additional global role '.ilObject::_lookupTitle((int) $role));
				$this->writer->xmlElement(
						'Role',
						array(
							'Id' => (int) $role,
							'Type' => 'Global',
							'Action' => 'Assign'),
						ilObject::_lookupTitle($role)
				);
			}
			else
			{
				$GLOBALS['ilLog']->write(__METHOD__.' Assigning additional local role '.ilObject::_lookupTitle((int) $role));
				$this->writer->xmlElement(
						'Role',
						array(
							'Id' => (int) $role,
							'Type' => 'Local',
							'Action' => 'Assign'),
						ilObject::_lookupTitle($role)
				);
			}
		}

		$this->writer->xmlElement('Active',array(),"true");
		$this->writer->xmlElement('Firstname',array(),$this->firstname);
		$this->writer->xmlElement('Lastname',array(),$this->lastname);
		$this->writer->xmlElement('Title',array(),$this->title);
		$this->writer->xmlElement('Email',array(),$this->email);
		$this->writer->xmlElement('TimeLimitOwner',array(),USER_FOLDER_ID);
		$this->writer->xmlElement('TimeLimitUnlimited',array(),1);
		$this->writer->xmlElement('TimeLimitFrom',array(),time());
		$this->writer->xmlElement('TimeLimitUntil',array(),time());
		$this->writer->xmlElement('AuthMode',array('type' => 'mssso_'.$this->server->getServerId()),'mssso_'.$this->server->getServerId());
		$this->writer->xmlElement('ExternalAccount',array(),$this->getExtAccount());
		
		$this->writer->xmlEndTag('User');
		$this->writer->xmlEndTag('Users');
		$this->log->write('MSSSO Auth: Started creation of user: '.$new_name);
		
		
		$GLOBALS['ilLog']->write(__METHOD__.': XML is '. $this->writer->xmlDumpMem());
		
		include_once './Services/User/classes/class.ilUserImportParser.php';
		$importParser = new ilUserImportParser();
		$importParser->setXMLContent($this->writer->xmlDumpMem(false));
		$importParser->setRoleAssignment($this->getRoleAssignments());
		$importParser->setFolderId(7);
		$importParser->startParsing();
		
	 	return $new_name;
	}
	
	
	protected function getRoleAssignments()
	{
		$assignments[$this->server->getRole()] = $this->server->getRole();
		foreach($this->getAdditionalRoles() as $role)
		{
			$assignments[$role] = $role;
		}
		return $assignments;
	}
}


?>