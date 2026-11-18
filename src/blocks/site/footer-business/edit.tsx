import { useBlockProps } from '@wordpress/block-editor';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { MouseEvent, ReactNode } from 'react';

import bindFields from '../../../utils/bindFields';
import ContactSettings from '../../../components/ContactSettings';
import Sidebar from '../../../components/Sidebar';
import TextInput from '../../../components/TextInput';
import useThemeIcons from '../../../utils/useThemeIcons';

type FooterBusinessAttributes = {
	companyName: string;
	address: string;
	className?: string;
};

type EditProps = {
	attributes: FooterBusinessAttributes;
	setAttributes: ( attributes: Partial< FooterBusinessAttributes > ) => void;
};

// The one core-data selector used here; the store's own types don't expose it.
type CoreSelectors = {
	getEntityRecord: (
		kind: string,
		name: string
	) => Record< string, unknown > | undefined;
};

type ContactDetails = {
	phone: string;
	whatsapp: string;
	email: string;
	serviceArea: string[];
};

const ICON_CLASS = 'jmc-footer-business__icon';

const text = ( value: unknown ): string =>
	typeof value === 'string' ? value.trim() : '';

/**
 * Read the saved (not draft) business contact settings.
 *
 * The REST API sanitises settings on save, so these are the values render.php
 * shows; edits in the sidebar appear once they're saved, as in the Contact
 * Details block. Users who can't read the settings get empty values.
 */
const useContactDetails = (): ContactDetails =>
	useSelect( ( select ) => {
		const { getEntityRecord } = select(
			coreStore
		) as unknown as CoreSelectors;
		const site = getEntityRecord( 'root', 'site' ) ?? {};
		const places = site.jmc_business_service_area;

		return {
			phone: text( site.jmc_business_phone ),
			whatsapp: text( site.jmc_business_whatsapp ),
			email: text( site.jmc_business_email ),
			serviceArea: Array.isArray( places )
				? places.map( text ).filter( Boolean )
				: [],
		};
	}, [] );

/**
 * Join places into one phrase, as render.php does: "Pudsey, Leeds and Bradford".
 *
 * @param places Place names.
 */
const joinPlaces = ( places: string[] ): string => {
	const rest = places.slice( 0, -1 );
	const last = places[ places.length - 1 ] ?? '';

	return rest.length
		? sprintf(
				/* translators: 1: comma-separated list of places, 2: the last place. */
				__( '%1$s and %2$s', 'jmc-theme' ),
				rest.join( ', ' ),
				last
			)
		: last;
};

// Links look as they do on the site but don't open apps from the editor.
const preventNavigation = ( event: MouseEvent ) => event.preventDefault();

type LineIconProps = {
	content?: string;
};

// Decorative, like the frontend. Renders an empty, sized box until loaded.
// Markup comes from the core icon registry, which validates SVG on registration.
const LineIcon = ( { content = '' }: LineIconProps ) => (
	<span
		className={ ICON_CLASS }
		aria-hidden="true"
		dangerouslySetInnerHTML={ { __html: content } }
	/>
);

type ContactLineProps = {
	name: string;
	icon?: string;
	children: ReactNode;
};

const ContactLine = ( { name, icon, children }: ContactLineProps ) => (
	<p className={ `jmc-footer-business__line jmc-footer-business__${ name }` }>
		<LineIcon content={ icon } />
		{ children }
	</p>
);

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps( { className: 'jmc-footer-business' } );

	const bind = bindFields( attributes, setAttributes );
	const icons = useThemeIcons();
	const { phone, whatsapp, email, serviceArea } = useContactDetails();
	const places = joinPlaces( serviceArea );
	const hasShared = !! ( phone || whatsapp || email || places );

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Footer details', 'jmc-theme' ) }>
					<p>
						{ __(
							'The business name and address are edited here, on the block. Empty fields are hidden on the site; use Shift+Enter for a new line in the address.',
							'jmc-theme'
						) }
					</p>
					<p>
						{ __(
							'The phone, WhatsApp, email and service area come from the shared business details below, so they match the contact buttons and Contact Details blocks.',
							'jmc-theme'
						) }
					</p>
				</Sidebar.Section>
				<ContactSettings />
			</Sidebar>
			<div { ...blockProps }>
				<TextInput
					{ ...bind.text( 'companyName' ) }
					className="jmc-footer-business__name"
					placeholder={ __( 'Business name', 'jmc-theme' ) }
				/>
				<address className="jmc-footer-business__contact">
					<div className="jmc-footer-business__line jmc-footer-business__address">
						<LineIcon content={ icons[ 'jmc/map-pin' ] } />
						<TextInput
							{ ...bind.text( 'address' ) }
							tagName="span"
							placeholder={ __( 'Address', 'jmc-theme' ) }
						/>
					</div>
					{ phone && (
						<ContactLine name="phone" icon={ icons[ 'jmc/phone' ] }>
							<a href="#phone" onClick={ preventNavigation }>
								{ phone }
							</a>
						</ContactLine>
					) }
					{ whatsapp && (
						<ContactLine
							name="whatsapp"
							icon={ icons[ 'jmc/whatsapp' ] }
						>
							<a href="#whatsapp" onClick={ preventNavigation }>
								<span className="jmc-footer-business__hidden-label">
									{ __( 'WhatsApp:', 'jmc-theme' ) }{ ' ' }
								</span>
								{ whatsapp }
							</a>
						</ContactLine>
					) }
					{ email && (
						<ContactLine name="email" icon={ icons[ 'jmc/mail' ] }>
							<a href="#email" onClick={ preventNavigation }>
								{ email }
							</a>
						</ContactLine>
					) }
				</address>
				{ places && (
					<ContactLine
						name="service-area"
						icon={ icons[ 'jmc/house' ] }
					>
						<span>
							<span className="jmc-footer-business__label">
								{ __( 'Areas covered:', 'jmc-theme' ) }
							</span>{ ' ' }
							{ places }
						</span>
					</ContactLine>
				) }
				{ ! hasShared && (
					<p className="jmc-footer-business__empty">
						{ __(
							'Add a phone number, WhatsApp number, email address or service area under Business details in the block settings.',
							'jmc-theme'
						) }
					</p>
				) }
			</div>
		</>
	);
}
