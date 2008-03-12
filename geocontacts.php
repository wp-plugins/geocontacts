<?php
/*
Plugin Name: GeoContacts
Plugin Script: geocontacts.php
Plugin URI: http://www.glutenenvy.com/software/geocontacts
Description: Geoencode addresses with built-in contact Gravatar support. Build templates and embed addresses in a post or page with the GEOCONTACT[] anchor.
Version: 0.1.1
License: GPL
Author: Ben King
Author URI: http://www.glutenenvy.com/
Min WP Version: 2.0
Max WP Version: 2.5

=== RELEASE NOTES ===2008-03-12 - v0.1 - first version
*/

/*
Commercial users of this plug-in should do one of the following:
1. Link back to glutenenvy.com 
2. Donate to development of this plugin
3. Promote the Gluten Free cause by sending me useful tid-bits of 
information on products, services, legislation, etc., etc.

This is program is free software; you can redistribute it and/or modifyit under the terms of the GNU General Public License as published bythe Free Software Foundation; either version 2 of the License, or(at your option) any later version.This program is distributed in the hope that it will be useful,but WITHOUT ANY WARRANTY; without even the implied warranty ofMERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See theGNU General Public License for more details.You should have received a copy of the GNU General Public Licensealong with this program; if not, write to the Free SoftwareFoundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USAOnline: http://www.gnu.org/licenses/gpl.txt*/
$geocontacts_version = '0.1';

define('geocontacts_google_geocoder', 'http://maps.google.com/maps/geo?q=', false);
define('geocontacts_google_regexp', "\<coordinates\>(.*),(.*),0\<\/coordinates\>");

add_action('admin_head', 'geocontacts_adminhead');
function geocontacts_adminhead() {
    ?><style type="text/css">
    .wrap h2 {margin:1em 0 0 0}
    form.geocontacts div.line {width:95%; margin:auto}
    form.geocontacts div.input {float:left}
    form.geocontacts div.input label {font-size:smaller; margin:0}
    form.geocontacts div.input input, form div.input textarea {width:100%; margin:0}
    form.geocontacts p.submit {clear:both}
    div#contact-info {}
    table#geocontacts-table {border-collapse:collapse}
    table#geocontacts-table th {text-align:left}
    table#geocontacts-table tr td {border:2px solid #e5f3ff; margin:0}
    table#geocontacts-table tr:hover td {cursor:pointer}
    form.geocontacts tr input {width:95%; border-color:#e5f3ff; background-color: white}
    </style>
    <?php
    echo geocontacts_geocode_header();
}

add_action('wp_head', 'geocontacts_wphead');
function geocontacts_wphead() {
	?>
    <style type="text/css">
      ol.geocontacts-list {list-style-type:none}
      li.geocontacts-item {border:1px solid #666; padding:0.5em; margin:0.5em auto; width:99%; height:10em}
      li.geocontacts-item .name {font-size:1.2em; font-weight:bolder}
      li.geocontacts-item .photo {float:right}
      li.geocontacts-item .address {display:block; margin-left:1em; width:50%; float:left; font-size:0.8em}
      li.geocontacts-item .address span {display:block}
    </style>
    <?php
	echo geocontacts_geocode_header();
} // end geocontacts_wphead()

function geocontacts_geocode_header() {
    $scripts = "<!-- Location provided by GeoContacts v ".get_option("geocontacts_version")." (http://www.glutenenvy.com) -->\n";
    $scripts .= "<meta name=\"plugin\" content=\"geocontacts\">";

	$google_apikey = get_settings('geocontacts_google_apikey', true);
	if($google_apikey != "") {
		$scripts .= "\n".'<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='. $google_apikey .'" ></script>';
	}
	
	$plugindir = get_bloginfo('wpurl') . "/wp-content/plugins/geocontacts";
	$scripts .= "\n".'<script type="text/javascript" src="'.$plugindir.'/geocontacts.js"></script>';
	
	return $scripts;
}

add_action('admin_menu', 'geocontacts_menus');
function geocontacts_menus() {
	$toplevelmenu = get_option('geocontacts_toplevelmenu');
	// The following menus have to be added in different orders depending on
	// whether the GeoContacts is a top-level menu or not.  I'm not sure why!
	if ($toplevelmenu=='true') {
		add_menu_page(__('GeoContacts'), 'GeoContacts', 4, 'geocontacts/geocontacts.php', 'geocontacts_main');
	    add_submenu_page('geocontacts/geocontacts.php', 'Options', 'Options', 4, 'geocontacts_options', 'geocontacts_options');
	    $geocontacts_basefile = "admin.php";
	} else {
	    add_options_page('GeoContacts Options', 'GeoContacts', 4, 'geocontacts/geocontacts.php', 'geocontacts_options');
	    add_management_page('GeoContacts', 'GeoContacts', 4, 'geocontacts/geocontacts.php', 'geocontacts_main');
	    $geocontacts_basefile = "edit.php";
	}
}

function geocontacts_options() {
	// read in current settings from database
    $toplevelmenu = get_settings('geocontacts_toplevelmenu', true) ? 'checked="checked"' : '';

    $default_rss_enable = get_settings('geocontacts_rss_enable', true) ? 'checked="checked"' : '';
    $rss_format = get_settings('geocontacts_rss_format', true);

    $google_apikey = get_settings('geocontacts_google_apikey', true);
    $garmin_apikey = get_settings('geocontacts_garmin_apikey', true);
    
	$gravatar_enable = get_settings('geocontacts_gravatar_enable', true) ? 'checked="checked"' : '';
    $gravatar_rating = get_settings('geocontacts_gravatar_rating', true);
    $gravatar_size = get_settings('geocontacts_gravatar_size', true);
    $gravatar_image = get_settings('geocontacts_gravatar_image', true);

    $sort_by = get_settings('geocontacts_sort_by', true);

	?>
	<div class="wrap">

	<form class="geocontacts" method="post" action="options.php">
	<?php wp_nonce_field('update-options');
	echo "<h2>Options</h2><h3>Menu Location</h3>";
	if ($toplevelmenu) {
		echo '<p><em>';
		_e("Note: Disabling this option will move the options page and you will be presented with a browser error.  
		Just click &lsquo;back&rsquo; and then navigate to the 'Manage &raquo; GeoContacts' or 'Options &raquo; GeoContacts' tab.");
		echo '</em></p>';
	} ?>

	<p>
	  <?php _e('Give GeoContacts its own top-level menu item? '); ?>
      <input type="checkbox" id="geocontacts_toplevelmenu" name="geocontacts_toplevelmenu" 
		     value="true" <?php echo $toplevelmenu ?> />
	</p>
	<h3>Map Providers</h3>	
	<p>
		<?php _e('Please enter your Google API Key: ') ?>
		<a href="http://www.google.com/apis/maps/signup.html"
		   target="_blank" title="GoogleMaps API Registration"> Get your GoogleMaps API Key here</a><br />
		<input name="geocontacts_google_apikey" type="text" id="geocontacts_google_apikey" style="width: 95%"
			   value="<?php echo $google_apikey ?>" size="45" />
	</p>
<?php
/*	
//	<p>
//		<?php _e('Please enter your Garmin API Key: ') ?>
//		<a href="http://developer.garmin.com/web-device/garmin-communicator-plugin/get-your-site-key/"
//		   target="_blank" title="Garmin API Registration"> Get your Garmin API Site Key here (localhost does not require a key)</a><br />
//		<input name="geocontacts_garmin_apikey" type="text" id="geocontacts_garmin_apikey" style="width: 95%"
//			   value="<?php echo $garmin_apikey ?>" size="45" />
//	</p>
*/
?>
	<h3>Gravatars</h3>
	<p>
		<?php _e('Enable Gravatar support? ') ?>
		<input name="geocontacts_gravatar_enable" type="checkbox" id="geocontacts_gravatar_enable"
		       value="true" <?php echo $gravatar_enable ?> />
	</p>
	<p>
		<?php _e('Gravatar rating system: ') ?>
		<select name="geocontacts_gravatar_rating" id="geocontacts_gravatar_rating">
		<?php
		    $select = "
			<option value='G' >[G] Child</option>
		    <option value='PG'>[G PG] Young Adult</option>
		    <option value='R' >[G PG R] Adult</option>
		    <option value='X' >[G PG R X] Explicit</option>";
		    echo str_replace("value='$gravatar_rating'","value='$gravatar_rating' selected='selected'", $select);
		?>
		</select>
	</p>
	<p>
		<?php _e('Size Gravatars to: ') ?>
		<select name="geocontacts_gravatar_size" id="geocontacts_gravatar_size">
		<?php
		    $select = "<option value='80'>80x80</option>
		    <option value='75'>75x75</option>
		    <option value='70'>70x70</option>
		    <option value='65'>65x65</option>
		    <option value='60'>60x60</option>
		    <option value='55'>55x55</option>
		    <option value='50'>50x50</option>
		    <option value='45'>45x45</option>
		    <option value='40'>40x40</option>
		    <option value='35'>35x35</option>
		    <option value='30'>30x30</option>
		    <option value='25'>25x25</option>
		    <option value='20'>20x20</option>
		    <option value='15'>15x15</option>
		    <option value='10'>10x10</option>
		    <option value='5'>5x5</option>";
		    echo str_replace("value='$gravatar_size'","value='$gravatar_size' selected='selected'", $select);
		?>
		</select>
	</p>
	<p>
		<?php _e('Default Gravatar image url: (Note: This image is not resized.)') ?>
		<input name="geocontacts_gravatar_image" type="text" id="geocontacts_gravatar_image"
			   style="width: 95%" value="<?php echo $gravatar_image ?>" size="45" />	         
	</p>	
	<h3>Display Options</h3>	
	<p>
		<?php _e('Sort full contact list display by: ') ?>
		<select name="geocontacts_sort_by" id="geocontacts_sort_by">
		<?php
		    $select = "
		    <option value='first_name'>First Name</option>
		    <option value='last_name'>Last Name</option>
		    <option value='email'>Email Address</option>
		    <option value='phone'>Phone Number</option>
		    <option value='organization'>Organization</option>
		    <option value='lat'>Lattitude</option>
		    <option value='lon'>Longitude</option>
		    <option value='zoom'>Zoom Level</option>
		    <option value='address_line1'>Address Line 1</option>
		    <option value='address_line2'>Address Line 2</option>
		    <option value='city'>City</option>
		    <option value='state'>State</option>
		    <option value='postcode'>Postal Code</option>
		    <option value='country'>Country</option>";
		    echo str_replace("value='$sort_by'","value='$sort_by' selected='selected'", $select);
		?>
		</select>
	</p>
	
	<p class="submit">
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" 
	value="geocontacts_toplevelmenu,geocontacts_rss_enable,geocontacts_rss_format,geocontacts_google_apikey,geocontacts_gravatar_enable,geocontacts_gravatar_rating,geocontacts_gravatar_size,geocontacts_gravatar_image,geocontacts_garmin_apikey,geocontacts_sort_by" />

	<input type="submit" name="submit" value="<?php _e('Update Options'); ?> &raquo" />
	</p>
	</form>
	
	<?php geocontacts_docs(); ?>
	
	</div>
	<?php
}

function geocontacts_docs() {
	?>
	
	<h2>Documentation</h2>
  <h3>GeoContacts Quick Start</h3>
  <p>Install your site's Google key, select a good sort field for your contacts, enter 
  a few addresses and include one of the following in a post:<br />
  <em>GEOCONTACT[*]</em> - To display the entire contact list<br />
  <em>GEOCONTACT[1]</em> - To display one contact by its id #<br /></p>
  <p>GeoContacts supports Gravatars. Several options are available to tailer Gravatar use for your 
  website. Blank.gif is automatically provided for the default image when there is no Gravatar available for a 
  contact. You can also choose your own site default Gravatar by supplying a valid image url. Please note that this image 
  will not be resized, so pick an image with a reasonable size. Preferably this image will match 
  the size of Gravatar you have chosen in the options.</p>
  <p>You can format the contact layout for your website to just about anything you like. You will 
  find a few contact templates in the templates directory. Simply rename the one you want to use 
  on your site to default.htm. You are encouraged to edit these templates to your liking and share 
  any templates that work especially well for you. Be sure to save a copy of your working template 
  so it does not get lost.</p>

  <h3>Geocoding</h3>
  <p>The built-in geocoder is what makes GeoContacts an powerful contact manager plugin. Click the 
  geocode link in the edit contact window and you have coordinates provided by Google.</p>
  <p>You can fine tune coordinates by navigating the map shown in the edit contact window. Single
  click where you want the new marker. If you don't see a map, first make sure you have a valid Google key installed 
  for your site. If you already know you have a good key then try refreshing the page or dragging the map.</p>
  <p>A design choice has been made to limit the stored zoom level to 18. This works for most major US cities. 
  In many cases you can zoom in a few more times but the maximum stored zoom level per contact will not go above 18.</p>
  
  <h3>Advanced Usage</h3>
  <p>Anchor templates can be individually selected for a contact or the entire contact list. If you have a
  template named geo.htm in your templates folder, you can chose this file using the following format:</p>
  <p><em>GEOCONTACT[*,geo.htm]</em> - to display all contacts using the geo.htm template<br />
  <em>GEOCONTACT[1,geo.htm]</em> - to display contact 1 using the geo.htm template<br /></p>
  <p>Your templates can contain these hot-words.<br />
  {$gravatar} = fully built Gravatar url<br />
  {$first_name}<br />
  {$last_name}<br />
  {$address_line1}<br />
  {$address_line2}<br />
  {$city}<br />
  {$state}<br />
  {$postcode}<br />
  {$phone}<br />
  {$website}<br />
  {$email}<br />
  {$notes}<br />
  {$organization}<br />
  {$country}<br />
  {$lat} = geocoded latitude<br /> 
  {$lon} = geocoded longitude<br />
  {$zoom} = selected zoom level<br />
  {$mapsite} = url to map provider</p>
  <p>Multiple anchors can be listed per page. With some creative GeoContacts templates you can make a robust 
  review site for restaurants, hotels, and other destinations. You can make your own geocaching site or simply 
  make a contact list of friends and family. You have control of the templates so you can also
  make imaginative use of the first_name, last_name, phone, organization, and notes fields.</p>
  <h3>Test Environment</h3>
  <p>GeoContacts has been tested with WordPress 2.3.1 on a PHP5 Apache MySQL server.</p>
  <p><b>Thank you for trying GeoContacts</b>. </p>
  <p><em>If you like this plug-in, you will be blessed with a warm fuzzy feeling by doing one or more of the following: leaving the links to my site intact in the templates, contributing a template, contributing to coding this plugin, or donating to the cause. </em></p>
  <p>I'd like to thank Sam Wilson who wrote Addressbook, GeoPress by Andrew Turner and Mikel Maron, 
  Geo-Mashup by Dylan Kuhn and AmazonSimpleAdmin by Timo Reith. These plug-ins and many more gave me ideas for GeoContacts.
  If GeoContacts is not what you need, perhaps one of the aforementioned plug-ins will suit you better.</p>
	
	<?php
}

function geocontacts_main() {
    global $wpdb, $geocontacts_version, $geocontacts_basefile;
    $abbf = $geocontacts_basefile;
 
    if ($_POST['new']) {
        $sql = "INSERT INTO ".$wpdb->prefix."geocontacts SET
            organization  = '".$wpdb->escape($_POST['organization'])."',
            first_name    = '".$wpdb->escape($_POST['first_name'])."',
            last_name     = '".$wpdb->escape($_POST['last_name'])."',
            email         = '".$wpdb->escape($_POST['email'])."',
            website       = '".$wpdb->escape($_POST['website'])."',
            address_line1 = '".$wpdb->escape($_POST['address_line1'])."',
            address_line2 = '".$wpdb->escape($_POST['address_line2'])."',
            city          = '".$wpdb->escape($_POST['city'])."',
            postcode      = '".$wpdb->escape($_POST['postcode'])."',
            state         = '".$wpdb->escape($_POST['state'])."',
            country       = '".$wpdb->escape($_POST['country'])."',
            phone         = '".$wpdb->escape($_POST['phone'])."',
            notes         = '".$wpdb->escape($_POST['notes'])."',
            lat           = '".$wpdb->escape($_POST['lat'])."',
            lon           = '".$wpdb->escape($_POST['lon'])."',
            zoom          = '".$wpdb->escape($_POST['zoom'])."'";
        $wpdb->query($sql); ?>
        <div id="message" class="updated fade">
            <p><strong><?php _e('Address added'); ?>.</strong>
            <a href="<?php echo $geocontacts_basefile; ?>?page=geocontacts/geocontacts.php"><?php _e('Continue'); ?> &raquo;</a></p>
       </div>

	   <?php
    } else if ($_GET['action']=='delete') {
        $sql = "SELECT * FROM ".$wpdb->prefix."geocontacts WHERE id='".$wpdb->escape($_GET['id'])."'";
        $row = $wpdb->get_row($sql);
        if ($_GET['confirm']=='yes') {
            $wpdb->query("DELETE FROM ".$wpdb->prefix."geocontacts WHERE id='".$wpdb->escape($_GET['id'])."'");
            echo '<div id="message" class="updated fade">
                <p><strong>'.__('The address has been deleted.').'</strong>
                <a href="'.$geocontacts_basefile.'?page=geocontacts/geocontacts.php">'.__('Continue').' &raquo;<a/></p>
            </div>';
        } else {
            echo "<div class='wrap'>
                  <p style='text-align:center'>".__('Are you sure you want to delete the following address?')."</p>
                  <p style='border:1px solid black; width:50%; margin:1em auto; padding:0.7em'>
                  ".stripslashes($row->first_name." ".$row->last_name)."<br />
                  ".stripslashes($row->organization)."<br />
                  ".stripslashes($row->email." ".$row->phone)."<br />
                  ".stripslashes($row->address_line1)."<br />
                  ".stripslashes($row->address_line2)."<br />
                  ".stripslashes($row->city." ".$row->postcode)."<br />
                  ".stripslashes($row->state)."<br />
                  ".stripslashes($row->country)."<br />
                  <em>".__('Notes:')."</em> ".stripslashes($row->notes)."
                  </p>
                  <p style='text-align:center; font-size:1.3em'>
                    <a href='$geocontacts_basefile?page=geocontacts/geocontacts.php&action=delete&id=".$row->id."&confirm=yes'>
                      <strong>".__('[Yes]')."</strong>
                    </a>&nbsp;&nbsp;&nbsp;&nbsp;
                    <a href='$geocontacts_basefile?page=geocontacts/geocontacts.php'>".__('[No]')."</a>
                  </p>
                  </div>";
        }
    } else if ($_GET['action']=='edit') {
		$id = $wpdb->escape($_GET['id']);
        $sql = "SELECT * FROM ".$wpdb->prefix."geocontacts WHERE id='".$id."'";
        $row = $wpdb->get_row($sql);
        if ($_POST['save']) {
            $wpdb->query("UPDATE ".$wpdb->prefix."geocontacts SET
                first_name    = '".$wpdb->escape($_POST['first_name'])."',
                last_name     = '".$wpdb->escape($_POST['last_name'])."',
                organization  = '".$wpdb->escape($_POST['organization'])."',
                email         = '".$wpdb->escape($_POST['email'])."',
                phone         = '".$wpdb->escape($_POST['phone'])."',
                address_line1 = '".$wpdb->escape($_POST['address_line1'])."',
                address_line2 = '".$wpdb->escape($_POST['address_line2'])."',
                city          = '".$wpdb->escape($_POST['city'])."',
                postcode      = '".$wpdb->escape($_POST['postcode'])."',
                state         = '".$wpdb->escape($_POST['state'])."',
                country       = '".$wpdb->escape($_POST['country'])."',
                notes         = '".$wpdb->escape($_POST['notes'])."',
                lat           = '".$wpdb->escape($_POST['lat'])."',
                lon           = '".$wpdb->escape($_POST['lon'])."',
                zoom          = '".$wpdb->escape($_POST['zoom'])."',
                website       = '".$wpdb->escape($_POST['website'])."'
                WHERE id ='".$id."'");
            echo '<div id="message" class="updated fade">
                <p><strong>'.__('The address has been updated.').'</strong>
                <a href="'.$geocontacts_basefile.'?page=geocontacts/geocontacts.php">'.__('Return to contact list').' &raquo;<a/>
            </div>';
			// reread sql for more edits
			$sql = "SELECT * FROM ".$wpdb->prefix."geocontacts WHERE id='".$id."'";
			$row = $wpdb->get_row($sql);
        }  
		?>
            <div class="wrap">
            <h2><a name="new"></a><?php _e('Edit Contact'); ?></h2>
            <form action="<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin.php?page=geocontacts/geocontacts.php&action=edit&id=<?php echo $id; ?>"
            	  method="post" class="geocontacts" id="geocontacts">
            <?php echo _geocontacts_getaddressform($row); ?>
                
           <p class="submit">
            	<a href="<?php echo $geocontacts_basefile; ?>?page=geocontacts/geocontacts.php"><?php _e('[Cancel]'); ?></a>
				<input type="submit" name="save" value="<?php _e('Save &raquo;'); ?>" />
            </p>
            </form>
            </div>
        <?php 
    } else {
    
        $table_name = $wpdb->prefix."geocontacts";
        If ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")!=$table_name
            || get_option("geocontacts_version")!=$geocontacts_version ) {
            // Call the install function here rather than through the more usual
            // activate_blah.php action hook so the user doesn't have to worry about
            // deactivating then reactivating the plugin.  Should happen seemlessly.
            _geocontacts_install();
            print('<div id="message" class="updated fade"><p><strong>');
            printf('The GeoContacts plugin (version %s) has been installed or upgraded.', get_option("geocontacts_version"));
            print('</strong></p></div>');
        } ?>
                
        <div class="wrap">
		<table width='100%'>
		  <tr>
			<td width='32%' style='text-align:center'>
	        <p style="font-size:110%"><strong><a href="#new"><?php _e('Jump to form &darr;'); ?></a></strong></p>
			</td>
		    <td width='32%'>
	        <form class="geocontacts" action="<?php echo $geocontacts_basefile; ?>?page=geocontacts/geocontacts.php" method="get" id="geocontacts">
	        	<div style="display:none">
	        		<input type="hidden" name="page" value="geocontacts/geocontacts.php" />
	        		<input type="hidden" name="action" value="search" />
	        	</div>
	        	<p>
	        		<?php _e("Filter contacts by search term:"); ?><br />
	        		<input type="text" name="q" /><input type="submit" value="<?php _e('Search&hellip;'); ?>" />
	        	</p>
	        </form>
			</td>
		    <td width='32%'>
	        <p style='font-size:smaller'><?php
	       		printf(__("<strong>GeoContacts %s</strong> by Ben King."), get_option("geocontacts_version"));
	        	echo '<br />';
	        	_e("Support forum at <a href='http://www.glutenenvy.com'>www.glutenenvy.com</a>.");
	         ?>
	        </p>
			</td>
		  </tr>
		</table>
        <h2 style="margin-top:0"><?php _e('GeoContacts'); ?></h2>

        <table style="width:100%; margin:auto" id="geocontacts-table">
            <tr style="background-color:#E5F3FF">
                <?php echo '<th>'.__('ID').'</th><th>'.__('Name').'</th><th>'.__('Organization').'</th><th>'.__('G').'</th><th>'.__('Email address').'</th><th>'.__('Phone number').'</th><th>'.__('Edit').'</th><th>'.__('Delete').'</th>'; ?>
            </tr>
            <?php
            if ($_GET['action']=='search') {
	            $sql = "SELECT * FROM ".$wpdb->prefix."geocontacts WHERE
	            	first_name LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR last_name LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR organization LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR email LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR phone LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	OR notes LIKE '%".$wpdb->escape($_GET['q'])."%'
	            	ORDER BY first_name";
            } else {
	            $sql = "SELECT * FROM ".$wpdb->prefix."geocontacts ORDER BY first_name";
            }
            $results = $wpdb->get_results($sql);
            // build display contact list
			foreach ($results as $row) {
                echo"<tr>
                    <td onclick='click_contact(this, ".$row->id.")'>".stripslashes($row->id)."</td>
                    <td onclick='click_contact(this, ".$row->id.")'>".stripslashes($row->first_name." ".$row->last_name)."&nbsp;</td><!-- nbsp is to stop collapse -->
                    <td onclick='click_contact(this, ".$row->id.")'>".stripslashes($row->organization)."</td>
                    <td onclick='click_contact(this, ".$row->id.")'><img  src='".geocontacts_gravatar($row->email,19,'R')."'></td>
                    <td onclick='click_contact(this, ".$row->id.")'>".stripslashes($row->email)."</td>
					<td onclick='click_contact(this, ".$row->id.")'>".stripslashes($row->phone)."</td>
                    <td><a href='$geocontacts_basefile?page=geocontacts/geocontacts.php&action=edit&id=".$row->id."'>".__('[O]')."</a></td>
                    <td><a href='$geocontacts_basefile?page=geocontacts/geocontacts.php&action=delete&id=".$row->id."'>".__('[X]')."</a></td>
					<input type='hidden' value='".stripslashes($row->first_name)."' name='first_name-".$row->id."' id='first_name-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->last_name)."' name='last_name-".$row->id."' id='last_name-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->organization)."' name='organization-".$row->id."' id='organization-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->email)."' name='email-".$row->id."' id='email-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->phone)."' name='phone-".$row->id."' id='phone-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->address_line1)."' name='address_line1-".$row->id."' id='address_line1-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->address_line2)."' name='address_line2-".$row->id."' id='address_line2-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->city)."' name='city-".$row->id."' id='city-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->postcode)."' name='postcode-".$row->id."' id='postcode-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->state)."' name='state-".$row->id."' id='state-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->country)."' name='country-".$row->id."' id='country-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->notes)."' name='notes-".$row->id."' id='notes-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->lat)."' name='lat-".$row->id."' id='lat-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->lon)."' name='lon-".$row->id."' id='lon-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->zoom)."' name='zoom-".$row->id."' id='zoom-".$row->id."' />
					<input type='hidden' value='".stripslashes($row->website)."' name='website-".$row->id."' id='website-".$row->id."' />
                </tr>";
            } ?>
        </table>
        <?php foreach ($results as $row) {
		// create  example 
            echo "<div id='contact-".$row->id."-info' style='display:none'>";
            echo "<table><tr>		
			<td style='width:100px; margin-right:20px'>
			<img src='".geocontacts_gravatar($row->email,80,'R')."'>
			</td> 
			<td >			
			<center>&lt;!--geocontacts".$row->id."--&gt;<br />
            <a href='$geocontacts_basefile?page=geocontacts/geocontacts.php&action=edit&id=".$row->id."'>".__('[Edit]')."</a>&nbsp;&nbsp; 		
            <a href='$geocontacts_basefile?page=geocontacts/geocontacts.php&action=delete&id=".$row->id."'>".__('[Delete]')."</a><br />
            <a href='".$geocontacts_basefile."?page=geocontacts/geocontacts.php'>".__('[Clear Form]')."</a></center>
			</td></tr></table>";
            echo "</div>";
        } ?>
        
        <h2><a name="new"></a>Add Contact</h2>
        <form class="geocontacts" action="<?php echo get_bloginfo( 'wpurl' ); ?>/wp-admin/admin.php?page=geocontacts/geocontacts.php" method="post" id="geocontacts">
        <?php echo _geocontacts_getaddressform(); ?>
        <p class="submit">
            <input type="submit" name="new" value="<?php _e('Add New Contact &raquo;'); ?>" />
        </p>
        </form>
        </div><?php
    }
}

function geocontacts_gravatar($email,$size='null',$rating='null') {
    $gravatar_image = get_settings('geocontacts_gravatar_image', true);
	if($gravatar_image == "") {
		$gravatar_image = get_bloginfo( 'wpurl' )."/wp-content/plugins/geocontacts/templates/empty.gif";
	}
	
	if( get_settings('geocontacts_gravatar_enable', true) ) {

		$gravatar = "http://www.gravatar.com/avatar.php?gravatar_id=".md5($email);
		
		if ($size!=='null') {
			$gravatar .= "&amp;size=".$size;
		} else {
			$gravatar .= "&amp;size=".get_settings('geocontacts_gravatar_size', true);
		}

		if ($rating!=='null') {
			$gravatar .= "&amp;rating=".$rating;
		} else {
			$gravatar .= "&amp;rating=".get_settings('geocontacts_gravatar_rating', true);
		}
		
		$gravatar .= "&amp;default=".$gravatar_image;

	} else {
		$gravatar = $gravatar_image;
	}
	return $gravatar;
}

function _geocontacts_getaddressform($data='null') {
	// Set default values (the website field is the only one with a default value).
    if ($data=='null') {
		$website = 'http://'; 
		$showmap=false;
		$zoom = "12";
	} else {
		$website = $data->website;
		$zoom = $data->zoom;
		$showmap=true;
	}
	
    $out = '	
	<div style="width:50%; float:left">
        <div class="line">
            <div class="input" style="width:48%; margin-right:10px">
                <label for="first_name">'.__('First name:').'</label>
                <input type="text" name="first_name" id="first_name" value="'.stripslashes($data->first_name).'" />
            </div>
            <div class="input" style="width:49%">
                <label for="last_name">'.__('Last Name:').'</label>
                <input type="text" name="last_name" id="last_name" value="'.stripslashes($data->last_name).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="organization">'.__('Organization:').'</label>
                <input type="text" name="organization" id="organization" value="'.stripslashes($data->organization).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="email">'.__('Email Address:').'</label>
                <input type="text" name="email" id="email" value="'.stripslashes($data->email).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="phone">'.__('Phone:').'</label>
                <input type="text" name="phone" id="phone" value="'.stripslashes($data->phone).'" />
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <label for="website">'.__('Website:').'</label>
                <input type="text" name="website" id="website" value="'.stripslashes($website).'" />
            </div>
        </div>
        </div>
        <div style="width:50%; float:right">
            <div class="line">
                <div class="input" style="width:100%">
                    <label for="address_line1">'.__('Address Line 1:').'</label>
                    <input type="text" name="address_line1" id="address_line1" value="'.stripslashes($data->address_line1).'" />
                </div>
            </div>
            <div class="line">
                <div class="input" style="width:100%">
                    <label for="address_line2">'.__('Address Line 2:').'</label>
                    <input type="text" name="address_line2" id="address_line2" value="'.stripslashes($data->address_line2).'" />
                </div>
            </div>
            <div class="line">
                <div class="input" style="width:68%; margin-right:10px">
                    <label for="city">'.__('City/Suburb:').'</label>
                    <input type="text" name="city" id="city" value="'.stripslashes($data->city).'" />
                </div>
                <div class="input" style="width:29%">
                    <label for="state">'.__('State/Province:').'</label>
                    <input type="text" name="state" id="state" value="'.stripslashes($data->state).'" />
                </div>
            </div>
            <div class="line">
                <div class="input" style="width:29%; margin-right:10px">
                    <label for="postcode">'.__('Postal Code:').'</label>
                    <input type="text" name="postcode" id="postcode" value="'.stripslashes($data->postcode).'" />
                </div>
                <div class="input" style="width:68%">
                    <label for="country">'.__('Country:').'</label>
                    <input type="text" name="country" id="country" value="'.stripslashes($data->country).'" />
                </div>
			</div>';
			
	if($showmap==false) {
		$out .= '
            <div class="line">
				<div class="input" style="width:31%; margin-right:10px">
					<label for="lat">'.__('Lattitude:').'</label>
					<input type="text" name="lat" id="lat" value="'.stripslashes($data->lat).'" />
				</div>
				<div class="input" style="width:31%; margin-right:10px">
					<label for="lat">'.__('Longitude:').'</label>
					<input type="text" name="lon" id="lon" value="'.stripslashes($data->lon).'" />
				</div>
				<div class="input" style="width:31%">
					<label for="lat">'.__('Zoom:').'</label>
					<input type="text" name="zoom" id="zoom" value="'.stripslashes($zoom).'" />
				</div>
            </div>';
	} else {
		$out .= '
            <div class="line">
				<div class="input">
		                '.__('<br />Post anchor: <em>GEOCONTACT['.$data->id.']</em>').'
				</div>
            </div>';
	}
	
		// display gravatar box on main page only
		if ($showmap==true) {
		$out .= '
        </div>
		<div class="line" style="width:97%">
			<div class="input" style="width:100%">
				<label for="notes">'.__('Notes:').'</label>
				<textarea name="notes" id="notes" rows="3">'.stripslashes($data->notes).'</textarea>
			</div>
        </div>';
		} else {
		$out .= '
        </div>
		<div class="line" style="width:97%">
			<div class="input" style="width:65%; margin-right:20px">
				<label for="notes">'.__('Notes:').'</label>
				<textarea name="notes" id="notes" rows="3">'.stripslashes($data->notes).'</textarea>
			</div>
	        <div class="input" name="contact-info" id="contact-info" style="width:30%">
				<br />To copy a contact simply select the contact and click Add New Contact.
			</div>
        </div>';
		}
	


	if ($showmap==true) {
	$out .= '
	<p />
	<div class="line" style="width:97%">
	<div style="width:29%; float:left">
        <div class="line">
            <div class="input" style="width:95%; margin-right:10px">
                <label for="lat">'.__('Lattitude:').'</label>
                <input type="text" name="lat" id="lat" value="'.stripslashes($data->lat).'" />
            </div>
		</div>
        <div class="line">
            <div class="input" style="width:95%">
                <label for="lon">'.__('Longitude:').'</label>
                <input type="text" name="lon" id="lon" value="'.stripslashes($data->lon).'" />
            </div>
		</div>
        <div class="line">
            <div class="input" style="width:95%">
                <label for="zoom">'.__('Zoom:').'</label><br />';
				$zoom = stripslashes($data->zoom);
				$select = "<select name='zoom' id='zoom' onclick='geocontacts_savezoom();return false;'>
						<option value='18'>18</option>
						<option value='17'>17</option>
						<option value='16'>16</option>
						<option value='15'>15</option>
						<option value='14'>14</option>
						<option value='13'>13</option>
						<option value='12'>12</option>
						<option value='11'>11</option>
						<option value='10'>10</option>
						<option value='9'>9</option>
						<option value='8'>8</option>
						<option value='7'>7</option>
						<option value='6'>6</option>
						<option value='5'>5</option>
						<option value='4'>4</option>
						<option value='3'>3</option>
						<option value='2'>2</option>
						<option value='1'>1</option>
					</select>";
					$out .= str_replace("value='$zoom'","value='$zoom' selected='selected'", $select);
				
	$out .= '			
            </div>
        </div>
        <div class="line">
            <div class="input" style="width:100%">
                <span><br />[ <a href="#" onclick="geocontacts_geocode();return false;" title="Geocode this address" 
				       id="geocode">Geocode this address</a> ]</span>
            </div>
        </div>
	</div>
	<div style="width:70%; float:right">
        <div class="line">
	<br />
	<div id="map" style="width: 500px; height:300px"></div>
	<script type="text/javascript">
    //<![CDATA[
	// load up the first map in the edit screen
	geocontacts_showmap (document.getElementById("lat").value, document.getElementById("lon").value, document.getElementById("zoom").value);
	geocontacts_geocode_finetune ();  
	//]]>
    </script>
		</div>
	</div>
	</div>
	';
	}
    return $out;
}

function _geocontacts_install() {
    global $wpdb, $geocontacts_version;
    $table_name = $wpdb->prefix."geocontacts";
	$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		first_name tinytext NOT NULL,
		last_name tinytext NOT NULL,
		organization tinytext NOT NULL,
		email tinytext NOT NULL,
		phone tinytext NOT NULL,
		address_line1 tinytext NOT NULL,
		address_line2 tinytext NOT NULL,
		city tinytext NOT NULL,
		postcode tinytext NOT NULL,
		state tinytext NOT NULL,
		country tinytext NOT NULL, 
		website VARCHAR(55) NOT NULL,
		lat VARCHAR(15) NOT NULL,
		lon VARCHAR(15) NOT NULL,
		zoom tinyint NOT NULL,
		notes tinytext NOT NULL,
		PRIMARY KEY  (id)
	);";
	
    require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
    dbDelta($sql);
    update_option('geocontacts_version', $geocontacts_version);
	
	add_option('geocontacts_toplevelmenu', "");
	add_option('geocontacts_rss_enable', "");
	add_option('geocontacts_rss_format', "");
	add_option('geocontacts_google_apikey', "");
	add_option('geocontacts_garmin_apikey', "");
	add_option('geocontacts_gravatar_enable', "true");
    add_option('geocontacts_gravatar_rating', "G");
    add_option('geocontacts_gravatar_size', "80");
    add_option('geocontacts_gravatar_image', "");
    add_option('geocontacts_sort_by', "last_name");

}

/**
 * For other plugins, etc., to use.
 */
function geocontacts_getselect($name, $sel_id=false) {
    global $wpdb;
    $out = "<select name='$name'>";
    $rows = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."geocontacts ORDER BY first_name, organization");
    foreach($rows as $row) {
		if ($row->id==$sel_id) {
			$selected = " selected";
		} else {
			$selected = "";
		}
        $out .= "<option$selected value='$row->id'>$row->first_name $row->last_name";
        if (!empty($row->organization)) {
        	$out .= " ($row->organization)";
        }
        $out .= "</option>";
    }
    $out .= "</select>";
    return $out;
}

/**
 * For other plugins, etc., to use.
 */
function geocontacts_getIdFromEmail($email) {
    global $wpdb;
    $sql = "SELECT id FROM ".$wpdb->prefix."geocontacts where email='".$wpdb->escape($email)."'";
    $res = $wpdb->get_var($sql);
    return $res;
}

/**
 * For other plugins, etc., to use.
 */
function geocontacts_getFullnameFromId($id) {
    global $wpdb;
	$sql = "SELECT CONCAT(first_name,' ',last_name) FROM ".$wpdb->prefix."geocontacts WHERE id='".$wpdb->escape($id)."'";
    $res = $wpdb->get_var($sql);
    return $res;
}

add_filter('the_content', 'geocontacts_list');
function geocontacts_list($content) {
	global $wpdb;

	if ( preg_match("/GEOCONTACT\[(.*)\]/", $content, $matches) ) {
		// parse match string
		$mylist = explode(',',$matches[1],2);
		if ( $mylist[0]!=null ) {
			$id = $mylist[0];
		} else {
			$id = "";
		}
		// check second part of input string
		if( $mylist[1]!=null ) {
			// no security checks for wild files / be sure your posters can be trusted
			$template_src = file_get_contents(dirname(__FILE__) .'/templates/'.$mylist[1]);	
		} else {
			// File portion not found in anchor use the default filename
			$template_src = file_get_contents(dirname(__FILE__) .'/templates/default.htm');	
		}
		
		$sql = "SELECT * FROM ".$wpdb->prefix."geocontacts ORDER BY ".get_settings('geocontacts_sort_by', true);
		$results = $wpdb->get_results($sql);

		$listheader = "<ol class='geocontacts-list'>\n\n";
		$listfooter = "</ol>\n";

		$out = $listheader;
		$single = "";

		// http://maps.google.com/?ie=UTF8&z=14&t=h&q=21.26186090589837,-157.80624389648437
		$googlemap1 = "http://maps.google.com/?ie=UTF8&amp;z=14&amp;t=h&amp;q=";
				
		// template codes 
		$findme[0] = "{\$gravatar}";
		$findme[1] = "{\$first_name}";
		$findme[2] = "{\$last_name}";
		$findme[3] = "{\$address_line1}";
		$findme[4] = "{\$address_line2}";
		$findme[5] = "{\$city}";
		$findme[6] = "{\$state}";
		$findme[7] = "{\$postcode}";
		$findme[8] = "{\$phone}";
		$findme[9] = "{\$website}";
		$findme[10] = "{\$email}";
		$findme[11] = "{\$notes}";
		$findme[12] = "{\$organization}";
		$findme[13] = "{\$country}";
		$findme[14] = "{\$lat}";
		$findme[15] = "{\$lon}";
		$findme[16] = "{\$zoom}";
		$findme[17] = "{\$mapsite}";
		
		foreach ($results as $row) {
			$changeto[0] = geocontacts_getIfNotEmpty("%s",geocontacts_gravatar(stripslashes($row->email)));
			$changeto[1] = geocontacts_getIfNotEmpty("%s",stripslashes($row->first_name));
			$changeto[2] = geocontacts_getIfNotEmpty("%s",stripslashes($row->last_name));
			$changeto[3] = geocontacts_getIfNotEmpty("%s",stripslashes($row->address_line1));
			$changeto[4] = geocontacts_getIfNotEmpty("%s",stripslashes($row->address_line2));
			$changeto[5] = geocontacts_getIfNotEmpty("%s",stripslashes($row->city));
			$changeto[6] = geocontacts_getIfNotEmpty("%s",stripslashes($row->state));
			$changeto[7] = geocontacts_getIfNotEmpty("%s",stripslashes($row->postcode));
			$changeto[8] = geocontacts_getIfNotEmpty("%s",stripslashes($row->phone));
			$changeto[9] = geocontacts_getIfNotEmpty("%s",stripslashes($row->website));
			$changeto[10] = geocontacts_getIfNotEmpty("%s",stripslashes($row->email));
			$changeto[11] = geocontacts_getIfNotEmpty("%s",stripslashes($row->notes));
			$changeto[12] = geocontacts_getIfNotEmpty("%s",stripslashes($row->organization));
			$changeto[13] = geocontacts_getIfNotEmpty("%s",stripslashes($row->country));
			$changeto[14] = geocontacts_getIfNotEmpty("%s",stripslashes($row->lat));
			$changeto[15] = geocontacts_getIfNotEmpty("%s",stripslashes($row->lon));
			$changeto[16] = geocontacts_getIfNotEmpty("%s",stripslashes($row->zoom));
			$changeto[17] = geocontacts_getIfNotEmpty("%s",$googlemap1.stripslashes($row->lat).",".stripslashes($row->lon));

			
			$contact = str_replace($findme, $changeto, $template_src);

			$out .= $contact;

			// id found now process the changes in the content stream
			if( $row->id == $id ) {		
				$single = $listheader . $contact . $listfooter;
				$searchfor = "/".quotemeta($matches[0])."/";
				$content = preg_replace($searchfor, $single, $content);
			} 				
		}
		$out .= $listfooter;
		// check for all contacts condition 
		if ( $id == "*" ) {
			$searchfor = "/".quotemeta($matches[0])."/";		
			$content = preg_replace($searchfor, $out, $content);
		}
	}
	// Check for other anchors to update
	// recursive call could mean resource trouble
	if ( preg_match("/GEOCONTACT\[(.*)\]/", $content, $matches) ) {
		return geocontacts_list($content);
	}
	return $content;
}

function geocontacts_getIfNotEmpty($format,$var) {
	if (!empty($var)) {
		return sprintf($format, $var);
	}
	return "";
}

function sendalert($alerttxt) {
	echo '
	<form class="geocontacts" method="post" action="options.php">
    <input type="hidden" name="alert" id="alert" value="'.stripslashes($alerttxt).'" />
  	</form>
	<script type="text/javascript">
    //<![CDATA[
	// load up the first map in the edit screen
 	alert( document.getElementById("alert").value );
	//]]>
    </script>';
}

?>
