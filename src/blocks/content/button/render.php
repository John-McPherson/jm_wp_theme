<?php
/**
 * Render the button content block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the button block.
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
 *         opensInNewTab?: bool,
 *         target?: string,
 *         rel?: string
 *     },
 *     className?: string,
 *     anchor?: string
 * } $attributes
 * @var WP_Block $block
 */

$button = jmc_button_link_args( $attributes );

jmc_component(
	name: 'button-link',
	args: array_merge(
		$button,
		[
			'classes' => [
				$attributes['className'] ?? null,
				$button['classes'],
			],
			'id'      => $attributes['anchor'] ?? '',
		]
	)
);
