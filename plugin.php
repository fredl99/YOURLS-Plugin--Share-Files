<?php
/*
Plugin Name: Upload & Shorten
Plugin URI: https://github.com/fredl99/YOURLS-Upload-and-Shorten
Description: Upload a file and create a short-YOURL for it in one step.
Version: 1.3.1/stable
Author: Fredl
Author URI: https://github.com/fredl99
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();


// Register our plugin admin page
yourls_add_action( 'plugins_loaded', 'my_upload_and_shorten_add_page' );

function my_upload_and_shorten_add_page() {
	// load custom text domain
	yourls_load_custom_textdomain( 'upload-and-shorten', dirname(__FILE__) . '/l10n/' );
	// create entry in the admin's plugin menu
	yourls_register_plugin_page( 'upload-and-shorten', 'Upload & Shorten', 'my_upload_and_shorten_do_page' );
	// parameters: page slug, page title, and function that will display the page itself
}

function my_say__($message) {
	$my_upload_and_shorten_domain = 'upload-and-shorten';
	return yourls_esc_html__($message, $my_upload_and_shorten_domain );
	}
	
// Display admin page
function my_upload_and_shorten_do_page() {
	// Check if a form was submitted
	$my_save_files_message = '';
	if(isset($_POST['submit']) && $_POST['submit'] != '') $my_save_files_message = my_upload_and_shorten_save_files();

	// input form
	echo '
	<h2>Upload & Shorten</h2>
	<h3>'.my_say__("Send a file to your webserver and create a short-URL for it.").'</h3>

	<p><strong>'.$my_save_files_message.'</strong></p>
	
	<form method="post" enctype="multipart/form-data"> 

	<fieldset> <legend>'.my_say__("Select a file ").'</legend>
	<p><input type="file" id="file_upload" name="file_upload" /></p>
	</fieldset>';

	// YOURLS options
	echo '
	<fieldset> <legend>'.my_say__("YOURLS database options").'</legend>
		<p><label><input type="checkbox" name="shorten_link">'.my_say__('Shorten the file link.').'</label></p>	
		<p><label for="custom_shortname">'.my_say__("Custom shortname: ").'</label> 
		<input type="text" id="custom_shortname" name="custom_shortname" />
	
		<label for="custom_title">'.my_say__("Custom title: ").'</label> 
		<input type="text" id="custom_title" name="custom_title" /></p>
	</fieldset>';

	// filename handling
	echo '
	<fieldset> <legend>'.my_say__("Filename conversions (optional)").'</legend>

		<p><input type="radio" id="plain_filename" name="convert_filename" value="original" checked="checked" />
		<label for="plain_filename">'.my_say__("No conversion ").'</label> 
		<small>('.my_say__("Filename will not be changed at all.").')<br/ >
		Ex.: "my_filename.txt" -> http://domain.tld/my_filename.txt </small></p>

		<p><input type="radio" id="safe_filename" name="convert_filename" value="browser-safe" />
		<label for="safe_filename">'.my_say__("Browser-safe filename ").'</label> 
		<small>('.my_say__("Recommended if the file should be accessed by web-browsers.").')<br/ >
		Ex.: "my not safe&clean filename #1.txt" -> http://domain.tld/my_not_safe_clean_filename_1.txt </small></p>

		<p><input type="radio" id="random_filename" name="convert_filename" value="randomized" />
		<label for="random_filename">'.my_say__("Randomize filename ").'</label> 
		<small>('.my_say__("Browser-safe filenames with a slight protection against systematic crawling your web-directory.").')<br/ >
		Ex.: "mypicture.jpg" -> http://domain.tld/9a3e97434689.jpg </small></p>
	
		<p><input type="checkbox" id="drop_extension" name="drop_extension" />
		<label for="drop_extension">'.my_say__("Drop extension ").'</label> 
		<small>('.my_say__("Hide the filetype. Can be combined with above options.").') <br/ >
		Ex.: "mypicture.jpg" -> http://domain.tld/mypicture or http://domain.tld/9a3e97434689 <br/ > 
		<strong>'.my_say__("Some webservers won't send correct headers and web-browsers may not handle the file correctly.").'</strong></small></p>

	</fieldset>';

	// do it!
	echo '	
	<p><input type="submit" name="submit" value="'.my_say__("  Go!  ").'" /></p>';

	// shameless plug :)
	echo '
	</form>

	<div id="footer">
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_paypal">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="H5B9UKVYP88X4">'
		.my_say__("This plugin is hosted at ").'<a href="https://github.com/fredl99/YOURLS-Upload-and-Shorten" target="_blank">GitHub</a>. '
		.my_say__("If you like it, remember someone spends his time working on it. ") 
		.my_say__("A cup of coffee might stimulate further improvements. ;-) ") 
		.'<br/ ><input type="image" src="https://www.paypalobjects.com/en_GB/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online!"> 
		</form>
	</div>';
	}

// Update option in database
function my_upload_and_shorten_save_files() {

	// did the user select any file?
	if ($_FILES['file_upload']['error'] == UPLOAD_ERR_NO_FILE) {
		return my_say__('You need to select a file to upload.');
	}
	else $my_save_files_message = '';
	
	// yes!
	$my_url = SHARE_URL;	// has to be defined in user/config.php like this: 
					// define( 'SHARE_URL', 'http://my.domain.tld/directory/' );

	$my_uploaddir = SHARE_DIR;	// has to be defined in user/config.php like this: 
					// define( 'SHARE_DIR', '/full/path/to/httpd/directory/' );

	// Handle the filename's extension
	$my_upload_extension = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);

	// If there is any extension at all then append it with a leading dot
	if(isset($my_upload_extension) && $my_upload_extension != NULL) {
		$my_extension = '.' . $my_upload_extension;
		}
	// If the following option is checked then drop the filename's extension to obfuscate the filetype. 
	// Beware: Some webservers won't send an appropriate HTTP-Header then!
	if(isset($_POST['drop_extension']) && $_POST['drop_extension'] = "checked" ) {
		$my_extension = '';
		}

	$my_upload_filename = pathinfo($_FILES['file_upload']['name'], PATHINFO_FILENAME);
	if(isset($_POST['convert_filename'])) {
		switch ($_POST['convert_filename']) { 
			case 'original': {
			$my_filename = $my_upload_filename;
			}
			break; 

			case 'browser-safe': {
			// make the filename web-safe: 
                        $my_filename_trim = trim($my_upload_filename);
                        $my_RemoveChars  = array( "([^()_\-\.,0-9a-zA-Z\[\]])" );	// replace what's NOT in here!
                        $my_filename = preg_replace($my_RemoveChars, "_", $my_filename_trim);
                        $my_filename = preg_replace( "(_{2,})", "_", $my_filename);
                        $my_extension = preg_replace($my_RemoveChars, "_", $my_extension);
                        $my_extension = preg_replace( "(_{2,})", "_", $my_extension);
                        }
			break;

			case 'randomized': {
                        // make up a random name for the uploaded file
                        // see http://www.mattytemple.com/projects/yourls-share-files/?replytocom=26686#respond (seems vanished...)
			// (seems vanished...)
                        $my_filename = substr(md5($my_upload_filename.strtotime("now")), 0, 12);
                        // end randomize filename
                        }
			break; 
		}
	}

	// avoid duplicate filenames
	$my_count = 2;
	$my_path = $my_uploaddir.$my_filename.$my_extension;
	$my_final_file_name = $my_filename.$my_extension;
	while(file_exists($my_path)) {
		$my_path = $my_uploaddir.$my_filename.'.'.$my_count.$my_extension;
		$my_final_file_name = $my_filename.'.'.$my_count.$my_extension;
		$my_count++;	
	}

	$my_upload_fullname = pathinfo($_FILES['file_upload']['name'], PATHINFO_BASENAME);

	// move the file from /tmp/ to destination and initiate link creation
	if(move_uploaded_file($_FILES['file_upload']['tmp_name'], $my_path . $_FILES['file_upload']['name'])) {
		// On success:
		// obey custom shortname, if given:
		$my_custom_shortname = '';
		if(isset($_POST['custom_shortname']) && $_POST['custom_shortname'] != NULL) {
			$my_custom_shortname = $_POST['custom_shortname'];
		}
		// change custom title, if given. Default is original filename, but if user provided one, use it:
		$my_custom_title = $_POST['convert_filename'].': '.$my_upload_fullname;
		if(isset($_POST['custom_title']) && $_POST['custom_title'] != NULL) {
			$my_custom_title = $_POST['custom_title'];
		}
		
		// let YOURLS create the link:
		$shorten_link = isset($_POST['shorten_link']) && $_POST['shoten_link'] = "checked";
		if ($shorten_link) $my_short_url = yourls_add_new_link($my_url.$my_final_file_name, $my_custom_shortname, $my_custom_title);
		
		return 	'<font color="green">"'.$my_upload_fullname.'"'.my_say__(" successfully sent. These are the links to your file:").'</font><br />'. 
			my_say__("Direct: ").'<a href="'.$my_url.$my_final_file_name.'" target="_blank">'.$my_url.$my_final_file_name.'</a>'.
			($shorten_link ? "<br />".my_say__("Short:  ").'<a href="'.$my_short_url['shorturl'].'" target="_blank">'.$my_short_url['shorturl'].'</a>' : "");
	}
	else {
		return '<font color="red">'.my_say__("Upload failed, sorry! The error was ").$_FILES['file_upload']['error'].'</font>';
	}
}
