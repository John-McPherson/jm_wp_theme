<?php

declare(strict_types=1);

$args ??= [];

$text = jm_args_string(
    args: $args,
    key: 'text'
);

if ($text === '') {
    return;
}

$level = jm_args_int(
    args: $args,
    key: 'level',
    default: 2,
    min: 1,
    max: 6
);

$tag   = "h{$level}";

$attributes = [
    'classes' => [
        'jm-heading',
        $args['classes'] ?? null,
    ]
];

?>

<<?php echo $tag; ?><?php jm_the_attributes($attributes); ?>>
    <?php echo esc_html($text); ?>
</<?php echo $tag; ?>>