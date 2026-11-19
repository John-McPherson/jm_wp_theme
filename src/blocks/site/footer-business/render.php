<?php
/**
 * Render the footer business details block.
 *
 * Every line comes from the site-wide business settings (see
 * inc/contact.php), so the footer matches every contact button and Contact
 * Details block.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the footer business details block.
 *
 * `companyName`, `address`, `phone`, `email` and `serviceArea` are legacy
 * attributes, kept only so the one-time upgrades can copy them into the
 * settings; they are not rendered.
 *
 * @var array{
 *     className?: string
 * } $attributes
 * @var WP_Block $block
 */

$company_name = jmc_sanitize_business_name( get_option( JMC_BUSINESS_NAME_OPTION, '' ) );
$address      = jmc_sanitize_business_address( get_option( JMC_BUSINESS_ADDRESS_OPTION, '' ) );

// Re-validated for display: a phone, WhatsApp or email value only comes with a working link.
$details      = jmc_business_contact_details();
$phone        = $details['phone'];
$whatsapp     = $details['whatsapp'];
$email        = $details['email'];
$service_area = $details['service_area'];

$has_contact = '' !== $address || '' !== $phone || '' !== $whatsapp || '' !== $email;

if ( '' === $company_name && ! $has_contact && [] === $service_area ) {
	return;
}

// Read the places as one phrase: "Pudsey, Leeds and Bradford".
$places_text = jmc_service_area_phrase( $service_area );

/*
 * Output a theme icon from the core icon registry (see inc/icons.php).
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
					<a href="<?php echo esc_url( $details['phone_url'] ); ?>"><?php echo esc_html( $phone ); ?></a>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $whatsapp ) : ?>
				<p class="jmc-footer-business__line jmc-footer-business__whatsapp">
					<?php $the_icon( 'jmc/whatsapp' ); ?>
					<?php // The number reads like a phone number, so screen readers are told it's WhatsApp. ?>
					<a href="<?php echo esc_url( $details['whatsapp_url'] ); ?>"><span class="jmc-footer-business__hidden-label"><?php esc_html_e( 'WhatsApp:', 'jmc-theme' ); ?> </span><?php echo esc_html( $whatsapp ); ?></a>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $email ) : ?>
				<p class="jmc-footer-business__line jmc-footer-business__email">
					<?php $the_icon( 'jmc/mail' ); ?>
					<a href="<?php echo esc_url( $details['email_url'] ); ?>"><?php echo esc_html( $email ); ?></a>
				</p>
			<?php endif; ?>
		</address>
	<?php endif; ?>

	<?php if ( '' !== $places_text ) : ?>
		<p class="jmc-footer-business__line jmc-footer-business__service-area">
			<?php $the_icon( 'jmc/house' ); ?>
			<span><span class="jmc-footer-business__label"><?php esc_html_e( 'Areas covered:', 'jmc-theme' ); ?></span> <?php echo esc_html( $places_text ); ?></span>
		</p>
	<?php endif; ?>
</div>
