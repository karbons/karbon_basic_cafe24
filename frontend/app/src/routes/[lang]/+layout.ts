import { initLocale } from '$lib/i18n';
import type { LayoutLoad } from './$types';
import { browser } from '$app/environment';

export const prerender = true;
export const ssr = false;

export const load: LayoutLoad = async ({ params }) => {
	const locale = params.lang || 'ko';
	
	if (browser) {
		initLocale(locale);
	}

	return {
		locale
	};
};

