<?php
/**
 * Render one list item, with its decorative icon when the list uses icons.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the list item block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     text?: string
 * } $attributes
 * @var WP_Block $block
 */

// Inline formatting from the editor (bold, italic, link) and nothing else.
$allowed_html = [
	'strong' => [],
	'em'     => [],
	'a'      => [
		'href'   => true,
		'target' => true,
		'rel'    => true,
	],
];

$text = jmc_args_string( args: $attributes, key: 'text' );

// An item with no visible text would be an empty bullet. Checked before
// filtering, which would otherwise keep the text of a stripped <script>.
if ( '' === wp_strip_all_tags( $text ) ) {
	return;
}

$text = wp_kses( $text, $allowed_html );

$list_style = jmc_html_allowed_value(
	value: $block->context['jmc/listStyle'] ?? null,
	allowed: [
		'icon',
		'bullet',
		'number',
	],
	fallback_value: 'icon'
);

$icon = jmc_html_allowed_value(
	value: $block->context['jmc/listIcon'] ?? null,
	allowed: [
		'check',
		'circle-check',
		'shield-check',
		'plus',
		'zap',
		'arrow-right',
	],
	fallback_value: 'check'
);

$item_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-list__item',
		$attributes['className'] ?? null,
	],
];

?>
<li<?php jmc_the_attributes( $item_attributes ); ?>>
	<?php
	if ( 'icon' === $list_style ) {
		// Decorative: wp_get_icon() marks the SVG aria-hidden, so only the text is announced.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registered SVG markup built by WP_HTML_Tag_Processor.
		echo wp_get_icon( "jmc/{$icon}", [ 'class' => 'jmc-list__icon' ] );
	}
	?>
	<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered through wp_kses() above. ?>
	<span class="jmc-list__text"><?php echo $text; ?></span>
</li>
