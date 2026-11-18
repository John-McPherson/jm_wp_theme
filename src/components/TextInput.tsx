import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

type TextTag = 'h1' | 'h2' | 'h3' | 'h4' | 'h5' | 'h6' | 'p' | 'a' | 'span';

type TextInputProps = {
	value: string;
	set: ( value: string ) => void;
	tagName?: TextTag;
	placeholder?: string;
	className?: string;
	// Rich-text formats such as 'core/bold'; none by default.
	allowedFormats?: string[];
	// Attribute key, so the editor can split and merge blocks at this field.
	identifier?: string;
};

const NO_FORMATS: string[] = [];

const TextInput = ( {
	value,
	set,
	tagName = 'p',
	placeholder,
	className = '',
	allowedFormats = NO_FORMATS,
	identifier,
}: TextInputProps ) => {
	return (
		<RichText
			tagName={ tagName }
			identifier={ identifier }
			allowedFormats={ allowedFormats }
			value={ value }
			onChange={ set }
			placeholder={ placeholder ?? __( 'Enter text here', 'jmc-theme' ) }
			className={ className }
		/>
	);
};

export default TextInput;
