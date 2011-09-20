<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\ActiveRecord\Model;

use ICanBoogie\Exception;
use ICanBoogie\ActiveRecord\Comment;
use ICanBoogie\ActiveRecord\Model;

class Comments extends Model
{
	public function save(array $properties, $key=null, array $options=array())
	{
		$properties += array
		(
			Comment::STATUS => 'pending',
			Comment::NOTIFY => 'no'
		);

		if (!in_array($properties[Comment::NOTIFY], array('no', 'yes', 'author', 'done')))
		{
			throw new Exception
			(
				'Invalid value for %property property (%value)', array
				(
					'%property' => Comment::NOTIFY,
					'%value' => $properties[Comment::NOTIFY]
				)
			);
		}

		return parent::save($properties, $key, $options);
	}
}