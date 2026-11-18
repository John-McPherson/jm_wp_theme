<?php
/**
 * Render the enquiry form block.
 *
 * The form works without JavaScript: it posts to `admin-post.php` and the
 * visitor is redirected back with `?jmc_enquiry_result=<status>#<notice id>`,
 * which this template shows as a notice. The view script submits it to the
 * REST API instead and updates the same notice and field errors in place.
 *
 * @package JMC_Theme
 */

declare(strict_types=1);

/**
 * Block attributes for the enquiry form block.
 *
 * @var array{
 *     anchor?: string,
 *     className?: string,
 *     palette?: string,
 *     submitText?: string
 * } $attributes
 * @var string $content Rendered inner-block content (the introduction).
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

$submit_text = jmc_plain_text( $attributes['submitText'] ?? '' );
$submit_text = '' === $submit_text ? __( 'Send enquiry', 'jmc-theme' ) : $submit_text;

$form_id   = sanitize_html_class( jmc_args_string( args: $attributes, key: 'anchor' ) );
$form_id   = '' === $form_id ? 'enquiry-form' : $form_id;
$notice_id = $form_id . '-notice';

$messages = jmc_enquiry_status_messages();

// Display-only result of the redirect after a submission without JavaScript.
$result = jmc_html_allowed_value(
	value: is_string( $_GET[ JMC_ENQUIRY_RESULT_ARG ] ?? null ) ? $_GET[ JMC_ENQUIRY_RESULT_ARG ] : null, // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Validated against the allowlist of statuses, which contain no slashes.
	allowed: array_keys( $messages ),
	fallback_value: ''
);

$services = jmc_enquiry_service_options();
$tokens   = jmc_enquiry_form_tokens();

$phone_url     = jmc_business_contact_url( 'phone' );
$email_url     = jmc_business_contact_url( 'email' );
$privacy_url   = (string) get_privacy_policy_url();
$business_name = jmc_plain_text( (string) get_bloginfo( 'name' ) );

/*
 * Fields in form order. `hint` is shown under the label; `optional` fields
 * are marked as such, since most are required.
 */
$fields = [
	'name'     => [
		'type'         => 'text',
		'autocomplete' => 'name',
	],
	'email'    => [
		'type'         => 'email',
		'autocomplete' => 'email',
		'hint'         => __( 'We’ll only use this to reply to your enquiry.', 'jmc-theme' ),
		'spellcheck'   => 'false',
	],
	'phone'    => [
		'type'         => 'tel',
		'autocomplete' => 'tel',
		'hint'         => __( 'In case it’s easier to call you.', 'jmc-theme' ),
	],
	'postcode' => [
		'type'           => 'text',
		'autocomplete'   => 'postal-code',
		'hint'           => __( 'So we can check we cover your area, for example LS1 4AP.', 'jmc-theme' ),
		'autocapitalize' => 'characters',
		'spellcheck'     => 'false',
		'pattern'        => '\s*[A-Za-z]{1,2}[0-9][A-Za-z0-9]?\s*[0-9][A-Za-z]{2}\s*',
		'class'          => 'jmc-enquiry-form__input--short',
	],
	'service'  => [
		'type' => 'select',
	],
	'message'  => [
		'type' => 'textarea',
		'hint' => __( 'Tell us about the work you need and when you’d like it done.', 'jmc-theme' ),
	],
];

if ( [] === $services ) {
	unset( $fields['service'] );
}

$definitions = jmc_enquiry_fields();

$section_attributes = [
	'id'      => $form_id,
	'classes' => [
		'jmc-section',
		'jmc-enquiry-form',
		$palette_classes[ $palette ] ?? null,
		$attributes['className'] ?? null,
	],
];

$form_attributes = [
	'classes'                         => 'jmc-enquiry-form__form',
	'method'                          => 'post',
	'action'                          => admin_url( 'admin-post.php' ),
	'data-jmc-enquiry-endpoint'       => rest_url( JMC_REST_NAMESPACE . '/enquiries' ),
	'data-jmc-enquiry-token-endpoint' => rest_url( JMC_REST_NAMESPACE . '/enquiries/token' ),
	'data-jmc-enquiry-messages'       => (string) wp_json_encode(
		$messages + [
			'network' => __( 'We couldn’t reach the server. Check your connection and try again, or contact us by phone or email.', 'jmc-theme' ),
			'summary' => __( 'There is a problem', 'jmc-theme' ),
			'sending' => __( 'Sending…', 'jmc-theme' ),
		]
	),
	'data-jmc-enquiry-errors'         => (string) wp_json_encode( jmc_enquiry_error_messages() ),
];

$notice_attributes = [
	'id'       => $notice_id,
	'classes'  => [
		'jmc-enquiry-form__notice',
		'' === $result ? null : ( 'sent' === $result ? 'jmc-enquiry-form__notice--success' : 'jmc-enquiry-form__notice--error' ),
	],
	'tabindex' => '-1',
	'hidden'   => '' === $result ? 'hidden' : null,
];

$alternative_attributes = [
	'classes' => 'jmc-enquiry-form__alternative',
	'hidden'  => in_array( $result, [ 'rate_limited', 'error' ], true ) ? null : 'hidden',
];

?>

<section <?php jmc_the_attributes( $section_attributes ); ?>>
	<div class="jmc-section__container">
		<?php if ( '' !== trim( $content ) ) : ?>
			<div class="jmc-section__column jmc-enquiry-form__intro">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered and filtered inner-block markup supplied by WordPress.
				echo $content;
				?>
			</div>
		<?php endif; ?>

		<div class="jmc-section__column jmc-enquiry-form__body">
			<div <?php jmc_the_attributes( $notice_attributes ); ?>>
				<p class="jmc-enquiry-form__notice-message"><?php echo esc_html( $messages[ $result ] ?? '' ); ?></p>
				<?php if ( '' !== $phone_url || '' !== $email_url ) : ?>
					<p <?php jmc_the_attributes( $alternative_attributes ); ?>>
						<?php if ( '' !== $phone_url ) : ?>
							<a class="jmc-enquiry-form__link" href="<?php echo esc_url( $phone_url ); ?>"><?php echo esc_html( jmc_sanitize_business_phone( get_option( JMC_BUSINESS_PHONE_OPTION, '' ) ) ); ?></a>
						<?php endif; ?>
						<?php if ( '' !== $email_url ) : ?>
							<a class="jmc-enquiry-form__link" href="<?php echo esc_url( $email_url ); ?>"><?php echo esc_html( jmc_sanitize_business_email( get_option( JMC_BUSINESS_EMAIL_OPTION, '' ) ) ); ?></a>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<ul class="jmc-enquiry-form__summary" hidden></ul>
			</div>

			<?php if ( 'sent' !== $result ) : ?>
				<form <?php jmc_the_attributes( $form_attributes ); ?>>
					<input type="hidden" name="action" value="<?php echo esc_attr( JMC_ENQUIRY_FORM_ACTION ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( JMC_ENQUIRY_NONCE_FIELD ); ?>" value="<?php echo esc_attr( $tokens['nonce'] ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( JMC_ENQUIRY_TIMING_FIELD ); ?>" value="<?php echo esc_attr( $tokens['started'] ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( JMC_ENQUIRY_RETURN_FIELD ); ?>" value="<?php echo esc_attr( $notice_id ); ?>" />

					<?php // Honeypot: hidden from people and assistive technology, left empty by them, filled by bots. ?>
					<div class="jmc-enquiry-form__trap" aria-hidden="true">
						<label for="<?php echo esc_attr( $form_id . '-website' ); ?>"><?php esc_html_e( 'Leave this field empty', 'jmc-theme' ); ?></label>
						<input type="text" id="<?php echo esc_attr( $form_id . '-website' ); ?>" name="<?php echo esc_attr( JMC_ENQUIRY_HONEYPOT_FIELD ); ?>" value="" tabindex="-1" autocomplete="off" />
					</div>

					<?php
					foreach ( $fields as $key => $field ) :
						$definition = $definitions[ $key ];
						$input_id   = $form_id . '-' . $key;
						$hint       = $field['hint'] ?? '';
						$described  = array_filter(
							[
								'' === $hint ? null : $input_id . '-hint',
								$input_id . '-error',
							]
						);

						$control_attributes = [
							'id'               => $input_id,
							'name'             => 'jmc_enquiry[' . $key . ']',
							'classes'          => [ 'jmc-enquiry-form__input', $field['class'] ?? null ],
							'autocomplete'     => $field['autocomplete'] ?? null,
							'autocapitalize'   => $field['autocapitalize'] ?? null,
							'spellcheck'       => $field['spellcheck'] ?? null,
							'pattern'          => $field['pattern'] ?? null,
							'maxlength'        => (string) $definition['max_length'],
							'required'         => $definition['required'] ? 'required' : null,
							'aria-describedby' => implode( ' ', $described ),
						];
						?>
						<div class="jmc-enquiry-form__field" data-jmc-enquiry-field="<?php echo esc_attr( $key ); ?>">
							<label class="jmc-enquiry-form__label" for="<?php echo esc_attr( $input_id ); ?>">
								<?php echo esc_html( $definition['label'] ); ?>
								<?php if ( ! $definition['required'] ) : ?>
									<span class="jmc-enquiry-form__optional"><?php esc_html_e( '(optional)', 'jmc-theme' ); ?></span>
								<?php endif; ?>
							</label>

							<?php if ( '' !== $hint ) : ?>
								<p class="jmc-enquiry-form__hint" id="<?php echo esc_attr( $input_id . '-hint' ); ?>"><?php echo esc_html( $hint ); ?></p>
							<?php endif; ?>

							<p class="jmc-enquiry-form__error" id="<?php echo esc_attr( $input_id . '-error' ); ?>" hidden></p>

							<?php if ( 'textarea' === $field['type'] ) : ?>
								<textarea <?php jmc_the_attributes( $control_attributes ); ?> rows="6"></textarea>
							<?php elseif ( 'select' === $field['type'] ) : ?>
								<select <?php jmc_the_attributes( $control_attributes ); ?>>
									<option value=""><?php esc_html_e( 'Not sure, or something else', 'jmc-theme' ); ?></option>
									<?php foreach ( $services as $service ) : ?>
										<option value="<?php echo esc_attr( $service ); ?>"><?php echo esc_html( $service ); ?></option>
									<?php endforeach; ?>
								</select>
							<?php else : ?>
								<input <?php jmc_the_attributes( $control_attributes ); ?> type="<?php echo esc_attr( $field['type'] ); ?>" />
							<?php endif; ?>
						</div>
					<?php endforeach; ?>

					<div class="jmc-enquiry-form__field jmc-enquiry-form__field--checkbox" data-jmc-enquiry-field="consent">
						<p class="jmc-enquiry-form__error" id="<?php echo esc_attr( $form_id . '-consent-error' ); ?>" hidden></p>
						<input type="checkbox" class="jmc-enquiry-form__checkbox" id="<?php echo esc_attr( $form_id . '-consent' ); ?>" name="jmc_enquiry[consent]" value="1" required aria-describedby="<?php echo esc_attr( $form_id . '-consent-error' ); ?>" />
						<label class="jmc-enquiry-form__label" for="<?php echo esc_attr( $form_id . '-consent' ); ?>">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: business name. */
									__( 'I agree to %s using these details to reply to my enquiry.', 'jmc-theme' ),
									'' === $business_name ? __( 'us', 'jmc-theme' ) : $business_name
								)
							);
							?>
						</label>
						<?php if ( '' !== $privacy_url ) : ?>
							<p class="jmc-enquiry-form__hint">
								<?php
								printf(
									/* translators: %s: link to the privacy policy. */
									esc_html__( 'Read how we use and store your details in our %s.', 'jmc-theme' ),
									sprintf(
										'<a class="jmc-enquiry-form__link" href="%s">%s</a>',
										esc_url( $privacy_url ),
										esc_html__( 'privacy policy', 'jmc-theme' )
									)
								);
								?>
							</p>
						<?php endif; ?>
					</div>

					<button type="submit" class="jmc-button jmc-enquiry-form__submit"><?php echo esc_html( $submit_text ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</section>
