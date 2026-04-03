import type { Handle } from '@sveltejs/kit';
import { redirect } from '@sveltejs/kit';

const supportedLocales = ['ko', 'en'];
const defaultLocale = 'ko';

export const handle: Handle = async ({ event, resolve }) => {
	const { cookies, url } = event;

	// Skip hook during prerendering
	if (url.pathname.startsWith('/main/_app') || url.pathname.includes('.')) {
		return resolve(event);
	}

	// Only handle requests for /main/*
	if (!url.pathname.startsWith('/main')) {
		return resolve(event);
	}

	const segments = url.pathname.split('/').filter(Boolean);
	const appType = segments[0]; // 'main'
	const lang = segments[1]; // 'ko', 'en', or undefined

	// Check if the URL has a valid language prefix
	if (supportedLocales.includes(lang)) {
		// Valid language - set cookie and continue
		cookies.set('lang', lang, {
			path: '/',
			maxAge: 60 * 60 * 24 * 365,
			sameSite: 'lax',
			secure: true,
			httpOnly: false
		});
		return resolve(event);
	}

	// No valid language prefix - determine language and redirect
	let targetLang = defaultLocale;

	// Priority: Cookie > Browser > Default
	const cookieLang = cookies.get('lang');
	if (cookieLang && supportedLocales.includes(cookieLang)) {
		targetLang = cookieLang;
	} else {
		// Check Accept-Language header
		const acceptLang = event.request.headers.get('accept-language');
		if (acceptLang) {
			const browserLang = acceptLang.split(',')[0].split('-')[0].toLowerCase();
			if (supportedLocales.includes(browserLang)) {
				targetLang = browserLang;
			}
		}
	}

	// Build redirect URL: /main/{lang}/{rest}
	const restOfPath = segments.slice(1).join('/');
	const redirectUrl = restOfPath 
		? `/${appType}/${targetLang}/${restOfPath}`
		: `/${appType}/${targetLang}`;

	throw redirect(302, redirectUrl);
};
