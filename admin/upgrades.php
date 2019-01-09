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
			'id'       => 'dbh_move_donor_note',
			'version'  => '0.0.3',
			'callback' => 'give_dbh_move_donor_note_callback',
		)
	);


	Give_Updates::get_instance()->register(
		array(
			'id'       => 'dbh_move_donation_note',
			'version'  => '0.0.3',
			'callback' => 'give_dbh_move_donation_note_callback',
		)
	);
}

add_action( 'give_register_updates', 'give_db_healthcheck_notices' );

/**
 * Add custom comment table
 *
 * @since 2.4.0
 */
function give_dbh_add_missing_comment_tables() {
	$custom_tables = array(
		Give()->comment->db,
		Give()->comment->db_meta,
	);

	/* @var Give_DB $table */
	foreach ( $custom_tables as $table ) {
		if ( ! $table->installed() ) {
			$table->register_table();
		}
	}
}


/**
 * Move donor notes to comment table
 *
 * @since 2.3.0
 */
function give_dbh_move_donor_note_callback() {
	// Add comment table if missing.
	give_dbh_add_missing_comment_tables();

	/* @var Give_Updates $give_updates */
	$give_updates = Give_Updates::get_instance();

	$donor_count = Give()->donors->count( array(
		'number' => - 1,
	) );

	$donors = Give()->donors->get_donors( array(
		'paged'  => $give_updates->step,
		'number' => 100,
	) );

	if ( $donors ) {
		$give_updates->set_percentage( $donor_count, $give_updates->step * 100 );
		// Loop through Donors
		foreach ( $donors as $donor ) {
			$notes = trim( Give()->donors->get_column( 'notes', $donor->id ) );

			// If first name meta of donor is not created, then create it.
			if ( ! empty( $notes ) ) {
				$notes = array_values( array_filter( array_map( 'trim', explode( "\n", $notes ) ), 'strlen' ) );

				foreach ( $notes as $note ) {
					$note      = array_map( 'trim', explode( '-', $note ) );
					$timestamp = strtotime( $note[0] );

					Give()->comment->db->add(
						array(
							'comment_content'  => $note[1],
							'user_id'          => absint( Give()->donors->get_column_by( 'user_id', 'id', $donor->id ) ),
							'comment_date'     => date( 'Y-m-d H:i:s', $timestamp ),
							'comment_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $timestamp ) ),
							'comment_parent'   => $donor->id,
							'comment_type'     => 'donor',
						)
					);
				}
			}
		}

	} else {
		// The Update Ran.
		give_set_upgrade_complete( 'dbh_move_donor_note' );
	}
}

/**
 * Move donation notes to comment table
 *
 * @since 2.3.0
 */
function give_dbh_move_donation_note_callback() {
	global $wpdb;

	// Add comment table if missing.
	give_dbh_add_missing_Comment_tables();

	/* @var Give_Updates $give_updates */
	$give_updates = Give_Updates::get_instance();

	$donation_note_count = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(*)
			FROM {$wpdb->comments}
			WHERE comment_type=%s
			",
			'give_payment_note'
		)
	);

	$query = $wpdb->prepare(
		"
			SELECT *
			FROM {$wpdb->comments}
			WHERE comment_type=%s
			ORDER BY comment_ID ASC
			LIMIT 100
			OFFSET %d
			",
		'give_payment_note',
		$give_updates->get_offset( 100 )
	);

	$comments = $wpdb->get_results( $query );

	if ( $comments ) {
		$give_updates->set_percentage( $donation_note_count, $give_updates->step * 100 );

		// Loop through Donors
		foreach ( $comments as $comment ) {
			$donation_id = $comment->comment_post_ID;
			$form_id     = give_get_payment_form_id( $donation_id );

			$comment_id = Give()->comment->db->add(
				array(
					'comment_content'  => $comment->comment_content,
					'user_id'          => $comment->user_id,
					'comment_date'     => date( 'Y-m-d H:i:s', strtotime( $comment->comment_date ) ),
					'comment_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', strtotime( $comment->comment_date_gmt ) ) ),
					'comment_parent'   => $comment->comment_post_ID,
					'comment_type'     => is_numeric( get_comment_meta( $comment->comment_ID, '_give_donor_id', true ) )
						? 'donor_donation'
						: 'donation',
				)
			);

			if ( ! $comment_id ) {
				continue;
			}

			// @see https://github.com/impress-org/give/issues/3737#issuecomment-428460802
			$restricted_meta_keys = array(
				'akismet_result',
				'akismet_as_submitted',
				'akismet_history',
			);

			if ( $comment_meta = get_comment_meta( $comment->comment_ID ) ) {
				foreach ( $comment_meta as $meta_key => $meta_value ) {
					// Skip few comment meta keys.
					if ( in_array( $meta_key, $restricted_meta_keys ) ) {
						continue;
					}

					$meta_value = maybe_unserialize( $meta_value );
					$meta_value = is_array( $meta_value ) ? current( $meta_value ) : $meta_value;

					Give()->comment->db_meta->update_meta( $comment_id, $meta_key, $meta_value );
				}
			}

			Give()->comment->db_meta->update_meta( $comment_id, '_give_form_id', $form_id );

			// Delete comment.
			update_comment_meta( $comment->comment_ID, '_give_comment_moved', 1 );
		}

	} else {
		$comment_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT DISTINCT comment_id
				FROM {$wpdb->commentmeta}
				WHERE meta_key=%s
				AND meta_value=%d
				",
				'_give_comment_moved',
				1
			)
		);

		if ( ! empty( $comment_ids ) ) {
			$comment_ids = "'" . implode( "','", $comment_ids ) . "'";

			$wpdb->query( "DELETE FROM {$wpdb->comments} WHERE comment_ID IN ({$comment_ids})" );
			$wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE comment_id IN ({$comment_ids})" );
		}

		// The Update Ran.
		give_set_upgrade_complete( 'dbh_move_donation_note' );
	}
}