<?php
/**
 * Receive enquiry form submissions.
 *
 * The form submits with JavaScript to the REST API and updates in place:
 *
 * - `GET  /wp-json/jmc/v1/enquiries/token` returns a fresh nonce and timing
 *   token, so a form on a cached page can still be submitted.
 * - `POST /wp-json/jmc/v1/enquiries` handles the submission and returns
 *   `{ status, message, errors }`.
 *
 * Without JavaScript the form posts to `admin-post.php`, which runs the same
 * handler and redirects back with the status in `?jmc_enquiry_result=`.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * REST namespace for theme endpoints.
 */
const JMC_REST_NAMESPACE = 'jmc/v1';

/**
 * Nonce action protecting enquiry submissions.
 */
const JMC_ENQUIRY_NONCE_ACTION = 'jmc_enquiry_submit';

/**
 * Name of the form field carrying the nonce.
 */
const JMC_ENQUIRY_NONCE_FIELD = 'jmc_enquiry_nonce';

/**
 * Admin-post action for submissions made without JavaScript.
 */
const JMC_ENQUIRY_FORM_ACTION = 'jmc_enquiry';

/**
 * Query argument carrying the result after a submission without JavaScript.
 */
const JMC_ENQUIRY_RESULT_ARG = 'jmc_enquiry_result';

/**
 * Name of the form field carrying the ID of the form, to return to after a
 * submission without JavaScript.
 */
const JMC_ENQUIRY_RETURN_FIELD = 'jmc_enquiry_return';

/**
 * List the services a visitor can choose from: the titles of the pages in
 * the Services section.
 *
 * @return string[] Service names in display order.
 */
function jmc_enquiry_service_options(): array {
	return array_column( jmc_get_services( jmc_get_services_page()['id'] ), 'title' );
}

/**
 * Secret key used to sign the timing token and hash visitor IP addresses.
 *
 * @return string Key derived from the site's salts.
 */
function jmc_enquiry_signing_key(): string {
	return wp_salt( 'nonce' ) . '|jmc-enquiry';
}

/**
 * Create the hidden-field values a form needs to be submitted.
 *
 * @return array{nonce: string, started: string} Nonce and signed timing token.
 */
function jmc_enquiry_form_tokens(): array {
	return [
		'nonce'   => wp_create_nonce( JMC_ENQUIRY_NONCE_ACTION ),
		'started' => jmc_enquiry_timing_token( time(), jmc_enquiry_signing_key() ),
	];
}

/**
 * Retrieve the visitor's IP address.
 *
 * Uses `REMOTE_ADDR`, which can't be spoofed. Behind a reverse proxy or CDN
 * every visitor shares the proxy's address; filter it with the trusted
 * header your proxy sets.
 *
 * @return string IP address, or an empty string when unknown.
 */
function jmc_enquiry_client_ip(): string {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	/**
	 * Filters the IP address used to rate-limit enquiries.
	 *
	 * @param string $ip IP address from `REMOTE_ADDR`.
	 */
	$ip = (string) apply_filters( 'jmc_enquiry_client_ip', $ip );

	return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
}

/**
 * Record a submission attempt and report whether the visitor is over the limit.
 *
 * Counts attempts per visitor in a transient keyed by a hash of their IP
 * address, so no address is stored.
 *
 * @return bool Whether this attempt goes over the limit.
 */
function jmc_enquiry_hit_rate_limit(): bool {
	$ip = jmc_enquiry_client_ip();

	if ( '' === $ip ) {
		return false;
	}

	$transient = 'jmc_enquiry_rate_' . substr( jmc_enquiry_rate_key( $ip, jmc_enquiry_signing_key() ), 0, 32 );
	$record    = get_transient( $transient );
	$now       = time();

	if ( ! is_array( $record ) || ( (int) ( $record['start'] ?? 0 ) ) + JMC_ENQUIRY_RATE_WINDOW <= $now ) {
		$record = [
			'start' => $now,
			'count' => 0,
		];
	}

	++$record['count'];

	set_transient( $transient, $record, max( 1, (int) $record['start'] + JMC_ENQUIRY_RATE_WINDOW - $now ) );

	return $record['count'] > JMC_ENQUIRY_RATE_LIMIT;
}

/**
 * Determine whether an enquiry with this fingerprint was stored recently.
 *
 * @param string $fingerprint Value from `jmc_enquiry_fingerprint()`.
 *
 * @return bool Whether it is a repeat.
 */
function jmc_enquiry_is_repeat( string $fingerprint ): bool {
	return [] !== get_posts(
		[
			'post_type'              => JMC_ENQUIRY_POST_TYPE,
			'post_status'            => 'private',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'meta_key'               => JMC_ENQUIRY_FINGERPRINT_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One indexed lookup per accepted submission.
			'meta_value'             => $fingerprint, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One indexed lookup per accepted submission.
			'date_query'             => [
				[
					'column' => 'post_date_gmt',
					'after'  => gmdate( 'Y-m-d H:i:s', time() - JMC_ENQUIRY_DUPLICATE_WINDOW ),
				],
			],
		]
	);
}

/**
 * Handle a submission from either the REST API or the no-JavaScript fallback.
 *
 * @param array<string,mixed> $post Unslashed request fields.
 *
 * @return array{status: string, outcome: string, errors: array<string,string>} Result for the visitor and for monitoring.
 */
function jmc_submit_enquiry( array $post ): array {
	$nonce = $post[ JMC_ENQUIRY_NONCE_FIELD ] ?? '';

	if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, JMC_ENQUIRY_NONCE_ACTION ) ) {
		$result = [
			'status'  => 'expired',
			'outcome' => 'expired',
			'errors'  => [],
		];
	} else {
		$result = jmc_handle_enquiry(
			post: $post,
			services: jmc_enquiry_service_options(),
			now: time(),
			key: jmc_enquiry_signing_key(),
			hit_limit: 'jmc_enquiry_hit_rate_limit',
			is_repeat: 'jmc_enquiry_is_repeat',
			store: 'jmc_insert_enquiry',
			send: 'jmc_send_enquiry'
		);
	}

	/**
	 * Fires after every enquiry submission with its operational outcome.
	 *
	 * Passes no personal data or message content, so it is safe to use for
	 * monitoring and aggregate reporting.
	 *
	 * @param string $outcome One of `sent`, `delivery_failed`, `duplicate`, `spam`
	 *                        (discarded), `spam_stored` (held for review),
	 *                        `rate_limited`, `invalid`, `store_failed` or `expired`.
	 */
	do_action( 'jmc_enquiry_outcome', $result['outcome'] );

	return [
		'status'  => $result['status'],
		'outcome' => $result['outcome'],
		'errors'  => $result['errors'],
	];
}

add_action(
	'rest_api_init',
	function (): void {
		register_rest_route(
			JMC_REST_NAMESPACE,
			'/enquiries/token',
			[
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => function (): WP_REST_Response {
					$response = new WP_REST_Response( jmc_enquiry_form_tokens() );
					$response->header( 'Cache-Control', 'no-store, private' );

					return $response;
				},
			]
		);

		register_rest_route(
			JMC_REST_NAMESPACE,
			'/enquiries',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => function ( WP_REST_Request $request ): WP_REST_Response {
					$result = jmc_submit_enquiry( $request->get_body_params() );

					$codes = [
						'sent'         => 200,
						'invalid'      => 422,
						'rate_limited' => 429,
						'expired'      => 403,
						'error'        => 500,
					];

					$response = new WP_REST_Response(
						[
							'status'  => $result['status'],
							'message' => jmc_enquiry_status_messages()[ $result['status'] ] ?? '',
							'errors'  => (object) $result['errors'],
						],
						$codes[ $result['status'] ] ?? 500
					);
					$response->header( 'Cache-Control', 'no-store, private' );

					return $response;
				},
			]
		);
	}
);

/**
 * Handle a submission made without JavaScript and redirect back to the form.
 *
 * @return void
 */
function jmc_enquiry_fallback_submit(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in jmc_submit_enquiry().
	$post = is_array( $_POST ) ? wp_unslash( $_POST ) : [];

	$result = jmc_submit_enquiry( $post );
	$return = sanitize_html_class( (string) ( is_string( $post[ JMC_ENQUIRY_RETURN_FIELD ] ?? null ) ? $post[ JMC_ENQUIRY_RETURN_FIELD ] : '' ) );
	$page   = strtok( (string) wp_get_referer(), '#' );

	$url = add_query_arg(
		JMC_ENQUIRY_RESULT_ARG,
		$result['status'],
		remove_query_arg( JMC_ENQUIRY_RESULT_ARG, $page ? $page : home_url( '/' ) )
	);

	wp_safe_redirect( '' === $return ? $url : $url . '#' . $return, 303 );
	exit;
}

add_action( 'admin_post_nopriv_' . JMC_ENQUIRY_FORM_ACTION, 'jmc_enquiry_fallback_submit' );
add_action( 'admin_post_' . JMC_ENQUIRY_FORM_ACTION, 'jmc_enquiry_fallback_submit' );
