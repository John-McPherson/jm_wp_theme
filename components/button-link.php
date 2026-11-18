<?php
/**
 * Render a button-style link component.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Component arguments for the button link template.
 *
 * @var array{
 *     text?: string,
 *     link?: array{
 *         url?: string,
 *         target?: string,
 *         rel?: string
 *     },
 *     id?: string,
 *     classes?: string|string[],
 *     icon?: string
 * } $args
 */


$text = jmc_args_string(
	args: $args,
	key: 'text'
);

$button_link = is_array( $args['link'] ?? null )
	? $args['link']
	: [];

$url = jmc_args_string(
	args: $button_link,
	key: 'url'
);

$target = jmc_args_string(
	args: $button_link,
	key: 'target'
);

// Optional registered icon name (e.g. `jmc/whatsapp`), shown before the text.
$icon = jmc_args_string(
	args: $args,
	key: 'icon'
);

if ( '' === $text || '' === $url ) {
	return;
}

$attributes = [
	'id'      => $args['id'] ?? null,
	'href'    => $url,
	'target'  => '' !== $target ? $target : null,
	'rel'     =>
	'_blank' === $target
		? [ 'noopener', 'noreferrer' ]
		: null,
	'classes' => [
		'jmc-button',
		$args['classes'] ?? null,
	],
];
?>

<a<?php jmc_the_attributes( $attributes ); ?>>
	<?php
	if ( '' !== $icon ) {
		// Decorative: wp_get_icon() marks it aria-hidden, and the text names the action.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registered SVG markup built by WP_HTML_Tag_Processor.
		echo wp_get_icon( $icon, [ 'class' => 'jmc-button__icon' ] );
	}
	?>
	<?php echo esc_html( $text ); ?>

	<?php
	if ( '_blank' === $target ) :
		?>
		<span class="screen-reader-text">
			<?php esc_html_e( '(opens in a new tab)', 'jmc-theme' ); ?>
		</span>
	<?php endif; ?>
	</a>