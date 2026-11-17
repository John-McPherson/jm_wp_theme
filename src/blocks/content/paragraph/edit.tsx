import { useBlockProps } from '@wordpress/block-editor';

import TextInput from '../../../components/TextInput';
import bindFields from '../../../utils/bindFields';

const PARAGRAPH_VARIANT_CLASSES = {
	default: '',
	label: 'jmc-label',
} as const;

const CONTEXT_VARIANT_CLASSES = {
	default: '',
	hero: 'jmc-hero__text-para',
} as const;

type ParagraphVariant = keyof typeof PARAGRAPH_VARIANT_CLASSES;
type ContextVariant = keyof typeof CONTEXT_VARIANT_CLASSES;

export default function Edit( { attributes, setAttributes, context } ) {
	const bind = bindFields( attributes, setAttributes );

	const paragraphVariant = ( attributes.variant ??
		'default' ) as ParagraphVariant;

	const contextVariant = ( context[ 'jmc/variant' ] ??
		'default' ) as ContextVariant;

	const blockProps = useBlockProps( {
		className: [
			'jmc-paragraph',
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
