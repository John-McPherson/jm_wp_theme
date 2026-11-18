import { registerBlockType } from '@wordpress/blocks';
import WhatsAppIcon from '../../../components/WhatsAppIcon';

import Edit from './edit';
import metadata from './block.json';
import './style.scss';

// block.json icons can only name a Dashicon, so the WhatsApp logo is set here.
const variations = metadata.variations.map( ( variation ) =>
	'whatsapp' === variation.name
		? { ...variation, icon: WhatsAppIcon }
		: variation
);

registerBlockType( metadata.name, {
	...metadata,
	variations,
	edit: Edit,
	save: () => null,
} as any );
