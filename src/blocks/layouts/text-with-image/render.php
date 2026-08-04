<?php

declare(strict_types=1);

/**
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     palette?: string,
 *     order?: string
 * } $attributes
 * @var string $content
 */

$palette = jmchtml_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'default',
		'secondary',
		'inverse',
	],
	default: 'default'
);

$order = jmchtml_allowed_value(
	value: $attributes['order'] ?? null,
	allowed: [
		'left',
		'right',
	],
	default: 'right'
);

$palette_classes = [
	'default'   => '',
	'secondary' => 'jm-palette--secondary',
	'inverse'   => 'jm-palette--inverse',
];

$order_classes = [
	'left'  => 'jm-text-with-image--image-left',
	'right' => 'jm-text-with-image--image-right',
];

$image_id = jmcargs_int(
	args: $attributes,
	key: 'imageId',
	default: 0,
	min: 1
);

$custom_classes = isset($attributes['className'])
	? preg_split('/\s+/', trim($attributes['className']))
	: [];

$section_attributes = [
	'classes' => array_filter(
		[
			'jm-section',
			'jm-text-with-image',
			$palette_classes[$palette],
			$order_classes[$order],
			...$custom_classes,
		]
	),
];

if (! empty($attributes['anchor'])) {
	$section_attributes['id'] = sanitize_title(
		$attributes['anchor']
	);
}

$image_html = $image_id > 0
	? wp_get_attachment_image(
		$image_id,
		'full',
		false,
		[
			'class'   => 'jm-text-with-image__media',
			'loading' => 'lazy',
		]
	)
	: '';

?>

<section <?php jmcthe_attributes($section_attributes); ?>>
	<div class="jm-section__container">
		<div class="jm-section__column">
			<?php echo $content; ?>
		</div>

		<div class="jm-section__column">
			<?php if ($image_html !== '') : ?>
				<div class="jm-image jm-text-with-image__image">
					<?php echo $image_html; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>