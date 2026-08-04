import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

type TextTag = 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'a' | 'span';

type TextInputProps = {
	value: string;
	set: ( value: string ) => void;
	tagName?: TextTag;
	placeholder?: string;
	className?: string;
};

const TextInput = ( {
	value,
	set,
	tagName = 'p',
	placeholder,
	className = '',
}: TextInputProps ) => {
	return (
		<RichText
			tagName={ tagName }
			allowedFormats={ [] }
			value={ value }
			onChange={ set }
			placeholder={ placeholder ?? __( 'Enter text here', 'jmc-theme' ) }
			className={ className }
		/>
	);
};

export default TextInput;
