<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ICanBoogie\Event;
use ICanBoogie\Exception;
use ICanBoogie\Exception\HTTP as HTTPException;
use ICanBoogie\I18n;
use ICanBoogie\Module;
use BrickRouge\Element;

class view_WdEditorElement extends WdEditorElement
{
	static public function to_content(array $params, $content_id, $page_id)
	{
		global $core;

		$content = parent::to_content($params, $content_id, $page_id);

		if ($content && strpos($content, '/') !== false)
		{
			$view_target_key = 'views.targets.' . strtr($content, '.', '_');

			$core->site->metas[$view_target_key] = $page_id;
		}

		return $content;
	}

	static public function render($id)
	{
		global $core, $page;

		$patron = WdPatron::get_singleton();

		if (empty($page))
		{
			$page = $core->site->resolve_view_target($id);

			if (!$page)
			{
				$page = $core->site->home;
			}
		}

		$views = \Icybee\Views::get();

		if (empty($views[$id]))
		{
			throw new Exception('Unknown view: %id.', array('%id' => $id));
		}

		$definition = $views[$id];

		$class = $definition['class'] ?: 'Icybee\Views\View';

		$view = new $class($id, $definition, $patron, $core->document, $page);

		return $view();
	}

	public function __construct($tags, $dummy=null)
	{
		parent::__construct
		(
			'div', $tags + array
			(
				'class' => 'view-editor'
			)
		);
	}

	public function render_inner_html()
	{
		global $core;

		$document = $core->document;

		$document->css->add('../public/view.css');
		$document->js->add('../public/view.js');

		$rc = parent::render_inner_html();

		$value = $this->get('value');
		$name = $this->get('name');

		$selected_category = null;
		$selected_subcategory = null;

		$by_category = array();
		$descriptors = $core->modules->descriptors;

//		var_dump(self::$views);

		$views = \Icybee\Views::get();

		foreach ($views as $id => $view)
		{
			list($module_id, $type) = explode('/', $id) + array(1 => null);

			if (!isset($core->modules[$module_id]))
			{
				continue;
			}

			$category = 'Misc';
			$subcategory = 'Misc';

			if ($type !== null && isset($descriptors[$module_id]))
			{
				$descriptor = $descriptors[$module_id];

				if (isset($descriptor[Module::T_CATEGORY]))
				{
					$category = $descriptors[$module_id][Module::T_CATEGORY];
					$category = t($category, array(), array('scope' => array('module_category', 'title')));
				}

				$subcategory = $descriptor[Module::T_TITLE];
			}

			$by_category[$category][$subcategory][$id] = $view;

			if ($id == $value)
			{
				$selected_category = $category;
				$selected_subcategory = $subcategory;
			}
		}

		uksort($by_category, 'wd_unaccent_compare_ci');

		$rc = '<table>';
		$rc .= '<tr>';

		$rc .= '<td class="view-editor-categories"><ul>';

		foreach ($by_category as $category => $dummy)
		{
			$rc .= '<li' . ($category == $selected_category ? ' class="active selected"' : '') . '><a href="#select">' . wd_entities($category) . '</a></li>';
		}

		$rc .= '</ul></td>';

		#
		#
		#

		$rc .= '<td class="view-editor-subcategories">';

		foreach ($by_category as $category => $subcategories)
		{
			uksort($subcategories, 'wd_unaccent_compare_ci');

			$by_category[$category] = $subcategories;

			$rc .= '<ul' . ($category == $selected_category ? ' class="active selected"' : '') . '>';

			foreach ($subcategories as $subcategory => $views)
			{
				$rc .= '<li' . ($subcategory == $selected_subcategory ? ' class="active selected"' : '') . '><a href="#select">' . wd_entities($subcategory) . '</a></li>';
			}

			$rc .= '</ul>';
		}

		$rc .= '</ul></td>';

		#
		#
		#

		$context = $core->site->path;

		$rc .= '<td class="view-editor-views">';

		foreach ($by_category as $category => $subcategories)
		{
			foreach ($subcategories as $subcategory => $views)
			{
				$active = '';
				$items = array();

				foreach ($views as $id => $view)
				{
					if (empty($view['title']))
					{
						continue;
					}

					$title = t($view['title'], $view['title args']);

					$description = null;

					if (isset($view['description']))
					{
						$description = $view['description'];

						// FIXME-20101008: finish that ! it this usefull anyway ?

						$description = strtr
						(
							$description, array
							(
								'#{url}' => $context . '/admin/'
							)
						);
					}

					if ($id == $value)
					{
						$active = ' class="active"';
					}

					$items[$title] = new Element
					(
						Element::TYPE_RADIO, array
						(
							Element::LABEL => $title,
							Element::DESCRIPTION => $description,

							'name' => $name,
							'value' => $id,
							'checked' => ($id == $value)
						)
					);
				}

				uksort($items, 'wd_unaccent_compare_ci');

				$rc .= "<ul$active><li>" . implode('</li><li>', $items) . '</li></ul>';
			}


		}

		$rc .= '</td>';

		$rc .= '</tr>';
		$rc .= '</table>';

		return $rc;
	}
}