<?php
/**
 * Icon registry test double backed by the services test fixture.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.

if ( ! class_exists( 'WP_Icons_Registry' ) ) {
	/**
	 * Icon registry double backed by the fixture's `icons` list.
	 */
	final class WP_Icons_Registry {

		/**
		 * Return the registry.
		 *
		 * @return self Registry instance.
		 */
		public static function get_instance(): self {
			return new self();
		}

		/**
		 * Whether an icon is registered.
		 *
		 * @param string $icon_name Icon name.
		 *
		 * @return bool True when registered.
		 */
		public function is_registered( string $icon_name ): bool {
			return in_array( $icon_name, (array) jmc_test_wp( 'icons' ), true );
		}
	}
}
