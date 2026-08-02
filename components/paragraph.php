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

$text = jm_args_string(
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
    'id' => $args['id'] ?? null
];
?>

<p<?php jm_the_attributes($attributes); ?>>
    <?php echo esc_html($text); ?>
    </p>