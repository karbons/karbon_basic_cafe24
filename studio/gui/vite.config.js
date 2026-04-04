import tailwindcss from '@tailwindcss/vite';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
	const env = loadEnv(mode, process.cwd(), '');
	const apiBaseUrl = env.VITE_API_BASE_URL || '/v1/fapi';
	const apiTarget = env.VITE_API_TARGET || 'https://karbon.kr';

	return {
		plugins: [tailwindcss(), sveltekit()],
		server: {
			proxy: {
				[apiBaseUrl]: {
					target: apiTarget,
					changeOrigin: true,
					secure: false,
					ws: false,
					cookieDomainRewrite: 'localhost',
					cookiePathRewrite: '/'
				}
			}
		}
	};
});