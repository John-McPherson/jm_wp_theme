import { registerPlugin } from '@wordpress/plugins';

import ServiceIconPanel from './ServiceIconPanel';
import { readServicesSettings } from './utils';

declare global {
	interface Window {
		jmcServices?: unknown;
	}
}

const settings = readServicesSettings( window.jmcServices );

registerPlugin( 'jmc-service-icon', {
	render: () => <ServiceIconPanel settings={ settings } />,
} );
