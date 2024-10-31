<?php
/*
Plugin Name: SF-ContactForm
Plugin URI: http://www.cuplaweb.com/software/wordpress-plugins/sf-contact-form/
Description: SF Contact Form is a contact form plugin which integrates with the SalesForce.com web 2 lead service, automatically creating a lead in SalesForce CRM when a user submits the form.
Author: Ronan Quirke
Author URI: http://www.cuplaweb.com
Version: 0.2.0
License: GPL
Based on: http://ryanduff.net/projects/wp-contactform/
*/
/*  Copyright 2009 Ronan Quirke  (email : ronan@cuplaweb.com)

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


load_plugin_textdomain('wpsf',$path = 'wp-content/plugins/sf-contact-form');

// Start a session
$sess_name = session_name();
if (session_start())
{
	setcookie($sess_name, session_id(), NULL, '/', $_SERVER["HTTP_HOST"], FALSE, TRUE);
}

//Grab some default user info, if available 
$wpsf_auto_email = get_profile('user_email');
$wpsf_auto_ID = get_profile('ID');
$wpsf_auto_first_name = get_usermeta($wpsf_auto_ID, 'first_name');
$wpsf_auto_last_name = get_usermeta($wpsf_auto_ID, 'last_name');

// Lets decide which fields are required
// set to reqired by giving value: (' . __('required', 'wpsf') . ')
//$fname_required = false;
//$lname_required = false;
$name_required = false;
$email_required = false;
$phone_required = false;
$msg_required = false;

if ($_POST && empty($_POST['wpsf_email'])) {
	$_POST['wpsf_email'] = $wpsf_auto_email;
}
if ($_POST && empty($_POST['wpsf_your_first_name'])) {
	$_POST['wpsf_your_first_name'] = $wpsf_auto_first_name;
}

if ($_POST && empty($_POST['wpsf_your_last_name'])) {
	$_POST['wpsf_your_last_name'] = $wpsf_auto_last_name;
}

/* Declare strings that change depending on input. This also resets them so errors clear on resubmission. */
$wpsf_strings = array(
	//'fname' => '<div class="contactright"><input type="text" name="wpsf_your_first_name" id="wpsf_your_name" size="30" maxlength="50" value="' . $_POST['wpsf_your_first_name'] . '" /> ' . $fname_required . '</div>',
	//'lname' => '<div class="contactright"><input type="text" name="wpsf_your_last_name" id="wpsf_your_name" size="30" maxlength="50" value="' . $_POST['wpsf_your_last_name'] . '" /> ' . $lname_required . '</div>',
	'name' => '<div class="contactright"><input type="text" name="wpsf_your_name" id="wpsf_your_name" size="30" maxlength="50" value="' . $_POST['wpsf_your_name'] . '" /> ' . $name_required . '</div>',
	'email' => '<div class="contactright"><input type="text" name="wpsf_email" id="wpsf_email" size="30" maxlength="50" value="' . $_POST['wpsf_email'] . '" /> ' . $email_required . '</div>',
	'phone' => '<div class="contactright"><input type="text" name="wpsf_phone" id="wpsf_phone" size="30" maxlength="50" value="' . $_POST['wpsf_phone'] . '" /> ' . $phone_required . '</div>',
	'msg' => '<div class="contactright"><textarea name="wpsf_msg" id="wpsf_msg" cols="35" rows="8" >' . $_POST['wpsf_msg'] . '</textarea></div>',
	'error' => '');

/*
This shows the quicktag on the write pages
Based off Buttonsnap Template
http://redalt.com/downloads
*/
if(get_option('wpsf_show_quicktag') == true) {
	include('buttonsnap.php');

	add_action('init', 'wpsf_button_init');
	add_action('marker_css', 'wpsf_marker_css');

	function wpsf_button_init() {
		$wpsf_button_url = buttonsnap_dirname(__FILE__) . '/wpsf_button.png';

		buttonsnap_textbutton($wpsf_button_url, __('Insert SF Contact Form', 'wpsf'), '<!--sfcontact form-->');
		buttonsnap_register_marker('contact form', 'wpsf_marker');
	}

	function wpsf_marker_css() {
		$wpsf_marker_url = buttonsnap_dirname(__FILE__) . '/wpsf_marker.gif';
		echo "
			.wpsf_marker {
					display: block;
					height: 15px;
					width: 155px
					margin-top: 5px;
					background-image: url({$wpsf_marker_url});
					background-repeat: no-repeat;
					background-position: center;
			}
		";
	}
}

function wpsf_is_malicious($input) {
	$is_malicious = false;
	$bad_inputs = array("\r", "\n", "mime-version", "content-type", "cc:", "to:");
	foreach($bad_inputs as $bad_input) {
		if(strpos(strtolower($input), strtolower($bad_input)) !== false) {
			$is_malicious = true; break;
		}
	}
	return $is_malicious;
}

/* This function checks for errors on input and changes $wpsf_strings if there are any errors. Shortcircuits if there has not been a submission */
function wpsf_check_input() {
	
	global $wpsf_strings;
	$ok = true;
	
	if(!(isset($_POST['wpsf_stage']))) {return false;} // Shortcircuit.
	if(!(isset($_POST['wpsf_referers']))) {return false;} // Spam prevention
	if(!(isset($_POST['wpsf_pages']))) {return false;} // Spam prevention
	
	$spam_check = get_option('wpsf_show_spamcheck');
	if ($spam_check) {
		// If showing the "This is not spam"-checkbox, check if this checkbox is set to true
		if ($_SESSION['wpsf_spamanswer'] != $_POST['wpsf_not_spam']) {			 
			$ok = false;
			$reason = 'spam';
		}
	}

	//$_POST['wpsf_your_first_name'] = htmlentities(stripslashes(trim($_POST['wpsf_your_first_name'])));
	//$_POST['wpsf_your_last_name'] = htmlentities(stripslashes(trim($_POST['wpsf_your_last_name'])));
	$_POST['wpsf_your_name'] = htmlentities(stripslashes(trim($_POST['wpsf_your_name'])));
	$_POST['wpsf_email'] = htmlentities(stripslashes(trim($_POST['wpsf_email'])));
	$_POST['wpsf_phone'] = htmlentities(stripslashes(trim($_POST['wpsf_phone'])));
	$_POST['wpsf_msg'] = htmlentities(stripslashes(trim($_POST['wpsf_msg'])));

	

	/*if(empty($_POST['wpsf_your_first_name']) && $fname_required)
	{
		$ok = false; $reason = 'empty';
		$wpsf_strings['fname'] = '<div class="contactright"><input type="text" name="wpsf_your_first_name" id="wpsf_your_first_name" size="30" maxlength="50" value="' . $_POST['wpsf_your_first_name'] . '" class="contacterror" /> (' . __('required', 'wpsf') . ')</div>';
	}
	
	if(empty($_POST['wpsf_your_last_name']) && $lname_required)
	{
		$ok = false; $reason = 'empty';
		$wpsf_strings['lname'] = '<div class="contactright"><input type="text" name="wpsf_your_last_name" id="wpsf_your_last_name" size="30" maxlength="50" value="' . $_POST['wpsf_your_last_name'] . '" class="contacterror" /> (' . __('required', 'wpsf') . ')</div>';
	}*/

	if(empty($_POST['wpsf_your_name']) && $name_required)
	{
		$ok = false; $reason = 'empty';
		$wpsf_strings['name'] = '<div class="contactright"><input type="text" name="wpsf_your_name" id="wpsf_your_name" size="30" maxlength="50" value="' . $_POST['wpsf_your_name'] . '" class="contacterror" /> (' . __('required', 'wpsf') . ')</div>';
	}
	
    if(!is_email($_POST['wpsf_email']) && $email_required)
    {
	    $ok = false; $reason = 'empty';
	    $wpsf_strings['email'] = '<div class="contactright"><input type="text" name="wpsf_email" id="wpsf_email" size="30" maxlength="50" value="' . $_POST['wpsf_email'] . '" class="contacterror" /> (' . __('required', 'wpsf') . ')</div>';
	}

	/* if(!is_email($_POST['wpsf_phone']))
    {
	    $ok = true; $reason = 'empty';
	    $wpsf_strings['phone'] = '<div class="contactright"><input type="text" name="wpsf_phone" id="wpsf_phone" size="30" maxlength="50" value="' . $_POST['wpsf_phone'] . '" class="contacterror" /> </div>';
	}
	
    if(empty($_POST['wpsf_msg']))
    {
	    $ok = true; $reason = 'empty';
	    $wpsf_strings['msg'] = '<div class="contactright"><textarea name="wpsf_msg" id="wpsf_msg" cols="35" rows="8" class="contacterror">' . $_POST['wpsf_msg'] . '</textarea></div>';
	}*/

	if(wpsf_is_malicious($_POST['wpsf_your_first_name']) || wpsf_is_malicious($_POST['wpsf_your_last_name']) || wpsf_is_malicious($_POST['wpsf_email']) || wpsf_is_malicious($_POST['wpsf_phone'])) {
		$ok = false; $reason = 'malicious';
	}

	if($ok == true)
	{
		return true;
	}
	else {
		if($reason == 'malicious') {
			$wpsf_strings['error'] = "<div style='font-weight: bold;'>You can not use any of the following in the First Name, Last Name Email or Phone fields: a linebreak, or the phrases 'mime-version', 'content-type', 'cc:' or 'to:'.</div>";
		} elseif($reason == 'empty') {
			$wpsf_strings['error'] = '<div style="font-weight: bold;">' . stripslashes(get_option('wpsf_error_msg')) . '</div>';
		}elseif($reason == 'spam') {
			$wpsf_strings['error'] = '<div style="font-weight: bold;">Please answer the anti-spam question.</div>';
		}
		return false;
	}
}

/*Wrapper function which calls the form.*/
function wpsf_callback( $content ) {
	global $wpsf_strings;

	/* Run the input check. */		
	if(false === strpos($content, '<!--sfcontact form-->')) {
		return $content;
	}

    if(wpsf_check_input()) // If the input check returns true (ie. there has been a submission & input is ok)
    {
	    // Check if we have curl available:
	    if (function_exists('curl_init') && get_option('wpsf_org_id')) {
		if (isset($_POST)) {
	
		if (count($_POST) == 0) exit("Error.  No data was passed
		     to this script.");
			
		// variable to hold cleaned up a version of $_POST data
		$cleanPOST = array();	
		
		//$cleanPOST["first_name"] = htmlentities(stripslashes(trim($_POST['wpsf_your_first_name'])));
		//$cleanPOST["last_name"] =  htmlentities(stripslashes(trim($_POST['wpsf_your_last_name'])));
		$names = split(" ", htmlentities(stripslashes(trim($_POST['wpsf_your_name']))), 2);
		$cleanPOST["first_name"] = $names[0];
		$cleanPOST["last_name"] = $names[1];
        $cleanPOST["email"] = htmlentities(stripslashes(trim($_POST['wpsf_email'])));
        $cleanPOST["phone"] = htmlentities(stripslashes(trim($_POST['wpsf_phone'])));
        $cleanPOST["lead_source"] = "Web";
        $cleanPOST["description"] = htmlentities(stripslashes(trim($_POST['wpsf_msg'])));
        
		// Add the Org ID
		$cleanPOST["oid"] = get_option('wpsf_org_id');
				
		$fullStringlen = strlen($cleanPOST["oid"] . $_POST['wpsf_your_first_name'] . $_POST['wpsf_your_last_name'] . $_POST['wpsf_email'] . $_POST['wpsf_phone'] . "Web" . $_POST['wpsf_msg']);
			
		} else {
		 exit("Error.  No data was passed to this script.");
		}
		// Create a new cURL resource
		$ch = curl_init();	
		
		if (curl_error($ch) != "") {
		    echo "Error: $error\n";
		}


		$headers = array(); 
		$headers[] = "Content-Type: text/html; charset=UTF-8"."\n" . "Accept: text/html; charset=UTF-8"."\n". "Content-length: $fullStringlen\n"; ; 

		// set headers
		//curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers); 
		// Point to the Salesforce Web to Lead page
		curl_setopt($ch, CURLOPT_URL, 
		"https://www.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8");
		// This is occassionally required to stop CURL from verifying the peer's certificate.
		// CURLOPT_SSL_VERIFYHOST may also need to be TRUE or FALSE if
		// CURLOPT_SSL_VERIFYPEER is disabled (it defaults to 2 - check the existence of a
		// common name and also verify that it matches the hostname provided)
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// Optional: Return the result instead of printing it
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				
		// Set the method to POST
		curl_setopt($ch, CURLOPT_POST, 1);
				
		// Pass POST data
		curl_setopt(
		$ch, CURLOPT_POSTFIELDS, http_build_query($cleanPOST));
				
		$response = curl_exec($ch); // Post to Salesforce
		curl_close($ch); // close cURL resource
		print_r($response);

		}else{
		$SF_Result = "fail";	
		}

            $recipient = get_option('wpsf_email');
            $subject = get_option('wpsf_subject');
						$success_msg = get_option('wpsf_success_msg');
						$success_msg = stripslashes($success_msg);

            //$name = $_POST['wpsf_your_first_name'] . " " . $_POST['wpsf_your_last_name'];
            $name = $_POST['wpsf_your_name'];
            $email = $_POST['wpsf_email'];
            $phone = $_POST['wpsf_phone'];
            $msg = $_POST['wpsf_msg'];

      			$headers = "MIME-Version: 1.0\n";
						$headers .= "From: $name <$email>\n";
						$headers .= "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\n";
			$fullmsg = "";
			if($SF_Result=="fail"){
			$fullmsg .= "WARNING: SalesForce Lead was not generated due to an error. Check your Org ID is set in options\n\n";
			}
			
            $fullmsg .= $response[0] ."  $name wrote:\n";
            $fullmsg .= wordwrap($msg, 80, "\n") . "\n\n";
            $fullmsg .= "Phone: $phone\n";
            $fullmsg .= "IP: " . sfgetip();

            
            mail($recipient, $subject, $fullmsg, $headers);

            $results = '<div style="font-weight: bold;">' . $success_msg . '</div>';
            echo $results;
    }
    else // Else show the form. If there are errors the strings will have updated during running the inputcheck.
    {
        $form = '<div class="contactform">
        ' . $wpsf_strings['error'] . '
        	<form action="' . get_permalink() . '" method="post">';
        	
        	//$form .= '	<div class="contactleft"><label for="wpsf_your_first_name">' . __('Your First Name: ', 'wpsf') . '</label></div>' . $wpsf_strings['fname']  . '
        	//	<div class="contactleft"><label for="wpsf_your_last_name">' . __('Your Last Name: ', 'wpsf') . '</label></div>' . $wpsf_strings['lname']  . '';
        	
        	$form .= '	<div class="contactleft"><label for="wpsf_your_name">' . __('Your Name: ', 'wpsf') . '</label></div>' . $wpsf_strings['name']  . '	
        		<div class="contactleft"><label for="wpsf_email">' . __('Your Email:', 'wpsf') . '</label></div>' . $wpsf_strings['email'] . '
        		<div class="contactleft"><label for="wpsf_phone">' . __('Your Phone:', 'wpsf') . '</label></div>' . $wpsf_strings['phone'] . '
        		<div class="contactleft"><label for="wpsf_msg">' . __('Your Message: ', 'wpsf') . '<br/>(optional)</label></div>' . $wpsf_strings['msg'];
        $spam_check = get_option('wpsf_show_spamcheck');
				if ($spam_check) {
					$rand1 = rand(0,20);
					$rand2 = rand(0,20);
					$form .= '<div class="contactleft"><label>'. get_option('wpsf_spamcheck1_txt') . ' '.$rand1.' '. get_option('wpsf_spamcheck2_txt') . ' '.$rand2.' ' . get_option('wpsf_spamcheck3_txt') . '&nbsp;  </label></div><div class="contactright"> <input style="margin:0; width: 100px;" type="text" name="wpsf_not_spam" autocomplete="off"/> </div>';
					// START THE SESSION AND SET THE COOKIE FOR ALL SUBDOMAINS
					
					$_SESSION['wpsf_spamanswer'] = $rand1+$rand2;
				}
		$form .= '	<div class="contactright"><input type="submit" name="Submit" value="' . __('Submit', 'wpsf') . '" id="contactsubmit" /><input type="hidden" name="wpsf_stage" value="process" /></div>
       <input type="hidden" name="wpsf_referers" value=\'' .urlencode(serialize($_SESSION['wpsfreferer'])). '\' />
				<input type="hidden" name="wpsf_pages" value=\'' .urlencode(serialize($_SESSION['wpsfpages'])). '\' />	
		</form>
        </div>
        <div style="clear:both; height:1px;">&nbsp;</div>';
        return str_replace('<!--sfcontact form-->', $form, $content);
    }
}


/*Can't use WP's function here, so lets use our own*/
function sfgetip() {
	if (isset($_SERVER))
	{
 		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
 		{
  			$ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];
 		}
 		elseif (isset($_SERVER["HTTP_CLIENT_IP"]))
 		{
  			$ip_addr = $_SERVER["HTTP_CLIENT_IP"];
 		}
 		else
 		{
 			$ip_addr = $_SERVER["REMOTE_ADDR"];
 		}
	}
	else
	{
 		if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
 		{
  			$ip_addr = getenv( 'HTTP_X_FORWARDED_FOR' );
 		}
 		elseif ( getenv( 'HTTP_CLIENT_IP' ) )
 		{
  			$ip_addr = getenv( 'HTTP_CLIENT_IP' );
 		}
 		else
 		{
  			$ip_addr = getenv( 'REMOTE_ADDR' );
 		}
	}
return $ip_addr;
}

function referer_session() {
	$baseurl = get_bloginfo('url');
	if (! isset($_SESSION) ) {
		session_start();
	}
	if (! isset($_SESSION['wpsfpages']) || ! is_array($_SESSION['wpsfpages']) ) {
		$_SESSION['wpsfpages'] = array();
	}
	if (! isset($_SESSION['wpsfreferer']) || ! is_array($_SESSION['wpsfreferer']) ) {
		$_SESSION['wpsfreferer'] = array();
	}
	if ( (strpos($_SERVER['HTTP_REFERER'], $baseurl) === false) && ! (in_array($_SERVER['HTTP_REFERER'], $_SESSION['wpsfreferer'])) ) {
		if (! isset($_SERVER['HTTP_REFERER'])) {
			$_SESSION['wpsfreferer'][] = "Type-in or bookmark";
		} else {
			$_SESSION['wpsfreferer'][] = $_SERVER['HTTP_REFERER'];	
		}
	}
	if (end($_SESSION['wpsfpages']) != "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']) {
		$_SESSION['wpsfpages'][] = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];	
	}
}

/*CSS Styling*/
function wpsf_css() {
	?>
<style type="text/css" media="screen">

/* Begin Contact Form CSS */
.contactform {
	position: static;
	overflow: hidden;
	width: 95%;
}

.contactleft {
	width: 25%;
	white-space: pre;
	text-align: right;
	clear: both;
	float: left;
	display: inline;
	padding: 4px;
	margin: 5px 0;
}

.contactright {
	width: 70%;
	text-align: left;
	float: right;
	display: inline;
	padding: 4px;
	margin: 5px 0;
}

.contacterror {
	border: 1px solid #ff0000;
}

.contactsubmit {
}
/* End Contact Form CSS */

	</style>

<?php

	}

function wpsf_add_options_page() {
		add_options_page(__('Contact Form Options', 'wpsf'), __('SF Contact Form', 'wpsf'), 'manage_options', 'sf-contact-form/options-sfcontactform.php');
	}

/* Action calls for all functions */

//if(get_option('wpsf_show_quicktag') == true) {add_action('admin_footer', 'wpsf_add_quicktag');}

add_action('admin_menu', 'wpsf_add_options_page');
add_filter('wp_head', 'wpsf_css');
add_filter('the_content', 'wpsf_callback', 7);
add_action('admin_notices', 'SFContact_warning');

function SFContact_warning(){


	if ( !get_option('wpsf_org_id')){
echo "
		<div id='warning' class='updated fade'><p><strong>".__('SF Contact Form is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your SalesForce.com Organisation ID</a> for it to work.<br/><pre>Hint: in SalesForce.com, go to Setup -> Company Profile -> Company Information</pre>'), "./options-general.php?page=sf-contact-form/options-sfcontactform.php")."</p></div>
		";
	}
}

?>
