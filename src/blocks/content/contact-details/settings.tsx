import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import Sidebar from '../../../components/Sidebar';
import SiteSetting from '../../../components/SiteSetting';

type Time = 'opens' | 'closes';

type Hours = Record< string, Partial< Record< Time, string > > >;

const DAYS = [
	{ key: 'monday', label: __( 'Monday', 'jmc-theme' ) },
	{ key: 'tuesday', label: __( 'Tuesday', 'jmc-theme' ) },
	{ key: 'wednesday', label: __( 'Wednesday', 'jmc-theme' ) },
	{ key: 'thursday', label: __( 'Thursday', 'jmc-theme' ) },
	{ key: 'friday', label: __( 'Friday', 'jmc-theme' ) },
	{ key: 'saturday', label: __( 'Saturday', 'jmc-theme' ) },
	{ key: 'sunday', label: __( 'Sunday', 'jmc-theme' ) },
];

const TEXT_SETTINGS = [
	{
		option: 'jmc_business_phone',
		label: __( 'Business phone number', 'jmc-theme' ),
		type: 'tel',
		help: __(
			'Write it as it should be read, e.g. 01234 567890.',
			'jmc-theme'
		),
	},
	{
		option: 'jmc_business_whatsapp',
		label: __( 'WhatsApp number', 'jmc-theme' ),
		type: 'tel',
		help: __(
			'Include the country code, e.g. +44 7700 900123.',
			'jmc-theme'
		),
	},
	{
		option: 'jmc_business_email',
		label: __( 'Business email address', 'jmc-theme' ),
		type: 'email',
		help: '',
	},
] as const;

/**
 * Normalise stored opening hours. An empty PHP array arrives as `[]`.
 *
 * @param value Stored setting value.
 */
const toHours = ( value: unknown ): Hours =>
	value && 'object' === typeof value && ! Array.isArray( value )
		? ( value as Hours )
		: {};

/**
 * Explain why a day's times will be saved as closed, if they will.
 *
 * Mirrors `jmc_sanitize_business_hours()`: both times are needed, and
 * zero-padded 24-hour times compare correctly as strings.
 *
 * @param times Opening and closing times for one day.
 */
const dayProblem = ( times: Partial< Record< Time, string > > = {} ) => {
	const { opens, closes } = times;

	if ( ! opens && ! closes ) {
		return '';
	}

	if ( ! opens || ! closes ) {
		return __(
			'Add both times, or clear both if you are closed. Saved as closed until then.',
			'jmc-theme'
		);
	}

	return closes <= opens
		? __(
				'Closing time must be after opening time. Saved as closed until then.',
				'jmc-theme'
		  )
		: '';
};

type HoursControlProps = {
	value: unknown;
	setValue: ( value: Hours ) => void;
};

const HoursControl = ( { value, setValue }: HoursControlProps ) => {
	const hours = toHours( value );

	const setTime = ( day: string, key: Time, time: string ) => {
		const times = { ...hours[ day ], [ key ]: time };

		// Empty times fail the REST schema, so they're removed rather than stored.
		if ( ! time ) {
			delete times[ key ];
		}

		const next = { ...hours, [ day ]: times };

		if ( ! times.opens && ! times.closes ) {
			delete next[ day ];
		}

		setValue( next );
	};

	return (
		<fieldset className="jmc-contact-details-settings__hours">
			<legend>{ __( 'Opening hours', 'jmc-theme' ) }</legend>
			<p className="components-base-control__help">
				{ __(
					'Leave both times empty on days you are closed.',
					'jmc-theme'
				) }
			</p>
			{ DAYS.map( ( { key, label } ) => {
				const problem = dayProblem( hours[ key ] );

				return (
					<div
						key={ key }
						className="jmc-contact-details-settings__day"
						role="group"
						aria-label={ label }
					>
						<strong>{ label }</strong>
						<TextControl
							__next40pxDefaultSize
							label={ __( 'Opens', 'jmc-theme' ) }
							type="time"
							value={ hours[ key ]?.opens ?? '' }
							onChange={ ( time ) =>
								setTime( key, 'opens', time )
							}
						/>
						<TextControl
							__next40pxDefaultSize
							label={ __( 'Closes', 'jmc-theme' ) }
							type="time"
							value={ hours[ key ]?.closes ?? '' }
							onChange={ ( time ) =>
								setTime( key, 'closes', time )
							}
							help={ problem }
						/>
					</div>
				);
			} ) }
		</fieldset>
	);
};

/**
 * Sidebar controls for the site-wide business contact details.
 *
 * Changes are saved with the site settings when the page is saved, so they
 * update every contact details block, phone, email and WhatsApp button.
 */
export default function ContactSettings() {
	return (
		<Sidebar.Section
			title={ __( 'Business details', 'jmc-theme' ) }
			initialOpen={ false }
		>
			<p>
				{ __(
					'Shared across the site and saved with the site settings when you save. The preview updates after saving.',
					'jmc-theme'
				) }
			</p>

			{ TEXT_SETTINGS.map( ( setting ) => (
				<SiteSetting< string >
					key={ setting.option }
					option={ setting.option }
				>
					{ ( value, setValue ) => (
						<TextControl
							__next40pxDefaultSize
							label={ setting.label }
							type={ setting.type }
							value={ value ?? '' }
							onChange={ setValue }
							help={ setting.help }
						/>
					) }
				</SiteSetting>
			) ) }

			<SiteSetting< unknown > option="jmc_business_hours">
				{ ( value, setValue ) => (
					<HoursControl value={ value } setValue={ setValue } />
				) }
			</SiteSetting>

			<SiteSetting< string[] > option="jmc_business_service_area">
				{ ( value, setValue ) => (
					<TextareaControl
						label={ __( 'Service area', 'jmc-theme' ) }
						help={ __(
							'The towns and areas you cover, one per line.',
							'jmc-theme'
						) }
						value={ ( Array.isArray( value ) ? value : [] ).join(
							'\n'
						) }
						// Blank lines are kept while typing and dropped when saved.
						onChange={ ( text ) => setValue( text.split( '\n' ) ) }
						__nextHasNoMarginBottom
					/>
				) }
			</SiteSetting>
		</Sidebar.Section>
	);
}
