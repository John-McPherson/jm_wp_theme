<?php
/**
 * Render the contact details block.
 *
 * Values come from the site-wide business contact settings; the block only
 * chooses which of them to show.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the contact details block.
 *
 * @var array{
 *     showPhone?: bool,
 *     showWhatsApp?: bool,
 *     showEmail?: bool,
 *     showHours?: bool,
 *     showServiceArea?: bool,
 *     className?: string
 * } $attributes
 */

// Fields are shown unless explicitly switched off; malformed values fall back to shown.
$shows = static fn ( string $key ): bool => false !== ( $attributes[ $key ] ?? true );

$details = jmc_business_contact_details();

$phone        = $shows( 'showPhone' ) ? $details['phone'] : '';
$whatsapp     = $shows( 'showWhatsApp' ) ? $details['whatsapp'] : '';
$email        = $shows( 'showEmail' ) ? $details['email'] : '';
$hours        = $shows( 'showHours' ) ? $details['hours'] : [];
$service_area = $shows( 'showServiceArea' ) ? $details['service_area'] : [];

if ( '' === $phone && '' === $whatsapp && '' === $email && [] === $hours && [] === $service_area ) {
	return;
}

$day_labels = jmc_business_day_labels();

/*
 * Output a theme Lucide icon from the core icon registry (see inc/icons.php),
 * matching the footer business details. Icons are decorative, so wp_get_icon()
 * marks them aria-hidden: each label already says what it is. Labels are
 * visually hidden, so each value sits beside its icon as in the footer, but
 * screen readers still announce them.
 */
$the_icon = static function ( string $name ): void {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registered SVG markup built by WP_HTML_Tag_Processor.
	echo wp_get_icon( $name, [ 'class' => 'jmc-contact-details__icon' ] );
};

$wrapper_attributes = [
	'classes' => [
		'jmc-contact-details',
		$attributes['className'] ?? null,
	],
];
?>
<address<?php jmc_the_attributes( $wrapper_attributes ); ?>>
	<dl class="jmc-contact-details__list">
		<?php if ( '' !== $phone ) : ?>
			<div class="jmc-contact-details__item jmc-contact-details__item--phone">
				<dt class="jmc-contact-details__label"><?php $the_icon( 'jmc/phone' ); ?><span class="jmc-contact-details__label-text"><?php esc_html_e( 'Phone', 'jmc-theme' ); ?></span></dt>
				<dd class="jmc-contact-details__value"><a href="<?php echo esc_url( $details['phone_url'] ); ?>"><?php echo esc_html( $phone ); ?></a></dd>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $whatsapp ) : ?>
			<div class="jmc-contact-details__item jmc-contact-details__item--whatsapp">
				<dt class="jmc-contact-details__label"><?php $the_icon( 'jmc/whatsapp' ); ?><span class="jmc-contact-details__label-text"><?php esc_html_e( 'WhatsApp', 'jmc-theme' ); ?></span></dt>
				<dd class="jmc-contact-details__value"><a href="<?php echo esc_url( $details['whatsapp_url'] ); ?>"><?php echo esc_html( $whatsapp ); ?></a></dd>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $email ) : ?>
			<div class="jmc-contact-details__item jmc-contact-details__item--email">
				<dt class="jmc-contact-details__label"><?php $the_icon( 'jmc/mail' ); ?><span class="jmc-contact-details__label-text"><?php esc_html_e( 'Email', 'jmc-theme' ); ?></span></dt>
				<dd class="jmc-contact-details__value"><a href="<?php echo esc_url( $details['email_url'] ); ?>"><?php echo esc_html( $email ); ?></a></dd>
			</div>
		<?php endif; ?>

		<?php if ( [] !== $hours ) : ?>
			<div class="jmc-contact-details__item jmc-contact-details__item--hours">
				<dt class="jmc-contact-details__label"><?php $the_icon( 'jmc/clock' ); ?><span class="jmc-contact-details__label-text"><?php esc_html_e( 'Opening hours', 'jmc-theme' ); ?></span></dt>
				<dd class="jmc-contact-details__value">
					<ul class="jmc-contact-details__hours" role="list">
						<?php foreach ( $hours as $day => $times ) : ?>
							<li class="jmc-contact-details__day">
								<span class="jmc-contact-details__day-name"><?php echo esc_html( $day_labels[ $day ] ); ?></span>
								<span class="jmc-contact-details__times">
									<?php
									if ( null === $times ) {
										esc_html_e( 'Closed', 'jmc-theme' );
									} else {
										$time_tags = array_map(
											static fn ( string $time ): string => sprintf(
												'<time datetime="%s">%s</time>',
												esc_attr( $time ),
												esc_html( jmc_format_business_time( $time ) )
											),
											$times
										);

										printf(
											/* translators: 1: opening time, 2: closing time. */
											esc_html__( '%1$s to %2$s', 'jmc-theme' ),
											$time_tags['opens'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- <time> markup built from escaped values above.
											$time_tags['closes'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- <time> markup built from escaped values above.
										);
									}
									?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</dd>
			</div>
		<?php endif; ?>

		<?php if ( [] !== $service_area ) : ?>
			<div class="jmc-contact-details__item jmc-contact-details__item--service-area">
				<dt class="jmc-contact-details__label"><?php $the_icon( 'jmc/house' ); ?><span class="jmc-contact-details__label-text"><?php esc_html_e( 'Areas covered', 'jmc-theme' ); ?></span></dt>
				<dd class="jmc-contact-details__value">
					<ul class="jmc-contact-details__areas" role="list">
						<?php foreach ( $service_area as $place ) : ?>
							<li><?php echo esc_html( $place ); ?></li>
						<?php endforeach; ?>
					</ul>
				</dd>
			</div>
		<?php endif; ?>
	</dl>
</address>
