<?php
/**
 * Render the column block wrapper with its inner content.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Rendered inner-block content for the column.
 *
 * @var string $content
 */

?>
<div class="jmc-section__column">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
	echo $content;
	?>
</div>