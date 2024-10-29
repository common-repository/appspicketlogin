<?php
/**
 * Plugin Name: AppsPicket Two Factor Authentication
 * Plugin URI: 
 * Description: This plugin provide custom login for wordpress.
 * Version: 1.0.0
 * Author: AppsPicket
 * Author URI: http://appspicket.com/
 */

include (dirname (__FILE__).'/plugin.php');
include (dirname (__FILE__).'/controller/main.php');

register_activation_hook(__FILE__,'appspicketlogin_activate');
register_deactivation_hook( __FILE__, 'appspicketlogin_deactivate' );
define('FS_METHOD', 'direct');
function appspicketlogin_activate () {
	update_option('users_can_register',1);
}

function appspicketlogin_deactivate () {
	update_option('users_can_register',0);
}
