<?php

use ICanBoogie\Module;
use ICanBoogie\ActiveRecord\Model;

return array
(
	Module::T_TITLE => 'Forms',
	Module::T_DESCRIPTION => 'Create forms based on models',
	Module::T_CATEGORY => 'feedback',
	Module::T_EXTENDS => 'nodes',
	Module::T_MODELS => array
	(
		'primary' => array
		(
			Model::T_EXTENDS => 'nodes',
			Model::T_SCHEMA => array
			(
				'fields' => array
				(
					'modelid' => array('varchar', 64),

					'before' => 'text',
					'after' => 'text',
					'complete' => 'text',

					'is_notify' => 'boolean',
					'notify_destination' => 'varchar',
					'notify_from' => 'varchar',
					'notify_bcc' => 'varchar',
					'notify_subject' => 'varchar',
					'notify_template' => 'text',

					'pageid' => 'foreign'
				)
			)
		)
	),

	Module::T_PERMISSIONS => array
	(
		'post form'
	)
);