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

$wrapper_attributes = [
	'classes' => [
		'jmc-footer-business',
		$attributes['className'] ?? null,
	],
];
?>
<div<?php jmc_the_attributes( $wrapper_attributes ); ?>>
	<?php if ( '' !== $company_name ) : ?>
		<p class="jmc-footer-business__name"><?php echo esc_html( $company_name ); ?></p>
	<?php endif; ?>

	<?php if ( $has_contact ) : ?>
		<address class="jmc-footer-business__contact">
			<?php if ( '' !== $address ) : ?>
				<p class="jmc-footer-business__address"><?php echo implode( '<br>', array_map( 'esc_html', explode( "\n", $address ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each line is escaped; only the joining <br> is markup. ?></p>
			<?php endif; ?>

			<?php if ( '' !== $phone ) : ?>
				<p class="jmc-footer-business__phone">
					<?php if ( '' !== $phone_url ) : ?>
						<a href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( $phone ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $phone ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $email ) : ?>
				<p class="jmc-footer-business__email">
					<a href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
				</p>
			<?php endif; ?>
		</address>
	<?php endif; ?>

	<?php if ( '' !== $service_area ) : ?>
		<p class="jmc-footer-business__service-area"><?php echo esc_html( $service_area ); ?></p>
	<?php endif; ?>
</div>
