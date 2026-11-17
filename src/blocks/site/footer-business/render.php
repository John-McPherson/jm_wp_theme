<?php
/**
 * Render the footer business details block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the footer business details block.
 *
 * @var array{
 *     companyName?: string,
 *     address?: string,
 *     phone?: string,
 *     email?: string,
 *     serviceArea?: string,
 *     className?: string
 * } $attributes
 * @var WP_Block $block
 */

$company_name = jmc_plain_text( $attributes['companyName'] ?? null );
$address      = jmc_plain_text( $attributes['address'] ?? null );
$phone        = jmc_plain_text( $attributes['phone'] ?? null );
$email        = jmc_plain_text( $attributes['email'] ?? null );
$service_area = jmc_plain_text( $attributes['serviceArea'] ?? null );

// A number the dialler can't use is still shown, just not linked.
$phone_url = '' === $phone
	? ''
	: jmc_safe_link_url( 'tel:' . $phone );

// An invalid email address is dropped rather than shown as broken contact info.
if ( false === filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
	$email = '';
}

$has_contact = '' !== $address || '' !== $phone || '' !== $email;

if ( '' === $company_name && ! $has_contact && '' === $service_area ) {
	return;
}

/*
 * Output a theme Lucide icon from the core icon registry (see inc/icons.php).
 * Icons are decorative, so wp_get_icon() marks them aria-hidden: each line's text
 * already says what it is.
 */
$the_icon = static function ( string $name ): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registered SVG markup built by WP_HTML_Tag_Processor.
	echo wp_get_icon( $name, [ 'class' => 'jmc-footer-business__icon' ] );
};

$wrapper_attributes = [
	'classes' => [
		'jmc-footer-business',
		$attributes['className'] ?? null,
	],
];
?>
<div<?php jmc_the_attributes( $wrapper_attributes ); ?>>
	<?php if ( '' !== $company_name ) : ?>
		<p class="jmc-footer-business__name"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $company_name ); ?></a></p>
	<?php endif; ?>

	<?php if ( $has_contact ) : ?>
		<address class="jmc-footer-business__contact">
			<?php if ( '' !== $address ) : ?>
				<p class="jmc-footer-business__line jmc-footer-business__address">
					<?php $the_icon( 'jmc/map-pin' ); ?>
					<span><?php echo implode( '<br>', array_map( 'esc_html', explode( "\n", $address ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each line is escaped; only the joining <br> is markup. ?></span>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $phone ) : ?>
				<p class="jmc-footer-business__line jmc-footer-business__phone">
					<?php $the_icon( 'jmc/phone' ); ?>
					<?php if ( '' !== $phone_url ) : ?>
						<a href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( $phone ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $phone ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $email ) : ?>
				<p class="jmc-footer-business__line jmc-footer-business__email">
					<?php $the_icon( 'jmc/mail' ); ?>
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
				</p>
			<?php endif; ?>
		</address>
	<?php endif; ?>

	<?php if ( '' !== $service_area ) : ?>
		<p class="jmc-footer-business__line jmc-footer-business__service-area">
			<?php $the_icon( 'jmc/house' ); ?>
			<span><span class="jmc-footer-business__label"><?php esc_html_e( 'Areas covered:', 'jmc-theme' ); ?></span> <?php echo esc_html( $service_area ); ?></span>
		</p>
	<?php endif; ?>
</div>
