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
			'id'       => 'give_dbh_recover_data_from_rcp',
			'version'  => '0.0.3',
			'callback' => 'give_dbh_recover_data_from_rcp_callback',
		)
	);
}

add_action( 'give_register_updates', 'give_db_healthcheck_notices' );


/**
 * Recover old payment data
 *
 * @since 0.0.3
 */
function give_dbh_recover_data_from_rcp_callback() {
	global $wpdb;

	$give_updates = Give_Updates::get_instance();

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

			// If form id exist then we do not need to recover data.
			if ( give_get_payment_form_id( get_the_ID() ) ) {
				continue;
			}

			$rcp_payment_meta = "{$wpdb->prefix}rcp_payment_meta";
			$sql              = $wpdb->prepare(
				"
					SELECT * FROM {$rcp_payment_meta}
					WHERE payment_id=%d
					",
				get_the_ID()
			);

			$meta_data = $wpdb->get_results( $sql, ARRAY_A );

			if ( ! empty( $meta_data ) ) {
				foreach ( $meta_data as $index => $data ) {
					$status = Give()->payment_meta->update_meta( $data['payment_id'], $data['meta_key'], maybe_unserialize( $data['meta_value'] ) );

					if ( $status ) {
						$wpdb->query( "DELETE FROM {$rcp_payment_meta} WHERE meta_id={$data['meta_id']};" );
					}
				}
			}

		}// End while().

		wp_reset_postdata();
	} else {
		// No more forms found, finish up.
		give_set_upgrade_complete( 'give_dbh_recover_data_from_rcp' );
	}
}