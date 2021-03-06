<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Modules\Comments;

use ICanBoogie\ActiveRecord\Comment;
use ICanBoogie\Exception;
use ICanBoogie\Mailer;
use ICanBoogie\Operation;

class SaveOperation extends \Icybee\Operation\ActiveRecord\Save
{
	protected function __get_properties()
	{
		global $core;

		$properties = parent::__get_properties();
		$user = $core->user;

		if ($this->key)
		{
			unset($properties[Comment::NID]);

			if (!$user->has_permission(Module::PERMISSION_ADMINISTER))
			{
				unset($properties[Comment::AUTHOR_IP]);
			}
		}
		else
		{
			if (empty($properties[Comment::NID]))
			{
				throw new Exception('Missing target node id');
			}

			$properties[Comment::AUTHOR_IP] = $_SERVER['REMOTE_ADDR'];

			if (!$user->is_guest)
			{
				$properties[Comment::UID] = $user->uid;
			}
		}

		if (!$user->has_permission(Module::PERMISSION_MANAGE, $this->module))
		{
			$properties['status'] = null;
		}

		if (!$this->key && empty($properties['status']))
		{
			$node = $core->models['nodes'][$properties[Comment::NID]];
			$properties['status'] = $node->site->metas->get($this->module->flat_id . '.default_status', 'pending');
		}

		return $properties;
	}

	protected function validate(\ICanboogie\Errors $errors)
	{
		global $core;

		$request = $this->request;

		#
		# the article id is required when creating a message
		#

		if (!$this->key && !$request[Comment::NID])
		{
			$errors[Comment::NID] = t('The node id is required while creating a new comment');

			return false;
		}

		#
		# validate IP
		#

		$ip = $request->ip;

		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE))
		{
			$errors[] = t('Invalid IP address: %ip', array('%ip' => $ip));

			return false;
		}

		if (!$core->user_id)
		{
			$score = Module\Comments::score_spam($request[Comment::CONTENTS], $request[Comment::AUTHOR_URL], $request[Comment::AUTHOR]);

			if ($score < 1)
			{
				$errors[Comment::CONTENTS] = t('@form.log.spam', array('%score' => $score));

				return false;
			}

			#
			# delay between last post
			#

			$interval = $core->site->metas->get($this->module->flat_id . '.delay', 5);

			$last = $this->module->model
			->select('created')
			->where
			(
				'(author = ? OR author_email = ? OR author_ip = ?) AND created + INTERVAL ? MINUTE > NOW()',
				$request['author'], $request['author_email'], $ip, $interval
			)
			->order('created DESC')
			->rc;

			if ($last)
			{
				$errors[] = t("Les commentaires ne peuvent être fait à moins de $interval minutes d'intervale.");

				return false;
			}
		}

		return true;
	}

	protected function process()
	{
		$rc = parent::process();

		if (!$this->key)
		{
			$this->notify($rc['key']);

			if ($this->properties['status'] == 'approved')
			{
				$comment = $this->module->model[$rc['key']];

				$this->response->location = $comment->url;
			}
		}

		return $rc;
	}

	/**
	 * Notify users that a reply to their comment has been posted.
	 *
	 * @param int $commentid
	 */
	protected function notify($commentid)
	{
		global $core;

		$form_id = $core->site->metas['comments.form_id'];

		if (!$form_id)
		{
			return;
		}

		try
		{
			$form = $core->models['forms'][$form_id];
		}
		catch (\Exception $e) { return; }

		$options = unserialize($form->metas['comments/reply']);

		if (!$options)
		{
			return;
		}

		$model = $this->module->model;
		$comment = $model[$commentid];

		#
		# search previous message for notify
		#

		$records = $model->where
		(
			'nid = ? AND `{primary}` < ? AND (`notify` = "yes" OR `notify` = "author") AND author_email != ?',

			$comment->nid, $commentid, $comment->author_email
		)
		->all;

		if (!$records)
		{
			return;
		}

		#
		# prepare subject and message
		#

		$patron = new \WdPatron();
		$subject = $patron($options['subject'], $comment);
		$message = $patron($options['template'], $comment);

		wd_log('Notify message: <pre>!message</pre>', array('!message' => $message));

		$from = $options['from'];
		$bcc = $options['bcc'];

		foreach ($records as $entry)
		{
			#
			# notify only if the author of the node post a comment
			#

			if ($entry->notify == 'author' && $comment->uid != $comment->node->uid)
			{
				continue;
			}

			wd_log
			(
				'Send notify to %author (email: %email, message n°%commentid, mode: %notify)', array
				(
					'%author' => $entry->author,
					'%email' => $entry->email,
					'%commentid' => $entry->commentid,
					'%notify' => $entry->notify
				)
			);

			$mailer = new Mailer
			(
				array
				(
					Mailer::T_DESTINATION => $entry->author_email,
					Mailer::T_FROM => $from,
					Mailer::T_BCC => $bcc,
					Mailer::T_MESSAGE => $message,
					Mailer::T_SUBJECT => $subject,
					Mailer::T_TYPE => 'plain'
				)
			);

			if (!$mailer())
			{
				wd_log_error('Unable to send notify to %author', array('%author' => $entry->author));

				continue;
			}

			$entry->notify = 'done';
			$entry->save();
		}
	}
}