import { EnquiryForm, MIN_SUBMIT_DELAY, validateEnquiry } from './view';

const ERRORS = {
	name: 'Enter your name.',
	email: 'Enter your email address.',
	email_format: 'Enter an email address in the correct format.',
	phone: 'Enter a valid phone number.',
	postcode: 'Enter a full UK postcode.',
	service: 'Choose a service from the list.',
	message: 'Enter a message.',
	consent: 'Confirm you agree.',
};

const MESSAGES = {
	sent: 'Thank you.',
	invalid: 'There’s a problem.',
	rate_limited: 'Too many enquiries.',
	expired: 'This form has expired.',
	error: 'Sorry, we couldn’t send it.',
	network: 'We couldn’t reach the server.',
	summary: 'There is a problem',
	sending: 'Sending…',
};

const field = ( key: string, control: string ): string => `
	<div class="jmc-enquiry-form__field" data-jmc-enquiry-field="${ key }">
		<label for="enquiry-form-${ key }">${ key }</label>
		<p class="jmc-enquiry-form__error" id="enquiry-form-${ key }-error" hidden></p>
		${ control }
	</div>
`;

const renderForm = (): void => {
	document.body.innerHTML = `
		<section id="enquiry-form">
			<div id="enquiry-form-notice" class="jmc-enquiry-form__notice" tabindex="-1" hidden>
				<p class="jmc-enquiry-form__notice-message"></p>
				<p class="jmc-enquiry-form__alternative" hidden><a href="tel:0123">0123</a></p>
				<ul class="jmc-enquiry-form__summary" hidden></ul>
			</div>
			<form
				method="post"
				action="/wp-admin/admin-post.php"
				data-jmc-enquiry-endpoint="/wp-json/jmc/v1/enquiries"
				data-jmc-enquiry-token-endpoint="/wp-json/jmc/v1/enquiries/token"
				data-jmc-enquiry-messages='${ JSON.stringify( MESSAGES ) }'
				data-jmc-enquiry-errors='${ JSON.stringify( ERRORS ) }'
			>
				<input type="hidden" name="jmc_enquiry_nonce" value="page-nonce" />
				<input type="hidden" name="jmc_enquiry_started" value="page-started" />
				<input type="hidden" name="jmc_enquiry_return" value="enquiry-form-notice" />
				${ field(
					'name',
					'<input id="enquiry-form-name" name="jmc_enquiry[name]" required />'
				) }
				${ field(
					'email',
					'<input id="enquiry-form-email" name="jmc_enquiry[email]" type="email" required />'
				) }
				${ field(
					'phone',
					'<input id="enquiry-form-phone" name="jmc_enquiry[phone]" type="tel" />'
				) }
				${ field(
					'postcode',
					'<input id="enquiry-form-postcode" name="jmc_enquiry[postcode]" />'
				) }
				${ field(
					'message',
					'<textarea id="enquiry-form-message" name="jmc_enquiry[message]" required></textarea>'
				) }
				${ field(
					'consent',
					'<input id="enquiry-form-consent" name="jmc_enquiry[consent]" type="checkbox" value="1" required />'
				) }
				<button type="submit">Send enquiry</button>
			</form>
		</section>
	`;
};

const form = (): HTMLFormElement =>
	document.querySelector( 'form' ) as HTMLFormElement;

const notice = (): HTMLElement =>
	document.getElementById( 'enquiry-form-notice' ) as HTMLElement;

const input = ( name: string ): HTMLInputElement =>
	document.querySelector(
		`[name="${ name }"]`
	) as unknown as HTMLInputElement;

const fillValid = (): void => {
	input( 'jmc_enquiry[name]' ).value = 'Alex Smith';
	input( 'jmc_enquiry[email]' ).value = 'alex@example.com';
	input( 'jmc_enquiry[message]' ).value = 'Please quote for a rewire.';
	input( 'jmc_enquiry[consent]' ).checked = true;
};

const jsonResponse = ( body: unknown, status = 200 ) =>
	( {
		ok: status >= 200 && status < 300,
		status,
		json: () => Promise.resolve( body ),
	} ) as Response;

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

const submit = async (): Promise< void > => {
	form().dispatchEvent(
		new Event( 'submit', { bubbles: true, cancelable: true } )
	);
	await flush();
	await flush();
};

/**
 * Initialise the form with a fetch mock: the first call answers the token
 * request, the rest answer submissions in order.
 *
 * @param submissions  Responses (or errors) for each submission.
 * @param options      Clock and wait overrides.
 * @param options.now
 * @param options.wait
 */
const setup = async (
	submissions: Array< Response | Error > = [],
	options: {
		now?: () => number;
		wait?: ( ms: number ) => Promise< void >;
	} = {}
) => {
	const fetchMock = jest
		.fn()
		.mockResolvedValueOnce(
			jsonResponse( { nonce: 'fresh-nonce', started: 'fresh-started' } )
		);

	submissions.forEach( ( response ) => {
		if ( response instanceof Error ) {
			fetchMock.mockRejectedValueOnce( response );
		} else {
			fetchMock.mockResolvedValueOnce( response );
		}
	} );

	global.fetch = fetchMock as unknown as typeof fetch;

	const wait = options.wait ?? jest.fn( () => Promise.resolve() );

	new EnquiryForm( form(), { now: options.now, wait } ).init();
	await flush();

	return { fetchMock, wait };
};

describe( 'validateEnquiry', () => {
	const data = ( values: Record< string, string > ): FormData => {
		const formData = new FormData();

		Object.entries( values ).forEach( ( [ key, value ] ) =>
			formData.append( `jmc_enquiry[${ key }]`, value )
		);

		return formData;
	};

	const valid = {
		name: 'Alex',
		email: 'alex@example.com',
		message: 'Hello',
		consent: '1',
	};

	it( 'accepts a valid submission with optional fields blank', () => {
		expect( validateEnquiry( data( valid ), ERRORS ) ).toEqual( {} );
	} );

	it( 'reports every required field, in form order', () => {
		expect( Object.keys( validateEnquiry( data( {} ), ERRORS ) ) ).toEqual(
			[ 'name', 'email', 'message', 'consent' ]
		);
	} );

	it( 'treats whitespace-only values as empty', () => {
		expect(
			validateEnquiry( data( { ...valid, name: '   ' } ), ERRORS )
		).toEqual( { name: ERRORS.name } );
	} );

	it.each( [
		[ 'email', 'alex@', ERRORS.email_format ],
		[ 'phone', '0800 ELECTRIC', ERRORS.phone ],
		[ 'postcode', 'LS1', ERRORS.postcode ],
		[ 'postcode', '90210', ERRORS.postcode ],
	] )( 'rejects an invalid %s (%s)', ( key, value, message ) => {
		expect(
			validateEnquiry( data( { ...valid, [ key ]: value } ), ERRORS )
		).toEqual( { [ key ]: message } );
	} );

	it.each( [
		[ 'phone', '+44 (0)113 496-0000' ],
		[ 'postcode', 'ls1 4ap' ],
		[ 'postcode', 'LS14AP' ],
	] )( 'accepts a valid %s (%s)', ( key, value ) => {
		expect(
			validateEnquiry( data( { ...valid, [ key ]: value } ), ERRORS )
		).toEqual( {} );
	} );
} );

describe( 'EnquiryForm', () => {
	beforeEach( () => {
		renderForm();
	} );

	afterEach( () => {
		document.body.innerHTML = '';
		jest.restoreAllMocks();
	} );

	it( 'takes over validation and refreshes the tokens on load', async () => {
		const { fetchMock } = await setup();

		expect( form().noValidate ).toBe( true );
		expect( fetchMock ).toHaveBeenCalledWith(
			'/wp-json/jmc/v1/enquiries/token',
			expect.objectContaining( { credentials: 'omit' } )
		);
		expect( input( 'jmc_enquiry_nonce' ).value ).toBe( 'fresh-nonce' );
		expect( input( 'jmc_enquiry_started' ).value ).toBe( 'fresh-started' );
	} );

	it( 'keeps the page tokens when the refresh fails', async () => {
		global.fetch = jest
			.fn()
			.mockRejectedValue(
				new Error( 'offline' )
			) as unknown as typeof fetch;

		new EnquiryForm( form() ).init();
		await flush();

		expect( input( 'jmc_enquiry_nonce' ).value ).toBe( 'page-nonce' );
	} );

	it( 'does nothing when the markup is incomplete', () => {
		document.getElementById( 'enquiry-form-notice' )?.remove();
		global.fetch = jest.fn() as unknown as typeof fetch;

		new EnquiryForm( form() ).init();

		expect( form().noValidate ).toBe( false );
		expect( global.fetch ).not.toHaveBeenCalled();
	} );

	it( 'shows inline errors and a focused summary without submitting', async () => {
		const { fetchMock } = await setup();

		input( 'jmc_enquiry[email]' ).value = 'alex@';
		await submit();

		expect( fetchMock ).toHaveBeenCalledTimes( 1 );
		expect( notice().hidden ).toBe( false );
		expect( notice().classList ).toContain(
			'jmc-enquiry-form__notice--error'
		);
		expect( notice().ownerDocument.activeElement ).toBe( notice() );
		expect(
			notice().querySelector( '.jmc-enquiry-form__notice-message' )
				?.textContent
		).toBe( MESSAGES.summary );

		const links = Array.from(
			notice().querySelectorAll< HTMLAnchorElement >( 'li a' )
		);

		expect( links.map( ( link ) => link.getAttribute( 'href' ) ) ).toEqual(
			[
				'#enquiry-form-name',
				'#enquiry-form-email',
				'#enquiry-form-message',
				'#enquiry-form-consent',
			]
		);

		const email = input( 'jmc_enquiry[email]' );
		const error = document.getElementById( 'enquiry-form-email-error' );

		expect( email.getAttribute( 'aria-invalid' ) ).toBe( 'true' );
		expect( error?.hidden ).toBe( false );
		expect( error?.textContent ).toBe( ERRORS.email_format );
		expect(
			input( 'jmc_enquiry[phone]' ).hasAttribute( 'aria-invalid' )
		).toBe( false );
	} );

	it( 'moves focus to the field when a summary link is followed', async () => {
		await setup();
		await submit();

		notice()
			.querySelector< HTMLAnchorElement >(
				'a[href="#enquiry-form-email"]'
			)
			?.click();

		expect( form().ownerDocument.activeElement ).toBe(
			input( 'jmc_enquiry[email]' )
		);
	} );

	it( 'clears earlier errors on the next submission', async () => {
		await setup( [ jsonResponse( { status: 'sent' } ) ] );
		await submit();

		fillValid();
		await submit();

		expect( document.querySelectorAll( '[aria-invalid]' ).length ).toBe(
			0
		);
		expect(
			document.getElementById( 'enquiry-form-name-error' )?.hidden
		).toBe( true );
	} );

	it( 'sends the form and confirms success', async () => {
		const sent = jest.fn();
		document.addEventListener( 'jmc:enquiry-sent', sent );

		const { fetchMock } = await setup( [
			jsonResponse( { status: 'sent' } ),
		] );

		fillValid();
		await submit();

		const [ url, request ] = fetchMock.mock.calls[ 1 ];
		const body = request.body as FormData;

		expect( url ).toBe( '/wp-json/jmc/v1/enquiries' );
		expect( request ).toEqual(
			expect.objectContaining( { method: 'POST', credentials: 'omit' } )
		);
		expect( body.get( 'jmc_enquiry[email]' ) ).toBe( 'alex@example.com' );
		expect( body.get( 'jmc_enquiry_nonce' ) ).toBe( 'fresh-nonce' );

		expect( form().hidden ).toBe( true );
		expect( notice().classList ).toContain(
			'jmc-enquiry-form__notice--success'
		);
		expect( notice().textContent ).toContain( MESSAGES.sent );
		expect( notice().ownerDocument.activeElement ).toBe( notice() );
		expect( sent ).toHaveBeenCalledTimes( 1 );

		document.removeEventListener( 'jmc:enquiry-sent', sent );
	} );

	it( 'waits out the minimum time before sending a quick submission', async () => {
		let time = 10_000;
		const wait = jest.fn( () => Promise.resolve() );

		await setup( [ jsonResponse( { status: 'sent' } ) ], {
			now: () => time,
			wait,
		} );

		time += 1000;
		fillValid();
		await submit();

		expect( wait ).toHaveBeenCalledWith( MIN_SUBMIT_DELAY - 1000 );
	} );

	it( 'does not wait when the visitor took long enough', async () => {
		let time = 10_000;
		const wait = jest.fn( () => Promise.resolve() );

		await setup( [ jsonResponse( { status: 'sent' } ) ], {
			now: () => time,
			wait,
		} );

		time += 60_000;
		fillValid();
		await submit();

		expect( wait ).not.toHaveBeenCalled();
	} );

	it( 'ignores a second submit while the first is sending', async () => {
		let release: () => void = () => undefined;
		const wait = jest.fn(
			() =>
				new Promise< void >( ( resolve ) => {
					release = resolve;
				} )
		);

		const { fetchMock } = await setup(
			[ jsonResponse( { status: 'sent' } ) ],
			{ now: () => 0, wait }
		);

		fillValid();
		await submit();

		const button = document.querySelector( 'button' );

		expect( button?.getAttribute( 'aria-disabled' ) ).toBe( 'true' );
		expect( button?.textContent ).toBe( MESSAGES.sending );
		expect( form().getAttribute( 'aria-busy' ) ).toBe( 'true' );

		await submit();
		release();
		await flush();
		await flush();

		expect( fetchMock ).toHaveBeenCalledTimes( 2 );
		expect( button?.hasAttribute( 'aria-disabled' ) ).toBe( false );
		expect( button?.textContent ).toBe( 'Send enquiry' );
	} );

	it( 'shows server field errors', async () => {
		await setup( [
			jsonResponse(
				{
					status: 'invalid',
					errors: { service: ERRORS.service },
				},
				422
			),
		] );

		fillValid();
		await submit();

		expect( form().hidden ).toBe( false );
		expect( notice().querySelector( 'li' )?.textContent ).toBe(
			ERRORS.service
		);
	} );

	it( 'refreshes the tokens and asks again when the form has expired', async () => {
		const { fetchMock } = await setup( [
			jsonResponse( { status: 'expired' }, 403 ),
		] );

		fetchMock.mockResolvedValueOnce(
			jsonResponse( { nonce: 'newer-nonce', started: 'newer-started' } )
		);

		fillValid();
		await submit();
		await flush();

		expect( input( 'jmc_enquiry_nonce' ).value ).toBe( 'newer-nonce' );
		expect( notice().textContent ).toContain( MESSAGES.expired );
		expect( form().hidden ).toBe( false );
	} );

	it.each( [
		[
			'rate limited',
			jsonResponse( { status: 'rate_limited' }, 429 ),
			MESSAGES.rate_limited,
		],
		[
			'server error',
			jsonResponse( { status: 'error' }, 500 ),
			MESSAGES.error,
		],
		[
			'unexpected response',
			jsonResponse( 'nope', 502 ),
			MESSAGES.network,
		],
		[ 'network failure', new Error( 'offline' ), MESSAGES.network ],
	] )(
		'offers phone and email after a %s',
		async ( _label, response, message ) => {
			await setup( [ response ] );

			fillValid();
			await submit();

			const alternative = notice().querySelector< HTMLElement >(
				'.jmc-enquiry-form__alternative'
			);

			expect( notice().textContent ).toContain( message );
			expect( alternative?.hidden ).toBe( false );
			expect( form().hidden ).toBe( false );
			expect(
				document
					.querySelector( 'button' )
					?.hasAttribute( 'aria-disabled' )
			).toBe( false );
		}
	);
} );
