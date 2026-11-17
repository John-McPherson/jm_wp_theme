<?php
/**
 * Render the buttons group block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Rendered inner-block content for the buttons group.
 *
 * @var string $content
 */

// Every button omits itself when its label or destination is missing or invalid,
// so skip the wrapper rather than leave an empty group behind.
if ( '' === trim( $content ) ) {
	return;
}

?>
<div class="jmc-buttons">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
	echo $content;
	?>
</div>
