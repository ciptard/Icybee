<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Modules\Images;

use BrickRouge\Element;

/**
 * Appends a preview to the response of the operation.
 *
 * @see ICanBoogie\Modules\Files\UploadOperation
 */
class UploadOperation extends \ICanBoogie\Modules\Files\UploadOperation
{
	protected $accept = array
	(
		'gif' => 'image/gif',
		'png' => 'image/png',
		'jpg' => 'image/jpeg'
	);

	protected function process()
	{
		$rc = parent::process();

		if ($this->response['infos'])
		{
			$path = $this->file->location;

			// TODO-20110106: compute surface w & h and use them for img in order to avoid poping

			$this->response['infos'] = '<div class="preview">'

			.

			new Element
			(
				'img', array
				(
					'src' => Operation::encode
					(
						'thumbnailer/get', array
						(
							'src' => $path,
							'w' => 64,
							'h' => 64,
							'format' => 'png',
							'background' => 'silver,white,medium',
							'm' => 'surface',
							'uniqid' => uniqid()
						)
					),

					'alt' => ''
				)
			)

			. '</div>' . $this->response['infos'];
		}

		return $rc;
	}
}