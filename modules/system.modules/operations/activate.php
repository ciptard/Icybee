<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Modules\System\Modules;

use ICanBoogie\Operation;
use ICanBoogie\Route;

class ActivateOperation extends Operation
{
	protected function __get_controls()
	{
		return array
		(
			self::CONTROL_PERMISSION => Module::PERMISSION_ADMINISTER
		)

		+ parent::__get_controls();
	}

	protected function validate(\ICanboogie\Errors $errors)
	{
		return true;
	}

	protected function process()
	{
		global $core;

		$errors = $this->response->errors;

		$enabled = array_keys($core->modules->enabled_modules_descriptors);
		$enabled = array_flip($enabled);

		foreach ((array) $this->key as $key => $dummy)
		{
			try
			{
				$core->modules[$key] = true;
				$module = $core->modules[$key];

				$rc = $module->is_installed($errors);

				if (!$rc || count($errors))
				{
					$module->install($errors);
				}

				$enabled[$key] = true;
			}
			catch (\Exception $e)
			{
				$errors[$e->getMessage()];
			}
		}

		$core->vars['enabled_modules'] = array_keys($enabled);

		unset($core->vars['views']);

		$this->response->location = Route::contextualize('/admin/' . (string) $this->module);

		return true;
	}
}