const path = require('path');
const defaultConfig = require("@wordpress/scripts/config/webpack.config");

const reactJSXRuntimePolyfill = {
	entry: {
		'react-jsx-runtime': {
			import: 'react/jsx-runtime',
		},
	},
	output: {
		path: path.resolve(__dirname, 'assets/js'),
		filename: 'react-jsx-runtime.js',
		library: {
			name: 'ReactJSXRuntime',
			type: 'window',
		},
	},
	externals: {
		react: 'React',
	},
};

module.exports = [
	{
		...defaultConfig,
		output: {
			...defaultConfig.output,
			filename: '[name].[contenthash].js',
			chunkFilename: '[name].[contenthash].js',
		},
		resolve: {
			...defaultConfig.resolve,
			fallback: {
				"path": require.resolve("path-browserify"),
			},
		},
		module: {
			...defaultConfig.module,
			rules: [
				...defaultConfig.module.rules,
				{
					test: /\.node$/,
					loader: 'node-loader',
				},
			],
		},
	},
	reactJSXRuntimePolyfill
];
