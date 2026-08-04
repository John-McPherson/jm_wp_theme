<?php

declare(strict_types=1);

/**
 * @var array{
 *     text?: string,
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


$attributes = [
	'classes' => [
		'jm-paragraph',
		$args['classes'] ?? null,
	],
	'id'      => $args['id'] ?? null,
];
?>

<p<?php jmcthe_attributes($attributes); ?>>
	<?php echo esc_html($text); ?>
	</p>