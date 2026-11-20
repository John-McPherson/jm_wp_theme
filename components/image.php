<?php
/**
 * Render an image component with optional sizing and a loading strategy.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Component arguments for the image template.
 *
 * @var array{
 *     image_id?: int|string,
 *     classes?: string|string[],
 *     size?: string,
 *     loading?: 'lazy'|'eager'|false,
 *     sizes?: string,
 *     alt?: string
 * } $args
 */


$image_id = jmc_args_int(
	args: $args,
	key: 'image_id',
	fallback_value: 0,
	min: 1
);

if ( 0 === $image_id ) {
	return;
}

$classes = jmc_html_classes(
	[
		'jmc-image',
		$args['classes'] ?? null,
	]
);

// `false` omits the attribute and leaves the loading strategy to WordPress.
$loading = false === ( $args['loading'] ?? null )
	? false
	: jmc_html_allowed_value(
		value: $args['loading'] ?? null,
		allowed: [ 'lazy', 'eager' ],
		fallback_value: 'lazy'
	);

// Unregistered sizes would make WordPress fall back to an arbitrary size.
$size = jmc_html_allowed_value(
	value: $args['size'] ?? null,
	allowed: [ ...get_intermediate_image_sizes(), 'full' ],
	fallback_value: 'large'
);

$image_attributes = [
	'class'   => $classes,
	'loading' => $loading,
	'sizes'   => jmc_args_string(
		args: $args,
		key: 'sizes',
		fallback_value: '(max-width: 768px) 100vw, 50vw'
	),
];

// An explicit alt replaces the Media Library text.
if ( is_string( $args['alt'] ?? null ) ) {
	$image_attributes['alt'] = trim( $args['alt'] );
}

$image = wp_get_attachment_image(
	attachment_id: $image_id,
	size: $size,
	icon: false,
	attr: $image_attributes
);

if ( '' !== $image ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated and escaped by wp_get_attachment_image().
	echo $image;
}
