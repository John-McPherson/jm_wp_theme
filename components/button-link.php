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
 *         opensInNewTab?: bool,
 *         target?: string
 *     },
 *     id?: string,
 *     classes?: string|string[]
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

if ( '' === $text || '' === $url ) {
	return;
}

// `opensInNewTab` is saved by the editor; `target` is kept for content saved before it.
// Telephone and email links hand off to the dialler or mail app, so never open them in a new tab.
$opens_in_new_tab = (
	true === ( $button_link['opensInNewTab'] ?? false )
	|| '_blank' === ( $button_link['target'] ?? null )
) && ! str_starts_with( $url, 'tel:' )
	&& ! str_starts_with( $url, 'mailto:' );

$attributes = [
	'id'      => $args['id'] ?? null,
	'href'    => $url,
	'target'  => $opens_in_new_tab ? '_blank' : null,
	'rel'     => $opens_in_new_tab ? [ 'noopener', 'noreferrer' ] : null,
	'classes' => [
		'jmc-button',
		$args['classes'] ?? null,
	],
];
?>

<a<?php jmc_the_attributes( $attributes ); ?>>
	<?php echo esc_html( $text ); ?>

	<?php
	if ( $opens_in_new_tab ) :
		?>
		<span class="screen-reader-text">
			<?php esc_html_e( '(opens in a new tab)', 'jmc-theme' ); ?>
		</span>
	<?php endif; ?>
	</a>