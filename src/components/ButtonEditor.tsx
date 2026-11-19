import { TextControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import bindFields from '../utils/bindFields';
import toContactUrl from '../utils/contactLink';
import useThemeIcons from '../utils/useThemeIcons';
import ButtonLink from './ButtonLink';
import SelectInput from './SelectInput';
import Sidebar from './Sidebar';
import SiteSetting from './SiteSetting';
import TextInput from './TextInput';

const VARIANT_CLASSES = {
	primary: '',
	secondary: 'jmc-button__secondary',
};

// Site-wide business details (Settings → Business) used by phone, email and WhatsApp buttons.
const CONTACT_SETTINGS = {
	phone: {
		option: 'jmc_business_phone',
		label: __( 'Business phone number', 'jmc-theme' ),
		type: 'tel',
		placeholder: __( 'Call us', 'jmc-theme' ),
	},
	email: {
		option: 'jmc_business_email',
		label: __( 'Business email address', 'jmc-theme' ),
		type: 'email',
		placeholder: __( 'Email us', 'jmc-theme' ),
	},
	whatsapp: {
		option: 'jmc_business_whatsapp',
		label: __( 'WhatsApp number', 'jmc-theme' ),
		type: 'tel',
		placeholder: __( 'Message us on WhatsApp', 'jmc-theme' ),
		hint: __(
			'Include the country code, e.g. +44 7700 900123.',
			'jmc-theme'
		),
	},
} as const;

type ContactType = keyof typeof CONTACT_SETTINGS;

// Icons shown in contact buttons; must match the icon set in inc/helpers/button.php.
const CONTACT_ICONS: Partial< Record< ContactType, string > > = {
	whatsapp: 'jmc/whatsapp',
};

type MessageField = {
	key: 'emailSubject' | 'emailBody' | 'whatsappMessage';
	label: string;
	multiline: boolean;
};

// Per-button message pre-filled in the visitor's email app or WhatsApp chat.
const MESSAGE_FIELDS: Partial< Record< ContactType, MessageField[] > > = {
	email: [
		{
			key: 'emailSubject',
			label: __( 'Subject', 'jmc-theme' ),
			multiline: false,
		},
		{
			key: 'emailBody',
			label: __( 'Message', 'jmc-theme' ),
			multiline: true,
		},
	],
	whatsapp: [
		{
			key: 'whatsappMessage',
			label: __( 'Message', 'jmc-theme' ),
			multiline: true,
		},
	],
};

export type ButtonAttributes = {
	text: string;
	buttonType: string;
	linkType: string;
	link: { url: string; opensInNewTab: boolean };
	emailSubject: string;
	emailBody: string;
	whatsappMessage: string;
};

type ButtonEditorProps = {
	attributes: ButtonAttributes;
	setAttributes: ( attributes: Partial< ButtonAttributes > ) => void;
	// Extra classes on the button, e.g. the block's own className.
	className?: string;
};

type ContactSettingProps = {
	type: ContactType;
};

/**
 * Edit the site-wide detail a phone, email or WhatsApp button links to.
 *
 * Saved with the site settings, so it changes every button of that type.
 * Users who can't manage options can't read the setting, so they get a notice.
 *
 * @param props      Component props.
 * @param props.type Contact type.
 */
const ContactSetting = ( { type }: ContactSettingProps ) => {
	const setting = CONTACT_SETTINGS[ type ];

	return (
		<SiteSetting< string >
			option={ setting.option }
			notice={ __(
				'This button uses the business details in Settings → Business. Ask an administrator to change them.',
				'jmc-theme'
			) }
		>
			{ ( value, setValue ) => (
				<TextControl
					__next40pxDefaultSize
					label={ setting.label }
					type={ setting.type }
					value={ value ?? '' }
					onChange={ setValue }
					help={ [
						'hint' in setting ? setting.hint : '',
						__(
							'Shared by every button of this type and saved with the site settings. Leave empty to hide these buttons.',
							'jmc-theme'
						),
					]
						.filter( Boolean )
						.join( ' ' ) }
				/>
			) }
		</SiteSetting>
	);
};

/**
 * Edit a button: its style, where it links to, and its label on the canvas.
 *
 * Shared by the button blocks so they offer the same button types and link
 * types; their render.php files resolve them with jmc_button_link_args().
 *
 * @param props               Component props.
 * @param props.attributes    Button attributes.
 * @param props.setAttributes Attribute setter.
 * @param props.className     Extra classes on the button.
 */
export default function ButtonEditor( {
	attributes,
	setAttributes,
	className,
}: ButtonEditorProps ) {
	const { buttonType, linkType } = attributes;

	const bind = bindFields( attributes, setAttributes );
	const link = bind.link( 'link' );

	// Let authors type a bare phone number or email address as the destination.
	const contactLink = {
		...link,
		set: ( value: { url: string; opensInNewTab: boolean } ) =>
			link.set( { ...value, url: toContactUrl( value.url ?? '' ) } ),
	};

	const contactType: ContactType | null =
		linkType in CONTACT_SETTINGS ? ( linkType as ContactType ) : null;

	// Registered icon shown before the label, as the render does.
	const icon = contactType ? CONTACT_ICONS[ contactType ] : undefined;
	const icons = useThemeIcons();

	const classes = [ className, VARIANT_CLASSES[ buttonType ] ]
		.filter( Boolean )
		.join( ' ' );

	const buttonClasses = [ classes, 'jmc-button' ]
		.filter( Boolean )
		.join( ' ' );

	const contactLabel = contactType && (
		<TextInput
			{ ...bind.text( 'text' ) }
			tagName={ icon ? 'span' : undefined }
			placeholder={ CONTACT_SETTINGS[ contactType ].placeholder }
			className={ icon ? undefined : buttonClasses }
		/>
	);

	// Mirrors the render: the icon sits inside the button, before the label.
	const contactButton = icon ? (
		<div className={ buttonClasses }>
			<span
				className="jmc-button__icon"
				aria-hidden="true"
				dangerouslySetInnerHTML={ { __html: icons[ icon ] ?? '' } }
			/>
			{ contactLabel }
		</div>
	) : (
		contactLabel
	);

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Button Type', 'jmc-theme' ) }>
					<SelectInput
						{ ...bind.select( 'buttonType' ) }
						type="style"
					/>
				</Sidebar.Section>
				{ contactType && (
					<Sidebar.Section
						title={ __( 'Contact Details', 'jmc-theme' ) }
					>
						<ContactSetting type={ contactType } />
					</Sidebar.Section>
				) }
				{ contactType && MESSAGE_FIELDS[ contactType ] && (
					<Sidebar.Section
						title={ __( 'Pre-filled Message', 'jmc-theme' ) }
					>
						<p>
							{ __(
								'Optional. Filled in for the visitor, who can change it before sending.',
								'jmc-theme'
							) }
						</p>
						{ MESSAGE_FIELDS[ contactType ].map(
							( { key, label, multiline } ) => {
								const field = {
									label,
									value: attributes[ key ] ?? '',
									onChange: ( value: string ) =>
										setAttributes( { [ key ]: value } ),
								};

								return multiline ? (
									<TextareaControl
										key={ key }
										{ ...field }
										__nextHasNoMarginBottom
									/>
								) : (
									<TextControl
										key={ key }
										{ ...field }
										__next40pxDefaultSize
										__nextHasNoMarginBottom
									/>
								);
							}
						) }
					</Sidebar.Section>
				) }
			</Sidebar>
			{ contactType ? (
				contactButton
			) : (
				<ButtonLink
					text={ bind.text( 'text' ) }
					link={ contactLink }
					className={ classes }
				/>
			) }
		</>
	);
}
