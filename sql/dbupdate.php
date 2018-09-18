<#1>
<?php

if(!$ilDB->tableExists('auth_authhk_mssso_s'))

	$fields = array(
		'sid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'active' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true
		),
		'title' => array(
			'type' => 'text',
			'length' => 64,
			'notnull' => false
		),
		'sync' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true
		),
		'role' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		)
	);

	#$ilDB->createTable('auth_authhk_mssso_s', $fields);
	#$ilDB->createSequence('auth_authhk_mssso_s');
	#$ilDB->addPrimaryKey('auth_authhk_mssso_s', array('sid'));
?>
<#2>
<?php

if(!$ilDB->tableExists('auth_authhk_mssso_s'))
{

	$fields = array(
		'sid' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		),
		'active' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true
		),
		'title' => array(
			'type' => 'text',
			'length' => 64,
			'notnull' => false
		),
		'sync' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true
		),
		'role' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
		)
	);
	$ilDB->createTable('auth_authhk_mssso_s', $fields);
	$ilDB->createSequence('auth_authhk_mssso_s');
	$ilDB->addPrimaryKey('auth_authhk_mssso_s', array('sid'));
}

?>
