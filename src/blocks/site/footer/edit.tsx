import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import bindFields from '../../../utils/bindFields';
import SelectInput from '../../../components/SelectInput';
import Sidebar from '../../../components/Sidebar';

type Palette = 'default' | 'secondary' | 'inverse';

type FooterAttributes = {
	palette: Palette;
	className?: string;
};

type EditProps = {
	attributes: FooterAttributes;
	setAttributes: ( attributes: Partial< FooterAttributes > ) => void;
};

const ALLOWED_BLOCKS = [ 'jmc/footer-business' ];

const TEMPLATE: [ string, Record< string, unknown > ][] = [
	[ 'jmc/footer-business', { lock: { remove: true } } ],
];

const PALETTE_CLASSES: Record< Palette, string > = {
	default: '',
	inverse: 'jmc-palette--inverse',
	secondary: 'jmc-palette--secondary',
};

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const bind = bindFields( attributes, setAttributes );

	const { palette } = attributes;

	const blockProps = useBlockProps( {
		className: [ 'jmc-footer-inner', PALETTE_CLASSES[ palette ] ]
			.filter( Boolean )
			.join( ' ' ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'jmc-footer-inner__container' },
		{ allowedBlocks: ALLOWED_BLOCKS, template: TEMPLATE }
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

			<div { ...blockProps }>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
