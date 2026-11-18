<?php
/**
 * Render the credential list around its credential items.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the credential list block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string
 * } $attributes
 * @var string $content Rendered credential items.
 */

// Credentials with no image or label render nothing, so the list may be empty.
if ( '' === trim( $content ) ) {
	return;
}

$list_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-credential-list',
		$attributes['className'] ?? null,
	],
	// Restores list semantics that Safari drops when `list-style: none` is set.
	'role'    => 'list',
];

?>
<ul<?php jmc_the_attributes( $list_attributes ); ?>>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
	echo $content;
	?>
</ul>
