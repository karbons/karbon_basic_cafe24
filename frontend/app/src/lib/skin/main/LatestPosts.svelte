<script lang="ts">
    import { onMount } from "svelte";
    import { apiGet } from "$lib/api";
    import type { Write } from "$lib/type/board";
    import { Gallery } from "$lib/skin/latest/gallery";
    import { base } from "$app/paths";

    interface Props {
        boTable?: string;
        limit?: number;
        title?: string;
        subtitle?: string;
        showMoreLink?: boolean;
    }

    let {
        boTable = "gallery",
        limit = 3,
        title = "최신 소식",
        subtitle = "커뮤니티의 최신 글과 유용한 정보를 확인하세요.",
        showMoreLink = true,
    }: Props = $props();

    let posts = $state<Write[]>([]);
    let loading = $state(true);
    let error = $state<string | null>(null);

    onMount(async () => {
        try {
            const data = await apiGet<{
                list: Write[];
            }>(`/bbs/${boTable}?page=1&limit=${limit}`);
            posts = data.list || [];
        } catch (e: any) {
            error = e.message || "게시글을 불러올 수 없습니다.";
        } finally {
            loading = false;
        }
    });
</script>

<section class="py-16 md:py-24 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-10 md:mb-16">
            <h2
                class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 md:mb-4"
            >
                {title}
            </h2>
            <p class="text-base md:text-lg text-gray-600">
                {subtitle}
            </p>
        </div>

        <!-- Content -->
        {#if loading}
            <Gallery posts={[]} {boTable} loading={true} />
        {:else if error}
            <div class="text-center text-gray-500 py-12">
                <p>{error}</p>
            </div>
        {:else if posts.length === 0}
            <div class="text-center text-gray-500 py-12">
                <p>게시글이 없습니다.</p>
            </div>
        {:else}
            <Gallery {posts} {boTable} />
        {/if}

        <!-- More Link -->
        {#if showMoreLink && !loading && posts.length > 0}
            <div class="text-center mt-10 md:mt-12">
                <a
                    href="{base}/bbs/{boTable}"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 text-white rounded-full font-medium hover:bg-gray-800 transition-colors"
                >
                    더 보기
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </a>
            </div>
        {/if}
    </div>
</section>
