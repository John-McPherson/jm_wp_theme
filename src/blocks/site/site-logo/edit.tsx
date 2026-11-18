import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ServerSideRender } from '@wordpress/server-side-render';

import Sidebar from '../../../components/Sidebar';

type SiteLogoAttributes = {
	className?: string;
};

type EditProps = {
	attributes: SiteLogoAttributes;
};

export default function Edit( { attributes }: EditProps ) {
	const blockProps = useBlockProps();

	return (
		<>
			<Sidebar>
				<Sidebar.Section title={ __( 'Logo', 'jmc-theme' ) }>
					<p>
						{ __(
							'Choose the logo colours under Styles. Follow palette adapts to the section or footer it sits in; Brand and Mono dark are for light backgrounds, Mono light for dark ones.',
							'jmc-theme'
						) }
					</p>
					<p>
						{ __(
							'Screen readers announce the logo link with the Site Title.',
							'jmc-theme'
						) }
					</p>
				</Sidebar.Section>
			</Sidebar>

			<div { ...blockProps }>
				{ /* The server render keeps the preview identical to the site; Disabled keeps the home link from leaving the editor. */ }
				<Disabled>
					<ServerSideRender
						block="jmc/site-logo"
						attributes={ attributes }
					/>
				</Disabled>
			</div>
		</>
	);
}
