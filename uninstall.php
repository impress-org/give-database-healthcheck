<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$db_upgrades = give_get_completed_upgrades();

if( isset( $db_upgrades['give_db_healthcheck_post_200_data'] ) ) {
	unset( $db_upgrades['give_db_healthcheck_post_200_data'] );
	update_option( 'give_completed_upgrades', $db_upgrades );
}
