<?php

// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Register upgrades
 *
 * @since 0.0.1
 */
function give_db_healthcheck_notices() {
	global $wpdb;

	Give_Updates::get_instance()->register(
		array(
			'id'       => 'give_db_healthcheck_rerun_g220_rename_donation_meta_type',
			'version'  => '0.0.3',
			'callback' => 'give_db_healthcheck_rerun_v220_rename_donation_meta_type_callback',
		)
	);
}

add_action( 'give_register_updates', 'give_db_healthcheck_notices' );

function give_db_healthcheck_rerun_v220_rename_donation_meta_type_callback() {
	if ( function_exists( 'give_v220_rename_donation_meta_type_callback' ) ) {
		give_v220_rename_donation_meta_type_callback();
	}

	if ( give_has_upgrade_completed( 'v220_rename_donation_meta_type' ) ) {
		give_set_upgrade_complete( 'give_db_healthcheck_rerun_g220_rename_donation_meta_type' );
	}
}
