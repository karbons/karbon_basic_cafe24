// SEO 기본 설정
export const seoConfig = {
    siteName: '그누보드5',
    siteUrl: 'https://example.com', // 실제 도메인으로 변경
    defaultTitle: '그누보드5 - 커뮤니티 플랫폼',
    defaultDescription: '그누보드5 기반 커뮤니티 사이트입니다. 다양한 정보를 공유하고 소통해보세요.',
    defaultImage: '/images/og-default.png', // OG 이미지 경로
    twitterHandle: '@gnuboard', // 트위터 핸들 (선택)
    locale: 'ko_KR',
};

// 페이지별 SEO 설정 헬퍼
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

// HTML 태그 제거 (description용)
export function stripHtml(html: string): string {
    return html.replace(/<[^>]*>/g, '').trim();
}

// 텍스트 길이 제한 (description용)
export function truncate(text: string, maxLength: number = 160): string {
    const stripped = stripHtml(text);
    if (stripped.length <= maxLength) return stripped;
    return stripped.substring(0, maxLength - 3) + '...';
}
