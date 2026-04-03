import type { RequestHandler } from '@sveltejs/kit';

// 정적 빌드 시 미리 렌더링
export const prerender = true;

// 동적 사이트맵 생성
export const GET: RequestHandler = async ({ url, fetch }) => {
    const baseUrl = url.origin;

    // 정적 페이지 목록
    const staticPages = [
        { loc: '/', priority: 1.0, changefreq: 'daily' },
        { loc: '/content/company', priority: 0.8, changefreq: 'monthly' },
        { loc: '/content/service', priority: 0.8, changefreq: 'monthly' },
    ];

    // 게시판 목록 가져오기 (API 호출)
    let boardPages: { loc: string; priority: number; changefreq: string }[] = [];
    try {
        // SvelteKit fetch는 서버사이드에서도 동일 origin 요청을 지원
        const boardsRes = await fetch(`${baseUrl}/api/boards`);
        if (boardsRes.ok) {
            const response = await boardsRes.json();
            // API 응답 구조: { code, data: { boards } }
            const boards = response.data?.boards || response.boards || [];
            boardPages = boards.map((board: { bo_table: string }) => ({
                loc: `/bbs/${board.bo_table}`,
                priority: 0.7,
                changefreq: 'daily'
            }));
        } else {
            console.error('Failed to fetch boards:', boardsRes.status, await boardsRes.text());
        }
    } catch (e) {
        console.error('Failed to fetch boards for sitemap:', e);
    }

    const allPages = [...staticPages, ...boardPages];

    const sitemap = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${allPages.map(page => `  <url>
    <loc>${baseUrl}${page.loc}</loc>
    <changefreq>${page.changefreq}</changefreq>
    <priority>${page.priority}</priority>
  </url>`).join('\n')}
</urlset>`;

    return new Response(sitemap, {
        headers: {
            'Content-Type': 'application/xml',
            'Cache-Control': 'max-age=3600'
        }
    });
};
