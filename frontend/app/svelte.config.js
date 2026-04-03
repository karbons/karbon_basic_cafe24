import adapter from '@sveltejs/adapter-static';
import { vitePreprocess } from '@sveltejs/vite-plugin-svelte';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	preprocess: vitePreprocess({
		postcss: true
	}),
	kit: {
		adapter: adapter({
			pages: 'build',
			assets: 'build',
			fallback: 'index.html',
			precompress: false,
			strict: true
		}),
		paths: {
			base: '/app'
		},
		prerender: {
			handleHttpError: ({ path, referrer, message }) => {
				// 404 에러를 경고로 처리 (빌드 실패 방지)
				if (message.includes('404')) {
					console.warn(`Warning: ${path} not found (linked from ${referrer})`);
					return;
				}
				// 다른 에러는 기본 동작 (throw)
				throw new Error(message);
			},
			handleUnseenRoutes: 'ignore' // 동적 라우트 무시 (예: /bbs/[bo_table])
		}
	}
};

export default config;
