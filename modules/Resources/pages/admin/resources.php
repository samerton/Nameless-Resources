<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr2
 *
 *  License: MIT
 *
 *  Resources module - admin resources page
 */

// Can the user view the AdminCP?
if($user->isLoggedIn()){
	if(!$user->canViewACP()){
		// No
		Redirect::to(URL::build('/'));
		die();
	} else {
		// Check the user has re-authenticated
		if(!$user->isAdmLoggedIn()){
			// They haven't, do so now
			Redirect::to(URL::build('/admin/auth'));
			die();
		}
	}
} else {
	// Not logged in
	Redirect::to(URL::build('/login'));
	die();
}
 
 
$page = 'admin';
$admin_page = 'resources';
?>
<!DOCTYPE html>
<html lang="<?php echo (defined('HTML_LANG') ? HTML_LANG : 'en'); ?>">
  <head>
    <!-- Standard Meta -->
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
	
	<?php 
	$title = $language->get('admin', 'admin_cp');
	require('core/templates/admin_header.php'); 
	?>
  
	<!-- Custom style -->
	<style>
	textarea {
		resize: none;
	}
	</style>

  </head>

  <body>
    <?php require('modules/Core/pages/admin/navbar.php'); ?>
    <div class="container">
	  <div class="row">
		<div class="col-md-3">
		  <?php require('modules/Core/pages/admin/sidebar.php'); ?>
		</div>
		<div class="col-md-9">
		  <div class="card">
		    <div class="card-block">
			    <h3 style="display:inline;"><?php echo $resource_language->get('resources', 'resources'); ?></h3>
			  <?php
			  if(!isset($_GET['view'])){
				if(!isset($_GET['action']) && !isset($_GET['category'])){
				?>
				<span class="pull-right"><a href="<?php echo URL::build('/admin/resources/', 'action=new'); ?>" class="btn btn-primary"><?php echo $resource_language->get('resources', 'new_category'); ?></a></span>
				<br /><br />
				<?php 
				if(Session::exists('adm-resources')){
					echo Session::flash('adm-resources');
				}
				$categories = $queries->orderAll('resources_categories', 'display_order', 'ASC');

				// Form token
				$token = Token::get();
				?>

				<div class="panel panel-default">
				  <div class="panel-heading">
				    <?php echo $resource_language->get('resources', 'categories'); ?>
				  </div>
				  <div class="panel-body">
					<?php 
					$number = count($categories);
					$i = 1;
					foreach($categories as $category){
					?>
					<div class="row">
					  <div class="col-md-9">
						<?php echo '<a href="' . URL::build('/admin/resources/', 'category=' . $category->id) . '">' . Output::getPurified(htmlspecialchars_decode($category->name)) . '</a><br />' . Output::getPurified(htmlspecialchars_decode($category->description)); ?>
					  </div>
					  <div class="col-md-3">
						<span class="pull-right">
						  <?php if($i != 1){ ?><a href="<?php echo URL::build('/admin/resources/', 'action=order&dir=up&cid=' . $category->id); ?>" class="btn btn-success btn-sm"><i class="fa fa-chevron-up" aria-hidden="true"></i></a><?php } ?>
						  <?php if($i != $number){ ?><a href="<?php echo URL::build('/admin/resources/', 'action=order&dir=down&cid=' . $category->id);?>" class="btn btn-danger btn-sm"><i class="fa fa-chevron-down" aria-hidden="true"></i></a><?php } ?>
						  <a href="<?php echo URL::build('/admin/resources/', 'action=delete&cid=' . $category->id);?>" class="btn btn-warning btn-sm"><i class="fa fa-trash" aria-hidden="true"></i></a>
						</span>
					  </div>
					</div>
					<?php 
						if($i != $number) echo '<hr />';
						$i++;
					}
					?>
				  </div>
				</div>
				<?php 
				} else if(isset($_GET['action'])){
					if($_GET['action'] == 'new'){
						// Category creation wizard
						echo '<hr /><h4>' . $resource_language->get('resources', 'creating_category') . '</h4>';
						
						if(!isset($_GET['step'])){
							if(Input::exists()){
								if(Token::check(Input::get('token'))){
									// Validate input
									$validate = new Validate();
									$validation = $validate->check($_POST, array(
										'catname' => array(
											'required' => true,
											'min' => 2,
											'max' => 150
										),
										'catdesc' => array(
											'max' => 255
										)
									));
									
									if($validation->passed()){
										// Create the category
										try {
											$description = Input::get('catdesc');
											
											$last_cat_order = $queries->orderAll('resources_categories', 'display_order', 'DESC');
											if(count($last_cat_order)) $last_cat_order = $last_cat_order[0]->display_order;
											else $last_cat_order = 0;
											
											$queries->create('resources_categories', array(
												'name' => htmlspecialchars(Input::get('catname')),
												'description' => htmlspecialchars($description),
												'display_order' => $last_cat_order + 1
											));
											
											$cat_id = $queries->getLastId();
											
											Redirect::to(URL::build('/admin/resources/', 'action=new&step=2&category=' . $cat_id));
											die();
											
										} catch(Exception $e){
											$error = '<div class="alert alert-danger">Unable to create category: ' . $e->getMessage() . '</div>';
										}
									} else {
										$error = '<div class="alert alert-danger">';
										foreach($validation->errors() as $item) {
											  if(strpos($item, 'is required') !== false){
												switch($item){
													case (strpos($item, 'catname') !== false):
														$error .= $resource_language->get('resources', 'input_category_title') . '<br />';
													break;
												}
											  } else if(strpos($item, 'minimum') !== false){
												switch($item){
													case (strpos($item, 'catname') !== false):
														$error .= $resource_language->get('resources', 'category_name_minimum') . '<br />';
													break;
												}
											  } else if(strpos($item, 'maximum') !== false){
												switch($item){
													case (strpos($item, 'catname') !== false):
														$error .= $resource_language->get('resources', 'category_name_maximum') . '<br />';
													break;
													case (strpos($item, 'catdesc') !== false):
														$error .= $resource_language->get('resources', 'category_description_maximum') . '<br />';
													break;
												}
											  }
										}
										$error .= '</div>';
									}
								} else {
									// Invalid token
									$error = '<div class="alert alert-danger">' . $language->get('general', 'invalid_token') . '</div>';
								}
							}
							if(isset($error)) echo $error;
							?>
							<form action="" method="post">
							  <div class="form-group">
								<input class="form-control" type="text" name="catname" id="catname" value="<?php echo Output::getClean(Input::get('catname')); ?>" placeholder="<?php echo $resource_language->get('resources', 'category_name'); ?>" autocomplete="off">
							  </div>
							  <div class="form-group">
								<textarea name="catdesc" placeholder="<?php echo $resource_language->get('resources', 'category_description'); ?>" class="form-control" rows="3"><?php echo Output::getClean(Input::get('catdesc')); ?></textarea>
							  </div>
							  <div class="form-group">
							    <input type="hidden" name="token" value="<?php echo Token::get(); ?>">
								<input type="submit" class="btn btn-primary" value="<?php echo $language->get('general', 'submit'); ?>">
							  </div>
							</form>
						<?php
						} else {
							if(!isset($_GET['category']) || !is_numeric($_GET['category'])){
								Redirect::to(URL::build('/admin/resources'));
								die();
							}
							
							// Get category from database
							$category = $queries->getWhere('resources_categories', array('id', '=', $_GET['category']));
							if(!count($category)){
								Redirect::to(URL::build('/admin/resources'));
								die();
							} else $category = $category[0];
									// Permissions
									// Obtain list of groups and permissions
									$groups = $queries->getWhere('groups', array('id', '<>', 0));
									$group_perms = $queries->getWhere('resources_categories_permissions', array('category_id', '=', $category->id));
									
									// Deal with input
									if(Input::exists()){
										if(Token::check(Input::get('token'))){
											// Guest category permissions
											$view = Input::get('perm-view-0');
											$post = 0;
											$move = 0;
											$edit_resource = 0;
											$delete_resource = 0;
											$edit_review = 0;
											$delete_review = 0;
											
											if(!($view)) $view = 0;
											
											$cat_perm_exists = 0;
											
											$cat_perm_query = $queries->getWhere('resources_categories_permissions', array('category_id', '=', $category->id));
											if(count($cat_perm_query)){
												foreach($cat_perm_query as $query){
													if($query->group_id == 0){
														$cat_perm_exists = 1;
														$update_id = $query->id;
														break;
													}
												}
											}
											
											try {
												if($cat_perm_exists != 0){ // Permission already exists, update
													// Update the category
													$queries->update('resources_categories_permissions', $update_id, array(
														'view' => $view,
														'post' => $post,
														'move_resource' => $move,
														'edit_resource' => $edit_resource,
                            'delete_resource' => $delete_resource,
                            'edit_review' => $edit_review,
                            'delete_review' => $delete_review
													));
												} else { // Permission doesn't exist, create
													$queries->create('resources_categories_permissions', array(
														'group_id' => 0,
														'category_id' => $category->id,
                            'view' => $view,
                            'post' => $post,
                            'move_resource' => $move,
                            'edit_resource' => $edit_resource,
                            'delete_resource' => $delete_resource,
                            'edit_review' => $edit_review,
                            'delete_review' => $delete_review
													));
												}
												
											} catch(Exception $e) {
												die($e->getMessage());
											}
											
											// Group category permissions
											foreach($groups as $group){
												$view = Input::get('perm-view-' . $group->id);
												$post = Input::get('perm-post-' . $group->id);
												$move = Input::get('perm-move-' . $group->id);
												$edit_resource = Input::get('perm-edit_resource-' . $group->id);
												$delete_resource = Input::get('perm-delete_resource-' . $group->id);
												$edit_review = Input::get('perm-edit_review-' . $group->id);
												$delete_review = Input::get('perm-delete_review-' . $group->id);
												
												if(!($view)) $view = 0;
												if(!($post)) $post = 0;
												if(!($move)) $move = 0;
												if(!($edit_resource)) $edit_resource = 0;
												if(!($delete_resource)) $delete_resource = 0;
												if(!($edit_review)) $edit_review = 0;
												if(!($delete_review)) $delete_review = 0;
												
												$cat_perm_exists = 0;

												if(count($cat_perm_query)){
													foreach($cat_perm_query as $query){
														if($query->group_id == $group->id){
															$cat_perm_exists = 1;
															$update_id = $query->id;
															break;
														}
													}
												}
												
												try {
													if($cat_perm_exists != 0){ // Permission already exists, update
														// Update the category
														$queries->update('resources_categories_permissions', $update_id, array(
                                'view' => $view,
                                'post' => $post,
                                'move_resource' => $move,
                                'edit_resource' => $edit_resource,
                                'delete_resource' => $delete_resource,
                                'edit_review' => $edit_review,
                                'delete_review' => $delete_review
														));
													} else { // Permission doesn't exist, create
														$queries->create('resources_categories_permissions', array(
															'group_id' => $group->id,
															'category_id' => $category->id,
                              'view' => $view,
                              'post' => $post,
                              'move_resource' => $move,
                              'edit_resource' => $edit_resource,
                              'delete_resource' => $delete_resource,
                              'edit_review' => $edit_review,
                              'delete_review' => $delete_review
														));
													}
													
												} catch(Exception $e) {
													die($e->getMessage());
												}
											}
											
											Session::flash('adm-categories', '<div class="alert alert-success">' . $resource_language->get('resources', 'category_created_successfully') . '</div>');
											Redirect::to(URL::build('/admin/resources'));
											die();
										} else {
											$error = '<div class="alert alert-danger">' . $language->get('general', 'invalid_token') . '</div>';
										}
									}
									?>
							<script>
							var groups = [];
							groups.push("0");
							</script>

							<input type="hidden" name="perm-post-0" value="0" />
							<input type="hidden" name="perm-move_resource-0" value="0" />
							<input type="hidden" name="perm-edit_resource-0" value="0" />
              <input type="hidden" name="perm-delete_resource-0" value="0" />
              <input type="hidden" name="perm-edit_review-0" value="0" />
              <input type="hidden" name="perm-delete_review-0" value="0" />

							<form action="" method="post">
							  <strong><?php echo $resource_language->get('resources', 'category_permissions'); ?></strong>
                <?php if(isset($error)) echo '<div class="alert alert-danger">' . $error . '</div>'; ?>
							  <table class="table table-striped">
								<thead>
								  <tr>
									<th><?php echo $resource_language->get('resources', 'group'); ?></th>
									<th><?php echo $resource_language->get('resources', 'can_view_category'); ?></th>
									<th><?php echo $resource_language->get('resources', 'can_post_resource'); ?></th>
								  </tr>
								</thead>
								<tbody>
								  <tr>
									<td><?php echo $language->get('user', 'guests'); ?></td>
									<td><input type="hidden" name="perm-view-0" value="0" /><input onclick="colourUpdate(this);" name="perm-view-0" id="Input-view-0" value="1" type="checkbox"<?php if(isset($view) && $view == 1){ echo ' checked'; } ?>></td>
									<td>&nbsp;</td>
								  </tr>
								  <?php
									foreach($groups as $group){
										// Get the existing group permissions
										$view = 0;
										$post = 0;
										
										foreach($group_perms as $group_perm){
											if($group_perm->group_id == $group->id){
												$view = $group_perm->view;
												$post = $group_perm->post;
												break;
											}
										}
								  ?>
								  <tr>
									<td onclick="toggleAll(this);"><?php echo htmlspecialchars($group->name); ?></td>
									<td><input type="hidden" name="perm-view-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-view-<?php echo $group->id; ?>" id="Input-view-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($view) && $view == 1){ echo ' checked'; } ?>></td>
									<td><input type="hidden" name="perm-post-<?php echo $group->id; ?>" value="0" /><input onclick="colourUpdate(this);" name="perm-post-<?php echo $group->id; ?>" id="Input-post-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($post) && $post == 1){ echo ' checked'; } ?>></td>
								  </tr>
								  <script>groups.push("<?php echo $group->id; ?>");</script>
								  <?php
									}
								  ?>
								</tbody>	
							  </table>
                <h4><?php echo $resource_language->get('resources', 'moderation'); ?></h4>
                <table class="table table-striped">
                  <thead>
                  <tr>
                    <th><?php echo $resource_language->get('resources', 'group'); ?></th>
                    <th><?php echo $resource_language->get('resources', 'can_move_resources'); ?></th>
                    <th><?php echo $resource_language->get('resources', 'can_edit_resources'); ?></th>
                    <th><?php echo $resource_language->get('resources', 'can_delete_resources'); ?></th>
                    <th><?php echo $resource_language->get('resources', 'can_edit_reviews'); ?></th>
                    <th><?php echo $resource_language->get('resources', 'can_delete_reviews'); ?></th>
                      <?php
                      foreach($groups as $group){
                      // Get the existing group permissions
                      $move = 0;
                      $edit_resource = 0;
                      $delete_resource = 0;
                      $edit_review = 0;
                      $delete_review = 0;

                      foreach($group_perms as $group_perm){
                          if($group_perm->group_id == $group->id){
                              $move = $group_perm->move_resource;
                              $edit_resource = $group_perm->edit_resource;
                              $delete_resource = $group_perm->delete_resource;
                              $edit_review = $group_perm->edit_review;
                              $delete_review = $group_perm->delete_review;
                              break;
                          }
                      }
                      ?>
                  <tr>
                    <td onclick="toggleAll(this);"><?php echo htmlspecialchars($group->name); ?></td>
                    <td><input type="hidden" name="perm-move_resource-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-move_resource-<?php echo $group->id; ?>" id="Input-move_resource-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($move) && $move == 1){ echo ' checked'; } ?>></td>
                    <td><input type="hidden" name="perm-edit_resource-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-edit_resource-<?php echo $group->id; ?>" id="Input-edit_resource-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($edit_resource) && $edit_resource == 1){ echo ' checked'; } ?>></td>
                    <td><input type="hidden" name="perm-delete_resource-<?php echo $group->id; ?>" value="0" /><input onclick="colourUpdate(this);" name="perm-delete_resource-<?php echo $group->id; ?>" id="Input-delete_resource-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($delete_resource) && $delete_resource == 1){ echo ' checked'; } ?>></td>
                    <td><input type="hidden" name="perm-edit_review-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-edit_review-<?php echo $group->id; ?>" id="Input-edit_review-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($edit_review) && $edit_review == 1){ echo ' checked'; } ?>></td>
                    <td><input type="hidden" name="perm-delete_review-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-delete_review-<?php echo $group->id; ?>" id="Input-delete_review-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($delete_review) && $delete_review == 1){ echo ' checked'; } ?>></td>
                  </tr>
                  <?php
                  }
                  ?>
                  </tr>
                  </thead>
                </table>
							  <div class="form-group">
							    <input type="hidden" name="token" value="<?php echo Token::get(); ?>">
								<input type="submit" class="btn btn-primary" value="<?php echo $language->get('general', 'submit'); ?>">
							  </div>
							</form>
							<?php
						}
					} else if($_GET['action'] == 'order'){
						if(!isset($_GET['dir']) || !isset($_GET['cid']) || !is_numeric($_GET['cid'])){
							Redirect::to(URL::build('/admin/resources'));
							die();
						}
						if($_GET['dir'] == 'up' || $_GET['dir'] == 'down'){
							$dir = $_GET['dir'];
						} else {
						  Redirect::to(URL::build('/admin/resources'));
							die();
						}
						
						$cat_id = $queries->getWhere('resources_categories', array('id', '=', $_GET['cid']));
						if(!count($cat_id)){
						  Redirect::to(URL::build('/admin/resources'));
						  die();
            }
            $cat_order = $cat_id[0]->display_order;
						$cat_id = $cat_id[0]->id;
						
						$previous_cats = $queries->orderAll('resources_categories', 'display_order', 'ASC');
						
						if($dir == 'up'){
							$n = 0;
							foreach($previous_cats as $previous_cat){
								if($previous_cat->id == $_GET['cid']){
									$previous_cid = $previous_cats[$n - 1]->id;
									$previous_c_order = $previous_cats[$n - 1]->display_order;
									break;
								}
								$n++;
							}

							try {
								$queries->update('resources_categories', $cat_id, array(
									'display_order' => $previous_c_order
								));	
								$queries->update('resources_categories', $previous_cid, array(
									'display_order' => $previous_c_order + 1
								));	
							} catch(Exception $e){
								die($e->getMessage());
							}

							Redirect::to(URL::build('/admin/resources'));
							die();

						} else if($dir == 'down'){
							$n = 0;
							foreach($previous_cats as $previous_cat){
								if($previous_cat->id == $_GET['cid']){
									$previous_cid = $previous_cats[$n + 1]->id;
									$previous_c_order = $previous_cats[$n + 1]->display_order;
									break;
								}
								$n++;
							}
							try {
								$queries->update('resources_categories', $cat_id, array(
									'display_order' => $previous_c_order
								));	
								$queries->update('resources_categories', $previous_cid, array(
									'display_order' => $previous_c_order - 1
								));	
							} catch(Exception $e){
								die($e->getMessage());
							}
							
							Redirect::to(URL::build('/admin/resources'));
							die();
							
						}
						
					} else if($_GET['action'] == 'delete'){
						if(!isset($_GET['cid']) || !is_numeric($_GET['cid'])){
							Redirect::to(URL::build('/admin/resources'));
							die();
						}
						
						if(Input::exists()) {
							if(Token::check(Input::get('token'))) {
								if(Input::get('confirm') === 'true') {
                    if (Input::get('move_resources') === 'none') {
                        $resources = $queries->getWhere('resources', array('category_id', '=', $_GET['cid']));
                        $releases = $queries->getWhere('resources_releases', array('category_id', '=', $_GET['cid']));
                        try {
                            foreach ($resources as $resource) {
                                $queries->delete('resources', array('id', '=', $resource->id));
                                $queries->delete('resources_comments', array('resource_id', '=', $resource->id));
                            }
                            foreach ($releases as $release) {
                                $queries->delete('resources_releases', array('id', '=', $release->id));
                            }
                            $queries->delete('resources_categories', array('id', '=', $_GET['cid']));

                            // Category perm deletion
                            $queries->delete('resources_categories_permissions', array('category_id', '=', $_GET['cid']));

                            Redirect::to(URL::build('/admin/resources'));
                            die();
                        } catch (Exception $e) {
                            die($e->getMessage());
                        }

                    } else {
                        $new_category = Input::get('move_resources');
                        $resources = $queries->getWhere('resources', array('category_id', '=', $_GET['cid']));
                        $releases = $queries->getWhere('resources_releases', array('category_id', '=', $_GET['cid']));
                        try {
                            foreach ($resources as $resource) {
                                $queries->update('resources', $resource->id, array(
                                    'category_id' => $new_category
                                ));
                            }
                            foreach ($releases as $release) {
                                $queries->update('resources_releases', $release->id, array(
                                    'category_id' => $new_category
                                ));
                            }

                            $queries->delete('resources_categories', array('id', '=', $_GET['cid']));

                            // Category perm deletion
                            $queries->delete('resources_categories_permissions', array('category_id', '=', $_GET['cid']));

                            Redirect::to(URL::build('/admin/resources'));
                            die();

                        } catch (Exception $e) {
                            die($e->getMessage());
                        }
                    }
                }
							} else
							    $error = $language->get('general', 'invalid_token');

						}
						?>
						<br /><br />
						<h4><?php echo $resource_language->get('resources', 'delete_category'); ?></h4>
						<form role="form" action="" method="post">
							<strong><?php echo $resource_language->get('resources', 'move_resources_to'); ?></strong>
							<select class="form-control" name="move_resources">
							  <option value="none" selected><?php echo $resource_language->get('resources', 'delete_resources'); ?></option>
							  <?php 
								$categories = $queries->orderAll('resources_categories', 'display_order', 'ASC');
								foreach($categories as $category){
									if($category->id !== $_GET['cid']){
										echo '<option value="' . $category->id . '">' . Output::getPurified(htmlspecialchars_decode($category->name)) . '</option>';
									}
								}
							  ?>
							</select>
						  <input type="hidden" name="token" value="<?php echo Token::get(); ?>">
						  <input type="hidden" name="confirm" value="true">
						  <br />
						  <input type="submit" value="<?php echo $language->get('general', 'submit'); ?>" class="btn btn-danger">
						</form>
						<?php 
					}
				} else if(isset($_GET['category'])){
					$groups = $queries->getWhere('groups', array('id', '<>', '0')); // Get a list of all groups

          // Get category
          $category = $queries->getWhere('resources_categories', array('id', '=', $_GET['category']));
          if(!count($category)) {
              Redirect::to(URL::build('/admin/resources'));
              die();
          }
          $category = $category[0];

          $group_perms = $queries->getWhere('resources_categories_permissions', array('category_id', '=', $category->id));

					if(Input::exists()) {
						if(Token::check(Input::get('token'))) {
								$validate = new Validate();
								$validation = $validate->check($_POST, array(
									'title' => array(
										'required' => true,
										'min' => 2,
										'max' => 150
									),
									'description' => array(
										'max' => 255
									)
								));
								
								if($validation->passed()){
									try {
										// Update the category
										$queries->update('resources_categories', $_GET['category'], array(
											'name' => Output::getClean(Input::get('title')),
											'description' => Output::getClean(Input::get('description'))
										));
										
									} catch(Exception $e) {
										die($e->getMessage());
									}

                  // Guest category permissions
                  $view = Input::get('perm-view-0');
                  $post = 0;
                  $move = 0;
                  $edit_resource = 0;
                  $delete_resource = 0;
                  $edit_review = 0;
                  $delete_review = 0;

                  if(!($view)) $view = 0;

                  $cat_perm_exists = 0;

                  $cat_perm_query = $queries->getWhere('resources_categories_permissions', array('category_id', '=', $category->id));
                  if(count($cat_perm_query)){
                      foreach($cat_perm_query as $query){
                          if($query->group_id == 0){
                              $cat_perm_exists = 1;
                              $update_id = $query->id;
                              break;
                          }
                      }
                  }

                  try {
                      if($cat_perm_exists != 0){ // Permission already exists, update
                          // Update the category
                          $queries->update('resources_categories_permissions', $update_id, array(
                              'view' => $view,
                              'post' => $post,
                              'move_resource' => $move,
                              'edit_resource' => $edit_resource,
                              'delete_resource' => $delete_resource,
                              'edit_review' => $edit_review,
                              'delete_review' => $delete_review
                          ));
                      } else { // Permission doesn't exist, create
                          $queries->create('resources_categories_permissions', array(
                              'group_id' => 0,
                              'category_id' => $category->id,
                              'view' => $view,
                              'post' => $post,
                              'move_resource' => $move,
                              'edit_resource' => $edit_resource,
                              'delete_resource' => $delete_resource,
                              'edit_review' => $edit_review,
                              'delete_review' => $delete_review
                          ));
                      }

                  } catch(Exception $e) {
                      die($e->getMessage());
                  }

                  // Group category permissions
                  foreach($groups as $group){
                      $view = Input::get('perm-view-' . $group->id);
                      $post = Input::get('perm-post-' . $group->id);
                      $move = Input::get('perm-move_resource-' . $group->id);
                      $edit_resource = Input::get('perm-edit_resource-' . $group->id);
                      $delete_resource = Input::get('perm-delete_resource-' . $group->id);
                      $edit_review = Input::get('perm-edit_review-' . $group->id);
                      $delete_review = Input::get('perm-delete_review-' . $group->id);

                      if(!($view)) $view = 0;
                      if(!($post)) $post = 0;
                      if(!($move)) $move = 0;
                      if(!($edit_resource)) $edit_resource = 0;
                      if(!($delete_resource)) $delete_resource = 0;
                      if(!($edit_review)) $edit_review = 0;
                      if(!($delete_review)) $delete_review = 0;

                      $cat_perm_exists = 0;

                      if(count($cat_perm_query)){
                          foreach($cat_perm_query as $query){
                              if($query->group_id == $group->id){
                                  $cat_perm_exists = 1;
                                  $update_id = $query->id;
                                  break;
                              }
                          }
                      }

                      try {
                          if($cat_perm_exists != 0){ // Permission already exists, update
                              // Update the category
                              $queries->update('resources_categories_permissions', $update_id, array(
                                  'view' => $view,
                                  'post' => $post,
                                  'move_resource' => $move,
                                  'edit_resource' => $edit_resource,
                                  'delete_resource' => $delete_resource,
                                  'edit_review' => $edit_review,
                                  'delete_review' => $delete_review
                              ));
                          } else { // Permission doesn't exist, create
                              $queries->create('resources_categories_permissions', array(
                                  'group_id' => $group->id,
                                  'category_id' => $category->id,
                                  'view' => $view,
                                  'post' => $post,
                                  'move_resource' => $move,
                                  'edit_resource' => $edit_resource,
                                  'delete_resource' => $delete_resource,
                                  'edit_review' => $edit_review,
                                  'delete_review' => $delete_review
                              ));
                          }

                      } catch(Exception $e) {
                          die($e->getMessage());
                      }
                  }
									
									Redirect::to(URL::build('/admin/resources'));
									die();
									
								} else {
									echo '<div class="alert alert-danger">';
									foreach($validation->errors() as $error) {
									  if(strpos($error, 'is required') !== false){
										switch($error){
											case (strpos($error, 'title') !== false):
												echo $resource_language->get('resources', 'input_category_title') . '<br />';
											break;
										}
									  } else if(strpos($error, 'minimum') !== false){
										switch($error){
											case (strpos($error, 'title') !== false):
												echo $resource_language->get('resources', 'category_name_minimum') . '<br />';
											break;
										}
									  } else if(strpos($error, 'maximum') !== false){
										switch($error){
											case (strpos($error, 'title') !== false):
												echo $resource_language->get('resources', 'category_name_maximum') . '<br />';
											break;
											case (strpos($error, 'description') !== false):
												echo $resource_language->get('resources', 'category_description_maximum') . '<br />';
											break;
										}
									  }
									}
									unset($error);
									echo '</div>';
								}
						} else
							$error = $language->get('general', 'invalid_token');
					}
					
					// Form token
					$token = Token::get();

					if(count($category)){
						echo '<hr /><h4 style="display: inline;">' . Output::getClean($category->name) . '</h2>';
						?>
						<br /><br />
            <?php if(isset($error)) echo '<div class="alert alert-danger">' . $error . '</div>'; ?>
						<form role="form" action="" method="post">
						  <div class="form-group">
							  <label for="InputTitle"><?php echo $resource_language->get('resources', 'category_name'); ?></label>
							  <input type="text" name="title" class="form-control" id="InputTitle" placeholder="<?php echo $resource_language->get('resources', 'category_name'); ?>" value="<?php echo Output::getPurified(htmlspecialchars_decode($category->name)); ?>">
						  </div>
						  <div class="form-group">
							  <label for="InputDescription"><?php echo $resource_language->get('resources', 'category_description'); ?></label>
							  <textarea name="description" id="InputDescription" placeholder="<?php echo $resource_language->get('resources', 'category_description'); ?>" class="form-control" rows="3"><?php echo Output::getPurified(htmlspecialchars_decode($category->description)); ?></textarea>
						  </div>
							<script>
							var groups = [];
							groups.push("0");
							</script>
              <input type="hidden" name="perm-post-0" value="0" />
              <input type="hidden" name="perm-move_resource-0" value="0" />
              <input type="hidden" name="perm-edit_resource-0" value="0" />
              <input type="hidden" name="perm-delete_resource-0" value="0" />
              <input type="hidden" name="perm-edit_review-0" value="0" />
              <input type="hidden" name="perm-delete_review-0" value="0" />

              <strong><?php echo $resource_language->get('resources', 'category_permissions'); ?></strong>
                <?php if(isset($error)) echo $error; ?>
              <table class="table table-striped">
                <thead>
                <tr>
                  <th><?php echo $resource_language->get('resources', 'group'); ?></th>
                  <th><?php echo $resource_language->get('resources', 'can_view_category'); ?></th>
                  <th><?php echo $resource_language->get('resources', 'can_post_resource'); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <?php
                    // Get the existing group permissions
                    $view = 0;

                    foreach($group_perms as $group_perm){
                        if($group_perm->group_id == 0){
                            $view = $group_perm->view;
                            break;
                        }
                    }
                    ?>
                  <td><?php echo $language->get('user', 'guests'); ?></td>
                  <td><input type="hidden" name="perm-view-0" value="0" /><input onclick="colourUpdate(this);" name="perm-view-0" id="Input-view-0" value="1" type="checkbox"<?php if(isset($view) && $view == 1){ echo ' checked'; } ?>></td>
                  <td>&nbsp;</td>
                </tr>
                <?php
                foreach($groups as $group){
                    // Get the existing group permissions
                    $view = 0;
                    $post = 0;

                    foreach($group_perms as $group_perm){
                        if($group_perm->group_id == $group->id){
                            $view = $group_perm->view;
                            $post = $group_perm->post;
                            break;
                        }
                    }
                    ?>
                  <tr>
                    <td onclick="toggleAll(this);"><?php echo htmlspecialchars($group->name); ?></td>
                    <td><input type="hidden" name="perm-view-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-view-<?php echo $group->id; ?>" id="Input-view-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($view) && $view == 1){ echo ' checked'; } ?>></td>
                    <td><input type="hidden" name="perm-post-<?php echo $group->id; ?>" value="0" /><input onclick="colourUpdate(this);" name="perm-post-<?php echo $group->id; ?>" id="Input-post-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($post) && $post == 1){ echo ' checked'; } ?>></td>
                  </tr>
                  <script>groups.push("<?php echo $group->id; ?>");</script>
                    <?php
                }
                ?>
                </tbody>
              </table>
              <strong><?php echo $resource_language->get('resources', 'moderation'); ?></strong>
              <table class="table table-striped">
                <thead>
                <tr>
                  <th><?php echo $resource_language->get('resources', 'group'); ?></th>
                  <th><?php echo $resource_language->get('resources', 'can_move_resources'); ?></th>
                  <th><?php echo $resource_language->get('resources', 'can_edit_resources'); ?></th>
                  <th><?php echo $resource_language->get('resources', 'can_delete_resources'); ?></th>
                  <th><?php echo $resource_language->get('resources', 'can_edit_reviews'); ?></th>
                  <th><?php echo $resource_language->get('resources', 'can_delete_reviews'); ?></th>
                    <?php
                    foreach($groups as $group){
                    // Get the existing group permissions
                    $move = 0;
                    $edit_resource = 0;
                    $delete_resource = 0;
                    $edit_review = 0;
                    $delete_review = 0;

                    foreach($group_perms as $group_perm){
                        if($group_perm->group_id == $group->id){
                            $move = $group_perm->move_resource;
                            $edit_resource = $group_perm->edit_resource;
                            $delete_resource = $group_perm->delete_resource;
                            $edit_review = $group_perm->edit_review;
                            $delete_review = $group_perm->delete_review;
                            break;
                        }
                    }
                    ?>
                <tr>
                  <td onclick="toggleAll(this);"><?php echo htmlspecialchars($group->name); ?></td>
                  <td><input type="hidden" name="perm-move_resource-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-move_resource-<?php echo $group->id; ?>" id="Input-move_resource-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($move) && $move == 1){ echo ' checked'; } ?>></td>
                  <td><input type="hidden" name="perm-edit_resource-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-edit_resource-<?php echo $group->id; ?>" id="Input-edit_resource-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($edit_resource) && $edit_resource == 1){ echo ' checked'; } ?>></td>
                  <td><input type="hidden" name="perm-delete_resource-<?php echo $group->id; ?>" value="0" /><input onclick="colourUpdate(this);" name="perm-delete_resource-<?php echo $group->id; ?>" id="Input-delete_resource-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($delete_resource) && $delete_resource == 1){ echo ' checked'; } ?>></td>
                  <td><input type="hidden" name="perm-edit_review-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-edit_review-<?php echo $group->id; ?>" id="Input-edit_review-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($edit_review) && $edit_review == 1){ echo ' checked'; } ?>></td>
                  <td><input type="hidden" name="perm-delete_review-<?php echo $group->id; ?>" value="0" /> <input onclick="colourUpdate(this);" name="perm-delete_review-<?php echo $group->id; ?>" id="Input-delete_review-<?php echo $group->id; ?>" value="1" type="checkbox"<?php if(isset($delete_review) && $delete_review == 1){ echo ' checked'; } ?>></td>
                </tr>
                <?php
                }
                ?>
                </tr>
                </thead>
              </table>
              <div class="form-group">
                <input type="hidden" name="token" value="<?php echo $token; ?>">
                <input type="submit" class="btn btn-primary" value="<?php echo $language->get('general', 'submit'); ?>">
              </div>
              </form>
						<?php 
					}
				}
			  } else {
          // Other tabs in the future if needed
			  }
			  ?>
			</div>
		  </div>
		</div>
      </div>
    </div>
	<?php
  require('modules/Core/pages/admin/footer.php');
  require('modules/Core/pages/admin/scripts.php');
  ?>

    <script type="text/javascript">
  	function colourUpdate(that) {
    	var x = that.parentElement;
    	if(that.checked) {
    		x.className = "bg-success";
    	} else {
    		x.className = "bg-danger";
    	}
	}
	function toggle(group) {
		if(document.getElementById('Input-view-' + group).checked) {
			document.getElementById('Input-view-' + group).checked = false;
		} else {
			document.getElementById('Input-view-' + group).checked = true;
		}
		if(document.getElementById('Input-post-' + group).checked) {
			document.getElementById('Input-post-' + group).checked = false;
		} else {
			document.getElementById('Input-post-' + group).checked = true;
		}
		if(document.getElementById('Input-move_resource-' + group).checked) {
			document.getElementById('Input-move_resource-' + group).checked = false;
		} else {
			document.getElementById('Input-move_resource-' + group).checked = true;
		}
		if(document.getElementById('Input-edit_resource-' + group).checked) {
			document.getElementById('Input-edit_resource-' + group).checked = false;
		} else {
			document.getElementById('Input-edit_resource-' + group).checked = true;
		}
    if(document.getElementById('Input-delete_resource-' + group).checked) {
        document.getElementById('Input-delete_resource-' + group).checked = false;
    } else {
        document.getElementById('Input-delete_resource-' + group).checked = true;
    }
    if(document.getElementById('Input-edit_review-' + group).checked) {
        document.getElementById('Input-edit_review-' + group).checked = false;
    } else {
        document.getElementById('Input-edit_review-' + group).checked = true;
    }
    if(document.getElementById('Input-delete_review-' + group).checked) {
        document.getElementById('Input-delete_review-' + group).checked = false;
    } else {
        document.getElementById('Input-delete_review-' + group).checked = true;
    }

		colourUpdate(document.getElementById('Input-view-' + group));
		colourUpdate(document.getElementById('Input-post-' + group));
		colourUpdate(document.getElementById('Input-move_resource-' + group));
		colourUpdate(document.getElementById('Input-edit_resource-' + group));
		colourUpdate(document.getElementById('Input-delete_resource-' + group));
    colourUpdate(document.getElementById('Input-edit_review-' + group));
    colourUpdate(document.getElementById('Input-delete_review-' + group));
	}
	for(var g in groups) {
		colourUpdate(document.getElementById('Input-view-' + groups[g]));
		if(groups[g] != "0") {
			colourUpdate(document.getElementById('Input-post-' + groups[g]));
			colourUpdate(document.getElementById('Input-move_resource-' + groups[g]));
        colourUpdate(document.getElementById('Input-edit_resource-' + groups[g]));
        colourUpdate(document.getElementById('Input-delete_resource-' + groups[g]));
        colourUpdate(document.getElementById('Input-edit_review-' + groups[g]));
        colourUpdate(document.getElementById('Input-delete_review-' + groups[g]));
		}
	}
	
	// Toggle all columns in row
	function toggleAll(that){
		var first = (($(that).parents('tr').find(':checkbox').first().is(':checked') == true) ? false : true);
		$(that).parents('tr').find(':checkbox').each(function(){
			$(this).prop('checked', first);
			colourUpdate(this);
		});
	}
    </script>
  </body>
</html>
