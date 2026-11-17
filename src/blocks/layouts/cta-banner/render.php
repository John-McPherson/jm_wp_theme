<?php
/**
 * Render the CTA banner block markup.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the CTA banner layout.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     palette?: string,
 *     order?: string,
 *     layout?: string
 * } $attributes
 * @var string $content Rendered inner-block content.
 */

// Headings, paragraphs and buttons omit themselves when empty, which can leave a
// `jmc/column` wrapper with nothing in it. Drop those so they don't hold a grid cell.
$content = (string) preg_replace(
	'#<div class="jmc-section__column">\s*</div>#',
	'',
	$content
);

if ( '' === trim( $content ) ) {
	return;
}

$palette = jmc_html_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'default',
		'secondary',
		'inverse',
	],
	fallback_value: 'inverse'
);

$col_order = jmc_html_allowed_value(
	value: $attributes['order'] ?? null,
	allowed: [
		'left',
		'right',
	],
	fallback_value: 'right'
);

$layout = jmc_html_allowed_value(
	value: $attributes['layout'] ?? null,
	allowed: [
		'columns',
		'stacked',
	],
	fallback_value: 'columns'
);

$palette_classes = [
	'default'   => '',
	'secondary' => 'jmc-palette--secondary',
	'inverse'   => 'jmc-palette--inverse',
];

$order_classes = [
	'left'  => 'jmc-column-left',
	'right' => 'jmc-column-right',
];


$custom_classes = isset( $attributes['className'] )
	? preg_split( '/\s+/', trim( $attributes['className'] ) )
	: [];

$section_attributes = [
	'classes' => array_filter(
		[
			'jmc-section',
			'jmc-cta',
			'stacked' === $layout ? 'jmc-cta--stacked' : null,
			$palette_classes[ $palette ],
			...$custom_classes,
		]
	),
];

$container_attributes = [
	'classes' => array_filter(
		[
			'jmc-section__container',
			// Column order only applies side by side.
			'columns' === $layout ? $order_classes[ $col_order ] : null,
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