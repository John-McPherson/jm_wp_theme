<?php

declare(strict_types=1);

/**
 * @var array{
 *     text?: string,
 *     level?: int|string,
 *     classes?: string|string[],
 *     id?: string
 * } $args
 */

$args ??= [];

$text = jmcargs_string(
	args: $args,
	key: 'text'
);

if ($text === '') {
	return;
}

$level = jmcargs_int(
	args: $args,
	key: 'level',
	default: 2,
	min: 1,
	max: 6
);

$tag = "h{$level}";

$attributes = [
	'classes' => [
		'jm-heading',
		$args['classes'] ?? null,
	],
	'id'      => $args['id'] ?? null,
];

?>

<<?php echo $tag; ?><?php jmcthe_attributes($attributes); ?>>
	<?php echo esc_html($text); ?>
</<?php echo $tag; ?>>