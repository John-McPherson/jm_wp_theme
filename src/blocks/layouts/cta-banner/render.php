<?php

declare(strict_types=1);

/**
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     palette?: string,
 *     order?: string
 * } $attributes
 * @var string $content Rendered inner-block content.
 */

$palette = jmchtml_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'default',
		'secondary',
		'inverse',
	],
	default: 'inverse'
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
	'left'  => 'jm-column-left',
	'right' => 'jm-column-right',
];


$custom_classes = isset($attributes['className'])
	? preg_split('/\s+/', trim($attributes['className']))
	: [];

$section_attributes = [
	'classes' => array_filter(
		[
			'jm-section',
			'jm-cta',
			$palette_classes[$palette],
			...$custom_classes,
		]
	),
];

$container_attributes = [
	'classes' => array_filter(
		[
			'jm-section__container',

			$order_classes[$order],

		]
	),

];

if (! empty($attributes['anchor'])) {
	$section_attributes['id'] = sanitize_title(
		$attributes['anchor']
	);
}


?>

<section <?php jmcthe_attributes($section_attributes); ?>>
	<div <?php jmcthe_attributes($container_attributes); ?>>
		<?php echo $content; ?>

	</div>
</section>