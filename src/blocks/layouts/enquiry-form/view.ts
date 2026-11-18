/**
 * Submit the enquiry form without reloading the page.
 *
 * Validates in the browser with the same rules and messages as the server,
 * then posts to the REST endpoint and shows the result in the form's notice:
 * a confirmation (with the form hidden), a focused error summary linking to
 * each invalid field, or a failure message offering phone and email. Without
 * JavaScript the form still posts to `admin-post.php`.
 */

type Status = 'sent' | 'invalid' | 'rate_limited' | 'expired' | 'error';

type Messages = Partial<
	Record< Status | 'network' | 'summary' | 'sending', string >
>;

type FieldErrors = Record< string, string >;

type SubmitResponse = {
	status?: string;
	errors?: FieldErrors;
};

type Options = {
	now?: () => number;
	wait?: ( ms: number ) => Promise< void >;
};

/**
 * Milliseconds to leave between fetching a timing token and submitting. The
 * server drops anything sent within 3 seconds as automated, so allow a margin.
 */
export const MIN_SUBMIT_DELAY = 3500;

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE = /^\+?\d{3,15}$/;
const POSTCODE = /^[A-Z]{1,2}[0-9][A-Z0-9]?[0-9][A-Z]{2}$/;

const parseJson = < T >( value: string | undefined, fallback: T ): T => {
	try {
		return value ? ( JSON.parse( value ) as T ) : fallback;
	} catch {
		return fallback;
	}
};

const text = ( data: FormData, key: string ): string => {
	const value = data.get( `jmc_enquiry[${ key }]` );

	return typeof value === 'string' ? value.trim() : '';
};

/**
 * Check a submission against the server's rules, in form order.
 *
 * @param data     Form data.
 * @param messages Error messages keyed as `jmc_enquiry_error_messages()`.
 * @return Field ID => message for each invalid field.
 */
export function validateEnquiry(
	data: FormData,
	messages: FieldErrors
): FieldErrors {
	const errors: FieldErrors = {};

	if ( ! text( data, 'name' ) ) {
		errors.name = messages.name;
	}

	const email = text( data, 'email' );

	if ( ! email ) {
		errors.email = messages.email;
	} else if ( ! EMAIL.test( email ) ) {
		errors.email = messages.email_format;
	}

	const phone = text( data, 'phone' );

	if ( phone && ! PHONE.test( phone.replace( /[\s\-.()]/g, '' ) ) ) {
		errors.phone = messages.phone;
	}

	const postcode = text( data, 'postcode' );

	if (
		postcode &&
		! POSTCODE.test( postcode.replace( /\s+/g, '' ).toUpperCase() )
	) {
		errors.postcode = messages.postcode;
	}

	if ( ! text( data, 'message' ) ) {
		errors.message = messages.message;
	}

	if ( text( data, 'consent' ) !== '1' ) {
		errors.consent = messages.consent;
	}

	return errors;
}

export class EnquiryForm {
	private readonly form: HTMLFormElement;
	private readonly notice: HTMLElement | null;
	private readonly submitButton: HTMLButtonElement | null;
	private readonly messages: Messages;
	private readonly errorMessages: FieldErrors;
	private readonly now: () => number;
	private readonly wait: ( ms: number ) => Promise< void >;

	private sending = false;
	private tokenFetchedAt = 0;
	private submitText = '';

	constructor( form: HTMLFormElement, options: Options = {} ) {
		this.form = form;
		this.now = options.now ?? Date.now;
		this.wait =
			options.wait ??
			( ( ms ) =>
				new Promise( ( resolve ) => setTimeout( resolve, ms ) ) );

		const noticeId = this.field( 'jmc_enquiry_return' )?.value ?? '';

		this.notice = noticeId ? document.getElementById( noticeId ) : null;
		this.submitButton = form.querySelector< HTMLButtonElement >(
			'button[type="submit"]'
		);
		this.messages = parseJson< Messages >(
			form.dataset.jmcEnquiryMessages,
			{}
		);
		this.errorMessages = parseJson< FieldErrors >(
			form.dataset.jmcEnquiryErrors,
			{}
		);
	}

	init(): void {
		if ( ! this.notice || ! this.form.dataset.jmcEnquiryEndpoint ) {
			return;
		}

		// The script takes over validation so errors match the server's.
		this.form.noValidate = true;
		this.form.addEventListener( 'submit', this.handleSubmit );
		this.notice
			.querySelector( '.jmc-enquiry-form__summary' )
			?.addEventListener( 'click', this.handleSummaryClick );

		this.tokenFetchedAt = this.now();
		void this.refreshToken();
	}

	/**
	 * Replace the page's nonce and timing token with fresh ones, so a form on
	 * a cached page can still be submitted.
	 */
	async refreshToken(): Promise< boolean > {
		const endpoint = this.form.dataset.jmcEnquiryTokenEndpoint;

		if ( ! endpoint ) {
			return false;
		}

		try {
			const response = await fetch( endpoint, {
				credentials: 'omit',
				headers: { Accept: 'application/json' },
			} );

			if ( ! response.ok ) {
				return false;
			}

			const token = ( await response.json() ) as {
				nonce?: string;
				started?: string;
			};

			const nonce = this.field( 'jmc_enquiry_nonce' );
			const started = this.field( 'jmc_enquiry_started' );

			if ( ! token.nonce || ! token.started || ! nonce || ! started ) {
				return false;
			}

			nonce.value = token.nonce;
			started.value = token.started;
			this.tokenFetchedAt = this.now();

			return true;
		} catch {
			return false;
		}
	}

	private handleSubmit = async ( event: Event ): Promise< void > => {
		event.preventDefault();

		if ( this.sending ) {
			return;
		}

		this.clearErrors();

		const data = new FormData( this.form );
		const errors = validateEnquiry( data, this.errorMessages );

		if ( Object.keys( errors ).length > 0 ) {
			this.showErrors( errors );

			return;
		}

		this.setSending( true );

		try {
			const remaining =
				MIN_SUBMIT_DELAY - ( this.now() - this.tokenFetchedAt );

			if ( remaining > 0 ) {
				await this.wait( remaining );
			}

			const response = await fetch(
				this.form.dataset.jmcEnquiryEndpoint as string,
				{
					method: 'POST',
					body: data,
					credentials: 'omit',
					headers: { Accept: 'application/json' },
				}
			);

			const body = ( await response
				.json()
				.catch( () => ( {} ) ) ) as SubmitResponse;

			await this.handleResponse( body );
		} catch {
			this.showNotice( 'error', this.messages.network, true );
		} finally {
			this.setSending( false );
		}
	};

	private async handleResponse( body: SubmitResponse ): Promise< void > {
		switch ( body.status ) {
			case 'sent':
				this.form.hidden = true;
				this.showNotice( 'success', this.messages.sent );
				// No personal data: lets analytics count conversions (#83).
				document.dispatchEvent( new CustomEvent( 'jmc:enquiry-sent' ) );
				break;

			case 'invalid':
				if ( body.errors && Object.keys( body.errors ).length > 0 ) {
					this.showErrors( body.errors );
				} else {
					this.showNotice( 'error', this.messages.invalid );
				}
				break;

			case 'expired':
				await this.refreshToken();
				this.showNotice( 'error', this.messages.expired );
				break;

			case 'rate_limited':
			case 'error':
				this.showNotice( 'error', this.messages[ body.status ], true );
				break;

			default:
				this.showNotice( 'error', this.messages.network, true );
		}
	}

	private showErrors( errors: FieldErrors ): void {
		const summary: HTMLLIElement[] = [];

		Object.entries( errors ).forEach( ( [ key, message ] ) => {
			const field = this.form.querySelector< HTMLElement >(
				`[data-jmc-enquiry-field="${ key }"]`
			);
			const error = field?.querySelector< HTMLElement >(
				'.jmc-enquiry-form__error'
			);
			const control = field?.querySelector< HTMLElement >(
				'input:not([type="hidden"]), select, textarea'
			);

			if ( error ) {
				error.textContent = message;
				error.hidden = false;
			}

			control?.setAttribute( 'aria-invalid', 'true' );

			const item = document.createElement( 'li' );

			if ( control?.id ) {
				const link = document.createElement( 'a' );

				link.className = 'jmc-enquiry-form__link';
				link.href = `#${ control.id }`;
				link.textContent = message;
				item.append( link );
			} else {
				item.textContent = message;
			}

			summary.push( item );
		} );

		this.showNotice( 'error', this.messages.summary, false, summary );
	}

	private clearErrors(): void {
		this.form
			.querySelectorAll< HTMLElement >( '.jmc-enquiry-form__error' )
			.forEach( ( error ) => {
				error.textContent = '';
				error.hidden = true;
			} );

		this.form
			.querySelectorAll( '[aria-invalid]' )
			.forEach( ( control ) =>
				control.removeAttribute( 'aria-invalid' )
			);

		if ( this.notice ) {
			this.notice.hidden = true;
		}
	}

	/**
	 * Show a message in the notice and move focus to it, so keyboard and
	 * screen reader users hear it straight away.
	 *
	 * @param type            Success or error styling.
	 * @param message         Message text.
	 * @param showAlternative Whether to offer the phone number and email.
	 * @param summary         Error summary items.
	 */
	private showNotice(
		type: 'success' | 'error',
		message = '',
		showAlternative = false,
		summary: HTMLLIElement[] = []
	): void {
		const notice = this.notice;

		if ( ! notice ) {
			return;
		}

		notice.classList.toggle(
			'jmc-enquiry-form__notice--success',
			type === 'success'
		);
		notice.classList.toggle(
			'jmc-enquiry-form__notice--error',
			type === 'error'
		);

		const messageElement = notice.querySelector(
			'.jmc-enquiry-form__notice-message'
		);
		const alternative = notice.querySelector< HTMLElement >(
			'.jmc-enquiry-form__alternative'
		);
		const list = notice.querySelector< HTMLElement >(
			'.jmc-enquiry-form__summary'
		);

		if ( messageElement ) {
			messageElement.textContent = message;
		}

		if ( alternative ) {
			alternative.hidden = ! showAlternative;
		}

		if ( list ) {
			list.replaceChildren( ...summary );
			list.hidden = summary.length === 0;
		}

		notice.hidden = false;
		notice.focus();
	}

	private handleSummaryClick = ( event: Event ): void => {
		const link = ( event.target as Element | null )?.closest( 'a' );
		const id = link?.getAttribute( 'href' )?.slice( 1 );
		const control = id ? document.getElementById( id ) : null;

		if ( ! control ) {
			return;
		}

		event.preventDefault();
		control.focus();
	};

	private setSending( sending: boolean ): void {
		this.sending = sending;
		this.form.setAttribute( 'aria-busy', String( sending ) );

		const button = this.submitButton;

		if ( ! button ) {
			return;
		}

		if ( sending ) {
			this.submitText = button.textContent ?? '';
			button.setAttribute( 'aria-disabled', 'true' );
			button.textContent = this.messages.sending ?? this.submitText;
		} else {
			button.removeAttribute( 'aria-disabled' );
			button.textContent = this.submitText || button.textContent;
		}
	}

	private field( name: string ): HTMLInputElement | null {
		return this.form.querySelector< HTMLInputElement >(
			`input[name="${ name }"]`
		);
	}
}

document
	.querySelectorAll< HTMLFormElement >( 'form[data-jmc-enquiry-endpoint]' )
	.forEach( ( form ) => new EnquiryForm( form ).init() );
