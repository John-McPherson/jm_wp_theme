<?php
/**
 * Render the footer container block with a selectable palette.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the footer container block.
 *
 * @var array{
 *     palette?: string,
 *     className?: string
 * } $attributes
 * @var string $content Rendered inner-block content.
 */

$content = is_string( $content ?? null )
	? $content
	: '';

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

$footer_attributes = [
	'classes' => [
		'jmc-footer-inner',
		$palette_classes[ $palette ] ?? null,
		$attributes['className'] ?? null,
	],
];
?>
<div<?php jmc_the_attributes( $footer_attributes ); ?>>
	<div class="jmc-footer-inner__container">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
		echo $content;
		?>
	</div>
</div>
