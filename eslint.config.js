const wordpress = require( '@wordpress/eslint-plugin' );
const globals = require( 'globals' );

module.exports = [
	...wordpress.configs.es5,
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.jquery,
			},
		},
	},
];
