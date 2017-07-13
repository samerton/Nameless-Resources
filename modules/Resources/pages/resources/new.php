<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr2
 *
 *  License: MIT
 *
 *  Resources creation page
 */

// Section disabled?
// TODO

// Always define page name
define('PAGE', 'resources');

// Ensure user is logged in
if(!$user->isLoggedIn()){
	Redirect::to(URL::build('/resources'));
	die();
}

// Handle input
if(Input::exists()){
	if(Token::check(Input::get('token'))){
		if(!isset($_POST['release'])){
			// Validate input
			$validate = new Validate();
			
			$validation = $validate->check($_POST, array(
				'name' => array(
					'required' => true,
					'min' => 2,
					'max' => 32
				),
				'category' => array(
					'required' => true
				),
				'content' => array(
					'required' => true,
					'min' => 2,
					'max' => 20000
				),
				'github_username' => array(
					'required' => true,
					'min' => 2,
					'max' => 32
				),
				'github_repo' => array(
					'required' => true,
					'min' => 2,
					'max' => 32
				),
				'contributors' => array(
					'max' => 255
				)
			));
			
			if($validation->passed()){
			  // Check permissions
        $permissions = $queries->getWhere('resources_categories_permissions', array('category_id', '=', $_POST['category']));
        if(!count($permissions)){
            Redirect::to(URL::build('/resources'));
            die();
        }

        foreach($permissions as $permission){
            if($permission->group_id == $user->data()->group_id && $permission->post == 1)
                $has_permission = 1;
        }
        if(!isset($has_permission)){
            Redirect::to(URL::build('/resources'));
            die();
        }

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
					
					$github_query = json_decode($github_query);
					
					if(!isset($github_query[0])) $error = str_replace('{x}', Output::getClean($_POST['github_username']) . '/' . Output::getClean($_POST['github_repo']), $resource_language->get('resources', 'unable_to_get_repo'));
					else {
						// Valid response
						$_SESSION['post_data'] = $_POST;
						
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
					
				} catch(Exception $e){
					$error = $e->getMessage();
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
							case (strpos($item, 'github_username') !== false):
								$errors[] = $resource_language->get('resources', 'github_username_required');
							break;
							case (strpos($item, 'github_repo') !== false):
								$errors[] = $resource_language->get('resources', 'github_repo_required');
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
							case (strpos($item, 'github_username') !== false):
								$errors[] = $resource_language->get('resources', 'github_username_min_2');
							break;
							case (strpos($item, 'github_repo') !== false):
								$errors[] = $resource_language->get('resources', 'github_repo_min_2');
							break;
						}
					} else if(strpos($item, 'maximum') !== false){
						switch($item){
							case (strpos($item, 'name') !== false):
								$errors[] = $resource_language->get('resources', 'name_max_32');
							break;
							case (strpos($item, 'content') !== false):
								$errors[] = $resource_language->get('resources', 'content_max_20000');
							break;
							case (strpos($item, 'github_username') !== false):
								$errors[] = $resource_language->get('resources', 'github_username_max_32');
							break;
							case (strpos($item, 'github_repo') !== false):
								$errors[] = $resource_language->get('resources', 'github_repo_max_32');
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
			// Validate release
			if(!isset($_SESSION['post_data'])){
				Redirect::to(URL::build('/resources/new'));
				die();
			}

      // Check permissions
      $permissions = $queries->getWhere('resources_categories_permissions', array('category_id', '=', $_SESSION['post_data']['category']));
      if(!count($permissions)){
          Redirect::to(URL::build('/resources'));
          die();
      }

      foreach($permissions as $permission){
          if($permission->group_id == $user->data()->group_id && $permission->post == 1)
              $has_permission = 1;
      }
      if(!isset($has_permission)){
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
				curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/' . Output::getClean($_SESSION['post_data']['github_username']) . '/' . Output::getClean($_SESSION['post_data']['github_repo']) . '/releases/' . Output::getClean($_POST['release']));
				
				if(!$github_query = curl_exec($ch)){
					$error = curl_error($ch);
				}
				
				curl_close($ch);
				
				$github_query = json_decode($github_query);
				
				if(!isset($github_query->id)) $error = str_replace('{x}', Output::getClean($_POST['github_username']) . '/' . Output::getClean($_POST['github_repo']), $resource_language->get('resources', 'unable_to_get_repo'));
				else {
					// Valid response
					// Create resource
					$queries->create('resources', array(
						'category_id' => $_SESSION['post_data']['category'],
						'creator_id' => $user->data()->id,
						'name' => Output::getClean($_SESSION['post_data']['name']),
						'description' => Output::getClean(nl2br($_SESSION['post_data']['content'])),
						'contributors' => ((isset($_SESSION['post_data']['contributors']) && !is_null($_SESSION['post_data']['contributors'])) ? Output::getClean($_SESSION['post_data']['contributors']) : null),
						'created' => date('U'),
						'updated' => date('U'),
						'github_url' => 'https://github.com/' . Output::getClean($_SESSION['post_data']['github_username']) . '/' . Output::getClean($_SESSION['post_data']['github_repo']),
						'github_username' => Output::getClean($_SESSION['post_data']['github_username']),
						'github_repo_name' => Output::getClean($_SESSION['post_data']['github_repo']),
						'latest_version' => Output::getClean($github_query->tag_name)
					));
					
					$resource_id = $queries->getLastId();
					
					$queries->create('resources_releases', array(
						'resource_id' => $resource_id,
						'category_id' => $_SESSION['post_data']['category'],
						'release_title' => Output::getClean($github_query->name),
						'release_description' => Output::getPurified($github_query->body),
						'release_tag' => Output::getClean($github_query->tag_name),
						'created' => date('U'),
						'download_link' => Output::getClean($github_query->html_url)
					));
					
					unset($_SESSION['post_data']);
					
					Redirect::to(URL::build('/resources/resource/', 'id=' . $resource_id));
					die();
				}
				
			} catch(Exception $e){
				$error = $e->getMessage();
			}
		}
	} else
		  $error = $language->get('general', 'invalid_token');
}
?>

<!DOCTYPE html>
<html lang="<?php echo (defined('HTML_LANG') ? HTML_LANG : 'en'); ?>">
  <head>
    <!-- Standard Meta -->
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

    <!-- Site Properties -->
	  <?php
	  $title = $resource_language->get('resources', 'new_resource');
	  require('core/templates/header.php');
	  ?>
  
	  <link rel="stylesheet" href="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/ckeditor/plugins/spoiler/css/spoiler.css">
    <link rel="stylesheet" href="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/emoji/css/emojione.min.css"/>
    <link rel="stylesheet" href="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/emojionearea/css/emojionearea.min.css"/>
  
  </head>

  <body>
  <?php
	require('core/templates/navbar.php');
	require('core/templates/footer.php'); 
	
	if(!isset($releases_array)){
		// Obtain categories + permissions from database
		$categories = $queries->getWhere('resources_categories', array('id', '<>', 0));
    $permissions = $queries->getWhere('resources_categories_permissions', array('group_id', '=', $user->data()->group_id));
		
		// Assign to Smarty array
		$categories_array = array();
    foreach($categories as $category){
        // Check permissions
        foreach($permissions as $permission){
            if($permission->category_id == $category->id && $permission->post == 1)
                $categories_array[] = array(
                    'name' => Output::getClean($category->name),
                    'id' => $category->id
                );
        }
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
			'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource'),
			'CANCEL' => $language->get('general', 'cancel'),
			'CANCEL_LINK' => URL::build('/resources'),
			'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
			'IN_CATEGORY' => $resource_language->get('resources', 'in_category'),
			'CATEGORIES' => $categories_array,
			'SELECT_CATEGORY' => $resource_language->get('resources', 'select_category'),
			'GITHUB_USERNAME' => $resource_language->get('resources', 'github_username'),
			'GITHUB_REPO_NAME' => $resource_language->get('resources', 'github_repo_name'),
			'REQUIRED' => $resource_language->get('resources', 'required'),
			'RESOURCE_NAME' => $resource_language->get('resources', 'resource_name'),
			'RESOURCE_DESCRIPTION' => $resource_language->get('resources', 'resource_description'),
			'CONTRIBUTORS' => $resource_language->get('resources', 'contributors'),
			'SUBMIT' => $language->get('general', 'submit'),
			'TOKEN' => Token::get()
		));
		
		// Load Smarty template
		$smarty->display('custom/templates/' . TEMPLATE . '/resources/new_resource.tpl');
		
	} else {
		// Select release
		if(isset($error)) $smarty->assign('ERROR', $error);
		
		// Assign Smarty variables
		$smarty->assign(array(
			'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource'),
			'CANCEL' => $language->get('general', 'cancel'),
			'CANCEL_LINK' => URL::build('/resources'),
			'CONFIRM_CANCEL' => $language->get('general', 'confirm_cancel'),
			'SELECT_RELEASE' => $resource_language->get('resources', 'select_release'),
			'RELEASES' => $releases_array,
			'SUBMIT' => $language->get('general', 'submit'),
			'TOKEN' => Token::get()
		));
		
		// Display template
		$smarty->display('custom/templates/' . TEMPLATE . '/resources/new_resource_select_release.tpl');
	}
	
	require('core/templates/scripts.php'); 
	
	// Display either Markdown or HTML editor
	if(!isset($formatting)){
		$cache->setCache('post_formatting');
		$formatting = $cache->retrieve('formatting');
	}
	?>
  <script src="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/emoji/js/emojione.min.js"></script>
  <?php
	if($formatting == 'markdown'){
	?>
	<script src="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/emojionearea/js/emojionearea.min.js"></script>
	
	<script type="text/javascript">
	  $(document).ready(function() {
	    var el = $("#markdown").emojioneArea({
			pickerPosition: "bottom"
		});
	  });
	</script>
	<?php } else { ?>
	<script src="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/ckeditor/plugins/spoiler/js/spoiler.js"></script>
	<script src="<?php if(defined('CONFIG_PATH')) echo CONFIG_PATH . '/'; else echo '/'; ?>core/assets/plugins/ckeditor/ckeditor.js"></script>
  <?php echo '<script>' . Input::createEditor('reply') . '</script>'; ?>
	<?php } ?>
  </body>
</html>