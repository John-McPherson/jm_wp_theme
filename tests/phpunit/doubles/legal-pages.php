<?php
/**
 * WordPress test doubles for the legal pages tests.
 *
 * Extends the shared doubles in tests/phpunit/bootstrap.php (which read
 * `jmc_test_options` and `jmc_test_posts`) with post updates, block
 * serialisation, permalinks and the admin request helpers the Legal pages
 * settings page needs. `wp_update_post()` records each update in
 * `$GLOBALS['jmc_test_updated_posts']`; `check_admin_referer()` records each
 * nonce action in `$GLOBALS['jmc_test_nonce_checks']` and fails when
 * `$GLOBALS['jmc_test_nonce_valid']` is false; `current_user_can()` reads
 * `$GLOBALS['jmc_test_wp']['can_edit']`, as the services double does; and
 * `wp_die()` throws. Doubles are guarded so they never replace a definition
 * supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

if ( ! function_exists( 'wp_slash' ) ) {
	/**
	 * Add slashes to a value.
	 *
	 * Test double that returns the value unchanged, as the `wp_insert_post()`
	 * and `wp_update_post()` doubles do not unslash.
	 *
	 * @param mixed $value Value to slash.
	 *
	 * @return mixed The value.
	 */
	function wp_slash( mixed $value ): mixed {
		return $value;
	}
}

if ( ! function_exists( 'serialize_blocks' ) ) {
	/**
	 * Serialise parsed blocks.
	 *
	 * Test double: encodes the blocks as JSON, the format the `parse_blocks()`
	 * double reads.
	 *
	 * @param array<int,array<string,mixed>> $blocks Parsed blocks.
	 *
	 * @return string JSON-encoded blocks.
	 */
	function serialize_blocks( array $blocks ): string {
		return json_encode( $blocks, JSON_THROW_ON_ERROR ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The parse_blocks() test double decodes plain JSON.
	}
}

if ( ! function_exists( 'wp_update_post' ) ) {
	/**
	 * Update a post.
	 *
	 * Test double that records the post data in
	 * `$GLOBALS['jmc_test_updated_posts']` and applies it to the matching
	 * `$GLOBALS['jmc_test_posts']` fixture.
	 *
	 * @param array<string,mixed> $postarr Post data, including `ID`.
	 *
	 * @return int The post ID, or 0 when no fixture matches.
	 */
	function wp_update_post( array $postarr ): int {
		$GLOBALS['jmc_test_updated_posts'][] = $postarr;

		$post_id = (int) ( $postarr['ID'] ?? 0 );
		$post    = $GLOBALS['jmc_test_posts'][ $post_id ] ?? null;

		if ( ! is_object( $post ) ) {
			return 0;
		}

		foreach ( $postarr as $field => $value ) {
			$post->{$field} = $value;
		}

		return $post_id;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	/**
	 * Return the fixture permalink for a post.
	 *
	 * @param object $post Post record.
	 *
	 * @return string|false Permalink.
	 */
	function get_permalink( object $post ): string|false {
		return $post->permalink ?? false;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Whether the fixture user has a capability.
	 *
	 * Reads the same fixture as the services double, so either may be loaded.
	 *
	 * @param string $capability Capability name (ignored).
	 *
	 * @return bool Capability state.
	 */
	function current_user_can( string $capability = '' ): bool {
		unset( $capability );

		return (bool) ( $GLOBALS['jmc_test_wp']['can_edit'] ?? false );
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	/**
	 * Stop the request.
	 *
	 * Test double that throws, so tests can assert the request stopped.
	 *
	 * @param string              $message Message.
	 * @param string              $title   Title (ignored).
	 * @param array<string,mixed> $args    Arguments (ignored).
	 *
	 * @throws RuntimeException Always.
	 *
	 * @return never
	 */
	function wp_die( string $message = '', string $title = '', array $args = [] ): never {
		unset( $title, $args );

		throw new RuntimeException( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test double.
	}
}

if ( ! function_exists( 'check_admin_referer' ) ) {
	/**
	 * Verify the request's nonce.
	 *
	 * @param string $action Nonce action.
	 *
	 * @return int Always 1 when the nonce is valid.
	 */
	function check_admin_referer( string $action = '' ): int {
		$GLOBALS['jmc_test_nonce_checks'][] = $action;

		if ( false === ( $GLOBALS['jmc_test_nonce_valid'] ?? true ) ) {
			wp_die( 'The link you followed has expired.' );
		}

		return 1;
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	/**
	 * Print a marker for the nonce field.
	 *
	 * @param string $action Nonce action.
	 *
	 * @return void
	 */
	function wp_nonce_field( string $action = '' ): void {
		printf( '<input type="hidden" name="_wpnonce" value="nonce-%s" />', esc_attr( $action ) );
	}
}

if ( ! function_exists( 'get_edit_post_link' ) ) {
	/**
	 * Retrieve a post's edit link.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $context Link context (ignored).
	 *
	 * @return string Edit link on the example host.
	 */
	function get_edit_post_link( int $post_id, string $context = 'display' ): string {
		unset( $context );

		return 'https://example.com/wp-admin/post.php?post=' . $post_id . '&action=edit';
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * Retrieve an admin URL.
	 *
	 * @param string $path Path relative to the admin URL.
	 *
	 * @return string Admin URL on the example host.
	 */
	function admin_url( string $path = '' ): string {
		return 'https://example.com/wp-admin/' . ltrim( $path, '/' );
	}
}
