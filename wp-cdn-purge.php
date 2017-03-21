<?php
/*
Plugin Name: WP CDN Purge
Plugin URI: github
Description: Creates a button for purging your website from the backend. Great if you don't want to login to Limelight
Version: 1.0
Author: Joey Bryan
Author URI: joeymbryan.com
*/
include("core.php");
global $wpdb; 

add_action( 'admin_menu', 'wp_cdn_purge_menu' );
add_action( 'admin_enqueue_scripts', form_scripts_styles);

add_action( 'admin_post_purge_cdn', 'post_form_to_purge_cdn' );
add_action( 'admin_post_update_cdn_settings', 'post_form_to_update_settings' );

function wp_cdn_purge_menu() {
	add_menu_page( 'WP CDN Purge Options', 'WP CDN Purge', 'manage_options', 'wp-cdn-purge.php', 'wp_cdn_purge_options' );
}

function form_scripts_styles() {
    wp_enqueue_script( 'form-script', plugin_dir_url( __FILE__ ) . 'js/form-script.js', array(), '1.0' );
}

// Build Admin Form and Recent Purges
function wp_cdn_purge_options() {
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
		$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'cdn_purge';
		if( isset( $_GET[ 'tab' ] ) ) {
		    $active_tab = $_GET[ 'tab' ];
		} // end if

	?>

	<h2 class="nav-tab-wrapper">
	    <a href="?page=wp-cdn-purge.php&tab=cdn_purge" class="nav-tab <?php echo $active_tab == 'cdn_purge' ? 'nav-tab-active' : ''; ?>">CDN Purge</a>
	    <a href="?page=wp-cdn-purge.php&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
	</h2>
	<?php
	if( $active_tab == 'cdn_purge' ) {
	?>
	<h1>WP CDN Purge Options</h1>
	<div class="wrap">
		<form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">
			<input type="hidden" name="action" value="purge_cdn"/>
			<label name="page-selection">Choose which page cache you would like to clear</label><br>
			<input type="radio" name="page_selection" value="*" checked >All <?php if (get_option( 'domain_name' )) { echo get_option( 'domain_name' ); } else { ?> <a href='/wp-admin/admin.php?page=wp-cdn-purge.php&tab=settings'>set domain</a> <?php } ?><br>
			<input type="radio" name="page_selection" value="">Home Page<br>
			<input type="radio" id="other-page" name="page_selection" value=""><?php if (get_option( 'domain_name' )) { echo get_option( 'domain_name' ); } else { ?> <a href='/wp-admin/admin.php?page=wp-cdn-purge.php&tab=settings'>set domain</a> <?php } ?>/<input type="text" id="other_reason" name="other_reason" /><br>
			<div class="submit">
				<input class="button button-primary" type="submit" value="Submit">
			</div>
		</form>
	</div>
	<h2>Recent Purges</h2>
	<div class="wrap">
	<?php
	$request_method = "GET";
	$page = '';
	$query_string = '';

	// List history of Purge Requests
	$history = new LLNW_API();
	echo $history->use_llnw_api( $query_string, $page, $request_method );
	?></div><?php
	// begin settings page
	} else { ?>
	<h1>Settings</h1>
	<div class="wrap">
		<form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">
			<input type="hidden" name="action" value="update_cdn_settings"/>
			<label name="account_name">Domain Name</label><br>
			<input type="text" name="domain_name" value="<?php echo get_option( 'domain_name' )?>"><br>
			<label name="account_name">Account Name</label><br>
			<input type="text" name="account_name" value="<?php echo get_option( 'account_name' )?>"><br>
			<label name="shared_key">Shared Key</label><br>
			<input type="text" name="shared_key" value="<?php echo get_option( 'shared_key' )?>"><br>
			<label name="shortname">Shortname</label><br>
			<input type="text" name="shortname" value="<?php echo get_option( 'shortname' )?>"><br>
			<div class="submit">
				<input class="button button-primary" type="submit" value="Submit">
			</div>
		</form>
	</div>

<?php } // end if/else
}

// Post the recent 
function post_form_to_purge_cdn(){ 
	$page = (!empty($_POST["page_selection"])) ? $_POST["page_selection"] : NULL;NULL;
	if (substr($page, -1) != "/" && $page != '' && $page != '*') {
		$page .= "/";
	}
	$query_string = '';
	$request_method = "POST";

	// Make Purge Request to Limelight
	$smartpurge = new LLNW_API();
	$smartpurge->use_llnw_api( $query_string, $page, $request_method);
}

function post_form_to_update_settings(){ 

	$account_name = (!empty($_POST["account_name"])) ? $_POST["account_name"] : NULL;NULL;
	$shared_key = (!empty($_POST["shared_key"])) ? $_POST["shared_key"] : NULL;NULL;
	$shortname = (!empty($_POST["shortname"])) ? $_POST["shortname"] : NULL;NULL;
	$domain_name = (!empty($_POST["domain_name"])) ? $_POST["domain_name"] : NULL;NULL;

	update_option( 'account_name', $account_name );
	update_option( 'shared_key', $shared_key );
	update_option( 'shortname', $shortname );
	update_option( 'domain_name', $domain_name );

	// Refresh and return to this page
	header('Refresh: 0; url=admin.php?page=wp-cdn-purge.php&tab=settings');
}

?>