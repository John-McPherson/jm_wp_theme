<?php
/**
 * Render the service list block from the published Services child pages.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the service list block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     headingLevel?: string
 * } $attributes
 */

$heading_level = jmc_html_allowed_value(
	value: $attributes['headingLevel'] ?? null,
	allowed: [
		'2',
		'3',
		'4',
		'5',
		'6',
	],
	fallback_value: '3'
);

$services_page = jmc_get_services_page();
$services      = jmc_get_services( $services_page['id'] );

if ( [] === $services ) {
	// Visitors see no list; editors previewing the block see why it is empty.
	if ( ! jmc_is_block_preview_request() ) {
		return;
	}

	$notices = [
		'invalid' => __( 'The Services page chosen under Settings → Reading is missing or unpublished. Choose a published page to list its services.', 'jmc-theme' ),
		'missing' => __( 'No Services page found. Choose one under Settings → Reading, or publish a page with the “services” slug.', 'jmc-theme' ),
	];

	$notice = $notices[ $services_page['status'] ]
		?? __( 'No services are published yet. Publish a page directly beneath the Services page, with an excerpt and icon, to list it here.', 'jmc-theme' );

	?>
	<p class="jmc-service-list__notice"><?php echo esc_html( $notice ); ?></p>
	<?php

	return;
}

$list_attributes = [
	'classes' => [
		'jmc-service-list',
		$attributes['className'] ?? null,
	],
	'id'      => $attributes['anchor'] ?? null,
	'role'    => 'list',
];

?>
<ul<?php jmc_the_attributes( $list_attributes ); ?>>
	<?php
	foreach ( $services as $service ) {
		jmc_component(
			name: 'service-card',
			args: [
				'title'         => $service['title'],
				'url'           => $service['url'],
				'summary'       => $service['summary'],
				'icon'          => $service['icon'],
				'heading_level' => (int) $heading_level,
			]
		);
	}
	?>
</ul>
