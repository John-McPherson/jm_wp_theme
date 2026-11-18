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

// Telephone links hand off to the dialler, so never open them in a new tab.
$opens_in_new_tab = true === ( $cta_link['opensInNewTab'] ?? false )
	&& ! str_starts_with( $url, 'tel:' );

jmc_component(
	name: 'button-link',
	args: [
		'text'    => $text,
		'link'    => [
			'url'    => $url,
			'target' => $opens_in_new_tab ? '_blank' : '',
		],
		'classes' => [
			'jmc-button--small',
			'jmc-header-cta',
			$attributes['className'] ?? null,
		],
	]
);
