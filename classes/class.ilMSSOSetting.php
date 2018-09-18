<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilMSSOSetting
{
	const MSSO_AUTH_USER = 'HTTP_X_TRUSTED_REMOTE_USER';
	const MSSO_AUTH_ATTIBUTES = 'HTTP_X_TRUSTED_REMOTE_ATTR';
	
	const DB_TABLE = 'auth_authhk_mssso_s';
	
	private static $instances = null;
	
	private $server_id = 1;
	private $active = false;
	
	/**
	 * Constructor
	 * @param type $a_server_id
	 */
	protected function __construct($a_server_id = 1)
	{
		$this->server_id = $a_server_id;
		$this->read();
	}
	
	/**
	 * Get instance by server id
	 * @param type $a_sid
	 * @return ilMSSOSetting
	 */
	public static function getInstance()
	{
		if(isset(self::$instances))
		{
			return self::$instances;
		}
		return self::$instances = new self();
	}
	
	public function getServerId()
	{
		return $this->server_id;
	}
	
	public function isActive()
	{
		return $this->active;
	}
	
	public function activate($a_stat)
	{
		$this->active = $a_stat;
	}
	
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	
	public function enableSync($a_stat)
	{
		$this->sync = $a_stat;
	}
	
	public function isSyncEnabled()
	{
		return $this->sync;
	}
	
	public function setRole($a_role)
	{
		$this->role = $a_role;
	}
	
	public function getRole()
	{
		return $this->role;
	}
	

	public function add()
	{
		global $ilDB;
		
		
		$query = 'INSERT INTO '.self::DB_TABLE.' '.
				'(sid,title, active, sync, role) '.
				'VALUES ( '.
				$ilDB->quote($this->getServerId(),'integer').', '.
				$ilDB->quote($this->getTitle(),'text').', '.
				$ilDB->quote($this->isActive(),'integer').', '.
				$ilDB->quote($this->isSyncEnabled(),'integer').', '.
				$ilDB->quote($this->getRole(),'integer').' '.
				')';
		$ilDB->manipulate($query);
		return $this->getServerId();
	}
	
	/**
	 * Update server
	 * @global type $ilDB
	 * @return boolean
	 */
	public function update()
	{
		global $ilDB;
		
		$this->delete();
		return $this->add();
		
		/**
		 * no update
		 */
		$query = 'UPDATE '.self::DB_TABLE.' '.
				'SET '.
				'title = '.$ilDB->quote($this->getTitle(),'text').', '.
				'active = '.$ilDB->quote($this->isActive(),'integer').', '.
				'sync = '.$ilDB->quote($this->isSyncEnabled(),'integer').', '.
				'role = '.$ilDB->quote($this->getRole(),'integer').', '.
				'WHERE sid = '.$ilDB->quote($this->getServerId(),'integer');
		$ilDB->manipulate($query);
		return true;
	}
	
	/**
	 * delete setting
	 * @global type $ilDB
	 * @return boolean
	 */
	public function delete()
	{
		global $ilDB;
		
		$query = 'DELETE from '.self::DB_TABLE.' '.
				'WHERE sid = '.$ilDB->quote($this->getServerId(),'integer');
		$ilDB->manipulate($query);
		return true;
	}
	
	/**
	 * read settings
	 * @global type $ilDB
	 * @return boolean
	 */
	protected function read()
	{
		global $ilDB;
		
		if(!$this->getServerId())
		{
			return true;
		}
		
		$query = 'SELECT * FROM '.self::DB_TABLE.' '.
				'WHERE sid = '.$ilDB->quote($this->getServerId(),'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(DB_FETCHMODE_OBJECT))
		{
			$this->setTitle($row->title);
			$this->setRole($row->role);
			$this->enableSync($row->sync);
			$this->activate($row->active);
		}
	}
}
?>
