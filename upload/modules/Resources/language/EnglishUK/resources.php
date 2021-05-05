<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  EnglishUK Language for Resources module
 */

$language = array(
	/*
	 *  Resources
	 */ 
	'resources' => 'Resources',
	'categories' => 'Categories',
	'no_resources' => 'No resources have been added yet.',
	'new_resource' => 'New Resource',
	'select_category' => 'Select Category',
	'github_username' => 'GitHub Username',
	'github_repo_name' => 'GitHub Repository Name',
	'link_to_github_repo' => 'Link to GitHub Repository',
	'required' => 'Required',
	'resource_name' => 'Name',
    	'resource_icon' => 'Resource Icon',
    	'resource_upload_icon' => 'Upload Icon',
    	'resource_change_icon' => 'Change Icon',
	'resource_short_description' => 'Short description',
	'resource_description' => 'Description',
	'version_tag' => 'Version Tag',
	'version_tag_help' => 'This must match your GitHub release tag',
	'contributors' => 'Contributors',
	'name_required' => 'Please enter a resource name',
	'short_description_required' => 'Please enter a short description',
	'content_required' => 'Please enter a resource description',
	'github_username_required' => 'Please enter your GitHub username',
	'github_repo_required' => 'Please enter your GitHub repository name',
	'version_tag_required' => 'Please enter the version tag for your resource',
	'category_required' => 'Please select a category for your resource',
	'name_min_2' => 'The resource name must be a minimum of 2 characters',
	'short_description_min_2' => 'The short description must be a minimum of 2 characters',
	'content_min_2' => 'The resource description must be a minimum of 2 characters',
	'github_username_min_2' => 'Your GitHub username must be a minimum of 2 characters',
	'github_repo_min_2' => 'Your GitHub repository name must be a minimum of 2 characters',
	'name_max_64' => 'The resource name must be a maximum of 64 characters',
	'short_description_max_64' => 'The short description must be a maximum of 64 characters',
	'content_max_20000' => 'The resource description must be a maximum of 20000 characters',
	'github_username_max_32' => 'Your GitHub username must be a maximum of 32 characters',
	'github_repo_max_64' => 'Your GitHub repository name must be a maximum of 64 characters',
	'version_max_16' => 'The version tag must be a maximum of 16 characters',
	'contributors_max_255' => 'The contributors list must be a maximum of 255 characters',
	'unable_to_get_repo' => 'Unable to get latest release information from {x}. Have you created a release on GitHub?',
	'update_already_exists' => 'An update with that tag already exists!',
	'select_release' => 'Select a release:',
	'resource' => 'Resource',
	'stats' => 'Stats',
	'author' => 'Author',
	'x_views' => '{x} views', // Don't replace {x}
	'x_downloads' => '{x} downloads', // Don't replace {x}
	'in_category_x' => 'in category {x}', // Don't replace {x}
	'viewing_resource_x' => 'Viewing resource {x}', // Don't replace {x}
	'resource_index' => 'Resource Index',
	'reviews' => 'Reviews',
	'view_other_resources' => 'View {x}\'s other resources', // Don't replace {x}
	'download' => 'Download',
	'other_releases' => 'Other Releases',
	'no_reviews' => 'No reviews',
	'new_review' => 'New Review',
	'update' => 'Update Resource',
	'updated_x' => 'updated {x}', // Don't replace {x}
	'viewing_all_releases' => 'Viewing all releases for resource {x}', // Don't replace {x}
	'viewing_release' => 'Viewing release {x} for resource {y}', // Don't replace {x} or {y}
	'viewing_all_versions' => 'Viewing all versions for resource {x}', // Don't replace {x}
	'viewing_all_reviews' => 'Viewing all reviews for resource {x}', // Don't replace {x}
    'editing_resource' => 'Editing Resource',
    'contributors_x' => 'Contributors: {x}', // Don't replace {x}
    'move_resource' => 'Move resource',
    'delete_resource' => 'Delete resource',
    'confirm_delete_resource' => 'Are you sure you want to delete the resource {x}?', // Don't replace {x}
    'invalid_category' => 'You have selected an invalid category',
    'move_to' => 'Move resource to:',
    'no_categories_available' => 'There are no categories available to move this resource to!',
    'delete_review' => 'Delete Review',
    'confirm_delete_review' => 'Are you sure you want to delete this review?',
    'viewing_resources_by_x' => 'Viewing resources by {x}', // Don't replace {x}
	'release_type' => 'Release Type',
    'zip_file' => 'Zip File',
    'github_release' => 'GitHub Release',
    'type' => 'Type',
    'free_resource' => 'Free Resource',
    'premium_resource' => 'Premium Resource',
    'price' => 'Price',
    'invalid_price' => 'Invalid price.',
    'paypal_email_address' => 'PayPal Email Address',
    'paypal_email_address_info' => 'This is the PayPal email address money will be sent to when someone purchases your premium resources.',
    'invalid_email_address' => 'Please enter a valid PayPal email address, between 4 and 64 characters.',
    'no_payment_email' => 'There is no PayPal email address linked with your account. You can add one afterwards in the UserCP.',
    'my_resources' => 'My Resources',
    'purchased_resources' => 'Purchased Resources',
    'no_purchased_resources' => 'You haven\'t purchased any resources yet.',
    'choose_file' => 'Choose file',
    'zip_only' => '.zip files only',
    'file_not_zip' => 'The file is not a .zip!',
    'filesize_max_x' => 'The file size must be at most {x}kb', // Don't replace {x}, unit kilobytes
	'file_upload_failed' => 'File upload failed with error {x}', // Don't replace {x}
	'purchase_for_x' => 'Purchase for {x}', // Don't replace {x}
	'purchase' => 'Purchase',
    'purchasing_resource_x' => 'Purchasing {x}', // Don't replace {x}
	'payment_pending' => 'Payment Pending',
    'update_title' => 'Update Title',
    'update_information' => 'Update Information',
	'paypal_not_configured' => 'PayPal integration hasn\'t been configured yet! Please contact an administrator.',
	'error_while_purchasing' => 'Sorry! There was an error whilst purchasing this resource. Please contact an administrator.',
	'author_doesnt_have_paypal' => 'Sorry! The resource author hasn\'t connected their PayPal account yet.',
	'sorry_please_try_again' => 'Sorry! There was a problem, please try again.',
    'purchase_cancelled' => 'The purchase has been cancelled successfully.',
    'purchase_complete' => 'The purchase has been successful. Please note, the resource will only become available for download once the payment has been fully completed.',
    'log_in_to_download' => 'Log in to download',
	'external_download' => 'External Download',
    'external_link' => 'External Link',
    'external_link_error' => 'Please enter a valid external link, between x and y characters long.',
    'select_release_type_error' => 'Please select a release type.',
    'sort_by' => 'Sort By',
    'last_updated' => 'Last Updated',
    'newest' => 'Newest',
    'overview' => 'Overview',
    'releases_x' => 'Releases ({x})', // Don't replace {x}
    'versions_x' => 'Versions ({x})', // Don't replace {x}
    'reviews_x' => 'Reviews ({x})', // Don't replace {x}

    //widgets
    'latest_resources' => 'Latest Resources',
    'top_resources' => 'Top Resources',
    'no_latest_resources' => 'No resources',
    'no_top_resources' => 'No resources',
	
    'total_downloads' => 'Total Downloads',
    'first_release' => 'First Release',
    'last_release' => 'Last Release',
    'views' => 'Views',
    'category' => 'Category',
    'rating' => 'Rating',
    'version_x' => 'Version {x}', // Don't replace {x}
    'release' => 'Release', // Don't replace {x}

    // Admin
    'permissions' => 'Permissions',
    'new_category' => '<i class="fa fa-plus-circle"></i> New Category',
    'creating_category' => 'Creating Category',
    'category_name' => 'Category Name',
    'category_description' => 'Category Description',
    'input_category_title' => 'Please input a category name.',
    'category_name_minimum' => 'Your category name must be a minimum of 2 characters.',
    'category_name_maxmimum' => 'Your category name must be a maximum of 150 characters.',
    'category_description_maximum' => 'Your category description must be a maximum of 250 characters.',
    'category_created_successfully' => 'Category created successfully.',
    'category_updated_successfully' => 'Category updated successfully.',
    'category_deleted_successfully' => 'Category deleted successfully.',
    'category_permissions' => 'Category Permissions',
    'group' => 'Group',
    'can_view_category' => 'Can view category?',
    'can_post_resource' => 'Can post resources?',
    'moderation' => 'Moderation',
    'can_move_resources' => 'Can move resources?',
    'can_edit_resources' => 'Can edit resources?',
    'can_delete_resources' => 'Can delete resources?',
    'can_edit_reviews' => 'Can edit reviews?',
    'can_delete_reviews' => 'Can delete reviews?',
    'can_download_resources' => 'Can download resources?',
    'can_post_premium_resource' => 'Can post premium resources?',
    'delete_category' => 'Delete Category',
    'move_resources_to' => 'Move resources to',
    'delete_resources' => 'Delete resources',
    'downloads' => 'Downloads',
    'no_categories' => 'No categories have been created yet.',
    'editing_category' => 'Editing Category',
    'settings' => 'Settings',
    'settings_updated_successfully' => 'Settings updated successfully.',
    'currency' => 'ISO-4217 Currency',
    'invalid_currency' => 'Invalid ISO-4217 currency! A list of valid codes can be found <a href="https://en.wikipedia.org/wiki/ISO_4217#Active_codes" target="_blank" rel="noopener nofollow">here</a>',
    'upload_directory_not_writable' => 'The uploads/resources directory is not writable!',
    'maximum_filesize' => 'Maximum filesize (kilobytes)',
    'invalid_filesize' => 'Invalid filesize!',
    'pre_purchase_information' => 'Pre-purchase information',
    'invalid_pre_purchase_info' => 'Invalid pre-purchase information! Please ensure it is under 100,000 characters.',
    'paypal_api_details' => 'PayPal API Details',
    'paypal_api_details_info' => 'The values of these fields are hidden for security reasons.<br />If you are updating these settings, please enter both the client ID and the client secret together.',
    'paypal_client_id' => 'PayPal Client ID',
    'paypal_client_secret' => 'PayPal Client Secret',
    'paypal_config_not_writable' => 'modules/Resources/paypal.php is not writable to save PayPal settings.',

    // Hook
    'new_resource_text' => 'New resource created in {x} by {y}',
    'updated_resource_text' => 'Resource updated in {x} by {y}'
);
