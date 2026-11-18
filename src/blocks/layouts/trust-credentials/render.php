<?php
/**
 * Render the trust and credentials section.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the trust and credentials block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     palette?: string
 * } $attributes
 * @var string $content Rendered inner-block content.
 */

// A list with no credentials leaves nothing to show.
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
	fallback_value: 'default'
);

$palette_classes = [
	'default'   => null,
	'secondary' => 'jmc-palette--secondary',
	'inverse'   => 'jmc-palette--inverse',
];

$section_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-section',
		'jmc-trust-credentials',
		$palette_classes[ $palette ] ?? null,
		$attributes['className'] ?? null,
	],
];

?>
<section<?php jmc_the_attributes( $section_attributes ); ?>>
	<div class="jmc-section__container">
		<div class="jmc-section__column">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
			echo $content;
			?>
		</div>
	</div>
</section>
