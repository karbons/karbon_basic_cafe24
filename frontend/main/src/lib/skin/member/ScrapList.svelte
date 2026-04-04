<script lang="ts">
    import { apiGet } from "$lib/api";
import { base } from '$app/paths';

    interface Scrap {
        ms_id: number;
        bo_table: string;
        wr_id: number;
        ms_datetime: string;
        wr_subject?: string; // API가 제목을 준다면 좋음
    }

    let scraps = $state<Scrap[]>([]);
    let loading = $state(true);
    let error = $state("");

    async function loadScraps() {
        try {
            loading = true;
            const data = await apiGet<{ scraps: Scrap[] }>("/member/scrap");
            scraps = data.scraps;
        } catch (e: any) {
            error = e.message || "스크랩을 불러올 수 없습니다.";
        } finally {
            loading = false;
        }
    }

    $effect(() => {
        loadScraps();
    });
</script>

<div class="scrap-view">
    {#if loading}
        <div class="py-8 text-center text-gray-500">로딩 중...</div>
    {:else if error}
        <div class="text-red-500 bg-red-50 p-4 rounded-lg">{error}</div>
    {:else if scraps.length === 0}
        <div
            class="py-12 text-center text-gray-400 border border-dashed border-gray-300 rounded-lg"
        >
            스크랩한 게시물이 없습니다.
        </div>
    {:else}
        <div class="space-y-3">
            {#each scraps as scrap}
                <a
                    href="{base}/bbs/{scrap.bo_table}/{scrap.wr_id}"
                    class="block p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors"
                >
                    <div class="flex justify-between items-start mb-1">
                        <span
                            class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded mb-2"
                            >{scrap.bo_table}</span
                        >
                        <span class="text-xs text-gray-400"
                            >{scrap.ms_datetime}</span
                        >
                    </div>
                    <div class="font-medium text-gray-800">
                        {scrap.wr_subject || `게시글 #${scrap.wr_id}`}
                    </div>
                </a>
            {/each}
        </div>
    {/if}
</div>
