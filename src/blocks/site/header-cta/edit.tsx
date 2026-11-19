import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import ButtonEditor, {
	type ButtonAttributes,
} from '../../../components/ButtonEditor';
import Sidebar from '../../../components/Sidebar';

type HeaderCtaAttributes = ButtonAttributes & {
	className?: string;
};

type EditProps = {
	attributes: HeaderCtaAttributes;
	setAttributes: ( attributes: Partial< HeaderCtaAttributes > ) => void;
};

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps( {} );

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Call to action', 'jmc-theme' ) }>
					<p>
						{ __(
							'Switch between a link and a business phone, email or WhatsApp button with the block variation control, as for the Button block. A link button takes a page, web address, phone number or email address from the link button in the toolbar; the others use the details in Settings → Business.',
							'jmc-theme'
						) }
					</p>
					<p>
						{ __(
							'The button is hidden on the site when the label is empty, or when its link or business detail is missing or invalid.',
							'jmc-theme'
						) }
					</p>
				</Sidebar.Section>
			</Sidebar>
			<div { ...blockProps }>
				<ButtonEditor
					attributes={ attributes }
					setAttributes={ setAttributes }
					className={ [
						'jmc-button--small jmc-header-cta',
						attributes.className,
					]
						.filter( Boolean )
						.join( ' ' ) }
				/>
			</div>
		</>
	);
}
