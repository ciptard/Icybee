<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Modules\Thumbnailer;

use ICanBoogie\Exception\HTTP as HTTPException;
use ICanBoogie\Operation;

/**
 * Creates a thumbnail of an image managed by the "resource.images" module.
 */
class ThumbnailOperation extends GetOperation
{
	protected function process()
	{
		global $core;

		$params = &$this->request->params;
		$params['src'] = null;

		// TODO-20101031: support for the 's' shorthand.

		$nid = (int) $params['nid'];

		if (function_exists('glob'))
		{
			$root = \ICanBoogie\DOCUMENT_ROOT;
			$files = glob($root . $core->config['repository.files'] . '/*/' . $nid . '-*');

			if ($files)
			{
				$params['src'] = substr(array_shift($files), strlen($root));
			}
		}
		else
		{
			$path = $core->config['repository.files'] . '/image';
			$root = \ICanBoogie\DOCUMENT_ROOT . $path;

			$nid .= '-';
			$nid_length = strlen($nid);

			$previous = getcwd();
			chdir($root);

			$dh = opendir($root);

			while (($file = readdir($dh)) !== false)
			{
				if ($file[0] == '.' || substr($file, 0, $nid_length) != $nid)
				{
					continue;
				}

				$params['src'] = $path . '/' . $file;

				break;
			}

			closedir($dh);

			chdir($previous);
		}

		if (empty($params['src']))
		{
			throw new HTTPException('Unable to locate image resource for the given identifier: %nid.', array('%nid' => $nid), 404);
		}

		return parent::process();
	}
}