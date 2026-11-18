import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';

import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

type Attributes = {
	palette: 'default' | 'secondary' | 'inverse';
	className?: string;
};

type Props = {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
};

// The section is just the list of credentials.
const TEMPLATE = [ [ 'jmc/credential-list' ] ];

const ALLOWED_BLOCKS = [ 'jmc/credential-list' ];

const VARIANT_CLASSES = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
};

export default function Edit( { attributes, setAttributes }: Props ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette } = attributes;

	const blockProps = useBlockProps( {
		className: [
			'jmc-section',
			'jmc-trust-credentials',
			VARIANT_CLASSES[ palette ],
		]
			.filter( Boolean )
			.join( ' ' ),
	} );

	// The section's structure is fixed; the credential list unlocks its own items.
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'jmc-section__column' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: 'insert',
		}
	);

	return (
		<>
			<Sidebar>
				<Sidebar.Section
					title={ __( 'Palette Settings', 'jmc-theme' ) }
				>
					<SelectInput
						{ ...bind.select( 'palette' ) }
						type="palette"
					/>
				</Sidebar.Section>
			</Sidebar>

			<section { ...blockProps }>
				<div className="jmc-section__container">
					<div { ...innerBlocksProps } />
				</div>
			</section>
		</>
	);
}
