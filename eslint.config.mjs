import wordpress from '@wordpress/eslint-plugin';

export default [
	{
		ignores: [
			'build/**',
			'dist/**',
			'node_modules/**',
			'vendor/**',
			'coverage/**',
			'artifacts/**',
			'playwright-report/**',
			'test-results/**',
			'.claude/**',
		],
	},
	...wordpress.configs.recommended,
	{
		rules: {
			// Every translation call must use the theme's text domain.
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'jmc-theme' },
			],
		},
	},
];
