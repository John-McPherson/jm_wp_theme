import { useBlockProps } from '@wordpress/block-editor';
import { Notice, TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

import bindFields from '../../../utils/bindFields';
import toContactUrl from '../../../utils/contactLink';
import ButtonLink from '../../../components/ButtonLink';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';
import TextInput from '../../../components/TextInput';

const VARIANT_CLASSES = {
	primary: '',
	secondary: 'jmc-button__secondary',
};

// Site-wide business details (Settings → General) used by phone and email buttons.
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
} as const;

type ContactType = keyof typeof CONTACT_SETTINGS;

type ContactSettingProps = {
	type: ContactType;
};

/**
 * Edit the site-wide detail a phone or email button links to.
 *
 * Saved with the site settings, so it changes every button of that type.
 * Users who can't manage options can't read the setting, so they get a notice.
 *
 * @param props      Component props.
 * @param props.type Contact type.
 */
const ContactSetting = ( { type }: ContactSettingProps ) => {
	const setting = CONTACT_SETTINGS[ type ];
	const [ value, setValue ] = useEntityProp( 'root', 'site', setting.option );

	if ( undefined === value ) {
		return (
			<Notice status="info" isDismissible={ false }>
				{ __(
					'This button uses the business details in Settings → General. Ask an administrator to change them.',
					'jmc-theme'
				) }
			</Notice>
		);
	}

	return (
		<TextControl
			__next40pxDefaultSize
			label={ setting.label }
			type={ setting.type }
			value={ value ?? '' }
			onChange={ setValue }
			help={ __(
				'Shared by every button of this type and saved with the site settings. Leave empty to hide these buttons.',
				'jmc-theme'
			) }
		/>
	);
};

export default function Edit( { attributes, setAttributes } ) {
	const { className, buttonType, linkType } = attributes;

	const blockProps = useBlockProps( {} );

	const bind = bindFields( attributes, setAttributes );
	const link = bind.link( 'link' );

	// Let authors type a bare phone number or email address as the destination.
	const contactLink = {
		...link,
		set: ( value: { url: string; opensInNewTab: boolean } ) =>
			link.set( { ...value, url: toContactUrl( value.url ?? '' ) } ),
	};

	const contactType: ContactType | null =
		linkType in CONTACT_SETTINGS ? linkType : null;

	const classes = [ className, VARIANT_CLASSES[ buttonType ] ]
		.filter( Boolean )
		.join( ' ' );

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
			</Sidebar>
			<div { ...blockProps }>
				{ contactType ? (
					<TextInput
						{ ...bind.text( 'text' ) }
						placeholder={
							CONTACT_SETTINGS[ contactType ].placeholder
						}
						className={ [ classes, 'jmc-button' ]
							.filter( Boolean )
							.join( ' ' ) }
					/>
				) : (
					<ButtonLink
						text={ bind.text( 'text' ) }
						link={ contactLink }
						className={ classes }
					/>
				) }
			</div>
		</>
	);
}
