<?php

declare(strict_types=1);

/** @var string $content Rendered inner-block content. */

?>
<div class="jmc-section__column">
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
	echo $content;
	?>
</div>