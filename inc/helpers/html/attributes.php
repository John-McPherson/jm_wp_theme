<?php

declare(strict_types=1);

/**
 * Build a string of escaped HTML attributes.
 *
 * Currently supports:
 * - classes → class
 */
function jm_get_the_attributes(array $attributes): string
{
    $html = [];

    foreach ($attributes as $name => $value) {
        if ($value === null || $value === '' || $value === false) {
            continue;
        }

        switch ($name) {
            case 'classes':
                $name = 'class';
                $value = jm_html_classes($value);
                break;
        }

        $html[] = sprintf(
            '%s="%s"',
            esc_attr($name),
            esc_attr($value)
        );
    }

    return implode(' ', $html);
}

/**
 * Output escaped HTML attributes.
 */
function jm_the_attributes(array $attributes): void
{
    $html = jm_get_the_attributes($attributes);

    if ($html !== '') {
        echo ' ' . $html;
    }
}


function jm_html_classes(mixed $value): string
{
    if (is_array($value)) {
        return implode(
            ' ',
            array_filter(
                array_map(
                    jm_html_classes(...),
                    $value
                )
            )
        );
    }

    $classes = preg_split('/\s+/', trim((string) $value));

    if ($classes === false) {
        return '';
    }

    return implode(
        ' ',
        array_map('sanitize_html_class', $classes)
    );
}
