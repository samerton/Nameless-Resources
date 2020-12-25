<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr5
 *
 *  License: MIT
 *
 *  Resources creation page
 */
// Always define page name
define('PAGE', 'resources');

require(ROOT_PATH . '/core/includes/emojione/autoload.php'); // Emojione
require(ROOT_PATH . '/core/includes/markdown/tohtml/Markdown.inc.php'); // Markdown to HTML
$emojione = new Emojione\Client(new Emojione\Ruleset());

require(ROOT_PATH . '/modules/Resources/classes/Resources.php');
$resources = new Resources();

// Ensure user is logged in
if(!$user->isLoggedIn()){
	Redirect::to(URL::build('/resources'));
	die();
}

$groups = array();
foreach ($user->getGroups() as $group) {
    $groups[] = $group->id;
}

// Handle input
if(Input::exists()){
	if(Token::check(Input::get('token'))){
		$validate = new Validate();

		if(!isset($_GET['step'])){
			// Initial step
			$validation = $validate->check($_POST, array(
				'name' => array(
					'required' => true,
					'min' => 2,
					'max' => 64
				),
				'category' => array(
					'required' => true
				),
				'content' => array(
					'required' => true,
					'min' => 2,
					'max' => 20000
				),
				'contributors' => array(
					'max' => 255
				)
			));

			if($validation->passed()){
				// Check permissions
				if (!$resources->canPostResourceInCategory($groups, $_POST['category'])) {
					Redirect::to(URL::build('/resources'));
					die();
				}

				$_SESSION['new_resource'] = $_POST;

				if(isset($_POST['type']) && $_POST['type'] == 'github'){
					Redirect::to(URL::build('/resources/new/', 'step=github'));
					die();
				} else {
					Redirect::to(URL::build('/resources/new/', 'step=type'));
					die();
				}

			} else {
				// Errors
				$errors = array();

				foreach($validation->errors() as $item){
					if(strpos($item, 'is required') !== false){
						switch($item){
							case (strpos($item, 'name') !== false):
								$errors[] = $resource_language->get('resources', 'name_required');
								break;
							case (strpos($item, 'content') !== false):
								$errors[] = $resource_language->get('resources', 'content_required');
								break;
							case (strpos($item, 'category') !== false):
								$errors[] = $resource_language->get('resources', 'category_required');
								break;
						}
					} else if(strpos($item, 'minimum') !== false){
						switch($item){
							case (strpos($item, 'name') !== false):
								$errors[] = $resource_language->get('resources', 'name_min_2');
								break;
							case (strpos($item, 'content') !== false):
								$errors[] = $resource_language->get('resources', 'content_min_2');
								break;
						}
					} else if(strpos($item, 'maximum') !== false){
						switch($item){
							case (strpos($item, 'name') !== false):
								$errors[] = $resource_language->get('resources', 'name_max_64');
								break;
							case (strpos($item, 'content') !== false):
								$errors[] = $resource_language->get('resources', 'content_max_20000');
								break;
							case (strpos($item, 'contributors') !== false):
								$errors[] = $resource_language->get('resources', 'contributors_max_255');
								break;
						}
					}
				}

				$error = implode('<br />', $errors);
			}

		} else {
			if($_GET['step'] == 'github'){
				// GitHub repository
				if(!isset($_SESSION['new_resource']) || !isset($_SESSION['new_resource']['type']) || (isset($_SESSION['new_resource']['type']) && $_SESSION['new_resource']['type'] != 'github')){
					Redirect::to(URL::build('/resources/new'));
					die();
				}

				$validation = $validate->check($_POST, array(
					'github_username' => array(
						'required' => true,
						'min' => 2,
						'max' => 32
					),
					'github_repo' => array(
						'required' => true,
						'min' => 2,
						'max' => 64
					),
				));

				if($validation->passed()){
					// Check GitHub API
					try {
						// Use cURL
						$ch = curl_init();

						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'Accept: application/vnd.github.v3+json',
							'User-Agent: NamelessMC-App'
						));
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($_POST['github_username']) . '/' . Output::getClean($_POST['github_repo']) . '/releases');

						if(!$github_query = curl_exec($ch)){
							$error = curl_error($ch);
						}

						curl_close($ch);

						$github_query = json_decode($github_query, true);

						if(!isset($github_query[0])){
							$error = str_replace('{x}', Output::getClean($_POST['github_username']) . '/' . Output::getClean($_POST['github_repo']), $resource_language->get('resources', 'unable_to_get_repo'));
						} else {
							// Valid response
							$releases_array = array();
							foreach($github_query as $release){
								// Select release
								$releases_array[] = array(
									'id' => $release['id'],
									'tag' => Output::getClean($release['tag_name']),
									'name' => Output::getClean($release['name'])
								);
							}

							$_SESSION['new_resource']['github'] = $_POST;
							$_SESSION['github_releases'] = $releases_array;

							Redirect::to(URL::build('/resources/new/', 'step=release'));
							die();

						}

					} catch(Exception $e){
						$error = $e->getMessage();
					}

				} else {
					// Errors
					$errors = array();

					foreach($validation->errors() as $item){
						if(strpos($item, 'is required') !== false){
							switch($item){
								case (strpos($item, 'github_username') !== false):
									$errors[] = $resource_language->get('resources', 'github_username_required');
									break;
								case (strpos($item, 'github_repo') !== false):
									$errors[] = $resource_language->get('resources', 'github_repo_required');
									break;
							}
						} else if(strpos($item, 'minimum') !== false){
							switch($item){
								case (strpos($item, 'github_username') !== false):
									$errors[] = $resource_language->get('resources', 'github_username_min_2');
									break;
								case (strpos($item, 'github_repo') !== false):
									$errors[] = $resource_language->get('resources', 'github_repo_min_2');
									break;
							}
						} else if(strpos($item, 'maximum') !== false){
							switch($item){
								case (strpos($item, 'github_username') !== false):
									$errors[] = $resource_language->get('resources', 'github_username_max_32');
									break;
								case (strpos($item, 'github_repo') !== false):
									$errors[] = $resource_language->get('resources', 'github_repo_max_64');
									break;
							}
						}
					}

					$error = implode('<br />', $errors);
				}

			} else if($_GET['step'] == 'release'){
				// Validate release
				if(!isset($_SESSION['new_resource']) || !isset($_SESSION['new_resource']['type']) || (isset($_SESSION['new_resource']['type']) && $_SESSION['new_resource']['type'] != 'github')){
					Redirect::to(URL::build('/resources/new'));
					die();
				}

				if(!isset($_SESSION['github_releases']) || (isset($_SESSION['github_releases']) && !count($_SESSION['github_releases']))){
					Redirect::to(URL::build('/resources/new/', 'step=github'));
					die();
				}

				// Check permissions
				if (!$resources->canPostResourceInCategory($groups, $_SESSION['new_resource']['category'])) {
					Redirect::to(URL::build('/resources'));
					die();
				}

				try {
					// Use cURL
					$ch = curl_init();

					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Accept: application/vnd.github.v3+json',
						'User-Agent: NamelessMC-App'
					));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($_SESSION['new_resource']['github']['github_username']) . '/' . Output::getClean($_SESSION['new_resource']['github']['github_repo']) . '/releases/' . Output::getClean($_POST['release']));

					if(!$github_query = curl_exec($ch)){
						$error = curl_error($ch);
					}

					curl_close($ch);

					$github_query = json_decode($github_query);

					if(!isset($github_query->id)) $error = str_replace('{x}', Output::getClean($_SESSION['new_resource']['github']['github_username']) . '/' . Output::getClean($_SESSION['new_resource']['github']['github_repo']), $resource_language->get('resources', 'unable_to_get_repo'));
					else {
						// Valid response
						// Create resource
						// Format description
						$cache->setCache('post_formatting');
						$formatting = $cache->retrieve('formatting');

						if($formatting == 'markdown'){
							$content = Michelf\Markdown::defaultTransform($_SESSION['new_resource']['content']);
							$content = Output::getClean($content);
						} else $content = Output::getClean($_SESSION['new_resource']['content']);

						$queries->create('resources', array(
							'category_id' => $_SESSION['new_resource']['category'],
							'creator_id' => $user->data()->id,
							'name' => Output::getClean($_SESSION['new_resource']['name']),
							'description' => $content,
							'contributors' => ((isset($_SESSION['new_resource']['contributors']) && !is_null($_SESSION['new_resource']['contributors'])) ? Output::getClean($_SESSION['new_resource']['contributors']) : null),
							'created' => date('U'),
							'updated' => date('U'),
							'github_url' => 'https://github.com/' . Output::getClean($_SESSION['new_resource']['github']['github_username']) . '/' . Output::getClean($_SESSION['new_resource']['github']['github_repo']),
							'github_username' => Output::getClean($_SESSION['new_resource']['github']['github_username']),
							'github_repo_name' => Output::getClean($_SESSION['new_resource']['github']['github_repo']),
							'latest_version' => Output::getClean($github_query->tag_name)
						));

						$resource_id = $queries->getLastId();

						$queries->create('resources_releases', array(
							'resource_id' => $resource_id,
							'category_id' => $_SESSION['new_resource']['category'],
							'release_title' => Output::getClean($github_query->name),
							'release_description' => Output::getPurified($github_query->body),
							'release_tag' => Output::getClean($github_query->tag_name),
							'created' => date('U'),
							'download_link' => Output::getClean($github_query->html_url)
						));

						// Hook
						$new_resource_category = $queries->getWhere('resources_categories', array('id', '=', $_SESSION['new_resource']['category']));

						if(count($new_resource_category))
							$new_resource_category = Output::getClean($new_resource_category[0]->name);

						else
							$new_resource_category = 'Unknown';

						HookHandler::executeEvent('newResource', array(
							'event' => 'newResource',
							'username' => Output::getClean($user->data()->nickname),
							'content' => str_replace(array('{x}', '{y}'), array($new_resource_category, Output::getClean($user->data()->nickname)), $resource_language->get('resources', 'new_resource_text')),
							'content_full' => str_replace('&nbsp;', '', strip_tags(Output::getDecoded($content))),
							'avatar_url' => $user->getAvatar(null, 128, true),
							'title' => Output::getClean($_SESSION['new_resource']['name']),
							'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/resources/resource/' . $resource_id . '-' . Util::stringToURL(Output::getClean($_SESSION['new_resource']['name'])))
						));

						unset($_SESSION['new_resource']);
						unset($_SESSION['github_releases']);

						Redirect::to(URL::build('/resources/resource/' . $resource_id));
						die();
					}

				} catch(Exception $e){
					$error = $e->getMessage();
				}

			} else if($_GET['step'] == 'type') {
				// Free or premium
				if(!isset($_SESSION['new_resource'])){
					Redirect::to(URL::build('/resources/new'));
					die();
				}

				$category = $queries->getWhere('resources_categories', array('id', '=', $_SESSION['new_resource']['category']));
				if(!count($category)){
					Redirect::to(URL::build('/resources/new'));
					die();
				}
				$category = $category[0];

                $permission = $resources->getAvailableResourceTypes($groups, $category->id);

				if(!$permission->post){
					Redirect::to(URL::build('/resources/new'));
					die();
				}

				if(!$permission->premium){
					Redirect::to(URL::build('/resources/new/', 'step=upload'));
					die();
				}

				if(Input::exists()){
					if(Token::check(Input::get('token'))){
						if(isset($_POST['type']) && $_POST['type'] == 'premium'){
							$type = 'premium';

							if(!isset($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] < 0.01 || $_POST['price'] > 100 || !preg_match('/^\d+(?:\.\d{2})?$/', $_POST['price'])){
								$error = $resource_language->get('resources', 'invalid_price');
							} else {
								$price = number_format($_POST['price'], 2, '.', '');
							}

						} else
							$type = 'free';

						if(!isset($error)){
							// OK to continue
							$to_continue = array('type' => $type);
							if(isset($price)) $to_continue['price'] = $price;

							$type = $_SESSION['new_resource']['type'];
							$_SESSION['new_resource']['type'] = $to_continue;

							if($type == 'external'){
								Redirect::to(URL::build('/resources/new/', 'step=link'));
								die();
							} else {
								Redirect::to(URL::build('/resources/new/', 'step=upload'));
								die();
							}
						}

					} else
						$error = $language->get('general', 'invalid_token');
				}

			} else if($_GET['step'] == 'upload'){
				// Upload zip
				if(!isset($_SESSION['new_resource'])){
					Redirect::to(URL::build('/resources/new'));
					die();
				}

				$category = $queries->getWhere('resources_categories', array('id', '=', $_SESSION['new_resource']['category']));
				if(!count($category)){
					Redirect::to(URL::build('/resources/new'));
					die();
				}
				$category = $category[0];

				$permission = $resources->getAvailableResourceTypes($groups, $category->id);

				if(!$permission->post){
					Redirect::to(URL::build('/resources/new'));
					die();
				}

				if(!isset($_SESSION['new_resource']['type'])){
					Redirect::to(URL::build('/resources/new/', 'step=type'));
					die();
				}

				if(!isset($_POST['version']))
					$version = '1.0.0';
				else
					$version = $_POST['version'];

				if(!is_dir(ROOT_PATH . '/uploads/resources'))
					mkdir(ROOT_PATH . '/uploads/resources');

				$user_dir = ROOT_PATH . '/uploads/resources/' . $user->data()->id;

				if(!is_dir($user_dir)){
					if(!mkdir($user_dir)){
						$error = $resource_language->get('resources', 'upload_directory_not_writable');
					}
				}

				if(isset($_FILES['resourceFile']) && !isset($error)){
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
							// Create resource
							// Format description
							$cache->setCache('post_formatting');
							$formatting = $cache->retrieve('formatting');

							if($formatting == 'markdown'){
								$content = Michelf\Markdown::defaultTransform($_SESSION['new_resource']['content']);
								$content = Output::getClean($content);
							} else $content = Output::getClean($_SESSION['new_resource']['content']);

							$type = 0;
							$price = null;

							if(isset($_SESSION['new_resource']['type']['type'])){
								if($_SESSION['new_resource']['type']['type'] == 'premium'){
									$type = 1;

									if(isset($_SESSION['new_resource']['type']['price']))
										$price = $_SESSION['new_resource']['type']['price'];
								}
							}

							$queries->create('resources', array(
								'category_id' => $_SESSION['new_resource']['category'],
								'creator_id' => $user->data()->id,
								'name' => Output::getClean($_SESSION['new_resource']['name']),
								'description' => $content,
								'contributors' => ((isset($_SESSION['new_resource']['contributors']) && !is_null($_SESSION['new_resource']['contributors'])) ? Output::getClean($_SESSION['new_resource']['contributors']) : null),
								'created' => date('U'),
								'updated' => date('U'),
								'github_url' => 'none',
								'github_username' => 'none',
								'github_repo_name' => 'none',
								'latest_version' => Output::getClean($version),
								'type' => $type,
								'price' => $price
							));

							$resource_id = $queries->getLastId();

							// Create release
							$queries->create('resources_releases', array(
								'resource_id' => $resource_id,
								'category_id' => $_SESSION['new_resource']['category'],
								'release_title' => Output::getClean($version),
								'release_description' => $content,
								'release_tag' => Output::getClean($version),
								'created' => date('U'),
								'download_link' => 'local'
							));

							$release_id = $queries->getLastId();

							$uploadPath = $user_dir . DIRECTORY_SEPARATOR . $resource_id;

							if(!is_dir($uploadPath))
								mkdir($uploadPath);

							$uploadPath .= DIRECTORY_SEPARATOR . $release_id;

							if(!is_dir($uploadPath))
								mkdir($uploadPath);

							$uploadPath .= DIRECTORY_SEPARATOR . basename($_FILES['resourceFile']['name']);

							if(move_uploaded_file($_FILES['resourceFile']['tmp_name'], $uploadPath)){
								// File uploaded
								// Hook
								$new_resource_category = $queries->getWhere('resources_categories', array('id', '=', $_SESSION['new_resource']['category']));

								if(count($new_resource_category))
									$new_resource_category = Output::getClean($new_resource_category[0]->name);
								else
									$new_resource_category = 'Unknown';

								HookHandler::executeEvent('newResource', array(
									'event' => 'newResource',
									'username' => $user->getDisplayname(),
									'content' => str_replace(array('{x}', '{y}'), array($new_resource_category, Output::getClean($user->data()->nickname)), $resource_language->get('resources', 'new_resource_text')),
									'content_full' => str_replace('&nbsp;', '', strip_tags(Output::getDecoded($content))),
									'avatar_url' => $user->getAvatar(null, 128, true),
									'title' => Output::getClean($_SESSION['new_resource']['name']),
									'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/resources/resource/' . $resource_id . '-' . Util::stringToURL(Output::getClean($_SESSION['new_resource']['name'])))
								));

								unset($_SESSION['new_resource']);

								Redirect::to(URL::build('/resources/resource/' . $resource_id));
								die();

							} else {
								// Unable to upload file
								$error = str_replace('{x}', $_FILES['resourceFile']['error'], $resource_language->get('resources', 'file_upload_failed'));

								$queries->delete('resources', array('id', '=', $resource_id));
								$queries->delete('resources_releases', array('id', '=', $release_id));
							}
						}
					}
				}
			} else if($_GET['step'] == 'link'){
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
						$cache->setCache('post_formatting');
						$formatting = $cache->retrieve('formatting');

						if($formatting == 'markdown'){
							$content = Michelf\Markdown::defaultTransform($_SESSION['new_resource']['content']);
							$content = Output::getClean($content);
						} else $content = Output::getClean($_SESSION['new_resource']['content']);

						if(!isset($_POST['version']))
							$version = '1.0.0';
						else
							$version = $_POST['version'];

						$type = 0;
						$price = null;

						if(isset($_SESSION['new_resource']['type']['type'])){
							if($_SESSION['new_resource']['type']['type'] == 'premium'){
								$type = 1;

								if(isset($_SESSION['new_resource']['type']['price']))
									$price = $_SESSION['new_resource']['type']['price'];
							}
						}

						$queries->create('resources', array(
							'category_id' => $_SESSION['new_resource']['category'],
							'creator_id' => $user->data()->id,
							'name' => Output::getClean($_SESSION['new_resource']['name']),
							'description' => $content,
							'contributors' => ((isset($_SESSION['new_resource']['contributors']) && !is_null($_SESSION['new_resource']['contributors'])) ? Output::getClean($_SESSION['new_resource']['contributors']) : null),
							'created' => date('U'),
							'updated' => date('U'),
							'github_url' => 'none',
							'github_username' => 'none',
							'github_repo_name' => 'none',
							'latest_version' => Output::getClean($version),
							'type' => $type,
							'price' => $price
						));

						$resource_id = $queries->getLastId();

						$queries->create('resources_releases', array(
							'resource_id' => $resource_id,
							'category_id' => $_SESSION['new_resource']['category'],
							'release_title' => Output::getClean($version),
							'release_description' => $content,
							'release_tag' => Output::getClean($version),
							'created' => date('U'),
							'download_link' => Output::getClean($_POST['link'])
						));

						// Hook
						$new_resource_category = $queries->getWhere('resources_categories', array('id', '=', $_SESSION['new_resource']['category']));

						if(count($new_resource_category))
							$new_resource_category = Output::getClean($new_resource_category[0]->name);

						else
							$new_resource_category = 'Unknown';

						HookHandler::executeEvent('newResource', array(
							'event' => 'newResource',
							'username' => $user->getDisplayname(),
							'content' => str_replace(array('{x}', '{y}'), array($new_resource_category, Output::getClean($user->data()->nickname)), $resource_language->get('resources', 'new_resource_text')),
							'content_full' => str_replace('&nbsp;', '', strip_tags(Output::getDecoded($content))),
							'avatar_url' => $user->getAvatar(null, 128, true),
							'title' => Output::getClean($_SESSION['new_resource']['name']),
							'url' => rtrim(Util::getSelfURL(), '/') . URL::build('/resources/resource/' . $resource_id . '-' . Util::stringToURL(Output::getClean($_SESSION['new_resource']['name'])))
						));

						unset($_SESSION['new_resource']);

						Redirect::to(URL::build('/resources/resource/' . $resource_id));
						die();

					} else {
						$error = $resource_language->get('resources', 'external_link_error');
					}
				} else {
					$error = $language->get('general', 'invalid_token');
				}
			}
		}
	} else
		$error = $language->get('general', 'invalid_token');
}

$page_title = $resource_language->get('resources', 'new_resource');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

if(!isset($_GET['step'])){
	$categories = $resources->getCategories($groups);

	// Assign to Smarty array
	$categories_array = array();
	foreach($categories as $category){
        $categories_array[] = array(
            'name' => Output::getClean($category->name),
            'id' => $category->id
        );
	}
	$categories = null;

	// Assign post content if it already exists
	if(isset($_POST['description'])) $smarty->assign('CONTENT', Output::getClean($_POST['description']));
	else $smarty->assign('CONTENT', '');

	// Markdown or HTML?
	$cache->setCache('post_formatting');
	$formatting = $cache->retrieve('formatting');

	if($formatting == 'markdown'){
		// Markdown
		$smarty->assign('MARKDOWN', true);
		$smarty->assign('MARKDOWN_HELP', $language->get('general', 'markdown_help'));
	}

	// Errors?
	if(isset($error)) $smarty->assign('ERROR', $error);

	// Assign Smarty variables
	$smarty->assign(array(
		'IN_CATEGORY' => $resource_language->get('resources', 'in_category'),
		'CATEGORIES' => $categories_array,
		'SELECT_CATEGORY' => $resource_language->get('resources', 'select_category'),
		'REQUIRED' => $resource_language->get('resources', 'required'),
		'RESOURCE_NAME' => $resource_language->get('resources', 'resource_name'),
		'RESOURCE_DESCRIPTION' => $resource_language->get('resources', 'resource_description'),
		'CONTRIBUTORS' => $resource_language->get('resources', 'contributors'),
		'RELEASE_TYPE' => $resource_language->get('resources', 'release_type'),
		'ZIP_FILE' => $resource_language->get('resources', 'zip_file'),
		'GITHUB_RELEASE' => $resource_language->get('resources', 'github_release'),
		'EXTERNAL_DOWNLOAD' => $resource_language->get('resources', 'external_download')
	));

	$template_file = 'resources/new_resource.tpl';

} else {
	switch($_GET['step']){
		case 'github':
			// Errors?
			if(isset($error)) $smarty->assign('ERROR', $error);

			$smarty->assign(array(
				'GITHUB_USERNAME' => $resource_language->get('resources', 'github_username'),
				'GITHUB_REPO_NAME' => $resource_language->get('resources', 'github_repo_name'),
				'REQUIRED' => $resource_language->get('resources', 'required')
			));

			$template_file = 'resources/new_resource_github.tpl';

			break;

		case 'release':
			// Select release
			if(isset($error)) $smarty->assign('ERROR', $error);

			// Assign Smarty variables
			$smarty->assign(array(
				'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource'),
				'CANCEL' => $language->get('general', 'cancel'),
				'CANCEL_LINK' => URL::build('/resources'),
				'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
				'SELECT_RELEASE' => $resource_language->get('resources', 'select_release'),
				'RELEASES' => $_SESSION['github_releases']
			));

			$template_file = 'resources/new_resource_select_release.tpl';

			break;

		case 'type':
			if(!isset($_SESSION['new_resource'])){
				Redirect::to(URL::build('/resources/new'));
				die();
			}

			$category = $queries->getWhere('resources_categories', array('id', '=', $_SESSION['new_resource']['category']));
			if(!count($category)){
				Redirect::to(URL::build('/resources/new'));
				die();
			}
			$category = $category[0];

			$permission = $resources->getAvailableResourceTypes($groups, $category->id);

			if (!$permission->post) {
				Redirect::to(URL::build('/resources/new'));
				die();
			}

			if (!$permission->premium) {
				Redirect::to(URL::build('/resources/new/', 'step=upload'));
				die();
			}

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
				'TYPE' => $resource_language->get('resources', 'type'),
				'FREE_RESOURCE' => $resource_language->get('resources', 'free_resource'),
				'PREMIUM_RESOURCE' => $resource_language->get('resources', 'premium_resource'),
				'PREMIUM_RESOURCE_PRICE' => $resource_language->get('resources', 'price'),
				'CURRENCY' => Output::getClean($currency)
			));

			if(isset($error)) $smarty->assign('ERROR', $error);

			$user_premium_details = $queries->getWhere('resources_users_premium_details', array('user_id', '=', $user->data()->id));
			if(!count($user_premium_details) || !$user_premium_details[0]->paypal_email){
				$smarty->assign('NO_PAYMENT_EMAIL', $resource_language->get('resources', 'no_payment_email'));
			}

			$template_file = 'resources/new_resource_type.tpl';

			break;

		case 'upload':
			if(isset($error)) $smarty->assign('ERROR', $error);

			$smarty->assign(array(
				'CHOOSE_FILE' => $resource_language->get('resources', 'choose_file'),
				'ZIP_ONLY' => $resource_language->get('resources', 'zip_only'),
				'VERSION_TAG' => $resource_language->get('resources', 'version_tag')
			));

			$template_file = 'resources/new_resource_upload.tpl';

			break;

		case 'link':
			if(isset($error)) $smarty->assign('ERROR', $error);

			$smarty->assign(array(
				'EXTERNAL_LINK' => $resource_language->get('resources', 'external_link'),
				'VERSION_TAG' => $resource_language->get('resources', 'version_tag')
			));

			$template_file = 'resources/new_resource_external_link.tpl';

			break;

		default:
			Redirect::to(URL::build('/resources/new'));
			die();

			break;
	}

}

$smarty->assign(array(
	'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource'),
	'CANCEL' => $language->get('general', 'cancel'),
	'CANCEL_LINK' => URL::build('/resources'),
	'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
	'SUBMIT' => $language->get('general', 'submit'),
	'TOKEN' => Token::get()
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
	$template->addCSSFiles(array(
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/css/spoiler.css' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/prism/prism.css' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/plugins/spoiler/css/spoiler.css' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/css/emojione.min.css' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emoji/css/emojione.sprites.css' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/emojionearea/css/emojionearea.min.css' => array(),
	));
	$template->addJSFiles(array(
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/prism/prism.js' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/plugins/spoiler/js/spoiler.js' => array(),
		(defined('CONFIG_PATH') ? CONFIG_PATH : '') . '/core/assets/plugins/tinymce/tinymce.min.js' => array()
	));

	$template->addJSScript(Input::createTinyEditor($language, 'reply'));
}

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

$template->displayTemplate($template_file, $smarty);
