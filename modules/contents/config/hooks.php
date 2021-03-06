<?php

return array
(
	'events' => array
	(
		'ICanBoogie\Modules\System\Cache\Module::alter.block.manage' => 'ICanBoogie\Modules\Contents\Hooks::alter_block_manage'
	),

	'objects.methods' => array
	(
		'ICanBoogie\Modules\System\Cache\EnableOperation::enable_contents_body' => 'ICanBoogie\Modules\Contents\Hooks::enable_cache',
		'ICanBoogie\Modules\System\Cache\DisableOperation::disable_contents_body' => 'ICanBoogie\Modules\Contents\Hooks::disable_cache',
		'ICanBoogie\Modules\System\Cache\StatOperation::stat_contents_body' => 'ICanBoogie\Modules\Contents\Hooks::stat_cache',
		'ICanBoogie\Modules\System\Cache\ClearOperation::clear_contents_body' => 'ICanBoogie\Modules\Contents\Hooks::clear_cache'
	),

	'patron.markups' => array
	(
		'contents' => array
		(
			'o:contents_view_WdMarkup', array
			(
				'constructor' => 'contents',
				'select' => array('expression' => true, 'required' => true)
			)
		),

		'contents:home' => array
		(
			'o:contents_home_WdMarkup', array
			(
				'constructor' => 'contents'
			)
		),

		'contents:list' => array
		(
			'o:contents_list_WdMarkup', array
			(
				'constructor' => 'contents',
				'select' => array('expression' => true)
			)
		)
	)
);