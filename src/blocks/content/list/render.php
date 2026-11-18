<?php
/**
 * Render a styled list around its list items.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the list block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     listStyle?: string,
 *     icon?: string
 * } $attributes
 * @var string $content Rendered list items.
 */

// Items with no text render nothing, so the list may be empty.
if ( '' === trim( $content ) ) {
	return;
}

$list_style = jmc_html_allowed_value(
	value: $attributes['listStyle'] ?? null,
	allowed: [
		'icon',
		'bullet',
		'number',
	],
	fallback_value: 'icon'
);

$list_tag = 'number' === $list_style ? 'ol' : 'ul';

$list_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-list',
		"jmc-list--{$list_style}",
		$attributes['className'] ?? null,
	],
	// Restores list semantics that Safari drops when `list-style: none` is set.
	'role'    => 'icon' === $list_style ? 'list' : null,
];

?>
<<?php echo tag_escape( $list_tag ); ?><?php jmc_the_attributes( $list_attributes ); ?>>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
	echo $content;
	?>
</<?php echo tag_escape( $list_tag ); ?>>
