<?php
/**
 * Service grid block render template.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Render the service grid block.
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Rendered inner-block markup.
 */
// * @var array{
// * anchor ?: string,
// * className ?: string,
// * palette ?: string,
// * } $attributes
// * @var string $content Rendered inner - block content .
// * /

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


$custom_classes = isset( $attributes['className'] )
	? preg_split( '/\s+/', trim( $attributes['className'] ) )
	: [];

$section_attributes = [
	'classes' => array_filter(
		[
			'jmc-section',
			'jmc-service-grid',
			$palette_classes[ $palette ],
			...$custom_classes,
		]
	),
];

$container_attributes = [
	'classes' => array_filter(
		[
			'jmc-section__container',
		]
	),

];

if ( ! empty( $attributes['anchor'] ) ) {
	$section_attributes['id'] = sanitize_title(
		$attributes['anchor']
	);
}


?>

<section <?php jmc_the_attributes( $section_attributes ); ?>>
	<div <?php jmc_the_attributes( $container_attributes ); ?>>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
		echo $content;
		?>
	</div>
</section>