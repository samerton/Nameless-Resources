<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  View resource page
 */

// Always define page name
define('PAGE', 'resources');
define('RESOURCE_PAGE', 'view_resource');

// Initialise
$timeago = new Timeago();

require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
require(ROOT_PATH . '/core/includes/markdown/tohtml/Markdown.inc.php'); // Markdown to HTML
$emojione = new Emojione\Client(new Emojione\Ruleset());

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Get user group ID
if($user->isLoggedIn()) $user_group = $user->data()->group_id; else $user_group = null;

// Get resource
$rid = explode('/', $route);
$rid = $rid[count($rid) - 1];

if(!isset($rid[count($rid) - 1])){
    Redirect::to(URL::build('/resources'));
    die();
}

$rid = explode('-', $rid);
if(!is_numeric($rid[0])){
    Redirect::to(URL::build('/resources'));
    die();
}
$rid = $rid[0];

// Get page
if(isset($_GET['p'])){
    if(!is_numeric($_GET['p'])){
        Redirect::to(URL::build('/resources/resource/' . $rid));
        die();
    } else {
        $p = $_GET['p'];
    }
} else {
    $p = 1;
}

$resource = $queries->getWhere('resources', array('id', '=', $rid));

if(!count($resource)){
	// Doesn't exist
	Redirect::to(URL::build('/resources'));
	die();
} else $resource = $resource[0];

if(!$resources->canViewCategory($resource->category_id, $user_group, ($user->isLoggedIn() ? $user->data()->secondary_groups : null))){
	Redirect::to(URL::build('/resources'));
	die();
}

// Get latest release
$latest_release = $queries->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC');
if(!count($latest_release)) die('Unable to get latest release');
else $latest_release = $latest_release[0];

// View count
if($user->isLoggedIn() || Cookie::exists('alert-box')){
	if(!Cookie::exists('nl-resource-' . $resource->id)) {
		$queries->increment('resources', $resource->id, 'views');
		Cookie::put('nl-resource-' . $resource->id, "true", 3600);
	}
} else {
	if(!Session::exists('nl-resource-' . $resource->id)){
		$queries->increment('resources', $resource->id, 'views');
		Session::put("nl-resource-" . $resource->id, "true");
	}
}

// Get metadata
$page_metadata = $queries->getWhere('page_descriptions', array('page', '=', '/resources/resource'));
if(count($page_metadata)){
	$category = $queries->getWhere('resources_categories', array('id', '=', $resource->category_id));
	if(count($category)){
		$category = Output::getClean($category[0]->name);
	} else {
		$category = 'Unknown';
	}

	$description = strip_tags(str_ireplace(array('<br />', '<br>', '<br/>', '&nbsp;'), array("\n", "\n", "\n", ' '), Output::getDecoded($resource->description)));

	define('PAGE_DESCRIPTION', str_replace(array('{site}', '{title}', '{author}', '{category_title}', '{page}', '{description}'), array(SITE_NAME, Output::getClean($resource->name), Output::getClean($user->idToName($resource->creator_id)), $category, Output::getClean($p), substr($description, 0, 160) . '...'), $page_metadata[0]->description));
	define('PAGE_KEYWORDS', $page_metadata[0]->tags);
}

$page_title = Output::getClean($resource->name);
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$template->addCSSFiles(array(
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/css/spoiler.css' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/prism/prism.css' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/plugins/spoiler/css/spoiler.css' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/css/emojione.min.css' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/css/emojione.sprites.css' => array(),
	(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emojionearea/css/emojionearea.min.css' => array(),
));

$template->addCSSStyle('
	.star-rating.set {
		line-height:32px;
		font-size:1.25em;
		cursor: pointer;
	}
');

// Get post formatting type (HTML or Markdown)
$cache->setCache('post_formatting');
$formatting = $cache->retrieve('formatting');

if(!isset($_GET['releases']) && !isset($_GET['do'])){
	// Handle input
	if(Input::exists()){
		if($user->isLoggedIn()){
			if(Token::check(Input::get('token'))){
				$validate = new Validate();

				$validation = $validate->check($_POST, array(
					'rating' => array(
						'required' => true,
						'min' => 1,
						'max' => 5
					),
					'content' => array(
						'required' => true,
						'min' => 1,
						'max' => 20000
					)
				));

				if($validation->passed()){
					// Create review
					// Validate rating
					$rating = round($_POST['rating']);

					// Get latest release tag
					$release_tag = $latest_release->release_tag;

					// Create comment
					$queries->create('resources_comments', array(
						'resource_id' => $resource->id,
						'author_id' => $user->data()->id,
						'content' => Output::getClean(Input::get('content')),
						'release_tag' => $release_tag,
						'created' => date('U'),
						'rating' => $rating
					));
					$rating_id = $queries->getLastId();

					// Calculate overall rating
					// Ensure user hasn't already rated, and if so, hide their rating
					$ratings = $queries->getWhere('resources_comments', array('resource_id', '=', $resource->id));
					if(count($ratings)){
						$overall_rating = 0;
						$overall_rating_count = 0;
						$release_rating = 0;
						$release_rating_count = 0;

						foreach($ratings as $rating){
							if($rating_id != $rating->id && $rating->author_id == $user->data()->id && $rating->hidden == 0){
								// Hide rating
								$queries->update('resources_comments', $rating->id, array(
									'hidden' => 1
								));
							} else if($rating->hidden == 0){
								// Update rating
								// Overall
								$overall_rating = $overall_rating + $rating->rating;
								$overall_rating_count++;

								if($rating->release_tag == $release_tag){
									// Release
									$release_rating = $release_rating + $rating->rating;
									$release_rating_count++;
								}
							}
						}

						$overall_rating = $overall_rating / $overall_rating_count;
						$overall_rating = round($overall_rating * 10);

						$release_rating = $release_rating / $release_rating_count;
						$release_rating = round($release_rating * 10);

						$queries->update('resources', $resource->id, array(
							'rating' => $overall_rating
						));
						$queries->update('resources_releases', $latest_release->id, array(
							'rating' => $release_rating
						));
					}

					$cache->setCache('resource-comments-' . $resource->id);
					$cache->erase('comments');

					Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
					die();

				} else {
					// Errors
					$error = $resource_language->get('resources', 'invalid_review');
				}
			}
		}
	}

	// Check comment cache
	$cache->setCache('resource-comments-' . $resource->id);

	if(!$cache->isCached('comments')){
		// Get comments
		$comments = $queries->orderWhere('resources_comments', 'resource_id = ' . $resource->id . ' AND hidden = 0', 'created', 'DESC');

		// Remove replies
		$replies_array = array();
		foreach($comments as $key => $comment){
			if(!is_null($comment->reply_id)){
				$replies_array[$comment->reply_id][] = $comment;
				unset($comments[$key]);
			}
		}

		// Cache
		$cache->store('comments', $comments, 120);

	} else $comments = $cache->retrieve('comments');

	// Pagination
	$paginator = new Paginator((isset($template_pagination) ? $template_pagination : array()));
	$results = $paginator->getLimited($comments, 10, $p, count($comments));
	$pagination = $paginator->generate(7, URL::build('/resources/resource/' . $resource->id . '-' . Util::stringtoURL($resource->name) . '/', true));

	if(count($comments))
		$smarty->assign('PAGINATION', $pagination);
	else
		$smarty->assign('PAGINATION', '');

	// Array to pass to template
	$comments_array = array();

	// Can the user delete reviews?
	if($user->isLoggedIn() && $resources->canDeleteReviews($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
		$can_delete_reviews = true;
		$smarty->assign(array(
			'DELETE_REVIEW' => $resource_language->get('resources', 'delete_review'),
			'CONFIRM_DELETE_REVIEW' => $resource_language->get('resources', 'confirm_delete_review')
		));
	}

	if(count($comments)){
		// Display the correct number of comments
		$n = 0;

		// Get post formatting type (HTML or Markdown)
		$cache->setCache('post_formatting');
		$formatting = $cache->retrieve('formatting');

		while($n < count($results->data)){
			$comments_array[] = array(
				'username' => Output::getClean($user->idToNickname($results->data[$n]->author_id)),
				'user_avatar' => $user->getAvatar($results->data[$n]->author_id, '../', 30),
				'user_style' => $user->getGroupClass($results->data[$n]->author_id),
				'user_profile' => URL::build('/profile/' . Output::getClean($user->idToName($results->data[$n]->author_id))),
				'content' => Output::getPurified($emojione->unicodeToImage(htmlspecialchars_decode($results->data[$n]->content))),
				'date' => $timeago->inWords(date('d M Y, H:i', $results->data[$n]->created), $language->getTimeLanguage()),
				'date_full' => date('d M Y, H:i', $results->data[$n]->created),
				'replies' => (isset($replies_array[$results->data[$n]->id]) ? $replies_array[$results->data[$n]->id] : array()),
				'rating' => $results->data[$n]->rating,
				'release_tag' => Output::getClean($results->data[$n]->release_tag),
				'delete_link' => (isset($can_delete_reviews) ? URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=delete_review&amp;review=' . $results->data[$n]->id) : '')
			);
			$n++;
		}
	} else $comments_array = null;

	// Get latest update
	$latest_update = $queries->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 1');

	if(!count($latest_update)){
		Redirect::to(URL::build('/resources'));
		die();
	} else $latest_update = $latest_update[0];

	// Assign Smarty variables
	$smarty->assign(array(
		'VIEWING_RESOURCE' => str_replace('{x}', Output::getClean($resource->name), $resource_language->get('resources', 'viewing_resource_x')),
		'BACK_LINK' => URL::build('/resources'),
		'RESOURCE_INDEX' => $resource_language->get('resources', 'resource_index'),
		'AUTHOR' => $resource_language->get('resources', 'author'),
		'AUTHOR_RESOURCES' => URL::build('/resources/author/' . $resource->creator_id . '-' . Util::stringToURL($user->idToName($resource->creator_id))),
		'VIEW_OTHER_RESOURCES' => str_replace('{x}', Output::getClean($user->idToNickname($resource->creator_id)), $resource_language->get('resources', 'view_other_resources')),
		'DESCRIPTION' => Output::getPurified(htmlspecialchars_decode($resource->description)),
		'CREATED' => $timeago->inWords(date('d M Y, H:i', $resource->created), $language->getTimeLanguage()),
		'CREATED_FULL' => date('d M Y, H:i', $resource->created),
		'REVIEWS' => $resource_language->get('resources', 'reviews'),
		'COMMENT_ARRAY' => $comments_array,
		'NO_REVIEWS' => $resource_language->get('resources', 'no_reviews'),
		'NEW_REVIEW' => $resource_language->get('resources', 'new_review'),
		'AUTHOR_NICKNAME' => Output::getClean($user->idToNickname($resource->creator_id)),
		'AUTHOR_NAME' => Output::getClean($user->idToName($resource->creator_id)),
		'AUTHOR_STYLE' => $user->getGroupClass($resource->creator_id),
		'AUTHOR_AVATAR' => $user->getAvatar($resource->creator_id, '../', 75),
		'AUTHOR_PROFILE' => URL::build('/profile/' . Output::getClean($user->idToName($resource->creator_id))),
		'RESOURCE' => $resource_language->get('resources', 'resource'),
		'VIEWS' => str_replace('{x}', $resource->views, $resource_language->get('resources', 'x_views')),
		'DOWNLOADS' => str_replace('{x}', $resource->downloads, $resource_language->get('resources', 'x_downloads')),
		'RATING' => round($resource->rating / 10),
		'OTHER_RELEASES' => $resource_language->get('resources', 'other_releases'),
		'OTHER_RELEASES_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'releases=all'),
		'RELEASE_TITLE' => Output::getClean($latest_update->release_title),
		'RELEASE_DESCRIPTION' => Output::getPurified(htmlspecialchars_decode($latest_update->release_description)),
		'RELEASE_TAG' => Output::getClean($latest_update->release_tag),
		'RELEASE_RATING' => round($latest_update->rating / 10),
		'RELEASE_DOWNLOADS' => $latest_update->downloads,
		'RELEASE_DATE' => $timeago->inWords(date('d M Y, H:i', $latest_update->created), $language->getTimeLanguage()),
		'RELEASE_DATE_FULL' => date('d M Y, H:i', $latest_update->created),
		'LOGGED_IN' => ($user->isLoggedIn() ? true : false),
		'CAN_REVIEW' => (($user->isLoggedIn() && $user->data()->id != $resource->creator_id) ? true : false),
		'TOKEN' => Token::get(),
		'SUBMIT' => $language->get('general', 'submit'),
		'CONTRIBUTORS' => str_replace('{x}', Output::getClean($resource->contributors), $resource_language->get('resources', 'contributors_x')),
		'HAS_CONTRIBUTORS' => (strlen(trim($resource->contributors)) > 0) ? 1 : 0
	));

	if(isset($error))
		$smarty->assign('ERROR', $error);

	// Get currency
	$currency = $queries->getWhere('settings', array('name', '=', 'resources_currency'));
	if(!count($currency)){
		$queries->create('settings', array(
			'name' => 'resources_currency',
			'value' => 'GBP'
		));
		$currency = 'GBP';

	} else
		$currency = $currency[0]->value;

	if($resource->type == 0){
		// Can the user download?
		if($user->isLoggedIn())
			$group_id = $user->data()->group_id;
		else
			$group_id = 0;

		$download = DB::getInstance()->query('SELECT download FROM nl2_resources_categories_permissions WHERE category_id = ? AND group_id = ?', array($resource->category_id, $group_id))->results();

		if(count($download)){
			if($download[0]->download == 1){
				$smarty->assign(array(
					'DOWNLOAD' => $resource_language->get('resources', 'download'),
					'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download')
				));
			}
		}
	} else {
		// Can the user download?
		if($user->isLoggedIn()){
			$group_id = $user->data()->group_id;

			$download = DB::getInstance()->query('SELECT download FROM nl2_resources_categories_permissions WHERE category_id = ? AND group_id = ?', array($resource->category_id, $group_id))->results();

			if(count($download)){
				$download = $download[0];

				if($download->download == 1){
					if($user->data()->id == $resource->creator_id){
						// Author can download their own resources
						$smarty->assign(array(
							'DOWNLOAD' => $resource_language->get('resources', 'download'),
							'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download')
						));

					} else {
						// Check purchases
						$paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', array($resource->id, $user->data()->id))->results();

						if(count($paid)){
							$paid = $paid[0];

							if($paid->status == 1){
								// Purchased
								$smarty->assign(array(
									'DOWNLOAD' => $resource_language->get('resources', 'download'),
									'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download')
								));

							} else if($paid->status == 0){
								// Pending
								$smarty->assign(array(
									'PAYMENT_PENDING' => $resource_language->get('resources', 'payment_pending')
								));

							} else if($paid->status == 2){
								// Cancelled
								$smarty->assign(array(
									'PURCHASE_FOR_PRICE' => str_replace('{x}', Output::getClean($resource->price) . ' ' . Output::getClean($currency), $resource_language->get('resources', 'purchase_for_x')),
									'PURCHASE_LINK' => URL::build('/resources/purchase/' . Output::getClean($resource->id) . '-' . Output::getClean(Util::stringToURL($resource->name)))
								));

							}
						} else {
							// Needs to purchase
							$smarty->assign(array(
								'PURCHASE_FOR_PRICE' => str_replace('{x}', Output::getClean($resource->price) . ' ' . Output::getClean($currency), $resource_language->get('resources', 'purchase_for_x')),
								'PURCHASE_LINK' => URL::build('/resources/purchase/' . Output::getClean($resource->id) . '-' . Output::getClean(Util::stringToURL($resource->name)))
							));
						}
					}
				}
			}

		} else {
			$smarty->assign(array(
				'PURCHASE_FOR_PRICE' => str_replace('{x}', Output::getClean($resource->price) . ' ' . $currency, $resource_language->get('resources', 'purchase_for_x'))
			));
		}
	}

	if($user->isLoggedIn() && $resource->creator_id == $user->data()->id){
		// Allow updating
		$smarty->assign(array(
			'CAN_UPDATE' => true,
			'UPDATE' => $resource_language->get('resources', 'update'),
			'UPDATE_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=update')
		));
	}

	if($user->isLoggedIn()){
		if($resource->creator_id == $user->data()->id || $resources->canEditResources($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
			$smarty->assign(array(
				'CAN_EDIT' => true,
				'EDIT' => $language->get('general', 'edit'),
				'EDIT_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=edit')
			));
		}

		// Moderation
		$moderation = array();
		if($resources->canMoveResources($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
			$moderation[] = array(
				'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=move'),
				'title' => $resource_language->get('resources', 'move_resource')
			);
		}
		if($resources->canDeleteResources($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
			$moderation[] = array(
				'link' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=delete'),
				'title' => $resource_language->get('resources', 'delete_resource')
			);
		}

		$smarty->assign('MODERATION', $moderation);
		$smarty->assign('MODERATION_TEXT', $resource_language->get('resources', 'moderation'));
	} else {
		$smarty->assign('LOG_IN_TO_DOWNLOAD', $resource_language->get('resources', 'log_in_to_download'));
	}

	// Markdown?
	if($formatting == 'markdown'){
		// Markdown
		$smarty->assign('MARKDOWN', true);
		$smarty->assign('MARKDOWN_HELP', $language->get('general', 'markdown_help'));
	}

	$template_file = 'resources/resource.tpl';

} else {
	if(isset($_GET['releases'])){
		if($_GET['releases'] == 'all'){
			// Display list of all releases
			$releases = $queries->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC');

			if(!count($releases)){
				Redirect::to('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name));
				die();
			}

			// Pagination
			$paginator = new Paginator((isset($template_pagination) ? $template_pagination : array()));
			$results = $paginator->getLimited($releases, 10, $p, count($releases));
			$pagination = $paginator->generate(7, URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'releases=all&amp;'));

			$smarty->assign('PAGINATION', $pagination);

			// Assign releases to new array for Smarty
			$releases_array = array();
			foreach($releases as $release){
				$releases_array[] = array(
					'id' => $release->id,
					'url' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'releases=' . $release->id),
					'tag' => Output::getClean($release->release_tag),
					'name' => Output::getClean($release->release_title),
					'description' => Output::getPurified(nl2br(Output::getDecoded($release->release_description))),
					'date' => $timeago->inWords(date('d M Y, H:i', $release->created), $language->getTimeLanguage()),
					'date_full' => date('d M Y, H:i', $release->created),
					'rating' => round($release->rating / 10),
					'downloads' => str_replace('{x}', $release->downloads, $resource_language->get('resources', 'x_downloads'))
				);
			}

			// Assign Smarty variables
			$smarty->assign(array(
				'VIEWING_ALL_RELEASES' => str_replace('{x}', Output::getClean($resource->name), $resource_language->get('resources', 'viewing_all_releases')),
				'RELEASES' => $releases_array,
				'BACK' => $language->get('general', 'back'),
				'BACK_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name))
			));

			$template_file = 'resources/resource_all_releases.tpl';

		} else {
			if(!is_numeric($_GET['releases'])){
				Redirect::to(URL::build('/resources'));
				die();
			}

			// Get info about a specific release
			$release = $queries->getWhere('resources_releases', array('id', '=', $_GET['releases']));

			if(!count($release)){
				Redirect::to(URL::build('/resources'));
				die();
			} else $release = $release[0];

			// Assign Smarty variables
			$smarty->assign(array(
				'VIEWING_RELEASE' => str_replace(array('{x}', '{y}'), array(Output::getClean($release->release_title), Output::getClean($resource->name)), $resource_language->get('resources', 'viewing_release')),
				'BACK' => $language->get('general', 'back'),
				'BACK_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
				'DOWNLOADS' => str_replace('{x}', $release->downloads, $resource_language->get('resources', 'x_downloads')),
				'RATING' => round($release->rating / 10),
				'DESCRIPTION' => Output::getPurified(nl2br(Output::getDecoded($release->release_description))),
				'DATE' => $timeago->inWords(date('d M Y, H:i', $release->created), $language->getTimeLanguage()),
				'DATE_FULL' => date('d M Y, H:i', $release->created)
			));

			$currency = $queries->getWhere('settings', array('name', '=', 'resources_currency'));
			if(!count($currency)){
				$queries->create('settings', array(
					'name' => 'resources_currency',
					'value' => 'GBP'
				));
				$currency = 'GBP';

			} else
				$currency = $currency[0]->value;

			// Ensure user has download permission
			if($resource->type == 0){
				// Can the user download?
				if($user->isLoggedIn())
					$group_id = $user->data()->group_id;
				else
					$group_id = 0;

				$download = DB::getInstance()->query('SELECT download FROM nl2_resources_categories_permissions WHERE category_id = ? AND group_id = ?', array($resource->category_id, $group_id))->results();

				if(count($download)){
					if($download[0]->download == 1){
						$smarty->assign(array(
							'DOWNLOAD' => $resource_language->get('resources', 'download'),
							'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download')
						));
					}
				}
			} else {
				// Can the user download?
				if($user->isLoggedIn()){
					$group_id = $user->data()->group_id;

					$download = DB::getInstance()->query('SELECT download FROM nl2_resources_categories_permissions WHERE category_id = ? AND group_id = ?', array($resource->category_id, $group_id))->results();

					if(count($download)){
						$download = $download[0];

						if($download->download == 1){
							if($user->data()->id == $resource->creator_id){
								// Author can download their own resources
								$smarty->assign(array(
									'DOWNLOAD' => $resource_language->get('resources', 'download'),
									'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download')
								));

							} else {
								// Check purchases
								$paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', array($resource->id, $user->data()->id))->results();

								if(count($paid)){
									$paid = $paid[0];

									if($paid->status == 1){
										// Purchased
										$smarty->assign(array(
											'DOWNLOAD' => $resource_language->get('resources', 'download'),
											'DOWNLOAD_URL' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name) . '/', 'do=download')
										));

									} else if($paid->status == 0){
										// Pending
										$smarty->assign(array(
											'PAYMENT_PENDING' => $resource_language->get('resources', 'payment_pending')
										));

									} else if($paid->status == 2){
										// Cancelled
										$smarty->assign(array(
											'PURCHASE_FOR_PRICE' => str_replace('{x}', Output::getClean($resource->price) . ' ' . Output::getClean($currency), $resource_language->get('resources', 'purchase_for_x')),
											'PURCHASE_LINK' => URL::build('/resources/purchase/' . Output::getClean($resource->id) . '-' . Output::getClean(Util::stringToURL($resource->name)))
										));

									}
								} else {
									// Needs to purchase
									$smarty->assign(array(
										'PURCHASE_FOR_PRICE' => str_replace('{x}', Output::getClean($resource->price) . ' ' . Output::getClean($currency), $resource_language->get('resources', 'purchase_for_x')),
										'PURCHASE_LINK' => URL::build('/resources/purchase/' . Output::getClean($resource->id) . '-' . Output::getClean(Util::stringToURL($resource->name)))
									));
								}
							}
						}
					}

				} else {
					$smarty->assign(array(
						'PURCHASE_FOR_PRICE' => str_replace('{x}', Output::getClean($resource->price) . ' ' . $currency, $resource_language->get('resources', 'purchase_for_x'))
					));
				}
			}

			$template_file = 'resources/resource_view_release.tpl';;

		}
	} else if(isset($_GET['do'])){
		if($_GET['do'] == 'download'){
			if(!isset($_GET['release'])){
				// Get latest release
				$release = $queries->orderWhere('resources_releases', 'resource_id = ' . $resource->id, 'created', 'DESC LIMIT 3');
				if(!count($release)){
					Redirect::to(URL::build('/resources'));
					die();
				} else $release = $release[0];

			} else {
				// Get specific release
				if(!is_numeric($_GET['release'])){
					Redirect::to(URL::build('/resources'));
					die();
				}

				$release = $queries->getWhere('resources_releases', array('id', '=', $_GET['release']));
				if(!count($release) || $release[0]->resource_id != $resource->id){
					Redirect::to(URL::build('/resources'));
					die();
				} else $release = $release[0];
			}

			// Download permission?
			if($user->isLoggedIn()){
				$group_id = $user->data()->group_id;

				if($user->data()->id == $resource->creator_id)
					$can_download = true;
			} else
				$group_id = 0;

			$permissions = DB::getInstance()->query('SELECT `view`, download, premium FROM nl2_resources_categories_permissions WHERE category_id = ? AND group_id = ?', array($resource->category_id, $group_id))->results();
			if(!isset($can_download) && count($permissions)){
				$permissions = $permissions[0];
				if($permissions->view == 0 || $permissions->download == 0){
					Redirect::to(URL::build('/resources'));
					die();
				}

			} else if(!count($permissions)){
				// No permission
				Redirect::to(URL::build('/resources'));
				die();
			}

			if($release->download_link != 'local'){
				// Increment download counter
				if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
					if($user->isLoggedIn() || Cookie::exists('accept')){
						if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
							$queries->increment('resources', $resource->id, 'downloads');
							$queries->increment('resources_releases', $release->id, 'downloads');
							Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
						}
					} else {
						if(!Session::exists('nl-resource-download-' . $resource->id)) {
							$queries->increment('resources', $resource->id, 'downloads');
							$queries->increment('resources_releases', $release->id, 'downloads');
							Session::put('nl-resource-download-' . $resource->id, "true", 3600);
						}
					}
				}

				// Redirect to download
				Redirect::to(Output::getClean($release->download_link));
				die();

			} else {
				// Local zip
				$dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $resource->creator_id . DIRECTORY_SEPARATOR . $resource->id . DIRECTORY_SEPARATOR . $release->id;
				$files = scandir($dir);

				if(!count($files)){
					// Unable to find files
					Redirect::to(URL::build('/resources/resource/' . $resource->id));
					die();
				}

				$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type
				foreach($files as $file){
					// Ensure file is zip
					if($file == '.' || $file == '..')
						continue;

					if(finfo_file($finfo, $dir . DIRECTORY_SEPARATOR . $file) == 'application/zip')
						$zip = $dir . DIRECTORY_SEPARATOR . $file;
				}
				finfo_close($finfo);

				if(!isset($zip)){
					// No valid .zip
					Redirect::to(URL::build('/resources/resource/' . $resource->id));
					die();
				}

				// Get resource type
				if($resource->type == 0){
					if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
						if($user->isLoggedIn() || Cookie::exists('accept')){
							if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
								$queries->increment('resources', $resource->id, 'downloads');
								$queries->increment('resources_releases', $release->id, 'downloads');
								Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
							}
						} else {
							if(!Session::exists('nl-resource-download-' . $resource->id)) {
								$queries->increment('resources', $resource->id, 'downloads');
								$queries->increment('resources_releases', $release->id, 'downloads');
								Session::put('nl-resource-download-' . $resource->id, "true", 3600);
							}
						}
					}

					// Free, continue
					header('Content-Type: application/octet-stream');
					header('Content-Transfer-Encoding: Binary');
					header('Content-disposition: attachment; filename="' . basename($zip) . '"');
					ob_clean();
					flush();
					readfile($zip);

					die();

				} else {
					// Premium, ensure user is logged in and has purchased this resource
					if(!$user->isLoggedIn()){
						Redirect::to(URL::build('/resources'));
						die();
					}

					if(isset($can_download)){
						if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
							if($user->isLoggedIn() || Cookie::exists('accept')){
								if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
									$queries->increment('resources', $resource->id, 'downloads');
									$queries->increment('resources_releases', $release->id, 'downloads');
									Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
								}
							} else {
								if(!Session::exists('nl-resource-download-' . $resource->id)) {
									$queries->increment('resources', $resource->id, 'downloads');
									$queries->increment('resources_releases', $release->id, 'downloads');
									Session::put('nl-resource-download-' . $resource->id, "true", 3600);
								}
							}
						}

						header('Content-Type: application/octet-stream');
						header('Content-Transfer-Encoding: Binary');
						header('Content-disposition: attachment; filename="' . basename($zip) . '"');
						ob_clean();
						flush();
						readfile($zip);

						die();
					}

					$paid = DB::getInstance()->query('SELECT status FROM nl2_resources_payments WHERE resource_id = ? AND user_id = ?', array($resource->id, $user->data()->id))->results();
					if(count($paid)){
						$paid = $paid[0];

						if($paid->status == 1){
							if(!$user->isLoggedIn() || $user->data()->id != $resource->creator_id){
								if($user->isLoggedIn() || Cookie::exists('accept')){
									if(!Cookie::exists('nl-resource-download-' . $resource->id)) {
										$queries->increment('resources', $resource->id, 'downloads');
										$queries->increment('resources_releases', $release->id, 'downloads');
										Cookie::put('nl-resource-download-' . $resource->id, "true", 3600);
									}
								} else {
									if(!Session::exists('nl-resource-download-' . $resource->id)) {
										$queries->increment('resources', $resource->id, 'downloads');
										$queries->increment('resources_releases', $release->id, 'downloads');
										Session::put('nl-resource-download-' . $resource->id, "true", 3600);
									}
								}
							}

							// Purchased
							header('Content-Type: application/octet-stream');
							header('Content-Transfer-Encoding: Binary');
							header('Content-disposition: attachment; filename="' . basename($zip) . '"');
							ob_clean();
							flush();
							readfile($zip);
							
							die();

						} else {
							Redirect::to(URL::build('/resources/resource/' . Output::getClean($resource->id)));
							die();

						}
					} else {
						Redirect::to(URL::build('/resources/resource/' . Output::getClean($resource->id)));
						die();

					}
				}

				die();
			}

		} else if($_GET['do'] == 'update'){
			// Update resource
			if($user->isLoggedIn() && $resource->creator_id == $user->data()->id){
				// Can update
				if(Input::exists()){
					if(Token::check(Input::get('token'))){
						// Validate release
						if($resource->type == 0 && $resource->github_url != 'none'){
							try {
								// Use cURL
								$ch = curl_init();

								curl_setopt($ch, CURLOPT_HTTPHEADER, array(
									'Accept: application/vnd.github.v3+json',
									'User-Agent: NamelessMC-App'
								));
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
								curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo_name) . '/releases/' . Output::getClean($_POST['release']));

								if(!$github_query = curl_exec($ch)){
									$error = curl_error($ch);
								}

								curl_close($ch);

								$github_query = json_decode($github_query);

								if(!isset($github_query->id)) $error = str_replace('{x}', Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo_name), $resource_language->get('resources', 'unable_to_get_repo'));
								else {
									// Valid response
									// Check update doesn't already exist
									$exists = $queries->getWhere('resources_releases', array('release_tag', '=', Output::getClean($github_query->tag_name)));
									if(count($exists)){
										foreach($exists as $item){
											if($item->resource_id == $resource->id){
												$update_exists = true;
											}
										}
									}

									if(isset($update_exists)){
										$error = $resource_language->get('resources', 'update_already_exists');
									} else {
										$queries->update('resources', $resource->id, array(
											'updated' => date('U'),
											'latest_version' => Output::getClean($github_query->tag_name)
										));

										$queries->create('resources_releases', array(
											'resource_id' => $resource->id,
											'category_id' => $resource->category_id,
											'release_title' => Output::getClean($github_query->name),
											'release_description' => Output::getPurified($github_query->body),
											'release_tag' => Output::getClean($github_query->tag_name),
											'created' => date('U'),
											'download_link' => Output::getClean($github_query->html_url)
										));

										// Hook
										$new_resource_category = $queries->getWhere('resources_categories', array('id', '=', $resource->category_id));

										if(count($new_resource_category))
											$new_resource_category = Output::getClean($new_resource_category[0]->name);

										else
											$new_resource_category = 'Unknown';

										HookHandler::executeEvent('updateResource', array(
											'event' => 'updateResource',
											'username' => Output::getClean($user->data()->nickname),
											'content' => str_replace(array('{x}', '{y}'), array($new_resource_category, Output::getClean($user->data()->nickname)), $resource_language->get('resources', 'updated_resource_text')),
											'content_full' => str_replace(array('&amp', '&nbsp;', '&#39;'), array('&', '', '\''), strip_tags($github_query->body)),
											'avatar_url' => $user->getAvatar($user->data()->id, null, 128, true),
											'title' => Output::getClean($resource->name),
											'url' => Util::getSelfURL() . ltrim(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)), '/')
										));

										Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
										die();
									}
								}

							} catch(Exception $e){
								$error = $e->getMessage();
							}

						} else {
							if($latest_release->download_link == 'local'){
								// Upload zip
								require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
								require(ROOT_PATH . '/core/includes/markdown/tohtml/Markdown.inc.php'); // Markdown to HTML
								$emojione = new Emojione\Client(new Emojione\Ruleset());

								if(!isset($_POST['version']))
									$version = '1.0.0';
								else
									$version = $_POST['version'];

								$user_dir = ROOT_PATH . '/uploads/resources/' . $user->data()->id . DIRECTORY_SEPARATOR . $resource->id;

								if(isset($_FILES['resourceFile'])){
									$filename = $_FILES['resourceFile']['name'];
									$fileext = pathinfo($filename, PATHINFO_EXTENSION);

									if(strtolower($fileext) != 'zip'){
										$error = $resource_language->get('resources', 'file_not_zip');
									} else {
										// Check file size
										$filesize = $queries->getWhere('settings', array('name', '=', 'resources_filesize'));
										if(!count($filesize)){
											$queries->create('settings', array(
												'name' => 'resources_filesize',
												'value' => '2048'
											));
											$filesize = '2048';

										} else {
											$filesize = $filesize[0]->value;

											if(!is_numeric($filesize))
												$filesize = '2048';
										}

										if($_FILES['resourceFile']['size'] > ($filesize * 1000)){
											$error = str_replace('{x}', Output::getClean($filesize), $resource_language->get('resources', 'filesize_max_x'));

										} else {
											// Create release
											// Format description
											$cache->setCache('post_formatting');
											$formatting = $cache->retrieve('formatting');

											if($formatting == 'markdown'){
												$content = Michelf\Markdown::defaultTransform($_SESSION['new_resource']['content']);
												$content = Output::getClean($content);
											} else $content = Output::getClean($_SESSION['new_resource']['content']);

											$queries->create('resources_releases', array(
												'resource_id' => $resource->id,
												'category_id' => $resource->category_id,
												'release_title' => Output::getClean($version),
												'release_description' => $content,
												'release_tag' => Output::getClean($version),
												'created' => date('U'),
												'download_link' => 'local'
											));

											$release_id = $queries->getLastId();

											$uploadPath = $user_dir . DIRECTORY_SEPARATOR . $release_id;

											if(!is_dir($uploadPath))
												mkdir($uploadPath);

											$uploadPath .= DIRECTORY_SEPARATOR . basename($_FILES['resourceFile']['name']);

											if(move_uploaded_file($_FILES['resourceFile']['tmp_name'], $uploadPath)){
												// File uploaded
												$new_resource_category = $queries->getWhere('resources_categories', array('id', '=', $resource->category_id));

												if(count($new_resource_category))
													$new_resource_category = Output::getClean($new_resource_category[0]->name);

												else
													$new_resource_category = 'Unknown';

												$queries->update('resources', $resource->id, array(
													'updated' => date('U'),
													'latest_version' => Output::getClean($version)
												));

												HookHandler::executeEvent('updateResource', array(
													'event' => 'updateResource',
													'username' => Output::getClean($user->data()->nickname),
													'content' => str_replace(array('{x}', '{y}'), array($new_resource_category, Output::getClean($user->data()->nickname)), $resource_language->get('resources', 'updated_resource_text')),
													'content_full' => str_replace(array('&amp', '&nbsp;', '&#39;'), array('&', '', '\''), strip_tags($content)),
													'avatar_url' => $user->getAvatar($user->data()->id, null, 128, true),
													'title' => Output::getClean($resource->name),
													'url' => Util::getSelfURL() . ltrim(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)), '/')
												));

												Redirect::to(URL::build('/resources/resource/' . $resource->id));
												die();

											} else {
												// Unable to upload file
												$error = str_replace('{x}', $_FILES['resourceFile']['error'], $resource_language->get('resources', 'file_upload_failed'));

												$queries->delete('resources_releases', array('id', '=', $release_id));
											}
										}
									}
								}
							} else {
								// Update link
								if(Token::check(Input::get('token'))){
									// Validate link
									$validate = new Validate();
									$validation = $validate->check($_POST, array(
										'link' => array(
											'required' => true,
											'min' => 4,
											'max' => 256
										)
									));

									if($validation->passed()){
										if(!isset($_POST['version']))
											$version = '1.0.0';
										else
											$version = $_POST['version'];

										// Format description
										$cache->setCache('post_formatting');
										$formatting = $cache->retrieve('formatting');

										if($formatting == 'markdown'){
											$content = Michelf\Markdown::defaultTransform($_SESSION['new_resource']['content']);
											$content = Output::getClean($content);
										} else $content = Output::getClean($_SESSION['new_resource']['content']);

										$queries->update('resources', $resource->id, array(
											'updated' => date('U'),
											'latest_version' => Output::getClean($version)
										));

										$queries->create('resources_releases', array(
											'resource_id' => $resource->id,
											'category_id' => $resource->category_id,
											'release_title' => Output::getClean($version),
											'release_description' => $content,
											'release_tag' => Output::getClean($version),
											'created' => date('U'),
											'download_link' => Output::getClean($_POST['link'])
										));

										// Hook
										$new_resource_category = $queries->getWhere('resources_categories', array('id', '=', $resource->category_id));

										if(count($new_resource_category))
											$new_resource_category = Output::getClean($new_resource_category[0]->name);

										else
											$new_resource_category = 'Unknown';

										HookHandler::executeEvent('updateResource', array(
											'event' => 'updateResource',
											'username' => Output::getClean($user->data()->nickname),
											'content' => str_replace(array('{x}', '{y}'), array($new_resource_category, Output::getClean($user->data()->nickname)), $resource_language->get('resources', 'updated_resource_text')),
											'content_full' => str_replace(array('&amp', '&nbsp;', '&#39;'), array('&', '', '\''), strip_tags($content)),
											'avatar_url' => $user->getAvatar($user->data()->id, null, 128, true),
											'title' => Output::getClean($resource->name),
											'url' => Util::getSelfURL() . ltrim(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)), '/')
										));

										Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
										die();

									} else {
										$error = $resource_language->get('resources', 'external_link_error');
									}
								} else {
									$error = $language->get('general', 'invalid_token');
								}
							}
						}
					} else {
						$error = $language->get('general', 'invalid_token');
					}
				}

				if($resource->type == 0 && $resource->github_url != 'none'){
					// Github API
					try {
						$ch = curl_init();

						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Accept: application/vnd.github.v3+json',
							'User-Agent: NamelessMC-App'
						));
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo_name) . '/releases');

						if(!$github_query = curl_exec($ch)){
							$error = curl_error($ch);
						}

						curl_close($ch);

					} catch(Exception $e){
						die($e->getMessage());
					}

					// Get list of all releases
					$github_query = json_decode($github_query);

					if(!isset($github_query[0])) $error = str_replace('{x}', Output::getClean($resource->github_username) . '/' . Output::getClean($resource->github_repo), $resource_language->get('resources', 'unable_to_get_repo'));
					else {
						// Valid response
						$releases_array = array();
						foreach($github_query as $release){
							// Select release
							$releases_array[] = array(
								'id' => $release->id,
								'tag' => Output::getClean($release->tag_name),
								'name' => Output::getClean($release->name)
							);
						}
					}

					// Select release
					if(isset($error)) $smarty->assign('ERROR', $error);

					// Assign Smarty variables
					$smarty->assign(array(
						'NEW_RESOURCE' => $resource_language->get('resources', 'update'),
						'CANCEL' => $language->get('general', 'cancel'),
						'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
						'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
						'SELECT_RELEASE' => $resource_language->get('resources', 'select_release'),
						'RELEASES' => $releases_array,
						'SUBMIT' => $language->get('general', 'submit'),
						'TOKEN' => Token::get()
					));

					$template_file = 'resources/new_resource_select_release.tpl';

				} else {
					if($latest_release->download_link == 'local'){
						require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
						require(ROOT_PATH . '/core/includes/markdown/tohtml/Markdown.inc.php'); // Markdown to HTML
						$emojione = new Emojione\Client(new Emojione\Ruleset());

						// Upload new zip
						if(isset($error)) $smarty->assign('ERROR', $error);

						$smarty->assign(array(
							'UPDATE_RESOURCE' => $resource_language->get('resources', 'update'),
							'CANCEL' => $language->get('general', 'cancel'),
							'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
							'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
							'CHOOSE_FILE' => $resource_language->get('resources', 'choose_file'),
							'ZIP_ONLY' => $resource_language->get('resources', 'zip_only'),
							'VERSION_TAG' => $resource_language->get('resources', 'version_tag'),
							'SUBMIT' => $language->get('general', 'submit'),
							'TOKEN' => Token::get(),
							'UPDATE_INFORMATION' => $resource_language->get('resources', 'update_information')
						));

						// Display either Markdown or HTML editor
						if(!isset($formatting)){
							$cache->setCache('post_formatting');
							$formatting = $cache->retrieve('formatting');
						}

						$template->addJSFiles(array(
							(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/js/emojione.min.js' => array()
						));

						if($formatting == 'markdown'){
							// Markdown
							$smarty->assign('MARKDOWN', true);
							$smarty->assign('MARKDOWN_HELP', $language->get('general', 'markdown_help'));

							$template->addJSFiles(array(
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emojionearea/js/emojionearea.min.js' => array()
							));

							$template->addJSScript('
							$(document).ready(function() {
							    var el = $("#markdown").emojioneArea({
									pickerPosition: "bottom"
								});
							});
						');

						} else {
							$template->addJSFiles(array(
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/prism/prism.js' => array(),
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/plugins/spoiler/js/spoiler.js' => array(),
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/tinymce.min.js' => array()
							));

							$template->addJSScript(Input::createTinyEditor($language, 'editor'));
						}

						$template_file = 'resources/update_resource_upload.tpl';

					} else {
						require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
						require(ROOT_PATH . '/core/includes/markdown/tohtml/Markdown.inc.php'); // Markdown to HTML
						$emojione = new Emojione\Client(new Emojione\Ruleset());

						// Upload new zip
						if(isset($error)) $smarty->assign('ERROR', $error);

						$smarty->assign(array(
							'NEW_RESOURCE' => $resource_language->get('resources', 'update'),
							'CANCEL' => $language->get('general', 'cancel'),
							'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
							'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
							'EXTERNAL_LINK' => $resource_language->get('resources', 'external_link'),
							'VERSION_TAG' => $resource_language->get('resources', 'version_tag'),
							'SUBMIT' => $language->get('general', 'submit'),
							'TOKEN' => Token::get(),
							'UPDATE_INFORMATION' => $resource_language->get('resources', 'update_information')
						));

						// Display either Markdown or HTML editor
						if(!isset($formatting)){
							$cache->setCache('post_formatting');
							$formatting = $cache->retrieve('formatting');
						}

						$template->addJSFiles(array(
							(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/js/emojione.min.js' => array()
						));

						if($formatting == 'markdown'){
							// Markdown
							$smarty->assign('MARKDOWN', true);
							$smarty->assign('MARKDOWN_HELP', $language->get('general', 'markdown_help'));

							$template->addJSFiles(array(
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emojionearea/js/emojionearea.min.js' => array()
							));

							$template->addJSScript('
							$(document).ready(function() {
							    var el = $("#markdown").emojioneArea({
									pickerPosition: "bottom"
								});
							});
						');

						} else {
							$template->addJSFiles(array(
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/prism/prism.js' => array(),
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/plugins/spoiler/js/spoiler.js' => array(),
								(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/tinymce.min.js' => array()
							));

							$template->addJSScript(Input::createTinyEditor($language, 'content'));
						}

						$template_file = 'resources/new_resource_external_link.tpl';

					}
				}

			} else {
				// Can't update, redirect
				Redirect::to(URL::build('/resources'));
				die();
			}
		} else if($_GET['do'] == 'edit'){
			// Check user can edit
			if(!$user->isLoggedIn()){
				Redirect::to(URL::build('/resources'));
				die();
			}
			if($resource->creator_id == $user->data()->id || $resources->canEditResources($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
				// Can edit
				$errors = array();

				if(Input::exists()){
					if(Token::check(Input::get('token'))){
						$validate = new Validate();
						$validation = $validate->check($_POST, array(
							'title' => array(
								'min' => 2,
								'max' => 64,
								'required' => true
							),
							'description' => array(
								'min' => 2,
								'max' => 20000,
								'required' => true
							),
							'contributors' => array(
								'max' => 255
							)
						));

						if($validation->passed()){
							if($resource->type == 1 && isset($_POST['price']) && !empty($_POST['price']) && is_numeric($_POST['price']) && $_POST['price'] >= 0.01 && $_POST['price'] < 100 && preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
								$price = number_format($_POST['price'], 2, '.', '');
							} else
								$price = $resource->price;

							try {
								$cache->setCache('post_formatting');
								$formatting = $cache->retrieve('formatting');

								if($formatting == 'markdown'){
									$content = Michelf\Markdown::defaultTransform($_POST['description']);
									$content = Output::getClean($content);
								} else $content = Output::getClean($_POST['description']);

								$queries->update('resources', $resource->id, array(
									'name' => Output::getClean(Input::get('title')),
									'description' => $content,
									'contributors' => Output::getClean(Input::get('contributors')),
									'price' => $price
								));

								Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL(Input::get('title'))));
								die();
							} catch(Exception $e){
								$errors[] = $e->getMessage();
							}
						} else {
							foreach($validation->errors() as $item){
								if(strpos($item, 'is required') !== false){
									switch($item){
										case (strpos($item, 'name') !== false):
											$errors[] = $resource_language->get('resources', 'name_required');
											break;
										case (strpos($item, 'description') !== false):
											$errors[] = $resource_language->get('resources', 'content_required');
											break;
									}
								} else if(strpos($item, 'minimum') !== false){
									switch($item){
										case (strpos($item, 'name') !== false):
											$errors[] = $resource_language->get('resources', 'name_min_2');
											break;
										case (strpos($item, 'description') !== false):
											$errors[] = $resource_language->get('resources', 'content_min_2');
											break;
									}
								} else if(strpos($item, 'maximum') !== false){
									switch($item){
										case (strpos($item, 'name') !== false):
											$errors[] = $resource_language->get('resources', 'name_max_64');
											break;
										case (strpos($item, 'description') !== false):
											$errors[] = $resource_language->get('resources', 'content_max_20000');
											break;
										case (strpos($item, 'contributors') !== false):
											$errors[] = $resource_language->get('resources', 'contributors_max_255');
											break;
									}
								}
							}
						}

					} else {
						$errors[] = $language->get('general', 'invalid_token');
					}
				}
			} else {
				Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
				die();
			}

			if(isset($errors) && count($errors))
				$smarty->assign('ERRORS', $errors);

			// Smarty variables
			$smarty->assign(array(
				'EDITING_RESOURCE' => $resource_language->get('resources', 'editing_resource'),
				'NAME' => $resource_language->get('resources', 'resource_name'),
				'DESCRIPTION' => $resource_language->get('resources', 'resource_description'),
				'CONTRIBUTORS' => $resource_language->get('resources', 'contributors'),
				'RESOURCE_NAME' => Output::getClean($resource->name),
				'RESOURCE_DESCRIPTION' => Output::getPurified(htmlspecialchars_decode($resource->description)),
				'RESOURCE_CONTRIBUTORS' => Output::getClean($resource->contributors),
				'CANCEL' => $language->get('general', 'cancel'),
				'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
				'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
				'TOKEN' => Token::get(),
				'SUBMIT' => $language->get('general', 'submit')
			));

			if($resource->type == 1){
				$currency = $queries->getWhere('settings', array('name', '=', 'resources_currency'));
				if(!count($currency)){
					$queries->create('settings', array(
						'name' => 'resources_currency',
						'value' => 'GBP'
					));
					$currency = 'GBP';

				} else
					$currency = $currency[0]->value;

				$smarty->assign(array(
					'PRICE' => $resource_language->get('resources', 'price'),
					'RESOURCE_PRICE' => Output::getClean($resource->price),
					'CURRENCY' => $currency
				));
			}

			// Get post formatting type (HTML or Markdown)
			$cache->setCache('post_formatting');
			$formatting = $cache->retrieve('formatting');

			if($formatting == 'markdown'){
				// Markdown
				$smarty->assign('MARKDOWN', true);
				$smarty->assign('MARKDOWN_HELP', $language->get('general', 'markdown_help'));
			}

			$template_file = 'resources/edit_resource.tpl';

		} else if($_GET['do'] == 'move'){
			// Check user can move
			if(!$user->isLoggedIn()){
				Redirect::to(URL::build('/resources'));
				die();
			}
			if($resources->canMoveResources($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
				$errors = array();

				// Get categories
				$categories = $queries->getWhere('resources_categories', array('id', '<>', $resource->category_id));
				if(!count($categories)){
					$smarty->assign('NO_CATEGORIES', $resource_language->get('resources', 'no_categories_available'));
				}

				if(Input::exists()){
					if(Token::check(Input::get('token'))){
						if(isset($_POST['category_id']) && is_numeric($_POST['category_id'])) {
							// Move resource
							$category = $queries->getWhere('resources_categories', array('id', '=', $_POST['category_id']));
							if(count($category)) {
								try {
									$queries->update('resources', $resource->id, array(
										'category_id' => $_POST['category_id']
									));

									$releases = $queries->getWhere('resources_releases', array('resource_id', '=', $resource->id));
									if (count($releases)) {
										foreach ($releases as $release) {
											$queries->update('resources_releases', $release->id, array(
												'category_id' => $_POST['category_id']
											));
										}
									}

									Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
									die();
								} catch (Exception $e) {
									$errors[] = $e->getMessage();
								}
							} else
								$errors[] = $resource_language->get('resources', 'invalid_category');
						} else
							$errors[] = $resource_language->get('resources', 'invalid_category');

					} else
						$errors[] = $language->get('general', 'invalid_token');
				}

				if(count($errors))
					$smarty->assign('ERRORS', $errors);

				$smarty->assign(array(
					'MOVE_RESOURCE' => $resource_language->get('resources', 'move_resource'),
					'TOKEN' => Token::get(),
					'CANCEL' => $language->get('general', 'cancel'),
					'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
					'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
					'SUBMIT' => $language->get('general', 'submit'),
					'MOVE_TO' => $resource_language->get('resources', 'move_to'),
					'CATEGORIES' => $categories
				));

				$template_file = 'resources/move.tpl';

			} else {
				Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
				die();
			}
		} else if($_GET['do'] == 'delete'){
			// Check user can delete
			if(!$user->isLoggedIn()){
				Redirect::to(URL::build('/resources'));
				die();
			}
			if($resources->canDeleteResources($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
				$errors = array();

				if(Input::exists()){
					if(Token::check(Input::get('token'))){
						// Delete resource
						try {
							$queries->delete('resources', array('id', '=', $resource->id));
							$queries->delete('resources_comments', array('resource_id', '=', $resource->id));
							$queries->delete('resources_releases', array('resource_id', '=', $resource->id));
							$queries->delete('resources_payments', array('resource_id', '=', $resource->id));

							if(is_dir(ROOT_PATH . '/uploads/resources/' . $resource->creator_id . '/' . $resource->id)){
								Util::recursiveRemoveDirectory(ROOT_PATH . '/uploads/resources/' . $resource->creator_id . '/' . $resource->id);
							}

							Redirect::to(URL::build('/resources'));
							die();
						} catch(Exception $e){
							$errors[] = $e->getMessage();
						}

					} else
						$errors[] = $language->get('general', 'invalid_token');
				}

				if(count($errors))
					$smarty->assign('ERRORS', $errors);

				$smarty->assign(array(
					'CONFIRM_DELETE_RESOURCE' => str_replace('{x}', Output::getClean($resource->name), $resource_language->get('resources', 'confirm_delete_resource')),
					'TOKEN' => Token::get(),
					'CANCEL' => $language->get('general', 'cancel'),
					'CANCEL_LINK' => URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)),
					'DELETE' => $language->get('general', 'delete')
				));

				$template_file = 'resources/delete.tpl';

			} else {
				Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
				die();
			}

		} else if($_GET['do'] == 'delete_review'){
			// Check user can delete reviews
			if(!$user->isLoggedIn()){
				Redirect::to(URL::build('/resources'));
				die();
			}
			if($resources->canDeleteReviews($resource->category_id, $user->data()->group_id, $user->data()->secondary_groups)){
				if(!isset($_GET['review']) || !is_numeric($_GET['review'])){
					Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
					die();
				}
				// Ensure review exists
				$review = $queries->getWhere('resources_comments', array('id', '=', $_GET['review']));
				if(count($review)){
					// Delete it
					try {
						$queries->delete('resources_comments', array('id', '=', $_GET['review']));

						// Re-calculate rating
						// Unhide user's previous rating if it exists
						$ratings = $queries->getWhere('resources_comments', array('resource_id', '=', $resource->id));
						if(count($ratings)){
							$overall_rating = 0;
							$overall_rating_count = 0;
							$release_rating = 0;
							$release_rating_count = 0;
							$last_rating = 0;
							$last_rating_created = 0;
							$last_rating_value = 0;

							foreach($ratings as $rating){
								if($rating->author_id == $user->data()->id && $rating->hidden == 1 && $rating->created > $last_rating_created){
									// Unhide rating
									$last_rating = $rating->id;
									$last_rating_created = $rating->created;

									if($rating->release_tag == $resource->latest_version)
										$last_rating_value = $rating->rating;

								} else if($rating->hidden == 0){
									// Update rating
									// Overall
									$overall_rating = $overall_rating + $rating->rating;
									$overall_rating_count++;

									if($rating->release_tag == $resource->latest_version){
										// Release
										$release_rating = $release_rating + $rating->rating;
										$release_rating_count++;
									}
								}
							}

							if($last_rating > 0){
								$queries->update('resources_comments', $last_rating, array(
									'hidden' => 0
								));

								if($last_rating_value > 0){
									$overall_rating += $last_rating_value;
									$overall_rating_count++;
									$release_rating = $release_rating += $last_rating_value;
									$release_rating_count++;
								}

							}

							if($overall_rating > 0) {
								$overall_rating = $overall_rating / $overall_rating_count;
								$overall_rating = round($overall_rating * 10);
							}

							if($release_rating > 0) {
								$release_rating = $release_rating / $release_rating_count;
								$release_rating = round($release_rating * 10);
							}
						} else {
							$overall_rating = 0;
							$release_rating = 0;
						}

						$queries->update('resources', $resource->id, array(
							'rating' => $overall_rating
						));
						$queries->update('resources_releases', $latest_release->id, array(
							'rating' => $release_rating
						));

						$cache->setCache('resource-comments-' . $resource->id);
						$cache->erase('comments');

					} catch(Exception $e){
						// error
					}
				}
				Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));
				die();
			}
		} else {
			Redirect::to(URL::build('/resources'));
			die();
		}
	}
}

if($user->isLoggedIn()){
	if($formatting == 'markdown'){
		$template->addJSFiles(array(
			(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/js/emojione.min.js' => array(),
			(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emojionearea/js/emojionearea.min.js' => array()
		));

		if(isset($_GET['do']) && $_GET['do'] == 'edit'){
			require(ROOT_PATH . '/core/includes/markdown/tomarkdown/autoload.php');
			$converter = new League\HTMLToMarkdown\HtmlConverter(array('strip_tags' => true));

			$clean = $converter->convert(htmlspecialchars_decode($resource->description));
			$clean = Output::getPurified($clean);

			$template->addJSScript('
            $(document).ready(function() {
                var el = $("#markdown").emojioneArea({
                    pickerPosition: "bottom"
                });

                el[0].emojioneArea.setText(\'' . str_replace(array("\'", "&gt;", "&amp;"), array("&#39;", ">", "&"), str_replace(array("\r", "\n"), array("\\r", "\\n"), $clean)) . '\');
            });
		');

		} else {
			$template->addJSScript('
			$(document).ready(function() {
				var el = $("#markdown").emojioneArea({
					pickerPosition: "bottom"
				});
			});
		');
		}

	} else {
		$template->addJSFiles(array(
			(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
			(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/prism/prism.js' => array(),
			(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/plugins/spoiler/js/spoiler.js' => array(),
			(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/tinymce.min.js' => array()
		));

		$template->addJSScript(Input::createTinyEditor($language, 'editor'));

	}

	$template->addJSScript('
	  var $star_rating = $(\'.star-rating.view .far\');
	  var $star_rating_set = $(\'.star-rating.set .far\');
	
	  var SetRatingStar = function(type = 0) {
		  if(type === 0) {
			  return $star_rating.each(function () {
				  if (parseInt($(this).parent().children(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
					  return $(this).removeClass(\'far\').addClass(\'fas\');
				  } else {
					  return $(this).removeClass(\'fas\').addClass(\'far\');
				  }
			  });
		  } else {
			  return $star_rating_set.each(function () {
				  if (parseInt($star_rating_set.siblings(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
					  return $(this).removeClass(\'far\').addClass(\'fas\');
				  } else {
					  return $(this).removeClass(\'fas\').addClass(\'far\');
				  }
			  });
		  }
	  };
	
	  $star_rating_set.on(\'click\', function() {
		  $star_rating_set.siblings(\'input.rating-value\').val($(this).data(\'rating\'));
		  return SetRatingStar(1);
	  });
	
	  SetRatingStar();
	');

} else {
	$template->addJSScript('
	  var $star_rating = $(\'.star-rating.view .far\');
	  var $star_rating_set = $(\'.star-rating.set .far\');
	
	  var SetRatingStar = function(type = 0) {
		  if(type === 0) {
			  return $star_rating.each(function () {
				  if (parseInt($(this).parent().children(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
					  return $(this).removeClass(\'far\').addClass(\'fas\');
				  } else {
					  return $(this).removeClass(\'fas\').addClass(\'far\');
				  }
			  });
		  } else {
			  return $star_rating_set.each(function () {
				  if (parseInt($star_rating_set.siblings(\'input.rating-value\').val()) >= parseInt($(this).data(\'rating\'))) {
					  return $(this).removeClass(\'far\').addClass(\'fas\');
				  } else {
					  return $(this).removeClass(\'fas\').addClass(\'far\');
				  }
			  });
		  }
	  };
	
	  SetRatingStar();
	');
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate($template_file, $smarty);