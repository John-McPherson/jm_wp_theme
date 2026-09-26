import wordpress from '@wordpress/eslint-plugin';

export default [
	{
		ignores: [
			'build/**',
			'dist/**',
			'node_modules/**',
			'vendor/**',
			'coverage/**',
			'playwright-report/**',
			'test-results/**',
		],
	},
	...wordpress.configs.recommended,
];
