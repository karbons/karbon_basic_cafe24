import { apiGet } from '$lib/api';
import type { PageLoad } from './$types';
import type { Content } from '$lib/type/content';

export const load: PageLoad = async ({ fetch }) => {
    try {
        const [provision, privacy, config] = await Promise.all([
            apiGet<Content>('/policy/terms', fetch),
            apiGet<Content>('/policy/privacy', fetch),
            apiGet<any>('/site', fetch)
        ]);

        return {
            provision,
            privacy,
            config
        };
    } catch (error: any) {
        console.error('Failed to load agreements:', error);
        return {
            provision: null,
            privacy: null,
            config: {},
            error: error.message
        };
    }
};
