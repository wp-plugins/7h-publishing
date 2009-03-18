<?php
/*
Plugin Name: 7H Publishing
Plugin URI: http://7h.com/
Description: Publishes the selected blog post to your 7h.com mailing list.
Version: 1.0
Author: Focus Design
*/


// show 7h button
// when 7h button is clicked,
//		create article with the content from the blog article being posted
//		send most recently created article to publisher's 7h list

// need the following information from Wordpress User:
//		7h site name (example: site17.7h.com)
//		7h admin username?
//		7h admin password?

session_start();

global $site_name_7h;
$site_name_7h = "";

function init_7h_publishing() {
	global $site_name_7h;

	if (!$site_name_7h)
		$site_name_7h = get_option('7h_publishing_cname');

	add_action('admin_menu', 'about_page_7h_publishing');
	add_action('admin_menu', 'config_page_7h_publishing');
	add_action('admin_menu', 'link_to_admin_7h_publishing');
}
add_action('init', 'init_7h_publishing');


if ( !get_option('7h_publishing_cname') && !$site_name_7h && !isset($_POST['cname']) ) {
	function warning_7h_publishing() {
		echo "
		<div id='7h-publishing-warning' class='updated fade'><p><strong>".__('7h Publishing is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your 7h site name</a> for it to work.'), "plugins.php?page=7h-publishing-config")."</p></div>
		";
	}
	add_action('admin_notices', 'warning_7h_publishing');
	return;
}

function config_page_7h_publishing() {
	if (function_exists('add_submenu_page')) {
		add_submenu_page('plugins.php', __('7h Publishing Configuration'), __('7h Publishing Configuration'), 'manage_options', '7h-publishing-config', 'conf_7h_publishing');
	}
}

function about_page_7h_publishing() {
	if (function_exists('add_submenu_page')) {
		add_submenu_page('plugins.php', __('About 7h Publishing'), __('About 7h Publishing'), 'manage_options', '7h-publishing-about', 'about_7h_publishing');
	}
}

function link_to_admin_7h_publishing() {
	if (function_exists('add_submenu_page')) {
		add_submenu_page('plugins.php', __('7h Publishing Admin'), __('7h Publishing Admin'), 'manage_options', '7h-publishing-admin', 'go_to_admin_7h_publishing');
	}
}

function conf_7h_publishing() {
	global $site_name_7h;

	if (isset($_POST['cname'])) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		if ($_POST['cname']) {
			update_option('7h_publishing_cname', $_POST['cname']);
		}
		else {
			//delete_option('7h_publishing_cname');
		}
	}
	// display form to enter cname
	else {
?>
<h2><?php _e('7H Configuration'); ?></h2><br>
With the 7h Publishing plugin you can send your blog post to your 7h mailing list. If you don't have a 7h site, <a href="http://7h.com/new_site/?link_to=step1&link_to_parameters=newsletter" target="_blank">click here</a> to create one for free.</a>
<br>
<form action="" method="post" id="7h-publishing-config">
Enter your 7h site name <input name="cname" value="<?php echo get_option('7h_publishing_cname'); ?>" style="text-align: right;">.7h.com<br>
For example if your 7h site is "mysite.7h.com" then enter "mysite" in this box.<br><br>

<input type="submit" value="Submit">

</form>

<?php
	}
}


function about_7h_publishing() {
	?>
	<h2><?php _e('About 7H'); ?></h2><br>
	With 7h you can easily create your own article-based website for free and email these articles to your mailing list.  <a href="http://7h.com/new_site/?link_to=step1&link_to_parameters=newsletter" target="_blank">Click here</a> to create your 7h site.
	<?php
}


function go_to_admin_7h_publishing() {
	global $site_name_7h;
	echo "<script>window.open('http://" . $site_name_7h . ".7h.com/admin/email_list/');</script>";
	echo "<br><br>If the popup window did not open, <a href='http://" . $site_name_7h . ".7h.com/admin/email_list/' target='_blank'>click here</a> to go to your 7h administrative section.";
}


function publish_blog_post_7h_publishing() {
	create_article_7h_publishing();

	if ($_POST['publish_to_7h'] == "true") {
		send_most_recently_created_article_7h_publishing(true);
		$_SESSION['display_7h_success_message'] = "<script language='javascript'>alert('Your post has been published to your 7h site and mailing list.');</script>";
	}
	else {
		send_most_recently_created_article_7h_publishing(false);
		$_SESSION['display_7h_success_message'] = "<script language='javascript'>alert('Your 7h site has been updated to include this post.');</script>";
	}

}

function create_article_7h_publishing() {
	global $site_name_7h;

	$vars = array();
	$vars["wordpress_7h"] = true;
	$vars["send"] = true;
	$vars["title"] = $_POST["post_title"];
	$vars["content"] = $_POST["content"];
	$vars["synopsis"] = $_POST["excerpt"];

	// default synopsis to first ~4 lines of content
	if (!$vars["synopsis"]) {
		$vars["synopsis"] = substr($vars["content"], 0, 350);
		if (strlen($vars["content"]) > 350)
			$vars["synopsis"] .= "...";
	}

	$formatted_post_vars = get_formatted_post_vars_7h_publishing($vars);

	$post_article_url = "http://" . $site_name_7h . ".7h.com/admin/article/add.php";

	$curl = curl_init();

	curl_setopt($curl, CURLOPT_URL, $post_article_url);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $formatted_post_vars);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 100);
	curl_setopt($curl, CURLOPT_VERBOSE, 1);

	$response = curl_exec($curl);

	$succeeded = false;


	/*
	echo $formatted_post_vars;
	echo "response: " . $response . "\n";
	echo "succeeded: " . $succeeded . "\n";
	print_r($_POST);
	echo curl_errno($curl);
	*/

	//echo $response;
	//print_r(curl_getinfo($curl));
	//exit();

	if (curl_errno($curl) == 0)
		$succeeded = true;

	if ($succeeded) {
		curl_close($curl);
		return $response;
	}
	else {
		$error_number = curl_errno($curl);
		curl_close($curl);
		
		return "There was an error posting your blog article to your 7h mailing list. " . $error_number;
	}
}

function send_most_recently_created_article_7h_publishing($email_article) {
	global $site_name_7h;

	$vars = array();
	$vars["title"] = $_POST["post_title"];
	$vars["wordpress_7h"] = true;
	$vars["confirmed"] = "true";

	if ($email_article)
		$vars["email"] = "true";

	$vars["web"] = "true";

	$formatted_post_vars = get_formatted_post_vars_7h_publishing($vars);

	$post_article_url = "http://" . $site_name_7h . ".7h.com/admin/article/send.php";

	$curl = curl_init();

	curl_setopt($curl, CURLOPT_URL, $post_article_url);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $formatted_post_vars);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 100);
	curl_setopt($curl, CURLOPT_VERBOSE, 1);

	$response = curl_exec($curl);

	$succeeded = false;

	if (curl_errno($curl) == 0)
		$succeeded = true;

	if ($succeeded) {
		curl_close($curl);
		return $response;
	}
	else {
		$error_number = curl_errno($curl);
		curl_close($curl);
		
		return "There was an error posting your blog article to your 7h mailing list. " . $error_number;
	}
}

function get_formatted_post_vars_7h_publishing($vars) {
	foreach($vars as $key=>$val) { 
		$data .= $key . "=" . urlencode($val) . "&";
		//$data .= $key . "=" . htmlspecialchars($val) . "&";
	}
	return $data;
}

add_action('publish_post', 'publish_blog_post_7h_publishing');

// display checkbox next to Publish/Update Post button
function show_publish_option_7h_publishing() {

	if ($_SESSION['display_7h_success_message']) {
		echo $_SESSION['display_7h_success_message'];
		unset($_SESSION['display_7h_success_message']);
	}

	// get image directory
	echo "<script language='javascript'>var image_directory = '" . get_option('siteurl') . "/wp-content/plugins/7h_publishing/';</script>";

	// modify the publish button container div html to include an option to publish to 7h
	echo <<<html
	<script language="javascript">
		var publishing_button_7h_html = "<div style='float: left; padding-left: 10px; padding-top: 3px;'>" +
										"<table cellpadding='0' cellspacing='0'><tr><td valign='top' style='padding-right: 5px;'>" +
											"<img src='" + image_directory + "7h_logo.gif' alt='7h'></td>" +
											"<td style='font-size: 11px;'>Publish to:<br><input type='radio' name='publish_to_7h' value='' style='width: auto;'> Web only<br>" +
											"<input type='radio' name='publish_to_7h' value='true' style='width: auto;' checked> Web and Email</td>" +
											"</tr>" +
										"</table></div><div class='clear'></div>";

		function add_7h_publishing_button() {
			var publishing_div = document.getElementById("major-publishing-actions");
			if (publishing_div) {
				//insert_7h_button_before_publish_button(publishing_div);
				insert_7h_button_at_far_left(publishing_div);
			}
			else
				setTimeout("add_7h_publishing_button();", 250);
		}
		
		add_7h_publishing_button();

		function insert_7h_button_before_publish_button(publishing_div) {
			var publish_button_index = publishing_div.innerHTML.indexOf('<div id="publishing-action">');

			var html_before_publish_button = publishing_div.innerHTML.substr(0, publish_button_index);
			var html_starting_from_publish_button = publishing_div.innerHTML.substr(publish_button_index);

			publishing_div.innerHTML = html_before_publish_button + publishing_button_7h_html + html_starting_from_publish_button;
		}

		function insert_7h_button_at_far_left(publishing_div) {
			//var publish_button_index = publishing_div.innerHTML.indexOf('<div id="publishing-action">');

			//var html_before_publish_button = publishing_div.innerHTML.substr(0, publish_button_index);
			//var html_starting_from_publish_button = publishing_div.innerHTML.substr(publish_button_index);

			publishing_div.innerHTML = publishing_button_7h_html + publishing_div.innerHTML;
		}

		/*
		function publish_to_7h() {
			// add hidden field tag to set option to publish to 7h
			var submit_to_7h_input_html = '<input type="hidden" name="publish_to_7h" value="true"><input type="hidden" name="publish" value="publish">';
			var post_form = document.getElementById("post");
			post_form.innerHTML = submit_to_7h_input_html +  post_form.innerHTML;

			//post_form.publish.value = "
			document.post.submit();
			//post_form.submit();
		}
		*/
	</script>
html;
}
add_action('wp_print_styles', 'show_publish_option_7h_publishing');


// this is for debugging only.  use publish_post action when live (send only when article is first created)
//add_action('save_post', 'publish_blog_post_7h_publishing');

?>
