<?php
/**
 * Render a linked service card with an optional icon and summary.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Component arguments for the service card template.
 *
 * @var array{
 *     title?: string,
 *     url?: string,
 *     summary?: string,
 *     icon?: string,
 *     heading_level?: int,
 *     classes?: string|string[]
 * } $args
 */

$args ??= [];

$card_title = jmc_args_string(
	args: $args,
	key: 'title'
);

$url = jmc_args_string(
	args: $args,
	key: 'url'
);

// A card without a title or destination cannot form a meaningful link.
if ( '' === $card_title || '' === $url ) {
	return;
}

$summary = jmc_args_string(
	args: $args,
	key: 'summary'
);

$icon = jmc_args_string(
	args: $args,
	key: 'icon'
);

$heading_level = jmc_args_int(
	args: $args,
	key: 'heading_level',
	fallback_value: 3,
	min: 2
);

$heading_tag = 'h' . min( $heading_level, 6 );

// The core Icon block renders the registered SVG with aria-hidden and
// returns nothing for unregistered names.
$icon_markup = '' !== $icon
	? (string) render_block(
		[
			'blockName'    => 'core/icon',
			'attrs'        => [ 'icon' => $icon ],
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		]
	)
	: '';

// Decorative cue that the whole card is a link; the link text names the page.
$arrow_markup = (string) render_block(
	[
		'blockName'    => 'core/icon',
		'attrs'        => [ 'icon' => 'jmc/arrow-right' ],
		'innerBlocks'  => [],
		'innerHTML'    => '',
		'innerContent' => [],
	]
);

$attributes = [
	'classes' => [
		'jmc-service-card',
		$args['classes'] ?? null,
	],
];
?>
<li<?php jmc_the_attributes( $attributes ); ?>>
	<?php if ( '' !== $icon_markup ) : ?>
		<div class="jmc-service-card__icon" aria-hidden="true">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup rendered by the core Icon block from the icon registry.
			echo $icon_markup;
			?>
		</div>
	<?php endif; ?>
	<<?php echo $heading_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tag is restricted to h2-h6 above. ?> class="jmc-service-card__title">
		<a class="jmc-service-card__link" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $card_title ); ?></a>
	</<?php echo $heading_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Tag is restricted to h2-h6 above. ?>>
	<?php if ( '' !== $summary ) : ?>
		<p class="jmc-service-card__summary"><?php echo esc_html( $summary ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $arrow_markup ) : ?>
		<div class="jmc-service-card__arrow" aria-hidden="true">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup rendered by the core Icon block from the icon registry.
			echo $arrow_markup;
			?>
		</div>
	<?php endif; ?>
</li>
