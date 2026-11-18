<?php
/**
 * Render one credential: an accreditation, guarantee or review logo with an optional link.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the credential block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     imageId?: int|string,
 *     link?: array{
 *         url?: string,
 *         opensInNewTab?: bool
 *     }
 * } $attributes
 */

$image_id = jmc_args_int(
	args: $attributes,
	key: 'imageId',
	fallback_value: 0,
	min: 1
);

// No logo (or one deleted from the Media Library) leaves nothing to show.
if ( 0 === $image_id || ! wp_attachment_is_image( $image_id ) ) {
	return;
}

// The logo is the credential, so it is informative and names the link.
// Without Media Library alternative text, fall back to the image's title.
$image_alt = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );

if ( '' === $image_alt ) {
	$image_alt = trim( get_the_title( $image_id ) );
}

$credential_link = is_array( $attributes['link'] ?? null )
	? $attributes['link']
	: [];

$url = jmc_safe_link_url( $credential_link['url'] ?? null );

// Telephone links hand off to the dialler, so never open them in a new tab.
$opens_in_new_tab = '' !== $url
	&& true === ( $credential_link['opensInNewTab'] ?? false )
	&& ! str_starts_with( $url, 'tel:' );

$item_attributes = [
	'id'      => $attributes['anchor'] ?? null,
	'classes' => [
		'jmc-credential',
		$attributes['className'] ?? null,
	],
];

$link_attributes = [
	'classes' => 'jmc-credential__link',
	'href'    => $url,
	'target'  => $opens_in_new_tab ? '_blank' : null,
	'rel'     => $opens_in_new_tab ? [ 'noopener', 'noreferrer' ] : null,
];

$image_args = [
	'image_id' => $image_id,
	'classes'  => 'jmc-credential__image',
	'size'     => 'medium',
	'sizes'    => '10rem',
	'alt'      => $image_alt,
];

?>
<li<?php jmc_the_attributes( $item_attributes ); ?>>
	<?php if ( '' !== $url ) : ?>
		<a<?php jmc_the_attributes( $link_attributes ); ?>>
			<?php jmc_component( name: 'image', args: $image_args ); ?>
			<?php if ( $opens_in_new_tab ) : ?>
				<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'jmc-theme' ); ?></span>
			<?php endif; ?>
		</a>
	<?php else : ?>
		<?php jmc_component( name: 'image', args: $image_args ); ?>
	<?php endif; ?>
</li>
