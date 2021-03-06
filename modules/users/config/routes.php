<?php

return array
(
	':admin/manage' => array
	(

	),

	':admin/new' => array
	(

	),

	':admin/edit' => array
	(

	),

	/*
	':admin/config' => array
	(

	),
	*/

	'users:admin/profile' => array
	(
		'pattern' => '/admin/profile',
		'title' => 'Profil',
		'block' => 'profile',
		'visibility' => 'auto',
		'workspace' => ''
	),

	'users:admin/authenticate' => array
	(
		'pattern' => '/admin/authenticate',
		'title' => 'Connection',
		'block' => 'connect',
		'workspace' => '',
		'visibility' => 'auto'
	),

	'users:nonce-login-request' => array
	(
		'pattern' => '/api/nonce-login-request',
		'class' => 'ICanBoogie\Modules\Users\NonceLoginRequestOperation'
	),

	'users:inline-nonce-login-request' => array
	(
		'pattern' => '/api/nonce-login-request/:email',
		'class' => 'ICanBoogie\Modules\Users\NonceLoginRequestOperation'
	),

	'users:nonce-login' => array
	(
		'pattern' => '/api/nonce-login/:email/:token',
		'class' => 'ICanBoogie\Modules\Users\NonceLoginOperation'
	)
);