import { initLocale } from '$lib/i18n';
import type { LayoutLoad } from './$types';

export const load: LayoutLoad = async ({ params }) => {
	const locale = params.lang || 'ko';
	initLocale(locale);

	return {
		locale
	};
};
