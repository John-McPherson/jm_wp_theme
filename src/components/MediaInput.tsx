import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type MediaInputTypes = 'image' | 'video' | 'audio';

type MediaInputProps = {
	value: string | number | null;
	type?: MediaInputTypes;
	set: ( media: { id: number; url: string } ) => void;
	className?: string;
};

const labels = {
	image: {
		select: __( 'Select image', 'jmc-theme' ),
		replace: __( 'Replace image', 'jmc-theme' ),
	},
	video: {
		select: __( 'Select video', 'jmc-theme' ),
		replace: __( 'Replace video', 'jmc-theme' ),
	},
	audio: {
		select: __( 'Select audio', 'jmc-theme' ),
		replace: __( 'Replace audio', 'jmc-theme' ),
	},
};

const MediaInput = ( {
	value,
	type = 'image',
	set,
	className = '',
}: MediaInputProps ) => {
	return (
		<MediaUploadCheck>
			<MediaUpload
				onSelect={ set }
				type={ type }
				allowedTypes={ [ type ] }
				className={ className }
				render={ ( { open } ) => (
					<Button variant="primary" onClick={ open }>
						{ value
							? labels[ type ].replace
							: labels[ type ].select }
					</Button>
				) }
			/>
		</MediaUploadCheck>
	);
};

export default MediaInput;
