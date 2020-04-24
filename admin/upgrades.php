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

	if (
		$wpdb->query( $wpdb->prepare( "SHOW TABLES LIKE %s", "{$wpdb->prefix}give_paymentmeta" ) )
		&& ! give_has_upgrade_completed( 'v220_move_data_to_donation_meta' )
	) {
		Give_Updates::get_instance()->register(
			array(
				'id'       => 'v220_move_data_to_donation_meta',
				'version'  => '0.0.3',
				'callback' => 'give_db_healthcheck_v220_move_data_to_donation_meta',
			)
		);
	}

	Give_Updates::get_instance()->register(
		array(
			'id'       => 'v220_split_give_payment_meta_to_recover_missing_meta_data_6',
			'version'  => '0.0.3',
			'callback' => 'give_db_healthcheck_v220_split_give_payment_meta_to_recover_missing_meta_data',
			'depend'   => 'v220_move_data_to_donation_meta',
		)
	);
}

add_action( 'give_register_updates', 'give_db_healthcheck_notices' );


/**
 * Move data from paymentmeta table to donationmeta table
 *
 * @since 0.0.1
 */
function give_db_healthcheck_v220_move_data_to_donation_meta() {
	global $wpdb;
	$give_updates         = Give_Updates::get_instance();
	$donation_id_col_name = 'donation_id';
	$donation_table       = "{$wpdb->prefix}give_donationmeta";

	$payments = $wpdb->get_results(
		"
			SELECT * FROM {$wpdb->prefix}give_paymentmeta
			ORDER BY donation_id ASC
			LIMIT 100",
		ARRAY_A
	);

	$paymentTotal = $wpdb->get_var( "SELECT COUNT(donation_id) FROM {$wpdb->prefix}give_paymentmeta" );

	if ( $payments ) {
		$give_updates->set_percentage( $paymentTotal, 100 );

		foreach ( $payments as $payment ) {
			$hasPayment = $wpdb->get_var( "SELECT {$donation_id_col_name} FROM {$donation_table} WHERE donation_id={$payment['donation_id']} AND meta_key='{$payment['meta_key']}'" );

			$wpdb->delete( "{$wpdb->prefix}give_paymentmeta", array( 'meta_id' => absint( $payment['meta_id'] ) ),
				array( '%d' ) );

			if ( ! empty( $hasPayment ) ) {
				continue;
			}

			unset( $payment['meta_id'] );
			Give()->payment_meta->insert( (array) $payment );
		}// End while().

		wp_reset_postdata();
	} else {
		// No more forms found, finish up.
		give_set_upgrade_complete( 'v220_move_data_to_donation_meta' );
	}
}

/**
 * Recover missing meta data fron _give_payment_meta
 */
function give_db_healthcheck_v220_split_give_payment_meta_to_recover_missing_meta_data() {
	global $wpdb;
	$give_updates = Give_Updates::get_instance();

	// form query
	$donations = new WP_Query(
		array(
			'paged'          => $give_updates->step,
			'status'         => 'any',
			'order'          => 'ASC',
			'post_type'      => 'give_payment',
			'posts_per_page' => 100,
			'fields'         => 'ids',
		)
	);

	if ( $donations->have_posts() ) {
		$give_updates->set_percentage( $donations->found_posts, ( $give_updates->step * 100 ) );

		while ( $donations->have_posts() ) {
			$donations->the_post();
			$postID = get_the_ID();

			$paymentMeta = maybe_unserialize( $wpdb->get_var( "
				SELECT meta_value
				FROM {$wpdb->prefix}give_donationmeta
				WHERE donation_id={$postID}
				AND meta_key='_give_payment_meta'
			" ) );

			if( empty( $paymentMeta ) ) {
				continue;
			}

			if ( array_key_exists( 'form_id', $paymentMeta ) && ! empty( $paymentMeta['form_id'] && ! Give()->payment_meta->get_meta( $postID, '_give_payment_form_id', true ) ) ) {
				Give()->payment_meta->update_meta( $postID, '_give_payment_form_id', $paymentMeta['form_id'] );
			}

			if ( array_key_exists( 'form_title', $paymentMeta ) && ! empty( $paymentMeta['form_title'] && ! Give()->payment_meta->get_meta( $postID, '_give_payment_form_title', true ) ) ) {
				Give()->payment_meta->update_meta( $postID, '_give_payment_form_title', $paymentMeta['form_title'] );
			}
		}
	} else {
		give_set_upgrade_complete( 'v220_split_give_payment_meta_to_recover_missing_meta_data_6' );
	}
}
