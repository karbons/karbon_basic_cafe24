import { register, init, getLocaleFromNavigator, locale } from 'svelte-i18n';

export const supportedLocales = ['ko', 'en'];

// Register locale loaders for lazy loading
register('ko', () => import('./ko.json'));
register('en', () => import('./en.json'));

/**
 * Initialize i18n with the given locale
 * @param initialLocale - Optional initial locale (e.g., 'ko', 'en')
 */
export function initLocale(initialLocale?: string): void {
	// Validate the initial locale
	const validLocale = initialLocale && supportedLocales.includes(initialLocale)
		? initialLocale
		: null;

	// Determine the locale to use
	const targetLocale = validLocale || getLocaleFromNavigator() || 'ko';

	// Initialize svelte-i18n
	init({
		fallbackLocale: 'ko',
		initialLocale: targetLocale
	});
}

export { locale };
