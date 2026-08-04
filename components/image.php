<?php

declare(strict_types=1);

/**
 * @var array{
 *     image_id?: int|string,
 *     classes?: string|string[],
 *     size?: string,
 *     loading?: string,
 *     sizes?: string
 * } $args
 */


$image_id = jmcargs_int(
	args: $args,
	key: 'image_id',
	default: 0,
	min: 1
);

if ($image_id === 0) {
	return;
}

$classes = jmchtml_classes(
	[
		'jm-image',
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

if ($image !== '') {
	echo $image;
}
