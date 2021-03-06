<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

// Back end modules
$GLOBALS['BE_MOD']['accounts']['debug'] = array
(
	'enable'                  => ['contao_manager.listener.debug', 'onEnable'],
	'disable'                 => ['contao_manager.listener.debug', 'onDisable'],
	'hideInNavigation'        => true,
	'disablePermissionChecks' => true
);
