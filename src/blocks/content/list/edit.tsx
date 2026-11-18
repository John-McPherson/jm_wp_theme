import { __ } from '@wordpress/i18n';

import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';

import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

import {
	LIST_ICON_OPTIONS,
	LIST_STYLE_OPTIONS,
	listTagName,
	toListStyle,
} from './options';

type Attributes = {
	listStyle: string;
	icon: string;
	className?: string;
};

type Props = {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
};

const ALLOWED_BLOCKS = [ 'jmc/list-item' ];

// A new list starts with one empty item; Enter adds the next.
const TEMPLATE: [ string ][] = [ [ 'jmc/list-item' ] ];

export default function Edit( { attributes, setAttributes }: Props ) {
	const bind = bindFields( attributes, setAttributes );

	const listStyle = toListStyle( attributes.listStyle );
	const TagName = listTagName( listStyle );

	const blockProps = useBlockProps( {
		className: `jmc-list jmc-list--${ listStyle }`,
		// Matches render.php: icon lists drop their markers, which Safari reads as "not a list".
		role: 'icon' === listStyle ? 'list' : undefined,
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		allowedBlocks: ALLOWED_BLOCKS,
		template: TEMPLATE,
		templateLock: false,
	} );

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'List Settings', 'jmc-theme' ) }>
					<SelectInput
						{ ...bind.select( 'listStyle' ) }
						label={ __( 'Style', 'jmc-theme' ) }
						options={ LIST_STYLE_OPTIONS }
					/>

					{ 'icon' === listStyle && (
						<SelectInput
							{ ...bind.select( 'icon' ) }
							label={ __( 'Icon', 'jmc-theme' ) }
							options={ LIST_ICON_OPTIONS }
						/>
					) }
				</Sidebar.Section>
			</Sidebar>

			<TagName { ...innerBlocksProps } />
		</>
	);
}
