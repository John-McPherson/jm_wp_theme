<?php
/**
 * Render the service list block from a parent page's published children, or
 * from the pages chosen in the block.
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
 *     headingLevel?: string,
 *     moreText?: string,
 *     source?: string,
 *     parentId?: int|string,
 *     pageIds?: array<int, int|string>
 * } $attributes
 * @var WP_Block|null $block Block instance, carrying the postId and postType context.
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

// Empty falls back to the card's default cue.
$more_text = jmc_args_string(
	args: $attributes,
	key: 'moreText'
);

$source = jmc_html_allowed_value(
	value: $attributes['source'] ?? null,
	allowed: [
		'children',
		'chosen',
	],
	fallback_value: 'children'
);

$parent_id = jmc_args_int(
	args: $attributes,
	key: 'parentId',
	fallback_value: 0,
	min: 1
);

$context         = isset( $block ) && is_object( $block ) ? (array) ( $block->context ?? [] ) : [];
$current_page_id = 'page' === ( $context['postType'] ?? null ) && is_int( $context['postId'] ?? null )
	? $context['postId']
	: 0;

$parent = jmc_get_service_list_parent(
	parent_id: $parent_id,
	current_page_id: $current_page_id
);

// Children are listed unless pages were chosen; with no children, the chosen pages are.
$services = 'children' === $source ? jmc_get_services( $parent['id'] ) : [];

if ( [] === $services ) {
	$services = jmc_get_chosen_services( $attributes['pageIds'] ?? [] );
}

if ( [] === $services ) {
	// Visitors see no list; editors previewing the block see why it is empty.
	if ( ! jmc_is_block_preview_request() ) {
		return;
	}

	$notices = [
		'invalid-parent' => __( 'The parent page chosen for this list is missing or unpublished. Choose a published page, or clear it to list this page’s children.', 'jmc-theme' ),
		'invalid'        => __( 'The Services page chosen under Settings → Reading is missing or unpublished. Choose a published page to list its services.', 'jmc-theme' ),
		'missing'        => __( 'No Services page found. Choose one under Settings → Reading, or publish a page with the “services” slug.', 'jmc-theme' ),
	];

	$notice = $notices[ $parent['status'] ]
		?? __( 'No services to list. Publish pages beneath the parent page, with an excerpt and icon, or choose pages to list in the block settings.', 'jmc-theme' );

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
				'more_text'     => $more_text,
			]
		);
	}
	?>
</ul>
