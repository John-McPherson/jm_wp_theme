<?php

declare(strict_types=1);

/**
 * @var array{
 *     text?: string,
 *     link?: array{
 *         url?: string,
 *         target?: string,
 *         rel?: string
 *     },
 *     id?: string,
 *     classes?: string|string[]
 * } $args
 */


$text = jmc_args_string(
	args: $args,
	key: 'text'
);

$link = is_array( $args['link'] ?? null )
	? $args['link']
	: [];

$url = jmc_args_string(
	args: $link,
	key: 'url'
);

$target = jmc_args_string(
	args: $link,
	key: 'target'
);

if ( $text === '' || $url === '' ) {
	return;
}

$attributes = [
	'id'      => $args['id'] ?? null,
	'href'    => $url,
	'target'  => $target !== '' ? $target : null,
	'rel'     => $target === '_blank'
		? [ 'noopener', 'noreferrer' ]
		: null,
	'classes' => [
		'jmc-button',
		$args['classes'] ?? null,
	],
];
?>

<a<?php jmc_the_attributes( $attributes ); ?>>
	<?php echo esc_html( $text ); ?>

	<?php if ( $target === '_blank' ) : ?>
		<span class="screen-reader-text">
			<?php esc_html_e( '(opens in a new tab)', 'jm-theme' ); ?>
		</span>
	<?php endif; ?>
	</a>