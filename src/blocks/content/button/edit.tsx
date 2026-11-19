import { useBlockProps } from '@wordpress/block-editor';

import ButtonEditor, {
	type ButtonAttributes,
} from '../../../components/ButtonEditor';

type EditProps = {
	attributes: ButtonAttributes & { className?: string };
	setAttributes: ( attributes: Partial< ButtonAttributes > ) => void;
};

export default function Edit( { attributes, setAttributes }: EditProps ) {
	const blockProps = useBlockProps( {} );

	return (
		<div { ...blockProps }>
			<ButtonEditor
				attributes={ attributes }
				setAttributes={ setAttributes }
				className={ attributes.className }
			/>
		</div>
	);
}
