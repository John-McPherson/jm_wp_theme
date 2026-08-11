<?php
/**
 * Render the hero block layout with optional background image and palette.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the hero layout.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     palette: string,
 * } $attributes
 * @var string $content Rendered inner-block content.
 */


$palette = jmc_html_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'default',
		'secondary',
		'inverse',
	],
	fallback_value: 'default'
);

$palette_classes = [
	'default'   => null,
	'secondary' => 'jmc-palette--secondary',
	'inverse'   => 'jmc-palette--inverse',
];


$image_id = jmc_args_int(
	args: $attributes,
	key: 'imageId',
	fallback_value: 0,
	min: 1
);


$image_url = 0 < $image_id
	? wp_get_attachment_image_url( $image_id, 'full' )
	: false;

$section_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-section',
		'jmc-hero',
		$palette_classes[ $palette ] ?? null,
		$attributes['className'] ?? null,
	],
];

$image_attributes = [
	'classes'     => [
		'jmc-hero__img',
	],
	'aria-hidden' => 'true',
];

if ( is_string( $image_url ) && '' !== $image_url ) {
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

	<?php if ( is_string( $image_url ) && '' !== $image_url ) : ?>
		<div <?php jmc_the_attributes( $image_attributes ); ?>></div>
	<?php endif; ?>
</section>