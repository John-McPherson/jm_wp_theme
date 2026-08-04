<?php
/**
 * Render a heading component with configurable level, classes, and ID.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Component arguments for the heading template.
 *
 * @var array{
 *     text?: string,
 *     level?: int|string,
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

$level = jmc_args_int(
	args: $args,
	key: 'level',
	default: 2,
	min: 1,
	max: 6
);

$heading_tag = "h{$level}";



$attributes = [
	'classes' => [
		'jmc-heading',
		$args['classes'] ?? null,
	],
	'id'      => $args['id'] ?? null,
];

// Tag is restricted to h1-h6 above.
?>
<<?php echo $heading_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php jmc_the_attributes( $attributes ); ?>>
	<?php echo esc_html( $text ); ?>
</<?php echo $heading_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>