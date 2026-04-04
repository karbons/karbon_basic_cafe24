export const seoConfig = {
    siteName: 'KARBON BUILDER',
    siteUrl: 'https://karbon.kr',
    defaultTitle: 'KARBON BUILDER',
    defaultDescription: 'KARBON BUILDER에서 다양한 서비스를 이용해보세요.',
    defaultImage: '/images/og-default.png',
    twitterHandle: '@karbonbuilder',
    locale: 'ko_KR',
};

export function generatePageSeo(options: {
    title?: string;
    description?: string;
    image?: string;
    url?: string;
    type?: 'website' | 'article';
    noindex?: boolean;
}) {
    const title = options.title
        ? `${options.title} | ${seoConfig.siteName}`
        : seoConfig.defaultTitle;

    return {
        title,
        description: options.description || seoConfig.defaultDescription,
        canonical: options.url || seoConfig.siteUrl,
        openGraph: {
            type: options.type || 'website',
            url: options.url || seoConfig.siteUrl,
            title,
            description: options.description || seoConfig.defaultDescription,
            images: [
                {
                    url: options.image || seoConfig.defaultImage,
                    width: 1200,
                    height: 630,
                    alt: title,
                }
            ],
            siteName: seoConfig.siteName,
            locale: seoConfig.locale,
        },
        twitter: {
            cardType: 'summary_large_image',
            handle: seoConfig.twitterHandle,
            site: seoConfig.twitterHandle,
        },
        ...(options.noindex && {
            robots: 'noindex,nofollow',
        }),
    };
}

export function stripHtml(html: string): string {
    return html.replace(/<[^>]*>/g, '').trim();
}

export function truncate(text: string, maxLength: number = 160): string {
    const stripped = stripHtml(text);
    if (stripped.length <= maxLength) return stripped;
    return stripped.substring(0, maxLength - 3) + '...';
}