<?php

/*
 * This file is part of the Icybee package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie\Modules\FirstPosition;

use ICanBoogie\ActiveRecord\Content;
use ICanBoogie\Event;
use ICanBoogie\Modules;

use BrickRouge\Element;
use BrickRouge\Form;
use BrickRouge\Text;

use WdPatron as Patron;

// http://www.google.com/webmasters/docs/search-engine-optimization-starter-guide.pdf

class Hooks
{
	public static function on_icybee_render(Event $event)
	{
		global $core, $page;

		if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $core->user_id == 1 || !$page->is_online || ($page->node && !$page->node->is_online))
		{
			return;
		}

		$ua = $core->site->metas['google_analytics_ua'];

		if (!$ua)
		{
			return;
		}

		// http://googlecode.blogspot.com/2009/12/google-analytics-launches-asynchronous.html
		// http://code.google.com/intl/fr/apis/analytics/docs/tracking/asyncUsageGuide.html
		// http://www.google.com/support/googleanalytics/bin/answer.py?answer=174090&cbid=-yb2wwt7lxo0o&src=cb&lev=%20index
		// http://developer.yahoo.com/blogs/ydn/posts/2007/07/high_performanc_5/

		$insert = <<<EOT


<script type="text/javascript">

	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '$ua']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();

</script>


EOT;

		$event->rc = str_replace('</body>', $insert . '</body>', $event->rc);
	}

	public static function on_document_render_title(Event $event)
	{
		global $page;

		$title = $page->document_title;
		$site_title = $page->site->title;

		$event->title = $title . $event->separator . $site_title;
	}

	public static function before_document_render_metas(Event $event)
	{
		global $page;

		$node = isset($page->node) ? $page->node : null;
		$description = $page->description;

		if ($node instanceof Content)
		{
			$description = $page->node->excerpt;
		}

		if ($description)
		{
			$description = html_entity_decode($description, ENT_QUOTES, ICanBoogie\CHARSET);
			$description = trim(strip_tags($description));

			$event->metas['Description'] = $description;
		}

		#
		#
		#

		if ($page->is_home)
		{
			$value = $page->site->metas['google_site_verification'];

			if ($value)
			{
				$event->metas['google-site-verification'] = $value;
			}
		}
	}

	public static function on_document_render_metas(Event $event)
	{
		global $page;

		$node = isset($page->node) ? $page->node : null;

		#
		# canonical
		#

//		http://yoast.com/articles/duplicate-content/

		if ($node && $node->has_property('absolute_url'))
		{
			$event->rc .= '<link rel="canonical" href="' . $node->absolute_url . '" />' . PHP_EOL;
		}
	}

	static public function event_alter_block_edit(Event $event, \ICanBoogie\Module $sender)
	{
		global $core;

		if ($sender instanceof Modules\Sites\Module)
		{
			$event->tags = wd_array_merge_recursive
			(
				$event->tags, array
				(
					Element::GROUPS => array
					(
						'firstposition' => array
						(
							'title' => '.seo',
							'class' => 'form-section flat',
							'weight' => 40
						)
					),

					Element::CHILDREN => array
					(
						'metas[google_analytics_ua]' => new Text
						(
							array
							(
								Form::LABEL => 'Google Analytics UA',
								Element::GROUP => 'firstposition'
							)
						),

						'metas[google_site_verification]' => new Text
						(
							array
							(
								Form::LABEL => 'Google Site Verification',
								Element::GROUP => 'firstposition'
							)
						)
					)
				)
			);

			return;
		}
		else if (!$sender instanceof Modules\Pages\Module)
		{
			return;
		}

		#
		# http://www.google.com/support/webmasters/bin/answer.py?answer=35264&hl=fr
		# http://googlewebmastercentral.blogspot.com/2009/09/google-does-not-use-keywords-meta-tag.html
		# http://www.google.com/support/webmasters/bin/answer.py?answer=79812
		#

		$event->tags = wd_array_merge_recursive
		(
			$event->tags, array
			(
				Element::GROUPS => array
				(
					'firstposition' => array
					(
						'title' => '.seo',
						'class' => 'form-section flat',
						'weight' => 40
					)
				),

				Element::CHILDREN => array
				(
					'metas[document_title]' => new Text
					(
						array
						(
							Form::LABEL => '.document_title',
							Element::GROUP => 'firstposition',
							Element::DESCRIPTION => '.document_title'
						)
					),

					'metas[description]' => new Element
					(
						'textarea', array
						(
							Form::LABEL => '.description',
							Element::GROUP => 'firstposition',
							Element::DESCRIPTION => '.description',

							'rows' => 3
						)
					)
				)
			)
		);
	}

	public function event_operation_export(Event $event)
	{
		global $core;

		$records = &$event->rc;
		$keys = array_keys($records);

		$metas = $core->models['system.registry/node']->where(array('targetid' => $keys, 'name' => array('document_title', 'description')))->all(PDO::FETCH_NUM);

		foreach ($metas as $meta)
		{
			list($pageid, $property, $value) = $meta;

			$records[$pageid]->site_firstposition[$property] = $value;
		}
	}
}