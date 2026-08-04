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

$palette = jmc_html_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'default',
		'secondary',
		'inverse',
	],
	default: 'default'
);

$order = jmc_html_allowed_value(
	value: $attributes['order'] ?? null,
	allowed: [
		'left',
		'right',
	],
	default: 'right'
);

$palette_classes = [
	'default'   => '',
	'secondary' => 'jmc-palette--secondary',
	'inverse'   => 'jmc-palette--inverse',
];

$order_classes = [
	'left'  => 'jmc-text-with-image--image-left',
	'right' => 'jmc-text-with-image--image-right',
];

$image_id = jmc_args_int(
	args: $attributes,
	key: 'imageId',
	default: 0,
	min: 1
);

$custom_classes = isset( $attributes['className'] )
	? preg_split( '/\s+/', trim( $attributes['className'] ) )
	: [];

$section_attributes = [
	'classes' => array_filter(
		[
			'jmc-section',
			'jmc-text-with-image',
			$palette_classes[ $palette ],
			$order_classes[ $order ],
			...$custom_classes,
		]
	),
];

if ( ! empty( $attributes['anchor'] ) ) {
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
			'class'   => 'jmc-text-with-image__media',
			'loading' => 'lazy',
		]
	)
	: '';

?>

<section <?php jmc_the_attributes( $section_attributes ); ?>>
	<div class="jmc-section__container">
		<div class="jmc-section__column">
			<?php echo $content; ?>
		</div>

		<div class="jmc-section__column">
			<?php if ( $image_html !== '' ) : ?>
				<div class="jmc-image jmc-text-with-image__image">
					<?php echo $image_html; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>