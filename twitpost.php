<?php
/*
Plugin Name: Twitpost
Plugin URI:  http://indeedle.com/projects/twitpost/
Description: A plugin that notifies your twitter feed when you've made a new post in your blog.
Author: Indeedle
Version: 0.0.9
Author URI: http://indeedle.com/
*/

/*  Copyright 2009  Indeedle  (email : twitpost@indeedle.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Grab the twitter API file, this is the class we use to access twitter
require_once('twitterAPI.class.php');

global $twitpost_services;
$twitpost_services = array(
	1	=>	array('http://tinyurl.com/api-create.php?url=', '/^http:\/\/tinyurl.com\/([0-9a-z]+)/', __('TinyURL')),
	2	=>	array('http://is.gd/api.php?longurl=', '/^http:\/\/is.gd\/([0-9a-z]+)/', __('is.gd')),
	3	=>	array('http://gurlx.com/sign?api=true&url=', '/^http:\/\/gurlx.com\/([0-9a-z]+)/', __('GURL')),
);

$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'twitpost', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );

// Add to the menu
add_action('admin_menu', 'twitpost_addmenu');
add_filter('wp_insert_post', 'twitpost_filterpublish', 5, 1);
register_activation_hook( __FILE__, 'twitpost_install' );
if (function_exists('register_deactivation_hook'))
	register_deactivation_hook(__FILE__, 'twitpost_uninstall');

function twitpost_addmenu()
{
	add_options_page(__('Twitpost', 'twitpost'), __('Twitpost', 'twitpost'), 'manage_options', __FILE__, 'twitpost_options_page');
}

// The filter
function twitpost_filterpublish($postID)
{
	// Get the post
	$post = get_post($postID);
	
	// Let's check, if the post isn't set as published then we're not going to do anything
	// Only do anything if it's both publish and we're authenticated
	
	// Secondly we need to work out, is this post being skipped?
	$status = get_post_meta($postID, 'twitpost_action', true);
	
	$options = get_option('twitpost');

	if(($post->post_status == "publish" || $status == 'post') && $options['authenticated'] == 1)
	{
		// We also need to get what the current option is
		$method = $options['method'];
		
		$twitdo = false; // Whether or not we should twitter
		
		if($method == 'man')
		{
			// To add the twitter the post needs to have a value of 'post' in the custom field
			if($status == 'post' && $status != 'added')
				$twitdo = true;
		}
		elseif($method == 'auto')
		{
			// If the post has a skip value OR has a added value, then we don't do anything
			if($status == 'skip' || $status == 'added')
				$twitdo = false;
			else
				$twitdo = true;
		}

		if($twitdo)
		{		
			// Firstly, check the validation
			$password = $options['password'];
			$username = $options['username'];
			
			$twit = new twitterAPI($username, $password);
			
			$valid = $twit->validateLogin();
			
			if($twit->validateLogin() == 1)
			{	
				// Update the custom field to posted
				update_post_meta($postID, 'twitpost_action', 'added', $status);
				
				// Add the twitter
				$message = $options['message'];
				
				// Get the variables to replace the message with
				global $current_user;
				get_currentuserinfo();
				
				$bloginfo = get_bloginfo();
				
				// Get the details	
				$user = $current_user->display_name;
				$title = $post->post_title;
				$url = $post->guid;
				$site = get_bloginfo('siteurl');
				$name = get_bloginfo('name');
				$shrinkurl = $options['shrinkurl'];
				
				/* Shrink URL Functionality */
				$purl = '';
				switch($shrinkurl)
				{
					case 1:
						$purl = twitpost_
						break;
					default:
						$purl = $url;
						break;
				}
				
				if($shrinkurl == 1){
					// TinyURL
					$purl = twitpost_calltinyurlAPI($url);
					
				}
				else{
					$purl = $url; // Use the normal URL
				}
				
				$twitMessage = str_replace(array(__('USER', 'twitpost'),__('TITLE', 'twitpost'),__('URL', 'twitpost'), __('SITE', 'twitpost'), __('NAME', 'twitpost')), array($user, $title, $purl, $site, $name), $message);
				
				if($password != '' && $username != '')
				{
					$twit->addStatus($twitMessage);
				}
			}
			else 
			{
				// We couldn't update the status
				$options['authenticated'] = 2;
				update_option('twitpost', $options);
			}
		}
	}
	
	return $postID;
}

// Installs the plugin
function twitpost_install()
{
	// Add the options to the database
	$options = array(
		'password'		=>	'',
		'username'		=>	'',
		'authenticated'	=>	'0',
		'message'		=>	__('New blog post: TITLE at URL', 'twitpost'),
		'method'		=>	'auto',
		'shrinkurl'		=>	'1'
	);
	
	// Time to check, did we actually have any old settings saved?
	// This is because changes on how we handled options have been made
	$tmp['password'] = get_option('twitpost_password');
	if(get_option('twitpost_password') != '')
		$options['password'] = $tmp['password'];
		
	$tmp['username'] = get_option('twitpost_username');
	if(get_option('twitpost_username') != '')
		$options['username'] = $tmp['username'];
		
	$tmp['message'] = get_option('twitpost_message');
	if(get_option('twitpost_message') != '')
		$options['message'] = $tmp['message'];
	
	$tmp['method'] = get_option('twitpost_method');
	if(get_option('twitpost_method') != '')
		$options['method'] = $tmp['method'];
		
	// Basically we deleted the old options from the earlier versions
	// And updated to the new one
		
	// Save them
	add_option('twitpost', $options);
	
	// To clean up the old method, get rid of the old options
	delete_option('twitpost_password');
	delete_option('twitpost_username');
	delete_option('twitpost_authenticated');
	delete_option('twitpost_message');
	delete_option('twitpost_method');
}

function twitpost_uninstall()
{
	// Let's make sure not to leave the options behind
	delete_option('twitpost');
}

// The actual options page
function twitpost_options_page()
{	
	$options = get_option('twitpost'); // We do this so we can be lazy with checking input
	// Otherwise options would be wiped
	
	global $twitpost_services;
	
	if(isset($_POST['authSubmit']))
	{
		check_admin_referer('update-options');
		
		if($_POST['action'] == "update") {
			
			$options['password'] = $_POST['twitpost_password'];
			$options['username'] = $_POST['twitpost_username'];
			$options['authenticated'] = 0;
			
			update_option("twitpost", $options);
		}
	}
	elseif(isset($_POST['setSubmit']))
	{
		check_admin_referer('update-options');
		
		if($_POST['action'] == "update") {		
			$options['message'] = $_POST['twitpost_message'];
			$options['method'] = $_POST['twitpost_method'];
			$options['shrinkurl'] = $_POST['twitpost_shrinkurl'];
			
			update_option("twitpost", $options);
		}
	}
	
	// Get the clean options
	$options = get_option('twitpost');
	
	$password = stripslashes($options['password']);
	$username = stripslashes($options['username']);
	$authenticated = stripslashes($options['authenticated']);
	$message = stripslashes($options['message']);
	$shrinkurl = stripslashes($options['shrinkurl']);
	
	// Set a default message if one isn't set up
	$message = (empty($message)) ? __('USER has blogged about "TITLE" at URL', 'twitpost') : $message;
	
	// Connect to the twitter API
	$twit = new twitterAPI($username, $password);
	
	$valid = 0;
	$err = array(); // Hold the error messages
	
	// Check, was either the password or username left blank, or has it not been previously authenticated
	if($password != '' && $username != '' && $authenticated == 0)
	{	
		// Validate login
		$valid = $twit->validateLogin();
		if($valid == 1)
		{
			// Basically by doing this we don't reauthenticate every page load
			// instead we only do it when the username/password was resubmitted
			$options['authenticated'] = 1;
			update_option('twitpost', $options);
			$authenticated = $options['authenticated'];			
		}
		elseif($valid == 2)
		{
			$err[] = __('Couldn\'t authenticate you with Twitter. Please check that your username &amp; passsword are both correct.', 'twitpost');
		}
	}
	elseif($authenticated == 2)
	{
		// This means during the script validation failed, this can be caused by a user's password being changed
		$err[] = __('Can no longer connect to Twitter, did you change your password? Please update your login details.', 'twitpost');
	}
	
	?><div class="wrap">
<h2><?php _e('Twitpost', 'twitpost'); ?></h2>
<p><?php _e('Twitpost will add a short note to your Twitter feed when you add a new post. This can be called automatically, or it can be called manually.', 'twitpost'); ?></p>

<h3><?php _e('Authentication', 'twitpost'); ?></h3>
<p><?php _e('This is needed to update your twitter feed.', 'twitpost'); ?></p>
<?php if(count($err) > 0){ ?><p style="padding: .5em; font-weight: bold;" class="error"><?php echo join('<br />', $err); ?></p><? } elseif($valid == 1 && $authenticated == 1){ ?><p style="padding: .5em; font-weight: bold;" class="updated fade"><?php _e('Great! Authenticated with twitter :)', 'twitpost'); ?></p><? } ?>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php wp_nonce_field('update-options'); ?>
<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e('Twitter Username', 'twitpost'); ?>:</th>
<td><input type="text" name="twitpost_username" value="<?php echo strtolower(str_replace(array("\n", "\r", "\t", " ", "\o", "\xOB"), '', $username)); ?>" class="code" />
<span class="setting-description"><?php _e('Your twitter username.', 'twitpost'); ?></span></td>
</tr><tr valign="top">
<th scope="row"><?php _e('Twitter Password:', 'twitpost'); ?></th>
<td><input type="password" name="twitpost_password" value="<?php echo $password; ?>" class="code" />
<span class="setting-description"><?php _e('Your twitter password.', 'twitpost'); ?></span></td>
</tr>
</table>
<input type="hidden" name="action" value="update" />
<input type="hidden" name="twitpost_authenticated" value="0" />
<input type="hidden" name="page_options" value="" />
<p class="submit">
<input type="submit" name="authSubmit" value="<?php _e('Update Login', 'twitpost') ?>" />
</p>
</form>
<?php if($authenticated > 0){
// The login is valid, addional options to set
?>
<h3><?php _e('Settings', 'twitpost'); ?></h3>
<p><?php _e('If you set the method to automatic, Twitpost will add the message to your twitter feed for every post you make, unless you specifically exclude the post.', 'twitpost'); ?></p>
<p><?php printf(__('With manual Twitpost will only add the message to your twitter feed if you specifically tell it to.</p>
<p>Add a custom field to your post titled %1$s. A value of %3$s when method is manual means the post will be sent to twitter, while a value of %2$s when the method is automatic means the post you\'ve written will not be sent to twitter.', 'twitpost'), '<code>twitpost_action</code>','<code>skip</code>', '<code>post</code>'); ?></p>
<p><?php printf(__('Once the written post has been published, %1$s will be changed to %2$s which means if edited in the future it will not be sent to twitter. If you want to notify twitter of it being updated, just replace %2$s with %3$s when editing the post, and twitter will be notified again.', 'twitpost'), '<code>twitpost_action</code>', '<code>added</code>', '<code>post</code>'); ?></p>
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php wp_nonce_field('update-options'); ?>
<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e('Method', 'twitpost'); ?>:</th>
<td><select name="twitpost_method" class="code">
		<option class="code" value="auto"<?php if($options['method'] == 'auto') { echo " selected"; } ?>><?php _e('Automatic (Upon publish)', 'twitpost'); ?></option>
		<option class="code" value="man"<?php if($options['method'] == 'man') { echo " selected"; } ?>><?php _e('Manual (Post by post)', 'twitpost'); ?></option>
		</select><br />
<span class="setting-description"></span></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Message', 'twitpost'); ?>:</th>
<td><textarea name="twitpost_message" class="large-text code"><?php echo $message; ?></textarea>
<span class="setting-description"><?php _e('This is the message that will be posted on twitter. <code>USER</code>, <code>TITLE</code>, <code>URL</code>, <code>SITE</code> and <code>NAME</code> will be replaced with your name, post title, post link, blog link and blog title respectivly.', 'twitpost'); ?></span></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Shrink URL', 'twitpost'); ?>:</th>
<td><select name="twitpost_shrinkurl" class="code">
<?php foreach($twitpost_services as $k=>$ts){ ?>
	<option class="code" value="<?php echo $k; ?>"<?php if($options['shrinkurl'] == $k) { echo " selected"; } ?>><?php $ts[2]; ?></option>
<?php } ?>
</select>
<span class="setting-description"><?php _e('Shrink the blog URL with one of these services, so the tweet doesn\'t appear too long. Generally the URL is the longest part of the tweet.', 'twitpost'); ?></span></td>
</tr>
</table>
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="" />
<p class="submit"><input type="submit" name="setSubmit" value="<?php _e('Save Settings', 'twitpost') ?>" /></p>
</form>
<? } ?>
</div>
<? }

// Extra functions
// Call shrink API
function twitpost_callShrinkAPI($url, $service=1){
	// We're going to check that CURL exists, otherwise we'll just return the url
	if(!function_exists('curl_version'))
		return $url;
		
	global $twitpost_services;
	
	$encodeURL = $twitpost_services[$service][0].urlencode($url);
		
	$curl = curl_init();
    @curl_setopt ($curl, CURLOPT_URL, $encodeURL);
    @curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = trim(curl_exec ($curl));
    @curl_close ($curl);
    
    // Check the URL returned, just to make sure there was no error
    // If it isn't a valid URL, just use the normal URL
    if(!preg_match($twitpost_services[$service][1], $result))
    	return $url;
    else
    	return $result;
}
?>