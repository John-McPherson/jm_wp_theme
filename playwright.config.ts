import { defineConfig, devices } from '@playwright/test';
import dotenv from 'dotenv';

dotenv.config( { path: '.env.e2e' } );

export default defineConfig( {
	testDir: './tests/e2e',
	globalSetup: './tests/e2e/setup/global-setup.ts',
	globalTeardown: './tests/e2e/setup/global-teardown.ts',
	outputDir: './artifacts/e2e/test-results',
	fullyParallel: false,
	forbidOnly: Boolean( process.env.CI ),
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	reporter: process.env.CI
		? [
				[ 'line' ],
				[
					'html',
					{
						outputFolder: './artifacts/e2e/report',
						open: 'never',
					},
				],
			]
		: [
				[ 'list' ],
				[
					'html',
					{
						outputFolder: './artifacts/e2e/report',
						open: 'never',
					},
				],
			],
	use: {
		baseURL: process.env.WP_BASE_URL ?? 'http://localhost:8888',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
	},

	/*
	 * Desktop projects run every test except those tagged `@phone`, which
	 * need touch. Phone projects emulate a phone (viewport, device scale,
	 * touch, mobile user agent and `isMobile`) and run every test except
	 * those tagged `@desktop`: Site Editor and admin journeys, and tests that
	 * set a desktop viewport or rely on a mouse or Tab key. Firefox has no
	 * phone project, because Playwright cannot emulate `isMobile` in it.
	 */
	projects: [
		{
			name: 'chromium',
			use: {
				...devices[ 'Desktop Chrome' ],
			},
			grepInvert: /@phone/,
		},
		{
			name: 'firefox',
			use: {
				...devices[ 'Desktop Firefox' ],
			},
			grepInvert: /@phone/,
		},
		{
			name: 'webkit',
			use: {
				...devices[ 'Desktop Safari' ],
			},
			grepInvert: /@phone/,
		},
		{
			name: 'phone-chromium',
			use: {
				...devices[ 'Pixel 7' ],
			},
			grepInvert: /@desktop/,
		},
		{
			name: 'phone-webkit',
			use: {
				...devices[ 'iPhone 15' ],
			},
			grepInvert: /@desktop/,
		},
	],
} );
