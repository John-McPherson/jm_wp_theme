import { __ } from '@wordpress/i18n';

import { useBlockProps } from '@wordpress/block-editor';

import bindFields from '../../../utils/bindFields';
import useThemeIcons from '../../../utils/useThemeIcons';

import TextInput from '../../../components/TextInput';

import { toListIconName, toListStyle } from '../list/options';

type Attributes = {
	text: string;
};

type Props = {
	attributes: Attributes;
	setAttributes: ( attributes: Partial< Attributes > ) => void;
	context: {
		'jmc/listStyle'?: string;
		'jmc/listIcon'?: string;
	};
};

// Inline formatting only; must match the tags render.php allows.
const ALLOWED_FORMATS = [ 'core/bold', 'core/italic', 'core/link' ];

export default function Edit( { attributes, setAttributes, context }: Props ) {
	const bind = bindFields( attributes, setAttributes );

	const listStyle = toListStyle( context[ 'jmc/listStyle' ] );
	const iconName = toListIconName( context[ 'jmc/listIcon' ] );
	const icons = useThemeIcons();

	const blockProps = useBlockProps( {
		className: 'jmc-list__item',
	} );

	return (
		<li { ...blockProps }>
			{ 'icon' === listStyle && (
				// Mirrors render.php: a decorative icon before the text.
				<span
					className="jmc-list__icon"
					aria-hidden="true"
					dangerouslySetInnerHTML={ {
						__html: icons[ iconName ] ?? '',
					} }
				/>
			) }
			<TextInput
				{ ...bind.text( 'text' ) }
				tagName="span"
				className="jmc-list__text"
				identifier="text"
				allowedFormats={ ALLOWED_FORMATS }
				placeholder={ __( 'List item', 'jmc-theme' ) }
			/>
		</li>
	);
}
