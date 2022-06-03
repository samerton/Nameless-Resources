<?php
/*
 *  Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  Image uploads
 */

// Initialisation
$page = 'icon_uploads';
//define('ROOT_PATH', '../..');

// Get the directory the user is trying to access
//$directory = $_SERVER['REQUEST_URI'];
//$directories = explode('/', $directory);

//require(ROOT_PATH . '/core/init.php');

// Require Bulletproof
require(ROOT_PATH . '/core/includes/bulletproof/bulletproof.php');

if (!$user->isLoggedIn()) {
    die();
}

$image_extensions = array('jpg', 'png', 'jpeg', 'gif');


// Deal with input
if(Input::exists()){
    // Check token
    if(Token::check(Input::get('token'))){

        $resource_id = Input::get('resource_id');
        if (empty($resource_id)){
            die();
        }

        $resource = DB::getInstance()->get('resources', array('id', '=', $resource_id))->first();

        // Token valid
        $image = new Bulletproof\Image($_FILES);
        $image->setSize(1, 2097152); // between 1b and 2mb
        $image->setDimension(2000, 2000); // 2k x 2k pixel maximum
        $image->setMime($image_extensions);
        
        if(!is_dir(join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'uploads', 'resources_icons')))){
            if(!mkdir(join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'uploads', 'resources_icons'))))
                die('/uploads folder not writable!');
        }

        $image->setLocation(join(DIRECTORY_SEPARATOR, array(ROOT_PATH, 'uploads', 'resources_icons')));
        $image->setName($resource->id);

        if($image['file']){
            try {
                $upload = $image->upload();

                if($upload){
                    // OK
                    
                    // Need to delete any other icons
                    $diff = array_diff($image_extensions, array(strtolower($upload->getMime())));
                    $diff_str = rtrim(implode(',', $diff), ',');

                    $to_remove = glob(ROOT_PATH . '/uploads/resources_icons/' . $resource->id . '.{' . $diff_str . '}', GLOB_BRACE);

                    if($to_remove){
                        foreach($to_remove as $item){
                            unlink($item);
                        }
                    }

                    DB::getInstance()->update('resources', $resource->id, array(
                        'icon' => rtrim(Util::getSelfURL(), '/') . (defined('CONFIG_PATH') ? CONFIG_PATH . '/' : '/') . 'uploads/resources_icons/'. $resource->id . '.' . $upload->getMime(),
                        'has_icon' => 1,
                        'icon_updated' => date('U')
                    ));

                    Redirect::to(URL::build('/resources/resource/' . $resource->id . '-' . Util::stringToURL($resource->name)));

                } else {
                    http_response_code(400);
                    echo $image["error"];
                    die();
                }
                
            } catch(Exception $e){
                // Error
                http_response_code(400);
                echo $e->getMessage();
                die();
            }
        } else {
                die('No image selected');
        }
        
    } else {
        // Invalid token
        Session::flash('token_error', '<div class="alert alert-danger">' . $language->get('general', 'invalid_token') . '</div>');
    }
}

die('Invalid input');
