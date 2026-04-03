import { register, init, getLocaleFromNavigator, locale, waitLocale } from 'svelte-i18n';

export const supportedLocales = ['ko', 'en'];

let initialized = false;

register('ko', () => import('./ko.json'));
register('en', () => import('./en.json'));

export function initLocale(initialLocale?: string): void {
	if (initialized) return;
	initialized = true;

	const validLocale = initialLocale && supportedLocales.includes(initialLocale)
		? initialLocale
		: null;

	const targetLocale = validLocale || getLocaleFromNavigator() || 'ko';

	init({
		fallbackLocale: 'ko',
		initialLocale: targetLocale
	});
}

export async function initLocaleAndWait(initialLocale?: string): Promise<void> {
	if (initialized) return;
	initialized = true;

	const validLocale = initialLocale && supportedLocales.includes(initialLocale)
		? initialLocale
		: null;

	const targetLocale = validLocale || getLocaleFromNavigator() || 'ko';

	init({
		fallbackLocale: 'ko',
		initialLocale: targetLocale
	});

	await waitLocale();
}

export { locale };
