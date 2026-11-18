import { siWhatsapp } from 'simple-icons';

/**
 * The WhatsApp logo (Simple Icons) for editor UI: block variation and menu icons.
 *
 * Filled with `currentColor` so it follows the editor's and site's colours,
 * like the frontend `jmc/whatsapp` icon. Decorative: the surrounding label
 * names the action.
 */
const WhatsAppIcon = (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 24 24"
		width="24"
		height="24"
		fill="currentColor"
		aria-hidden="true"
		focusable="false"
	>
		<path d={ siWhatsapp.path } />
	</svg>
);

export default WhatsAppIcon;
