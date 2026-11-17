import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

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
					<TextInput
						{ ...bind.text( 'address' ) }
						className="jmc-footer-business__address"
						placeholder={ __( 'Address', 'jmc-theme' ) }
					/>
					<TextInput
						{ ...bind.text( 'phone' ) }
						className="jmc-footer-business__phone"
						placeholder={ __( 'Telephone number', 'jmc-theme' ) }
					/>
					<TextInput
						{ ...bind.text( 'email' ) }
						className="jmc-footer-business__email"
						placeholder={ __( 'Email address', 'jmc-theme' ) }
					/>
				</address>
				<TextInput
					{ ...bind.text( 'serviceArea' ) }
					className="jmc-footer-business__service-area"
					placeholder={ __( 'Service area', 'jmc-theme' ) }
				/>
			</div>
		</>
	);
}
