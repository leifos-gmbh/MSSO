<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Component/classes/class.ilPluginConfigGUI.php';
include_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

/**
 * Description of class
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilMSSSOConfigGUI extends ilPluginConfigGUI
{
	const MODE_ADD_CONFIGURATION = 1;
	const MODE_EDIT_CONFIGURATION = 2;
	
	private $server = null;
	
	/**
	 * Get server setting
	 * @return ilBibAuthSetting
	 */
	protected function getServer()
	{
		return $this->server;
	}
	
	/**
	 * perform command
	 * @global type $ilTabs
	 * @param type $cmd
	 */
	public function performCommand($cmd)
	{
		global $ilTabs;

		$this->initServer();
		
		$GLOBALS['lng']->loadLanguageModule($this->getPluginObject()->getPrefix());

		$ilTabs->addTab(
			'overview',
			ilMSSSOPlugin::getInstance()->txt('tab_settings'),
			$GLOBALS['ilCtrl']->getLinkTarget($this,'overview')
		);

		if(!$cmd or $cmd == 'configure')
		{
			$cmd = 'editConfiguration';
		}

		switch ($cmd)
		{
			case 'updateConfiguration':
			case 'editConfiguration':
				$this->$cmd();
				break;
			
		}
	}
	
	
	/**
	 * Edit configuration
	 */
	protected function editConfiguration(ilPropertyFormGUI $form = null)
	{
		$form = $this->initFormConfiguration(self::MODE_EDIT_CONFIGURATION);
		$GLOBALS['tpl']->setContent($form->getHTML());
	}

	/**
	 * Update a configuration
	 */
	protected function updateConfiguration()
	{
		$form = $this->initFormConfiguration(self::MODE_EDIT_CONFIGURATION);
		if($form->checkInput())
		{
			$this->getServer()->activate($form->getInput('active'));
			$this->getServer()->setTitle($form->getInput('title'));
			$this->getServer()->enableSync($form->getInput('sync'));
			$this->getServer()->setRole($form->getInput('role'));
			$this->getServer()->update();
			ilUtil::sendSuccess($GLOBALS['lng']->txt('settings_saved'),true);
			$GLOBALS['ilCtrl']->redirect($this,'editConfiguration');
		}
		$form->setValuesByPost();
		ilUtil::sendFailure($GLOBALS['lng']->txt('err_check_input'));
		$this->editConfiguration($form);
	}

	
	/**
	 * Create configuration form
	 */
	protected function initFormConfiguration($a_mode = self::MODE_ADD_CONFIGURATION, ilPropertyFormGUI $form = null)
	{
		if($form instanceof ilPropertyFormGUI)
		{
			return $form;
		}
		
		$form = new ilPropertyFormGUI();
		$form->setFormAction($GLOBALS['ilCtrl']->getFormAction($this));
		$form->setShowTopButtons(false);
		
		if($a_mode == self::MODE_ADD_CONFIGURATION)
		{		
			$form->setTitle($this->getPluginObject()->txt('conf_add_title'));
			$form->addCommandButton(
					'saveConfiguration',
					$GLOBALS['lng']->txt('save')
			);
			$form->addCommandButton(
					'overview',
					$GLOBALS['lng']->txt('cancel')
			);
		}
		else
		{
			$form->setTitle($this->getPluginObject()->txt('conf_edit_title'));
			$form->addCommandButton(
					'updateConfiguration',
					$GLOBALS['lng']->txt('save')
			);
			$form->addCommandButton(
					'overview',
					$GLOBALS['lng']->txt('cancel')
			);
		}
		
		// activation
		$active = new ilCheckboxInputGUI(
				$this->getPluginObject()->txt('conf_activation'),
				'active'
		);
		$active->setValue(1);
		$active->setChecked($this->getServer()->isActive());
		$form->addItem($active);

		// configuration title
		$title = new ilTextInputGUI($GLOBALS['lng']->txt('title'),'title');
		$title->setValue($this->getServer()->getTitle());
		$title->setMaxLength(64);
		$title->setSize(32);
		$title->setRequired(true);
		$form->addItem($title);
		
		// User synchronization
		$sync = new ilRadioGroupInputGUI(
				$this->getPluginObject()->txt('conf_sync_login_type'),
				'sync'
		);
		$sync->setValue($this->getServer()->isSyncEnabled() ? 1 : 0);
		$sync->setRequired(true);
		$form->addItem($sync);

		// Disabled
		$dis = new ilRadioOption(
			$GLOBALS['lng']->txt('disabled'),
			0,
			''
		);
		$sync->addOption($dis);

		// on login
		$rad = new ilRadioOption(
			$this->getPluginObject()->txt('conf_sync_login'),
			1,
			''
		);
		$rad->setInfo($this->getPluginObject()->txt('conf_sync_login_info'));
		$sync->addOption($rad);

		$select = new ilSelectInputGUI(
				$this->getPluginObject()->txt('conf_role_selection'),
				'role'
		);
		$select->setValue($this->getServer()->getRole());
		$select->setOptions($this->prepareRoleSelection());
		$rad->addSubItem($select);
		
		return $form;
	}

	/**
	 * Prepare role selection
	 * @global type $rbacreview
	 * @global type $ilObjDataCache
	 * @return type
	 */
	private function prepareRoleSelection()
	{
		global $rbacreview;
		
		$global_roles = ilUtil::_sortIds($rbacreview->getGlobalRoles(),
			'object_data',
			'title',
			'obj_id');
		
		$select[0] = $GLOBALS['lng']->txt('links_select_one');
		foreach($global_roles as $role_id)
		{
			$select[$role_id] = ilObject::_lookupTitle($role_id);
		}
		
		return $select;
	}
	
	/**
	 * Init server
	 */
	protected function initServer()
	{
		$this->getPluginObject()->includeClass('class.ilMSSOSetting.php');
		$this->server = ilMSSSOSetting::getInstance();
	}
	
}
?>
