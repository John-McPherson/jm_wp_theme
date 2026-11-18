import { Notice } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { ReactNode } from 'react';

type SiteSettingProps< Value > = {
	option: string;
	notice?: string;
	children: ( value: Value, setValue: ( value: Value ) => void ) => ReactNode;
};

/**
 * Read and edit a site setting (Settings → Business) from a block.
 *
 * Edits are saved with the site settings when the post is saved, so they
 * change everywhere the setting is used. Users who can't manage options can't
 * read the setting, so they get a notice instead of the controls.
 *
 * @param props          Component props.
 * @param props.option   Registered option name, exposed on the REST settings endpoint.
 * @param props.notice   Message shown when the setting can't be read.
 * @param props.children Renders the controls for the current value.
 */
export default function SiteSetting< Value >( {
	option,
	notice,
	children,
}: SiteSettingProps< Value > ) {
	const [ value, setValue ] = useEntityProp( 'root', 'site', option );

	if ( undefined === value ) {
		return (
			<Notice status="info" isDismissible={ false }>
				{ notice ??
					__(
						'These details come from Settings → Business. Ask an administrator to change them.',
						'jmc-theme'
					) }
			</Notice>
		);
	}

	return <>{ children( value as Value, setValue ) }</>;
}
