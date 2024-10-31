<?php
/*
Author: Ronan Quirke
Author URI: http://cuplaweb.com
Description: Administrative options for SF-ContactForm
*/

//load_plugin_textdomain('wpsf',$path = 'wp-content/plugins/sf-contact-form');
$location = get_option('siteurl') . '/wp-admin/admin.php?page=sf-contact-form/options-sfcontactform.php'; // Form Action URI
// Guess the location
$wpsfpluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

load_plugin_textdomain('wpsf',$wpsfpluginpath);

/*Lets add some default options if they don't exist*/
add_option('wpsf_email', __('you@example.com', 'wpsf'));
add_option('wpsf_subject', __('Contact Form Results', 'wpsf'));
add_option('wpsf_success_msg', __('Thanks for your comments!', 'wpsf'));
add_option('wpsf_error_msg', __('Please fill in the required fields.', 'wpsf'));
add_option('wpsf_show_quicktag', TRUE);
add_option('wpsf_org_id', __('', 'wpsf'));
add_option('wpsf_spamcheck1_txt', __('The sum of', 'wpsf'));
add_option('wpsf_spamcheck2_txt', __('and', 'wpsf'));
add_option('wpsf_spamcheck3_txt', __('is:', 'wpsf'));
add_option('wpsf_show_spamcheck', TRUE);


/*check form submission and update options*/
if ('process' == $_POST['stage'])
{
update_option('wpsf_email', $_POST['wpsf_email']);
update_option('wpsf_subject', $_POST['wpsf_subject']);
update_option('wpsf_success_msg', $_POST['wpsf_success_msg']);
update_option('wpsf_error_msg', $_POST['wpsf_error_msg']);
update_option('wpsf_org_id', $_POST['wpsf_org_id']);
update_option('wpsf_spamcheck1_txt', $_POST['wpsf_spamcheck1_txt']); 
update_option('wpsf_spamcheck2_txt', $_POST['wpsf_spamcheck2_txt']); 
update_option('wpsf_spamcheck3_txt', $_POST['wpsf_spamcheck3_txt']); 

if(isset($_POST['wpsf_show_quicktag'])) // If wpsf_show_quicktag is checked
	{update_option('wpsf_show_quicktag', true);}
	else {update_option('wpsf_show_quicktag', false);}

	if(isset($_POST['wpsf_show_spamcheck'])) {
		// If wpsf_show_spamcheck is checked
		update_option('wpsf_show_spamcheck', true);
	} else {
		update_option('wpsf_show_spamcheck', false);
	}
	
}



/*Get options for form fields*/
$wpsf_email = stripslashes(get_option('wpsf_email'));
$wpsf_subject = stripslashes(get_option('wpsf_subject'));
$wpsf_success_msg = stripslashes(get_option('wpsf_success_msg'));
$wpsf_error_msg = stripslashes(get_option('wpsf_error_msg'));
$wpsf_org_id = stripslashes(get_option('wpsf_org_id'));
$wpsf_show_quicktag = get_option('wpsf_show_quicktag');
$wpsf_spamcheck1_txt = stripslashes(get_option('wpsf_spamcheck1_txt'));
$wpsf_spamcheck2_txt = stripslashes(get_option('wpsf_spamcheck2_txt'));
$wpsf_spamcheck3_txt = stripslashes(get_option('wpsf_spamcheck3_txt'));
$wpsf_show_spamcheck = get_option('wpsf_show_spamcheck');

?>

<div class="wrap">
  <h2><?php _e('SF Contact Form Options', 'wpsf') ?></h2>
  <iframe style="float: right; width: 200px; height: 250px" src="http://www.cuplaweb.com/plugs/sf-contact-form.php"></iframe>
				
  <form name="form1" method="post" action="<?php echo $location ?>&amp;updated=true">
	<input type="hidden" name="stage" value="process" />
    <table width="100%" cellspacing="2" cellpadding="5" class="editform">
    <tr valign="top">
        <th scope="row"><?php _e('SalesForce Organisation ID:', 'wpsf') ?></th>
        <td><input name="wpsf_org_id" type="text" id="wpsf_org_id" value="<?php echo $wpsf_org_id; ?>" size="40" />
        <br />
<?php _e('This is your Salesforce organisation ID.', 'wpsf') ?></td>
      </tr>
      <tr valign="top">
        <th scope="row"><?php _e('E-mail Address:', 'wpsf') ?></th>
        <td><input name="wpsf_email" type="text" id="wpsf_email" value="<?php echo $wpsf_email; ?>" size="40" />
        <br />
<?php _e('This address is where the email will be sent to.', 'wpsf') ?></td>
      </tr>
      <tr valign="top">
        <th scope="row"><?php _e('Subject:', 'wpsf') ?></th>
        <td><input name="wpsf_subject" type="text" id="wpsf_subject" value="<?php echo $wpsf_subject; ?>" size="50" />
        <br />
<?php _e('This will be the subject of the email.', 'wpsf') ?></td>
      </tr>
     </table>

	<fieldset class="options">
		<legend><?php _e('Messages', 'wpsf') ?></legend>
		<table width="100%" cellspacing="2" cellpadding="5" class="editform">
		  <tr valign="top">
			<th scope="row"><?php _e('Success Message:', 'wpsf') ?></th>
			<td><textarea name="wpsf_success_msg" id="wpsf_success_msg" style="width: 80%;" rows="4" cols="50"><?php echo $wpsf_success_msg; ?></textarea>
			<br />
	<?php _e('When the form is sucessfully submitted, this is the message the user will see.', 'wpsf') ?></td>
		  </tr>
		  <tr valign="top">
			<th scope="row"><?php _e('Error Message:', 'wpsf') ?></th>
			<td><textarea name="wpsf_error_msg" id="wpsf_error_msg" style="width: 80%;" rows="4" cols="50"><?php echo $wpsf_error_msg; ?></textarea>
			<br />
	<?php _e('If the user skips a required field, this is the message he will see.', 'wpsf') ?> <br />
	<?php _e('You can apply CSS to this text by wrapping it in <code>&lt;p style="[your CSS here]"&gt; &lt;/p&gt;</code>.', 'wpsf') ?><br />
	<?php _e('ie. <code>&lt;p style="color:red;"&gt;Please fill in the required fields.&lt;/p&gt;</code>.', 'wpsf') ?></td>
		  </tr>
		  <tr valign="top">
        <th scope="row"><?php _e('"Anti-Spam" Text :', 'wpsf') ?></th>
        <td><input name="wpsf_spamcheck1_txt" type="text" id="wpsf_spamcheck1_txt" value="<?php echo $wpsf_spamcheck1_txt; ?>" size="12" /><input name="wpsf_spamcheck2_txt" type="text" id="wpsf_spamcheck2_txt" value="<?php echo $wpsf_spamcheck2_txt; ?>" size="12" /><input name="wpsf_spamcheck3_txt" type="text" id="wpsf_spamcheck3_txt" value="<?php echo $wpsf_spamcheck3_txt; ?>" size="12" />
        <br />
		<?php _e('This will be the anti-spam text in the contact form.', 'wpsf') ?></td>
      </tr>
		</table>

	</fieldset>

	<fieldset class="options">
		<legend><?php _e('Advanced', 'wpsf') ?></legend>

	    <table width="100%" cellpadding="5" class="editform">
	      <tr valign="top">
	        <th width="30%" scope="row" style="text-align: left"><?php _e('Show \'Contact Form\' Quicktag', 'wpsf') ?></th>
	        <td>
	        	<input name="wpsf_show_quicktag" type="checkbox" id="wpsf_show_quicktag" value="wpsf_show_quicktag"
	        	<?php if($wpsf_show_quicktag == TRUE) {?> checked="checked" <?php } ?> />
			</td>
	      </tr>
	      <tr valign="top">
				<th width="30%" scope="row" style="text-align: left"><?php _e('Show \'Spam Prevention\' Option', 'wpsf') ?></th>
				<td>
					<input name="wpsf_show_spamcheck" type="checkbox" id="wpsf_show_spamcheck" value="wpsf_show_spamcheck"
					<?php if($wpsf_show_spamcheck == TRUE) {?> checked="checked" <?php } ?> />
				</td>
			</tr>
	     </table>

	</fieldset>

    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Update Options', 'wpsf') ?> &raquo;" />
    </p>
  </form>
  
  </div>
</div>