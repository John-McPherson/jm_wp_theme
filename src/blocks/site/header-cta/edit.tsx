import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import bindFields from '../../../utils/bindFields';
import ButtonLink from '../../../components/ButtonLink';
import Sidebar from '../../../components/Sidebar';

type HeaderCtaAttributes = {
	text: string;
	link: { url: string; opensInNewTab: boolean };
	className?: string;
};

type EditProps = {
	attributes: HeaderCtaAttributes;
	setAttributes: ( attributes: Partial< HeaderCtaAttributes > ) => void;
};

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps( {} );

	const bind = bindFields( attributes, setAttributes );

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Call to action', 'jmc-theme' ) }>
					<p>
						{ __(
							'Use the link button in the toolbar to choose a page, a web address, or a phone number such as tel:01234567890.',
							'jmc-theme'
						) }
					</p>
					<p>
						{ __(
							'The button is hidden on the site when the label or link is empty, or when the link is not a web address, site path or phone number.',
							'jmc-theme'
						) }
					</p>
				</Sidebar.Section>
			</Sidebar>
			<div { ...blockProps }>
				<ButtonLink
					text={ bind.text( 'text' ) }
					link={ bind.link( 'link' ) }
					className="jmc-button--small jmc-header-cta"
				/>
			</div>
		</>
	);
}
