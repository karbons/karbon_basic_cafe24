import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [sveltekit()],
	server: {
		proxy: {
			'/gnuboard': {
				target: 'http://localhost',
				changeOrigin: true,
				secure: false
			}
		},
		fs: {
			allow: ['..']
		}
	}
});
