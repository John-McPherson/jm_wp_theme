import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

import bindFields from '../../../utils/bindFields';
import ButtonLink from '../../../components/ButtonLink';
import Sidebar from '../../../components/Sidebar';
import SelectInput from '../../../components/SelectInput';

const VARIANT_CLASSES = {
	primary: '',
	secondary: 'jmc-button__secondary',
};

export default function Edit( { attributes, setAttributes } ) {
	const { className, buttonType } = attributes;

	const blockProps = useBlockProps( {} );

	const bind = bindFields( attributes, setAttributes );

	const classes = [ className, VARIANT_CLASSES[ buttonType ] ]
		.filter( Boolean )
		.join( ' ' );

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Button Type', 'jmc-theme' ) }>
					<SelectInput
						{ ...bind.select( 'buttonType' ) }
						type="style"
					/>
				</Sidebar.Section>
			</Sidebar>
			<div { ...blockProps }>
				<ButtonLink
					text={ bind.text( 'text' ) }
					link={ bind.link( 'link' ) }
					className={ classes }
				/>
			</div>
		</>
	);
}
