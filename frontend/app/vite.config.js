import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';

const __dirname = dirname(fileURLToPath(import.meta.url));
const packageJson = JSON.parse(readFileSync(resolve(__dirname, 'package.json'), 'utf-8'));


export default defineConfig({
	plugins: [sveltekit()],
	define: {
		'__APP_VERSION__': JSON.stringify(packageJson.version)
	},
	server: {
		proxy: {
			'/gnu/api': {
				target: 'https://karbon.kr',
				changeOrigin: true,
				secure: false,
				ws: false, // WebSocket 비활성화
				cookieDomainRewrite: 'localhost', // 쿠키 도메인 재작성
				cookiePathRewrite: '/' // 쿠키 경로 재작성
			}
		},
		fs: {
			// SvelteKit 내부 파일 접근 허용
			allow: ['..']
		}
	}
});

