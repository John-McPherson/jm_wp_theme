<?php
/**
 * Render an image component with support for optional sizing and lazy loading.
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
 *     loading?: string,
 *     sizes?: string
 * } $args
 */


$image_id = jmc_args_int(
	args: $args,
	key: 'image_id',
	default: 0,
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

$image = wp_get_attachment_image(
	attachment_id: $image_id,
	size: 'large',
	icon: false,
	attr: [
		'class'   => $classes,
		'loading' => 'lazy',
		'sizes'   => '(max-width: 768px) 100vw, 50vw',
	]
);

if ( '' !== $image ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated and escaped by wp_get_attachment_image().
	echo $image;
}
