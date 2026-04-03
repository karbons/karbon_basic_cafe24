import adapter from '@sveltejs/adapter-static';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			pages: 'build',
			assets: 'build',
			fallback: 'index.html',
			precompress: false,
			strict: true
		}),
		paths: {
			base: '/main'
		},
		prerender: {
			handleHttpError: ({ path, referrer, message }) => {
				if (message.includes('404')) {
					console.warn(`Warning: ${path} not found (linked from ${referrer})`);
					return;
				}
				throw new Error(message);
			},
			handleUnseenRoutes: 'ignore'
		}
	},
	vitePlugin: {
		dynamicCompileOptions: ({ filename }) =>
			filename.includes('node_modules') ? undefined : { runes: true }
	}
};

export default config;
