<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Icybee;

use ICanBoogie\Event;
use ICanBoogie\Exception;

/**
 * The views defined by the enabled modules.
 */
class Views implements \ArrayAccess, \IteratorAggregate
{
	private static $instance;

	/**
	 * Returns a unique instance.
	 *
	 * @return Views
	 */
	public static function get()
	{
		if (self::$instance)
		{
			return self::$instance;
		}

		$class = get_called_class();

		return self::$instance = new $class;
	}

	protected $views;

	protected function __construct()
	{
		global $core;

		if (CACHE_VIEWS)
		{
			$views = $core->vars['views'];

			if (!$views)
			{
				$views = $this->collect();

				$core->vars['views'] = $views;
			}
		}
		else
		{
			$views = $this->collect();
		}

		$this->views = $views;
	}

	protected function collect()
	{
		global $core;

		$modules = $core->modules;

		foreach ($modules->enabled_modules_descriptors as $id => $descriptor)
		{
			$module = $modules[$id];

			if (!$module->has_property('views'))
			{
				continue;
			}

			$module_views = $module->views;

			foreach ($module_views as $type => $definition)
			{
				$definition += array
				(
					'module' => $id,
					'type' => $type
				);

				if (empty($definition['renders']))
				{
					throw new \UnexpectedValueException(\ICanBoogie\format
					(
						'%property is empty for the view type %type defined by the module %module.', array
						(
							'property' => 'renders',
							'type' => $type,
							'module' => $id
						)
					));
				}

				$views[$id . '/' . $type] = $definition;
			}
		}

		Event::fire('alter', array('views' => &$views), $this);

		foreach ($views as &$view)
		{
			$view += array
			(
				'access_callback' => null,
				'class' => null,
				'title args' => array()
			);
		}

		return $views;
	}

	/**
	 * Checks if a view exists.
	 *
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset)
	{
		return isset($this->views[$offset]);
	}

	/**
	 * Returns the definition of a view.
	 *
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->views[$offset] : null;
	}

	public function offsetSet($offset, $value)
	{
		throw new Exception\OffsetNotWritable(array($offset, $this));
	}

	public function offsetUnset($offset)
	{
		throw new Exception\OffsetNotWritable(array($offset, $this));
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->views);
	}
}