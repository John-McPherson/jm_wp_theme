import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Icon, envelope, home, mapMarker } from '@wordpress/icons';

import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import TextInput from '../../../components/TextInput';

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

// The same icons the frontend renders with wp_get_icon().
const ICON_CLASS = 'jmc-footer-business__icon';

// Mirrors the theme-registered `jmc/phone` icon (see inc/icons.php).
const phone = (
	<svg xmlns="http://www.w3.org/2000/svg" viewBox="-3 -3 26 26">
		<path d="M12.06 6l-.21-.2c-.52-.54-.43-.79.08-1.3l2.72-2.75c.81-.82.96-1.21 1.73-.48l.21.2zm.53.45l4.4-4.4c.7.94 2.34 3.47 1.53 5.34-.73 1.67-1.09 1.75-2 3-1.85 2.11-4.18 4.37-6 6.07-1.26.91-1.31 1.33-3 2-1.8.71-4.4-.89-5.38-1.56l4.4-4.4 1.18 1.62c.34.46 1.2-.06 1.8-.66 1.04-1.05 3.18-3.18 4-4.07.59-.59 1.12-1.45.66-1.8zM1.57 16.5l-.21-.21c-.68-.74-.29-.9.52-1.7l2.74-2.72c.51-.49.75-.6 1.27-.11l.2.21z" />
	</svg>
);

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps( { className: 'jmc-footer-business' } );

	const bind = bindFields( attributes, setAttributes );

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
						<Icon icon={ mapMarker } className={ ICON_CLASS } />
						<TextInput
							{ ...bind.text( 'address' ) }
							tagName="span"
							placeholder={ __( 'Address', 'jmc-theme' ) }
						/>
					</div>
					<div className="jmc-footer-business__line jmc-footer-business__phone">
						<Icon icon={ phone } className={ ICON_CLASS } />
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
						<Icon icon={ envelope } className={ ICON_CLASS } />
						<TextInput
							{ ...bind.text( 'email' ) }
							tagName="span"
							placeholder={ __( 'Email address', 'jmc-theme' ) }
						/>
					</div>
				</address>
				<div className="jmc-footer-business__line jmc-footer-business__service-area">
					<Icon icon={ home } className={ ICON_CLASS } />
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
