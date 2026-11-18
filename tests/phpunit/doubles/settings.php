<?php
/**
 * WordPress test doubles for the Settings API.
 *
 * Record registrations in `$GLOBALS['jmc_test_settings']` (keys `settings`,
 * `pages`, `sections`, `fields`) and print stand-ins for the form helpers.
 * Doubles are guarded so they never replace a definition supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

if ( ! function_exists( 'register_setting' ) ) {
	/**
	 * Record a registered setting.
	 *
	 * @param string              $option_group Settings group.
	 * @param string              $option_name  Option name.
	 * @param array<string,mixed> $args         Registration arguments.
	 *
	 * @return void
	 */
	function register_setting( string $option_group, string $option_name, array $args = [] ): void {
		$GLOBALS['jmc_test_settings']['settings'][ $option_name ] = [ 'group' => $option_group ] + $args;
	}
}

if ( ! function_exists( 'add_options_page' ) ) {
	/**
	 * Record a Settings submenu page.
	 *
	 * @param string   $page_title Page title.
	 * @param string   $menu_title Menu title.
	 * @param string   $capability Capability required to see the page.
	 * @param string   $menu_slug  Page slug.
	 * @param callable $callback   Page renderer.
	 *
	 * @return string The page hook suffix.
	 */
	function add_options_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback ): string {
		$GLOBALS['jmc_test_settings']['pages'][ $menu_slug ] = [
			'page_title' => $page_title,
			'menu_title' => $menu_title,
			'capability' => $capability,
			'callback'   => $callback,
		];

		return 'settings_page_' . $menu_slug;
	}
}

if ( ! function_exists( 'add_settings_section' ) ) {
	/**
	 * Record a settings section.
	 *
	 * @param string   $id       Section ID.
	 * @param string   $title    Section heading.
	 * @param callable $callback Section intro renderer.
	 * @param string   $page     Settings page slug.
	 *
	 * @return void
	 */
	function add_settings_section( string $id, string $title, callable $callback, string $page ): void {
		$GLOBALS['jmc_test_settings']['sections'][ $id ] = [
			'title'    => $title,
			'callback' => $callback,
			'page'     => $page,
		];
	}
}

if ( ! function_exists( 'add_settings_field' ) ) {
	/**
	 * Record a settings field.
	 *
	 * @param string              $id       Field ID.
	 * @param string              $title    Row heading.
	 * @param callable            $callback Field renderer.
	 * @param string              $page     Settings page slug.
	 * @param string              $section  Section ID.
	 * @param array<string,mixed> $args     Extra arguments, e.g. `label_for`.
	 *
	 * @return void
	 */
	function add_settings_field( string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = [] ): void {
		$GLOBALS['jmc_test_settings']['fields'][ $id ] = [
			'title'    => $title,
			'callback' => $callback,
			'page'     => $page,
			'section'  => $section,
			'args'     => $args,
		];
	}
}

if ( ! function_exists( 'settings_fields' ) ) {
	/**
	 * Print a marker for the settings group's hidden fields.
	 *
	 * @param string $option_group Settings group.
	 *
	 * @return void
	 */
	function settings_fields( string $option_group ): void {
		printf( '<input type="hidden" name="option_page" value="%s" />', esc_attr( $option_group ) );
	}
}

if ( ! function_exists( 'do_settings_sections' ) ) {
	/**
	 * Print a marker for the page's sections.
	 *
	 * @param string $page Settings page slug.
	 *
	 * @return void
	 */
	function do_settings_sections( string $page ): void {
		printf( '<!-- sections:%s -->', esc_html( $page ) );
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	/**
	 * Print a submit button.
	 *
	 * @return void
	 */
	function submit_button(): void {
		echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" />';
	}
}

if ( ! function_exists( 'get_admin_page_title' ) ) {
	/**
	 * Return the current admin page's title.
	 *
	 * @return string The title of the recorded Business settings page.
	 */
	function get_admin_page_title(): string {
		return $GLOBALS['jmc_test_settings']['pages'][ JMC_BUSINESS_SETTINGS_PAGE ]['page_title'] ?? '';
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	/**
	 * Escape text for use in a textarea.
	 *
	 * @param string $text Text to escape.
	 *
	 * @return string Escaped text.
	 */
	function esc_textarea( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
