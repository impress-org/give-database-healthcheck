<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}


$db_upgrades = give_get_completed_upgrades();

if ( in_array( 'give_db_healthcheck_post_200_data', $db_upgrades ) ) {
	// Custom DB upgrades.
	$db_upgrades = array(
		'give_db_healthcheck_post_200_data',
		'give_db_healthcheck_donation_donor',
		'give_db_healthcheck_003_recover_old_paymentdata',
		'give_db_healthcheck_220_recover_donationmeta'
	);


	foreach ( $db_upgrades as $db_upgrade ) {
		unset( $db_upgrades[ array_search( $db_upgrade, $db_upgrades ) ] );
	}

	update_option( 'give_completed_upgrades', $db_upgrades );
}