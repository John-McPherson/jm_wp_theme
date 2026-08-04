import { useBlockProps } from '@wordpress/block-editor';

import TextInput from '../../../components/TextInput';
import bindFields from '../../../utils/bindFields';

const PARAGRAPH_VARIANT_CLASSES = {
	default: '',
	label: 'jm-label',
} as const;

const CONTEXT_VARIANT_CLASSES = {
	default: '',
	hero: 'jm-hero__text-para',
} as const;

type ParagraphVariant = keyof typeof PARAGRAPH_VARIANT_CLASSES;
type ContextVariant = keyof typeof CONTEXT_VARIANT_CLASSES;

export default function Edit( { attributes, setAttributes, context } ) {
	const bind = bindFields( attributes, setAttributes );

	const paragraphVariant = ( attributes.variant ??
		'default' ) as ParagraphVariant;

	const contextVariant = ( context[ 'jm/variant' ] ??
		'default' ) as ContextVariant;

	const blockProps = useBlockProps( {
		className: [
			'jm-paragraph',
			PARAGRAPH_VARIANT_CLASSES[ paragraphVariant ],
			CONTEXT_VARIANT_CLASSES[ contextVariant ],
		],
	} );

	return (
		<div { ...blockProps }>
			<TextInput { ...bind.text( 'text' ) } tagName="p" />
		</div>
	);
}
