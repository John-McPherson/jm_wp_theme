<?php
/**
 * Settings → Legal pages: tick the legal pages the Legal menu links to.
 *
 * The page lists each page from `jmc_legal_pages()` with its status and an
 * edit link. Saving applies the choice with `jmc_save_legal_pages()` (see
 * inc/legal-pages.php): ticking creates a missing page as a draft and links
 * it; unticking only removes the link.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Settings page slug.
 */
const JMC_LEGAL_PAGES_SETTINGS_PAGE = 'jmc-legal-pages';

/**
 * The `admin-post.php` action, and nonce action, that saves the page.
 */
const JMC_LEGAL_PAGES_SAVE_ACTION = 'jmc_save_legal_pages';

/**
 * Add the Settings → Legal pages submenu for administrators.
 *
 * @return void
 */
function jmc_add_legal_pages_settings_page(): void {
	add_options_page(
		__( 'Legal pages', 'jmc-theme' ),
		__( 'Legal pages', 'jmc-theme' ),
		'manage_options',
		JMC_LEGAL_PAGES_SETTINGS_PAGE,
		'jmc_legal_pages_settings_page'
	);
}

/**
 * Describe a legal page's status for the settings page.
 *
 * @param WP_Post|null $page The page, or null when it does not exist.
 *
 * @return string Status label.
 */
function jmc_legal_page_status_label( ?object $page ): string {
	if ( null === $page ) {
		return __( 'Not created', 'jmc-theme' );
	}

	$labels = [
		'publish' => __( 'Published', 'jmc-theme' ),
		'draft'   => __( 'Draft', 'jmc-theme' ),
		'pending' => __( 'Pending review', 'jmc-theme' ),
		'private' => __( 'Private', 'jmc-theme' ),
		'future'  => __( 'Scheduled', 'jmc-theme' ),
		'trash'   => __( 'Trashed', 'jmc-theme' ),
	];

	return $labels[ $page->post_status ] ?? (string) $page->post_status;
}

/**
 * Print the Settings → Legal pages admin page.
 *
 * WordPress only routes users with `manage_options` here (see
 * `jmc_add_legal_pages_settings_page()`).
 *
 * @return void
 */
function jmc_legal_pages_settings_page(): void {
	$disabled = jmc_disabled_legal_pages();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Legal pages', 'jmc-theme' ); ?></h1>
		<p><?php esc_html_e( 'Ticked pages are linked from the Legal menu in the footer, which only shows them once they are published. Ticking a page that does not exist creates it as a draft with placeholder text. Unticking a page removes its link but keeps the page.', 'jmc-theme' ); ?></p>
		<p><?php esc_html_e( 'Legal text must be written or reviewed by someone qualified before a page is published.', 'jmc-theme' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo esc_attr( JMC_LEGAL_PAGES_SAVE_ACTION ); ?>" />
			<?php wp_nonce_field( JMC_LEGAL_PAGES_SAVE_ACTION ); ?>
			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Pages linked from the Legal menu', 'jmc-theme' ); ?></legend>
				<?php
				foreach ( jmc_legal_pages() as $slug => $title ) :
					$page     = jmc_find_legal_page( slug: $slug );
					$field_id = 'jmc-legal-page-' . $slug;

					// Trashed pages cannot be edited, so link to the Trash instead.
					$edit_url = null === $page
						? ''
						: ( 'trash' === $page->post_status
							? admin_url( 'edit.php?post_status=trash&post_type=page' )
							: (string) get_edit_post_link( $page->ID, 'raw' ) );

					$edit_text = null !== $page && 'trash' === $page->post_status
						? __( 'View Trash', 'jmc-theme' )
						: __( 'Edit', 'jmc-theme' );
					?>
					<p>
						<input type="checkbox" name="jmc_legal_pages[]" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $slug ); ?>"<?php echo in_array( $slug, $disabled, true ) ? '' : ' checked'; ?> />
						<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $title ); ?></label>
						— <?php echo esc_html( jmc_legal_page_status_label( page: $page ) ); ?>
						<?php if ( '' !== $edit_url ) : ?>
							— <a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $edit_text ); ?><span class="screen-reader-text"> <?php echo esc_html( $title ); ?></span></a>
						<?php endif; ?>
					</p>
				<?php endforeach; ?>
			</fieldset>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Check and save a submitted Legal pages form.
 *
 * Requires `manage_options` and a valid nonce; WordPress stops the request
 * otherwise.
 *
 * @param array<string,mixed> $request Unslashed form fields.
 *
 * @return string The settings page URL to return to.
 */
function jmc_save_legal_pages_request( array $request ): string {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to change the legal pages.', 'jmc-theme' ), '', [ 'response' => 403 ] );
	}

	check_admin_referer( JMC_LEGAL_PAGES_SAVE_ACTION );

	$ticked = $request['jmc_legal_pages'] ?? [];

	jmc_save_legal_pages( ticked: is_array( $ticked ) ? $ticked : [] );

	return admin_url( 'options-general.php?page=' . JMC_LEGAL_PAGES_SETTINGS_PAGE . '&settings-updated=true' );
}

/**
 * Handle the Legal pages form and return to the settings page.
 *
 * @return void
 */
function jmc_handle_legal_pages_settings(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The nonce is checked, and slugs are matched against the registered list, in jmc_save_legal_pages_request().
	wp_safe_redirect( jmc_save_legal_pages_request( request: wp_unslash( $_POST ) ) );
	exit;
}

add_action( 'admin_menu', 'jmc_add_legal_pages_settings_page' );
add_action( 'admin_post_' . JMC_LEGAL_PAGES_SAVE_ACTION, 'jmc_handle_legal_pages_settings' );
