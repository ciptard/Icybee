<?php

use ICanBoogie\Module;

return array
(
	Module::T_TITLE => 'First Position',
	Module::T_CATEGORY => 'features',
	Module::T_PERMISSION => false,
	Module::T_DESCRIPTION => "Provides SEO features.",
	Module::T_REQUIRES => array
	(
		'pages' => 'x.x'
	)
);