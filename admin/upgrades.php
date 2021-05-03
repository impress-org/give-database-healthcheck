<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', 'give_mark_v220_rename_donation_meta_type_completed' );

function give_mark_v220_rename_donation_meta_type_completed(){
	if( ! give_has_upgrade_completed('v220_rename_donation_meta_type') ) {
		give_set_upgrade_complete('v220_rename_donation_meta_type');
	}
}
