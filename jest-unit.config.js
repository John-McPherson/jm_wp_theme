module.exports = {
	preset: '@wordpress/jest-preset-default',
	testEnvironment: 'jsdom',
	testMatch: [
		'<rootDir>/src/**/*.test.[jt]s?(x)',
		'<rootDir>/tests/unit/**/*.test.[jt]s?(x)',
	],
	transform: {
		'^.+\\.[jt]sx?$': [
			'babel-jest',
			{
				presets: [ '@wordpress/babel-preset-default' ],
			},
		],
	},
};
