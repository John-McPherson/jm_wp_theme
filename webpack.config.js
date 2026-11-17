/**
 * Extend the `@wordpress/scripts` build with non-block editor entry points.
 *
 * Block scripts are still discovered from block.json files.
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: () => ( {
		...defaultConfig.entry(),
		'editor/service-icon': path.resolve(
			__dirname,
			'src/editor/service-icon/index.tsx'
		),
	} ),
};
