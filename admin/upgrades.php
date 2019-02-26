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

	// Give_Updates::get_instance()->register(
	// 	array(
	// 		'id'       => 'give_db_healthcheck_post_200_data',
	// 		'version'  => '0.0.1',
	// 		'callback' => 'give_db_healthcheck_post_200_data_callback',
	// 		'depend'   => array(
	// 			'v20_move_metadata_into_new_table',
	// 			'v20_upgrades_form_metadata',
	// 			'v20_upgrades_payment_metadata',
	// 			'v201_upgrades_payment_metadata',
	// 			'v201_move_metadata_into_new_table',
	// 		),
	//
	// 	)
	// );
	//
	// Give_Updates::get_instance()->register(
	// 	array(
	// 		'id'       => 'give_db_healthcheck_donation_donor',
	// 		'version'  => '0.0.2',
	// 		'callback' => 'give_db_healthcheck_donation_donor_callback',
	// 		'depend'   => array(
	// 			'give_db_healthcheck_post_200_data',
	// 		),
	//
	// 	)
	// );
	//
	// Give_Updates::get_instance()->register(
	// 	array(
	// 		'id'       => 'give_db_healthcheck_003_recover_old_paymentdata',
	// 		'version'  => '0.0.3',
	// 		'callback' => 'give_db_healthcheck_003_recover_old_paymentdata_callback',
	// 		'depend'   => array(
	// 			'give_db_healthcheck_post_200_data',
	// 		),
	//
	// 	)
	// );

	Give_Updates::get_instance()->register(
		array(
			'id'       => 'give_dbh_recurring_v184_alter_amount_column_type',
			'version'  => '0.0.3',
			'callback' => 'give_dbh_recurring_v184_alter_amount_column_type_callback',
		)
	);
}

add_action( 'give_register_updates', 'give_db_healthcheck_notices' );


function give_dbh_recurring_v184_alter_amount_column_type_callback() {
	global $wpdb;

	$completed = give_has_upgrade_completed( 'give_dbh_recurring_v184_alter_amount_column_type' );

	if ( $completed ) {
		return;
	}

	// Alter columns.
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}give_subscriptions CHANGE `recurring_amount` `recurring_amount` DECIMAL(18,10) NOT NULL" );
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}give_subscriptions CHANGE `initial_amount` `initial_amount` DECIMAL(18,10) NOT NULL" );

	// The Update Ran.
	give_set_upgrade_complete( 'give_dbh_recurring_v184_alter_amount_column_type' );

}