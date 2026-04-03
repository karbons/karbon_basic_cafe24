import type { Handle } from '@sveltejs/kit';
import { redirect } from '@sveltejs/kit';

const supportedLocales = ['ko', 'en'];
const defaultLocale = 'ko';

export const handle: Handle = async ({ event, resolve }) => {
	const { cookies, url } = event;

	if (!url.pathname.startsWith('/app')) {
		return resolve(event);
	}

	const segments = url.pathname.split('/').filter(Boolean);
	const appType = segments[0];
	const lang = segments[1];

	if (supportedLocales.includes(lang)) {
		cookies.set('lang', lang, {
			path: '/',
			maxAge: 60 * 60 * 24 * 365,
			sameSite: 'lax',
			secure: true,
			httpOnly: false
		});
		return resolve(event);
	}

	let targetLang = defaultLocale;

	const cookieLang = cookies.get('lang');
	if (cookieLang && supportedLocales.includes(cookieLang)) {
		targetLang = cookieLang;
	} else {
		const acceptLang = event.request.headers.get('accept-language');
		if (acceptLang) {
			const browserLang = acceptLang.split(',')[0].split('-')[0].toLowerCase();
			if (supportedLocales.includes(browserLang)) {
				targetLang = browserLang;
			}
		}
	}

	const restOfPath = segments.slice(1).join('/');
	const redirectUrl = restOfPath 
		? `/${appType}/${targetLang}/${restOfPath}`
		: `/${appType}/${targetLang}`;

	throw redirect(302, redirectUrl);
};
