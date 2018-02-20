<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}


$db_upgrades = give_get_completed_upgrades();

if ( in_array( 'give_db_healthcheck_post_200_data', $db_upgrades ) ) {
	unset( $db_upgrades[ array_search( 'give_db_healthcheck_post_200_data', $db_upgrades ) ] );
	update_option( 'give_completed_upgrades', $db_upgrades );
}