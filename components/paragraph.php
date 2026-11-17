<?php
/**
 * Render a paragraph component with optional classes and ID.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Component arguments for the paragraph template.
 *
 * @var array{
 *     text?: string,
 *     classes?: string|string[],
 *     id?: string
 * } $args
 */

$args ??= [];

$text = jmc_args_string(
	args: $args,
	key: 'text'
);

if ( '' === $text ) {
	return;
}


$attributes = [
	'classes' => [
		'jmc-paragraph',
		$args['classes'] ?? null,
	],
	'id'      => $args['id'] ?? null,
];
?>

<p<?php jmc_the_attributes( $attributes ); ?>>
	<?php echo esc_html( $text ); ?>
</p>