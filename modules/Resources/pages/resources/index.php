<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr2
 *
 *  License: MIT
 *
 *  Resources index page
 */

// Section disabled?
// TODO

// Always define page name
define('PAGE', 'resources');

// Initialise
$timeago = new Timeago();
$paginator = new Paginator();

// Get page
if(isset($_GET['p'])){
	if(!is_numeric($_GET['p'])){
		Redirect::to(URL::build('/resources'));
		die();
	} else {
		if($_GET['p'] == 1){ 
			// Avoid bug in pagination class
			Redirect::to(URL::build('/resources'));
			die();
		}
		$p = $_GET['p'];
	}
} else {
	$p = 1;
}

// Get user group ID
if($user->isLoggedIn()) $user_group = $user->data()->group_id; else $user_group = 0;
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
	$title = $resource_language->get('resources', 'resources');
	require('core/templates/header.php'); 
	?>
  
  </head>

  <body>
    <?php 
	require('core/templates/navbar.php'); 
	require('core/templates/footer.php'); 
	
	// Obtain categories + permissions from database
	$categories = $queries->getWhere('resources_categories', array('id', '<>', 0));
  $permissions = $queries->getWhere('resources_categories_permissions', array('group_id', '=', $user_group));

	// Assign to Smarty array
	$category_array = array();
	foreach($categories as $category){
		// Check permissions
    foreach($permissions as $permission){
        if($permission->category_id == $category->id && $permission->view == 1)
            $category_array[] = array(
                'name' => Output::getClean($category->name),
                'link' => URL::build('/resources/category/', 'id=' . $category->id)
            );
    }
	}
	$categories = null;
	
	// Get latest releases
	$latest_releases = $queries->orderWhere('resources_releases', 'id <> 0', 'created', 'DESC');
	
	// Pagination
	$results = $paginator->getLimited($latest_releases, 10, $p, count($latest_releases));
	$pagination = $paginator->generate(7, URL::build('/resources/', true));
	
	$smarty->assign('PAGINATION', $pagination);

	// Array to pass to template
	$releases_array = array();
	
	if(count($latest_releases)){
		// Display the correct number of resources
		$n = 0;
		
		while($n < count($results->data) && isset($results->data[$n]->resource_id)){
      // Check permissions
      foreach($permissions as $permission){
          if($permission->category_id == $results->data[$n]->category_id && $permission->view == 1){
              // Have view permission
          } else continue;
      }
			// Get actual resource info
			$resource = $queries->getWhere('resources', array('id', '=', $results->data[$n]->resource_id));
			if(!count($resource))
				  continue;
			
			$resource = $resource[0];
			
			// Get category
			$category = $queries->getWhere('resources_categories', array('id', '=', $resource->category_id));
			if(count($category)){
				  $category = Output::getClean($category[0]->name);
			} else {
				  $category = 'n/a';
			}

			if(!isset($releases_array[$resource->id])){
				$releases_array[$resource->id] = array(
					'link' => URL::build('/resources/resource/', 'id=' . $resource->id),
					'name' => Output::getClean($resource->name),
					'description' => substr(Output::getPurified(preg_replace("/<img[^>]+\>/i", "(image) ", htmlspecialchars_decode($resource->description))), 0, 50),
					'author' => Output::getClean($user->idToNickname($resource->creator_id)),
					'author_style' => $user->getGroupClass($resource->creator_id),
					'author_profile' => URL::build('/profile/' . Output::getClean($user->idToName($resource->creator_id))),
					'author_avatar' => $user->getAvatar($resource->creator_id, '../', 30),
					'downloads' => str_replace('{x}', $resource->downloads, $resource_language->get('resources', 'x_downloads')),
					'views' => str_replace('{x}', $resource->views, $resource_language->get('resources', 'x_views')),
					'rating' => round($resource->rating / 10),
					'version' => $resource->latest_version,
					'category' => str_replace('{x}', $category, $resource_language->get('resources', 'in_category_x')),
					'updated' => str_replace('{x}', $timeago->inWords(date('d M Y, H:i', $resource->updated), $language->getTimeLanguage()), $resource_language->get('resources', 'updated_x')),
					'updated_full' => date('d M Y, H:i', $resource->updated)
				);
			}
			
			$n++;
		}
	} else $releases_array = null;
	
	// Assign Smarty variables
	$smarty->assign(array(
		'RESOURCES' => $resource_language->get('resources', 'resources'),
		'CATEGORIES_TITLE' => $resource_language->get('resources', 'categories'),
		'CATEGORIES' => $category_array,
		'LATEST_RESOURCES' => $releases_array,
		'PAGINATION' => $pagination,
		'NO_RESOURCES' => $resource_language->get('resources', 'no_resources'),
		'RESOURCE' => $resource_language->get('resources', 'resource'),
		'STATS' => $resource_language->get('resources', 'stats'),
		'AUTHOR' => $resource_language->get('resources', 'author')
	));
	
	if($user->isLoggedIn()){
		// TODO: permissions
		$smarty->assign(array(
			'NEW_RESOURCE_LINK' => URL::build('/resources/new'),
			'NEW_RESOURCE' => $resource_language->get('resources', 'new_resource')
		));
	}
	
	// Load Smarty template
	$smarty->display('custom/templates/' . TEMPLATE . '/resources/index.tpl'); 
	
	require('core/templates/scripts.php'); 
	?>
  </body>
</html>