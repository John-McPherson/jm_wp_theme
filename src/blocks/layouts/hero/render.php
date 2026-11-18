<?php
/**
 * Render the hero block layout with optional background image and palette.
 *
 * On child pages the hero shows the core Breadcrumbs trail in place of its
 * label, unless the editor has turned breadcrumbs off.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the hero layout.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     palette: string,
 *     showBreadcrumbs?: bool,
 * } $attributes
 * @var string   $content Rendered inner-block content.
 * @var WP_Block $block   Block instance, with the page in its context.
 */


$palette = jmc_html_allowed_value(
	value: $attributes['palette'] ?? null,
	allowed: [
		'default',
		'secondary',
		'inverse',
	],
	fallback_value: 'default'
);

$palette_classes = [
	'default'   => null,
	'secondary' => 'jmc-palette--secondary',
	'inverse'   => 'jmc-palette--inverse',
];


$image_id = jmc_args_int(
	args: $attributes,
	key: 'imageId',
	fallback_value: 0,
	min: 1
);


$image_url = 0 < $image_id
	? wp_get_attachment_image_url( $image_id, 'full' )
	: false;

$section_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-section',
		'jmc-hero',
		$palette_classes[ $palette ] ?? null,
		$attributes['className'] ?? null,
	],
];

$image_attributes = [
	'classes'     => [
		'jmc-hero__img',
	],
	'aria-hidden' => 'true',
];

if ( is_string( $image_url ) && '' !== $image_url ) {
	$image_attributes['style'] = [
		'--background-image' => sprintf(
			"url('%s')",
			esc_url( $image_url )
		),
	];
}

// Breadcrumbs are on unless explicitly turned off.
$show_breadcrumbs = false !== ( $attributes['showBreadcrumbs'] ?? true );

$page_id   = (int) ( $block->context['postId'] ?? 0 );
$page_type = $block->context['postType'] ?? 'page';

$breadcrumbs = '';

// Only child pages have a trail worth showing; core renders nothing on the
// front page or when there is no trail.
if ( $show_breadcrumbs && 0 < $page_id && 0 < (int) wp_get_post_parent_id( $page_id ) ) {
	$breadcrumbs = trim(
		( new WP_Block(
			[
				'blockName'    => 'core/breadcrumbs',
				// Home becomes an icon (inc/breadcrumbs.php).
				'attrs'        => [
					'className'    => 'jmc-hero__breadcrumbs',
					'showHomeItem' => false,
				],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			],
			[
				'postId'   => $page_id,
				'postType' => is_string( $page_type ) ? $page_type : 'page',
			]
		) )->render()
	);
}

$hero_content = $content;

// The trail takes the place of the hero label: render the inner blocks
// without the first label paragraph. The label stays saved for when
// breadcrumbs are off.
if ( '' !== $breadcrumbs ) {
	$hero_content  = '';
	$label_dropped = false;

	foreach ( $block->inner_blocks as $inner_block ) {
		if ( ! $label_dropped
			&& 'jmc/paragraph' === $inner_block->name
			&& 'label' === ( $inner_block->attributes['variant'] ?? null )
		) {
			$label_dropped = true;
			continue;
		}

		$hero_content .= $inner_block->render();
	}
}

?>

<section <?php jmc_the_attributes( $section_attributes ); ?>>
	<div class="jmc-hero__text ">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core Breadcrumbs block markup, escaped by core.
		echo $breadcrumbs;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
		echo $hero_content;
		?>
	</div>

	<?php if ( is_string( $image_url ) && '' !== $image_url ) : ?>
		<div <?php jmc_the_attributes( $image_attributes ); ?>></div>
	<?php endif; ?>
</section>