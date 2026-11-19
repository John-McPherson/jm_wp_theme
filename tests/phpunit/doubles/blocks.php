<?php
/**
 * WordPress test doubles for block instances and page ancestry.
 *
 * `WP_Block` renders inner blocks as marker paragraphs and core Breadcrumbs
 * as the markup in `$GLOBALS['jmc_test_breadcrumbs']`. Page parents come from
 * `$GLOBALS['jmc_test_post_parents']`, keyed by page ID. Doubles are guarded
 * so they never replace a definition supplied elsewhere.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WordPress test doubles must retain their core names.
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- Doubles are grouped by concern.

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * Translate and escape text for an attribute.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 *
	 * @return string Escaped text.
	 */
	function esc_attr__( string $text, string $domain = 'default' ): string {
		unset( $domain );

		return esc_attr( $text );
	}
}

if ( ! function_exists( 'wp_get_post_parent_id' ) ) {
	/**
	 * Return a page's parent ID from the fixture.
	 *
	 * @param int $post_id Page ID.
	 *
	 * @return int|false Parent ID, or false for an unknown page.
	 */
	function wp_get_post_parent_id( int $post_id ): int|false {
		return $GLOBALS['jmc_test_post_parents'][ $post_id ] ?? false;
	}
}

if ( ! class_exists( 'WP_Block' ) ) {
	/**
	 * Minimal block instance.
	 */
	final class WP_Block {

		/**
		 * Block name.
		 *
		 * @var string
		 */
		public string $name;

		/**
		 * Block attributes.
		 *
		 * @var array<string, mixed>
		 */
		public array $attributes;

		/**
		 * Available context.
		 *
		 * @var array<string, mixed>
		 */
		public array $context;

		/**
		 * Inner block instances.
		 *
		 * @var array<int, WP_Block>
		 */
		public array $inner_blocks = [];

		/**
		 * Build a block instance from a parsed block.
		 *
		 * @param array<string, mixed> $parsed_block      Parsed block.
		 * @param array<string, mixed> $available_context Context from the parent.
		 */
		public function __construct( array $parsed_block, array $available_context = [] ) {
			$this->name       = (string) ( $parsed_block['blockName'] ?? '' );
			$this->attributes = (array) ( $parsed_block['attrs'] ?? [] );
			$this->context    = $available_context;

			foreach ( (array) ( $parsed_block['innerBlocks'] ?? [] ) as $inner_block ) {
				$this->inner_blocks[] = new self( $inner_block, $available_context );
			}
		}

		/**
		 * Render the block.
		 *
		 * @return string Marker markup.
		 */
		public function render(): string {
			if ( 'core/breadcrumbs' === $this->name ) {
				$GLOBALS['jmc_test_breadcrumbs_rendered'][] = [
					'attributes' => $this->attributes,
					'context'    => $this->context,
				];

				return (string) ( $GLOBALS['jmc_test_breadcrumbs'] ?? '' );
			}

			return sprintf(
				'<p data-block="%s" data-variant="%s"></p>',
				htmlspecialchars( $this->name, ENT_QUOTES ),
				htmlspecialchars( (string) ( $this->attributes['variant'] ?? '' ), ENT_QUOTES )
			);
		}
	}
}
