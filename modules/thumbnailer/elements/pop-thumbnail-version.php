<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BrickRouge\Widget;

use BrickRouge\Element;

class PopThumbnailVersion extends \BrickRouge\Widget
{
	private $elements = array();

	public function __construct($tags=array())
	{
		parent::__construct
		(
			'div', wd_array_merge_recursive
			(
				array
				(
					Element::CHILDREN => array
					(
						$this->elements['value'] = new Element
						(
							'input', array
							(
								'type' => 'hidden'
							)
						)
					),

					'class' => 'like-input'
				),

				$tags
			)
		);
	}

	protected static function add_assets(\BrickRouge\Document $document)
	{
		parent::add_assets($document);

		$document->css->add('pop-thumbnail-version.css');
		$document->js->add('pop-thumbnail-version.js');
	}

	public function set($name, $value=null)
	{
		if (is_string($name))
		{
			switch ($name)
			{
				case self::DEFAULT_VALUE:
				case 'name':
				case 'value':
				{
					$options = $value;

					if (is_array($options))
					{
						$options = json_encode($options);
					}

					$this->elements['value']->set($name, $options);
				}
				break;
			}
		}

		parent::set($name, $value);
	}

	protected function render_inner_html()
	{
		$rc = parent::render_inner_html();

		$value = $this->get('value', $this->get(self::DEFAULT_VALUE));

		if (is_string($value))
		{
			$value = json_decode($value, true);
		}

		if ($value)
		{
			$value += array
			(
				'w' => null,
				'h' => null,
				'no-upscale' => false,
				'method' => 'fill',
				'format' => 'jpeg',
				'interlace' => false
			);

			$w = $value['w'] ?: '<em>auto</em>';
			$h = $value['h'] ?: '<em>auto</em>';
			$no_upscale = $value['no-upscale'] ? '(ne pas agrandir)' : '';
			$method = $value['method'];
			$format = strtoupper($value['format']);
			$interlace = $value['interlace'] ? '(entrelacé)' : '';

			$rc .= <<<EOT
<span class="value-w">$w</span> × <span class="value-h">$h</span> <span class="value-no-upscale">$no_upscale</span>, <span class="value-method">$method</span><br />
<span class="value-format">$format</span> <span class="value-interlace">$interlace</span><br />
EOT;
		}
		else
		{
			$rc .= '<em>Version non définie</em>';
		}

		return $rc;
	}
}