<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

class ilAuthProviderMSSO extends ilAuthProvider implements ilAuthProviderInterface
{
	/**
	 * @var \ilLogger
	 */
	private $logger = null;

	/**
	 * ilAuthProviderMSSO constructor.
	 * @param ilAuthCredentials $credentials
	 */
	public function __construct(ilAuthCredentials $credentials)
	{
		global $DIC;

		$this->logger = $DIC->$this->logger()->auth();
		parent::__construct($credentials);
	}
}