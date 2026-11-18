/**
 * Vitest configuration for the theme's JavaScript unit tests.
 *
 * Run through `npm run test:unit` (`wp-scripts test-unit-js`).
 */
import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		environment: 'jsdom',
		globals: false,
		restoreMocks: true,
		setupFiles: [ './tests/unit/setup.ts' ],
		include: [
			'src/**/*.test.{js,jsx,ts,tsx}',
			'tests/unit/**/*.test.{js,jsx,ts,tsx}',
			'scripts/**/*.test.{js,jsx,ts,tsx}',
		],
	},
} );
