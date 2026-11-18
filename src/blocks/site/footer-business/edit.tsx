import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import TextInput from '../../../components/TextInput';
import useThemeIcons from '../../../utils/useThemeIcons';

type FooterBusinessAttributes = {
	companyName: string;
	address: string;
	phone: string;
	email: string;
	serviceArea: string;
	className?: string;
};

type EditProps = {
	attributes: FooterBusinessAttributes;
	setAttributes: ( attributes: Partial< FooterBusinessAttributes > ) => void;
};

const ICON_CLASS = 'jmc-footer-business__icon';

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

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps( { className: 'jmc-footer-business' } );

	const bind = bindFields( attributes, setAttributes );
	const icons = useThemeIcons();

	return (
		<>
			<Sidebar>
				<Sidebar.Section
					title={ __( 'Business details', 'jmc-theme' ) }
				>
					<p>
						{ __(
							'These details appear in the footer on every page. Empty fields are hidden on the site.',
							'jmc-theme'
						) }
					</p>
					<p>
						{ __(
							'Use Shift+Enter for a new line in the address. The telephone number becomes a call link when it is a valid number, and an email address that is not valid is hidden.',
							'jmc-theme'
						) }
					</p>
				</Sidebar.Section>
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
					<div className="jmc-footer-business__line jmc-footer-business__phone">
						<LineIcon content={ icons[ 'jmc/phone' ] } />
						<TextInput
							{ ...bind.text( 'phone' ) }
							tagName="span"
							placeholder={ __(
								'Telephone number',
								'jmc-theme'
							) }
						/>
					</div>
					<div className="jmc-footer-business__line jmc-footer-business__email">
						<LineIcon content={ icons[ 'jmc/mail' ] } />
						<TextInput
							{ ...bind.text( 'email' ) }
							tagName="span"
							placeholder={ __( 'Email address', 'jmc-theme' ) }
						/>
					</div>
				</address>
				<div className="jmc-footer-business__line jmc-footer-business__service-area">
					<LineIcon content={ icons[ 'jmc/house' ] } />
					<span>
						<span className="jmc-footer-business__label">
							{ __( 'Areas covered:', 'jmc-theme' ) }
						</span>{ ' ' }
						<TextInput
							{ ...bind.text( 'serviceArea' ) }
							tagName="span"
							placeholder={ __(
								'e.g. Pudsey, Leeds and surrounding areas',
								'jmc-theme'
							) }
						/>
					</span>
				</div>
			</div>
		</>
	);
}
