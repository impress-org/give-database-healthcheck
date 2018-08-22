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
	Give_Updates::get_instance()->register(
		array(
			'id'       => 'give_dbh_delete_existing_donationmeta_table',
			'version'  => '0.0.3',
			'callback' => 'give_dbh_delete_existing_donationmeta_table_callback',
		)
	);


	Give_Updates::get_instance()->register(
		array(
			'id'       => 'give_db_healthcheck_003_recover_old_paymentdata',
			'version'  => '0.0.3',
			'callback' => 'give_db_healthcheck_003_recover_old_paymentdata_callback',
			'depend'   => array( 'give_dbh_delete_existing_donationmeta_table' ),
		)
	);
}

add_action( 'give_register_updates', 'give_db_healthcheck_notices' );


/**
 * Remove corrupted data from give_donationmeta
 *
 * @since 0.0.3
 */
function give_dbh_delete_existing_donationmeta_table_callback(){
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->prefix}give_donationmeta WHERE donation_id IS NULL" );

	give_set_upgrade_complete( 'give_dbh_delete_existing_donationmeta_table' );
}


/**
 * Recover old payment data
 *
 * @since 0.0.3
 */
function give_db_healthcheck_003_recover_old_paymentdata_callback() {
	global $wpdb;

	$give_updates         = Give_Updates::get_instance();
	$donation_id_col_name = Give()->payment_meta->get_meta_type() . '_id';
	$donation_table       = Give()->payment_meta->table_name;

	// form query
	$payments = new WP_Query( array(
			'paged'          => $give_updates->step,
			'status'         => 'any',
			'order'          => 'ASC',
			'post_type'      => array( 'give_payment' ),
			'posts_per_page' => 20,
		)
	);

	if ( $payments->have_posts() ) {
		$give_updates->set_percentage( $payments->found_posts, $give_updates->step * 20 );

		while ( $payments->have_posts() ) {
			$payments->the_post();

			$meta_data = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT * FROM $wpdb->postmeta
					WHERE post_id=%d
					",
					get_the_ID()
				),
				ARRAY_A
			);

			if ( ! empty( $meta_data ) ) {
				foreach ( $meta_data as $index => $data ) {
					// ignore _give_payment_meta key.
					if ( '_give_payment_meta' === $data['meta_key'] ) {
						continue;
					}

					$is_duplicate_meta_key = $wpdb->get_results(
						$wpdb->prepare(
							"
							SELECT * FROM {$donation_table}
							WHERE meta_key=%s
							AND {$donation_id_col_name}=%d
							",
							$data['meta_key'],
							$data['post_id']
						),
						ARRAY_A
					);

					if ( $is_duplicate_meta_key ) {

						continue;
					}

					$data[ $donation_id_col_name ] = $data['post_id'];

					unset( $data['post_id'] );
					unset( $data['meta_id'] );

					Give()->payment_meta->insert( $data );
				}
			}

		}// End while().

		wp_reset_postdata();
	} else {
		// No more forms found, finish up.
		give_set_upgrade_complete( 'give_db_healthcheck_003_recover_old_paymentdata' );
	}
}