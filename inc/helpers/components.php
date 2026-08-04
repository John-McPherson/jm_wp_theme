<?php

declare(strict_types=1);

function jmccomponent(string $name, array $args = []): void
{
	$slug = "components/{$name}";
	$path = locate_template("{$slug}.php", false, false);

	if ($path === '') {
		trigger_error(
			sprintf(
				'Component "%s" could not be found at "%s.php".',
				$name,
				$slug
			),
			E_USER_WARNING
		);

		return;
	}

	get_template_part($slug, null, $args);
}
