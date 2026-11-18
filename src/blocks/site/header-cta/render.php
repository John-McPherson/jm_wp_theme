<?php
/**
 * Render the header call-to-action block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the header call-to-action block.
 *
 * @var array{
 *     text?: string,
 *     link?: array{
 *         url?: string,
 *         opensInNewTab?: bool
 *     },
 *     className?: string
 * } $attributes
 * @var WP_Block $block
 */

$text = jmc_args_string(
	args: $attributes,
	key: 'text'
);

$cta_link = is_array( $attributes['link'] ?? null )
	? $attributes['link']
	: [];

$url = jmc_safe_link_url( $cta_link['url'] ?? null );

if ( '' === $text || '' === $url ) {
	return;
}

jmc_component(
	name: 'button-link',
	args: [
		'text'    => $text,
		// The component decides whether the stored link opens in a new tab.
		'link'    => [ 'url' => $url ] + $cta_link,
		'classes' => [
			'jmc-button--small',
			'jmc-header-cta',
			$attributes['className'] ?? null,
		],
	]
);
