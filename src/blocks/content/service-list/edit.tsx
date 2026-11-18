import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

import bindFields from '../../../utils/bindFields';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

type Attributes = {
	headingLevel: string;
};

type Props = {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
};

// H1 is reserved for the page title, so card titles start at H2.
const HEADING_LEVELS = [
	{ label: 'H2', value: '2' },
	{ label: 'H3', value: '3' },
	{ label: 'H4', value: '4' },
	{ label: 'H5', value: '5' },
	{ label: 'H6', value: '6' },
] as const;

export default function Edit( { attributes, setAttributes }: Props ) {
	const bind = bindFields( attributes, setAttributes );
	const blockProps = useBlockProps();

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Card Settings', 'jmc-theme' ) }>
					<SelectInput
						{ ...bind.select( 'headingLevel' ) }
						type="heading_level"
						options={ HEADING_LEVELS }
					/>
				</Sidebar.Section>
			</Sidebar>

			{ /* Preview render.php output so cards always match the page data. */ }
			<div { ...blockProps }>
				{ /* Disabled keeps the card links from leaving the editor. */ }
				<Disabled>
					<ServerSideRender
						block="jmc/service-list"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}
