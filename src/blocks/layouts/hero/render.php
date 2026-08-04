<?php

declare(strict_types=1);

/**
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     palette : string,
 * } $attributes
 * @var string $content
 */


$palette = jmc_html_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'primary',
		'secondary',
		'inverse',
	],
	default: 'primary'
);

$palette_classes = [
	'primary'   => 'jmc-palette--default',
	'secondary' => 'jmc-palette--secondary',
	'inverse'   => 'jmc-palette--inverse',
];


$image_id = jmc_args_int(
	args: $attributes,
	key: 'imageId',
	default: 0,
	min: 1
);


$image_url = $image_id > 0
	? wp_get_attachment_image_url( $image_id, 'full' )
	: false;

$section_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-section',
		'jmc-hero',
		$palette_classes[ $palette ],
		$attributes['className'] ?? null,
	],
];

$image_attributes = [
	'classes'     => [
		'jmc-hero__img',
	],
	'aria-hidden' => 'true',
];

if ( is_string( $image_url ) && $image_url !== '' ) {
	$image_attributes['style'] = [
		'--background-image' => sprintf(
			"url('%s')",
			esc_url( $image_url )
		),
	];
}

?>

<section <?php jmc_the_attributes( $section_attributes ); ?>>
	<div class="jmc-hero__text ">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
		echo $content;
		?>
	</div>

	<?php if ( is_string( $image_url ) && $image_url !== '' ) : ?>
		<div <?php jmc_the_attributes( $image_attributes ); ?>></div>
	<?php endif; ?>
</section>