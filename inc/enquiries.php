<?php
/**
 * Store, deliver, manage and expire enquiries.
 *
 * Each accepted enquiry is saved as a private `jmc_enquiry` post before it is
 * emailed, so a failed delivery is never lost: it is flagged in WordPress
 * admin and can be resent from there. Enquiries are deleted automatically
 * once they pass the retention period, and are included in WordPress's
 * personal data export and erasure tools.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Cron hook that deletes expired enquiries.
 */
const JMC_ENQUIRY_PURGE_HOOK = 'jmc_purge_enquiries';

/**
 * Admin-post action that resends an enquiry.
 */
const JMC_ENQUIRY_RESEND_ACTION = 'jmc_resend_enquiry';

/**
 * Retrieve an enquiry's stored values.
 *
 * @param int $post_id Enquiry ID.
 *
 * @return array<string,string> Values keyed by field ID; missing fields are empty strings.
 */
function jmc_get_enquiry_values( int $post_id ): array {
	$stored = get_post_meta( $post_id, JMC_ENQUIRY_VALUES_META, true );
	$stored = is_array( $stored ) ? $stored : [];

	$values = [];

	foreach ( array_keys( jmc_enquiry_fields() ) as $id ) {
		$values[ $id ] = is_string( $stored[ $id ] ?? null ) ? $stored[ $id ] : '';
	}

	return $values;
}

/**
 * Retrieve an enquiry's delivery status.
 *
 * @param int $post_id Enquiry ID.
 *
 * @return string A key of `jmc_enquiry_statuses()`; `accepted` when unknown.
 */
function jmc_get_enquiry_status( int $post_id ): string {
	return jmc_html_allowed_value(
		value: get_post_meta( $post_id, JMC_ENQUIRY_STATUS_META, true ),
		allowed: array_keys( jmc_enquiry_statuses() ),
		fallback_value: 'accepted'
	);
}

/**
 * Store a new enquiry.
 *
 * @param array<string,string> $values Cleaned, valid enquiry values.
 *
 * @return int The enquiry ID, or 0 when it could not be stored.
 */
function jmc_insert_enquiry( array $values ): int {
	$post_id = wp_insert_post(
		[
			'post_type'   => JMC_ENQUIRY_POST_TYPE,
			'post_status' => 'private',
			'post_title'  => $values['name'] ?? '',
			'post_author' => 0,
			'meta_input'  => [
				JMC_ENQUIRY_VALUES_META      => $values,
				JMC_ENQUIRY_EMAIL_META       => strtolower( $values['email'] ?? '' ),
				JMC_ENQUIRY_FINGERPRINT_META => jmc_enquiry_fingerprint( $values ),
				JMC_ENQUIRY_STATUS_META      => 'accepted',
				JMC_ENQUIRY_ATTEMPTS_META    => 0,
			],
		],
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return 0;
	}

	/**
	 * Fires when an enquiry has been stored and is about to be delivered.
	 *
	 * Passes no personal data, so it is safe to use for monitoring.
	 *
	 * @param int $post_id Enquiry ID.
	 */
	do_action( 'jmc_enquiry_accepted', $post_id );

	return $post_id;
}

/**
 * Email an enquiry to the business and record the outcome.
 *
 * Sends to the site-wide business email address, falling back to the site
 * admin email. `sent` means the mail provider accepted the message; whether
 * it reached the inbox depends on the provider (configure an SMTP plugin for
 * reliable delivery).
 *
 * @param int $post_id Enquiry ID.
 *
 * @return bool Whether the mail provider accepted the message.
 */
function jmc_send_enquiry( int $post_id ): bool {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || JMC_ENQUIRY_POST_TYPE !== $post->post_type ) {
		return false;
	}

	$to = jmc_sanitize_business_email( get_option( JMC_BUSINESS_EMAIL_OPTION, '' ) );

	if ( '' === $to ) {
		$to = (string) get_option( 'admin_email', '' );
	}

	$email = jmc_enquiry_email(
		values: jmc_get_enquiry_values( $post_id ),
		site_name: wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ),
		view_url: admin_url( 'post.php?post=' . $post_id . '&action=edit' )
	);

	$error   = '';
	$capture = static function ( WP_Error $mail_error ) use ( &$error ): void {
		$error = $mail_error->get_error_message();
	};

	add_action( 'wp_mail_failed', $capture );
	$sent = '' !== $to && wp_mail( $to, $email['subject'], $email['body'], $email['headers'] );
	remove_action( 'wp_mail_failed', $capture );

	if ( '' === $to ) {
		$error = __( 'No business email address or admin email address is set.', 'jmc-theme' );
	} elseif ( ! $sent && '' === $error ) {
		$error = __( 'The mail provider did not accept the message.', 'jmc-theme' );
	}

	update_post_meta(
		$post_id,
		JMC_ENQUIRY_ATTEMPTS_META,
		absint( get_post_meta( $post_id, JMC_ENQUIRY_ATTEMPTS_META, true ) ) + 1
	);
	update_post_meta( $post_id, JMC_ENQUIRY_STATUS_META, $sent ? 'sent' : 'failed' );
	update_post_meta( $post_id, JMC_ENQUIRY_ERROR_META, $error );

	if ( ! $sent ) {
		// Logged without personal data so server monitoring can pick it up.
		error_log( sprintf( 'JMC enquiry %d could not be delivered.', $post_id ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Operational signal for server log monitoring; contains no personal data.
	}

	/**
	 * Fires after an enquiry delivery attempt.
	 *
	 * Passes no personal data, so it is safe to use for monitoring.
	 *
	 * @param int  $post_id Enquiry ID.
	 * @param bool $sent    Whether the mail provider accepted the message.
	 */
	do_action( 'jmc_enquiry_delivery', $post_id, $sent );

	return $sent;
}

/**
 * Count enquiries that failed to send.
 *
 * @return int Number of failed enquiries.
 */
function jmc_count_failed_enquiries(): int {
	$query = new WP_Query(
		[
			'post_type'              => JMC_ENQUIRY_POST_TYPE,
			'post_status'            => 'private',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_key'               => JMC_ENQUIRY_STATUS_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Admin-only count over a small table.
			'meta_value'             => 'failed', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin-only count over a small table.
		]
	);

	return (int) $query->found_posts;
}

/**
 * Delete enquiries older than the retention period.
 *
 * Deletes up to 100 per run; the daily schedule catches up with any backlog.
 *
 * @return int Number of enquiries deleted.
 */
function jmc_purge_enquiries(): int {
	/**
	 * Filters how many days enquiries are kept before they are deleted.
	 *
	 * @param int $days Retention period in days.
	 */
	$days = absint( apply_filters( 'jmc_enquiry_retention_days', JMC_ENQUIRY_RETENTION_DAYS ) );

	if ( 0 === $days ) {
		return 0;
	}

	$expired = get_posts(
		[
			'post_type'      => JMC_ENQUIRY_POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'date_query'     => [
				[
					'column' => 'post_date_gmt',
					'before' => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ),
				],
			],
		]
	);

	foreach ( $expired as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}

	return count( $expired );
}

add_action(
	'init',
	function (): void {
		register_post_type(
			JMC_ENQUIRY_POST_TYPE,
			[
				'labels'              => [
					'name'               => __( 'Enquiries', 'jmc-theme' ),
					'singular_name'      => __( 'Enquiry', 'jmc-theme' ),
					'edit_item'          => __( 'View enquiry', 'jmc-theme' ),
					'search_items'       => __( 'Search enquiries', 'jmc-theme' ),
					'not_found'          => __( 'No enquiries yet.', 'jmc-theme' ),
					'not_found_in_trash' => __( 'No enquiries in the bin.', 'jmc-theme' ),
					'all_items'          => __( 'All enquiries', 'jmc-theme' ),
				],
				'description'         => __( 'Enquiries sent from the website’s enquiry form.', 'jmc-theme' ),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'menu_position'       => 25,
				'menu_icon'           => 'dashicons-email-alt',
				'capability_type'     => 'post',
				'capabilities'        => [ 'create_posts' => 'do_not_allow' ],
				'map_meta_cap'        => true,
				'supports'            => false,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => false,
				'delete_with_user'    => false,
			]
		);

		if ( ! wp_next_scheduled( JMC_ENQUIRY_PURGE_HOOK ) ) {
			wp_schedule_event( time(), 'daily', JMC_ENQUIRY_PURGE_HOOK );
		}
	}
);

add_action(
	JMC_ENQUIRY_PURGE_HOOK,
	function (): void {
		jmc_purge_enquiries();
	}
);

add_action(
	'switch_theme',
	function (): void {
		wp_clear_scheduled_hook( JMC_ENQUIRY_PURGE_HOOK );
	}
);

/*
 * Admin list: show the enquirer's details and delivery status, and let
 * failed enquiries be filtered and resent.
 */

add_filter(
	'manage_' . JMC_ENQUIRY_POST_TYPE . '_posts_columns',
	function (): array {
		return [
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Name', 'jmc-theme' ),
			'jmc_email'    => __( 'Email address', 'jmc-theme' ),
			'jmc_postcode' => __( 'Postcode', 'jmc-theme' ),
			'jmc_service'  => __( 'Service', 'jmc-theme' ),
			'jmc_status'   => __( 'Delivery', 'jmc-theme' ),
			'date'         => __( 'Received', 'jmc-theme' ),
		];
	}
);

add_action(
	'manage_' . JMC_ENQUIRY_POST_TYPE . '_posts_custom_column',
	function ( string $column, int $post_id ): void {
		$values = jmc_get_enquiry_values( $post_id );

		switch ( $column ) {
			case 'jmc_email':
				printf(
					'<a href="%s">%s</a>',
					esc_url( 'mailto:' . $values['email'] ),
					esc_html( $values['email'] )
				);
				break;

			case 'jmc_postcode':
				echo esc_html( $values['postcode'] );
				break;

			case 'jmc_service':
				echo esc_html( '' === $values['service'] ? '—' : $values['service'] );
				break;

			case 'jmc_status':
				echo esc_html( jmc_enquiry_statuses()[ jmc_get_enquiry_status( $post_id ) ] );
				break;
		}
	},
	10,
	2
);

add_filter(
	'post_row_actions',
	function ( array $actions, WP_Post $post ): array {
		if ( JMC_ENQUIRY_POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		// Enquiries are read-only: replace "Edit" and "Quick Edit" with "View".
		unset( $actions['edit'], $actions['inline hide-if-no-js'] );

		$view = [];

		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$view['view'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_edit_post_link( $post->ID ) ),
				esc_html__( 'View', 'jmc-theme' )
			);

			$view['jmc_resend'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( jmc_enquiry_resend_url( $post->ID ) ),
				esc_html__( 'Resend', 'jmc-theme' )
			);
		}

		return $view + $actions;
	},
	10,
	2
);

add_filter(
	'bulk_actions-edit-' . JMC_ENQUIRY_POST_TYPE,
	function ( array $actions ): array {
		unset( $actions['edit'] );

		return $actions;
	}
);

add_filter(
	'views_edit-' . JMC_ENQUIRY_POST_TYPE,
	function ( array $views ): array {
		$failed = jmc_count_failed_enquiries();

		if ( 0 === $failed ) {
			return $views;
		}

		$current = 'failed' === jmc_enquiry_status_filter();

		$views['jmc_failed'] = sprintf(
			'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
			esc_url( admin_url( 'edit.php?post_type=' . JMC_ENQUIRY_POST_TYPE . '&jmc_enquiry_status=failed' ) ),
			$current ? ' class="current" aria-current="page"' : '',
			esc_html__( 'Failed to send', 'jmc-theme' ),
			$failed
		);

		return $views;
	}
);

/**
 * Read the delivery status the admin list is filtered by.
 *
 * @return string A key of `jmc_enquiry_statuses()`, or an empty string for no filter.
 */
function jmc_enquiry_status_filter(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filter.
	$status = isset( $_GET['jmc_enquiry_status'] ) ? sanitize_key( wp_unslash( $_GET['jmc_enquiry_status'] ) ) : '';

	return array_key_exists( $status, jmc_enquiry_statuses() ) ? $status : '';
}

add_action(
	'pre_get_posts',
	function ( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || JMC_ENQUIRY_POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$status = jmc_enquiry_status_filter();

		if ( '' !== $status ) {
			$query->set( 'meta_key', JMC_ENQUIRY_STATUS_META );
			$query->set( 'meta_value', $status );
		}
	}
);

/*
 * Enquiry screen: a read-only view of the enquiry with its delivery status
 * and a resend button, in place of the editor.
 */

add_action(
	'add_meta_boxes_' . JMC_ENQUIRY_POST_TYPE,
	function ( WP_Post $post ): void {
		remove_meta_box( 'submitdiv', JMC_ENQUIRY_POST_TYPE, 'side' );
		remove_meta_box( 'slugdiv', JMC_ENQUIRY_POST_TYPE, 'normal' );

		add_meta_box(
			'jmc-enquiry-details',
			__( 'Enquiry', 'jmc-theme' ),
			function () use ( $post ): void {
				$values = jmc_get_enquiry_values( $post->ID );
				$fields = jmc_enquiry_fields();

				echo '<dl class="jmc-enquiry-details">';

				foreach ( $fields as $id => $field ) {
					if ( 'consent' === $id ) {
						continue;
					}

					printf(
						'<dt><strong>%s</strong></dt><dd>%s</dd>',
						esc_html( $field['label'] ),
						'' === $values[ $id ] ? '—' : nl2br( esc_html( $values[ $id ] ) )
					);
				}

				printf(
					'<dt><strong>%s</strong></dt><dd>%s</dd>',
					esc_html__( 'Consent', 'jmc-theme' ),
					'1' === $values['consent']
						? esc_html__( 'Agreed to their details being used to reply to this enquiry.', 'jmc-theme' )
						: '—'
				);

				echo '</dl>';

				if ( '' !== $values['email'] ) {
					printf(
						'<p><a class="button button-primary" href="%s">%s</a></p>',
						esc_url( 'mailto:' . $values['email'] ),
						esc_html__( 'Reply by email', 'jmc-theme' )
					);
				}
			},
			JMC_ENQUIRY_POST_TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'jmc-enquiry-delivery',
			__( 'Delivery', 'jmc-theme' ),
			function () use ( $post ): void {
				$status = jmc_get_enquiry_status( $post->ID );
				$error  = (string) get_post_meta( $post->ID, JMC_ENQUIRY_ERROR_META, true );

				printf(
					'<p>%s <strong>%s</strong></p>',
					esc_html__( 'Status:', 'jmc-theme' ),
					esc_html( jmc_enquiry_statuses()[ $status ] )
				);

				printf(
					'<p>%s %d</p>',
					esc_html__( 'Attempts:', 'jmc-theme' ),
					absint( get_post_meta( $post->ID, JMC_ENQUIRY_ATTEMPTS_META, true ) )
				);

				printf(
					'<p>%s %s</p>',
					esc_html__( 'Received:', 'jmc-theme' ),
					esc_html( (string) get_the_date( '', $post ) . ' ' . (string) get_the_time( '', $post ) )
				);

				if ( 'failed' === $status && '' !== $error ) {
					printf(
						'<p>%s %s</p>',
						esc_html__( 'Last error:', 'jmc-theme' ),
						esc_html( $error )
					);
				}

				printf(
					'<p><a class="button" href="%s">%s</a></p>',
					esc_url( jmc_enquiry_resend_url( $post->ID ) ),
					esc_html__( 'Resend to the business email', 'jmc-theme' )
				);

				if ( current_user_can( 'delete_post', $post->ID ) ) {
					printf(
						'<p><a class="submitdelete" href="%s">%s</a></p>',
						esc_url( (string) get_delete_post_link( $post->ID ) ),
						esc_html__( 'Move to bin', 'jmc-theme' )
					);
				}
			},
			JMC_ENQUIRY_POST_TYPE,
			'side',
			'high'
		);
	}
);

/**
 * Build the nonce-protected URL that resends an enquiry.
 *
 * @param int $post_id Enquiry ID.
 *
 * @return string Admin URL.
 */
function jmc_enquiry_resend_url( int $post_id ): string {
	return wp_nonce_url(
		add_query_arg(
			[
				'action' => JMC_ENQUIRY_RESEND_ACTION,
				'post'   => $post_id,
			],
			admin_url( 'admin-post.php' )
		),
		JMC_ENQUIRY_RESEND_ACTION . '_' . $post_id
	);
}

add_action(
	'admin_post_' . JMC_ENQUIRY_RESEND_ACTION,
	function (): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- The ID is needed to name the nonce, which is verified on the next line.
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		check_admin_referer( JMC_ENQUIRY_RESEND_ACTION . '_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) || JMC_ENQUIRY_POST_TYPE !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to resend this enquiry.', 'jmc-theme' ), '', [ 'response' => 403 ] );
		}

		$sent = jmc_send_enquiry( $post_id );

		wp_safe_redirect(
			add_query_arg(
				'jmc_resent',
				$sent ? 'sent' : 'failed',
				wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=' . JMC_ENQUIRY_POST_TYPE )
			)
		);
		exit;
	}
);

/*
 * Admin notices: confirm resends, and warn about failed deliveries on the
 * dashboard and enquiry screens so they aren't missed.
 */

add_action(
	'admin_notices',
	function (): void {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, [ 'dashboard', 'edit-' . JMC_ENQUIRY_POST_TYPE, JMC_ENQUIRY_POST_TYPE ], true ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_others_posts' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag set by the resend redirect.
		$resent = isset( $_GET['jmc_resent'] ) ? sanitize_key( wp_unslash( $_GET['jmc_resent'] ) ) : '';

		if ( 'sent' === $resent ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Enquiry sent to the business email address.', 'jmc-theme' )
			);
		} elseif ( 'failed' === $resent ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html__( 'The enquiry still couldn’t be sent. Check the business email address in Settings → Business and the site’s email (SMTP) settings, then try again.', 'jmc-theme' )
			);
		}

		$failed = jmc_count_failed_enquiries();

		if ( 0 === $failed || 'failed' === $resent ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s <a href="%s">%s</a></p></div>',
			esc_html(
				sprintf(
					/* translators: %d: number of enquiries. */
					_n(
						'%d enquiry couldn’t be emailed to the business.',
						'%d enquiries couldn’t be emailed to the business.',
						$failed,
						'jmc-theme'
					),
					$failed
				)
			),
			esc_url( admin_url( 'edit.php?post_type=' . JMC_ENQUIRY_POST_TYPE . '&jmc_enquiry_status=failed' ) ),
			esc_html__( 'Review and resend them', 'jmc-theme' )
		);
	}
);

/*
 * Privacy: suggested policy text, and personal data export and erasure by
 * email address (Tools → Export/Erase Personal Data).
 */

add_action(
	'admin_init',
	function (): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$days = absint( apply_filters( 'jmc_enquiry_retention_days', JMC_ENQUIRY_RETENTION_DAYS ) );

		wp_add_privacy_policy_content(
			__( 'Enquiry form', 'jmc-theme' ),
			'<p>' . esc_html(
				sprintf(
					/* translators: %d: retention period in days. */
					_n(
						'When you send an enquiry we collect your name, email address, phone number (if you give it), postcode, the service you are interested in and your message. We use them only to reply to your enquiry. They are stored on this website and emailed to us, and are deleted automatically after %d day. No tracking or third-party spam-protection service is used on the form.',
						'When you send an enquiry we collect your name, email address, phone number (if you give it), postcode, the service you are interested in and your message. We use them only to reply to your enquiry. They are stored on this website and emailed to us, and are deleted automatically after %d days. No tracking or third-party spam-protection service is used on the form.',
						$days,
						'jmc-theme'
					),
					$days
				)
			) . '</p>'
		);
	}
);

/**
 * Find enquiries sent from an email address.
 *
 * @param string $email Email address.
 * @param int    $page  1-based page of results.
 *
 * @return int[] Enquiry IDs, 50 per page.
 */
function jmc_find_enquiries_by_email( string $email, int $page ): array {
	return array_map(
		'intval',
		get_posts(
			[
				'post_type'      => JMC_ENQUIRY_POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'paged'          => max( 1, $page ),
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_key'       => JMC_ENQUIRY_EMAIL_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Personal data requests are rare, admin-only operations.
				'meta_value'     => strtolower( trim( $email ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Personal data requests are rare, admin-only operations.
			]
		)
	);
}

add_filter(
	'wp_privacy_personal_data_exporters',
	function ( array $exporters ): array {
		$exporters['jmc-enquiries'] = [
			'exporter_friendly_name' => __( 'Enquiries', 'jmc-theme' ),
			'callback'               => function ( string $email, int $page = 1 ): array {
				$ids    = jmc_find_enquiries_by_email( $email, $page );
				$fields = jmc_enquiry_fields();
				$data   = [];

				foreach ( $ids as $post_id ) {
					$item = [
						[
							'name'  => __( 'Received', 'jmc-theme' ),
							'value' => (string) get_the_date( 'Y-m-d H:i', $post_id ),
						],
					];

					foreach ( jmc_get_enquiry_values( $post_id ) as $id => $value ) {
						$item[] = [
							'name'  => $fields[ $id ]['label'],
							'value' => $value,
						];
					}

					$data[] = [
						'group_id'    => 'jmc-enquiries',
						'group_label' => __( 'Enquiries', 'jmc-theme' ),
						'item_id'     => 'jmc-enquiry-' . $post_id,
						'data'        => $item,
					];
				}

				return [
					'data' => $data,
					'done' => count( $ids ) < 50,
				];
			},
		];

		return $exporters;
	}
);

add_filter(
	'wp_privacy_personal_data_erasers',
	function ( array $erasers ): array {
		$erasers['jmc-enquiries'] = [
			'eraser_friendly_name' => __( 'Enquiries', 'jmc-theme' ),
			'callback'             => function ( string $email ): array {
				// Always read the first page: erased enquiries drop out of the results.
				$ids     = jmc_find_enquiries_by_email( $email, 1 );
				$removed = 0;

				foreach ( $ids as $post_id ) {
					$removed += wp_delete_post( $post_id, true ) ? 1 : 0;
				}

				// Stop when a delete fails, or the same enquiries would be found again forever.
				return [
					'items_removed'  => $removed > 0,
					'items_retained' => $removed < count( $ids ),
					'messages'       => $removed < count( $ids )
						? [ __( 'Some enquiries could not be deleted. Delete them from the Enquiries screen.', 'jmc-theme' ) ]
						: [],
					'done'           => count( $ids ) < 50 || $removed < count( $ids ),
				];
			},
		];

		return $erasers;
	}
);
