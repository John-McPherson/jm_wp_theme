<?php
/**
 * Render the header call-to-action block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the header call-to-action block.
 *
 * @var array{
 *     text?: string,
 *     buttonType?: string,
 *     linkType?: string,
 *     emailSubject?: string,
 *     emailBody?: string,
 *     whatsappMessage?: string,
 *     link?: array{
 *         url?: string,
 *         opensInNewTab?: bool
 *     },
 *     className?: string
 * } $attributes
 * @var WP_Block $block
 */

// Saved header CTAs without a link or button type render as primary `link` buttons.
$button = jmc_button_link_args( $attributes );

jmc_component(
	name: 'button-link',
	args: array_merge(
		$button,
		[
			'classes' => [
				'jmc-button--small',
				'jmc-header-cta',
				$button['classes'],
				$attributes['className'] ?? null,
			],
		]
	)
);
