<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { 
        Search, 
        PenLine, 
        LayoutGrid, 
        List as ListIcon, 
        Rows,
        Clock,
        Eye,
        MessageSquare
    } from "lucide-svelte";
    import { onMount } from "svelte";
    import type { BoardConfig, Write } from "$lib/type/board";
    import { base } from '$app/paths';
    import { page } from "$app/state";
    import { goto } from "$app/navigation";

    interface Props {
        board: BoardConfig;
        list: Write[];
        total_count: number;
        page_current: number;
    }

    let { board, list = [], total_count = 0, page_current = 1 }: Props = $props();

    let searchQuery = $state("");

    function handleSearch(e: Event) {
        e.preventDefault();
        if (searchQuery.trim()) {
            goto(`${base}/bbs/${board.bo_table}?stx=${encodeURIComponent(searchQuery)}`);
        }
    }

    function formatDate(dateStr: string): string {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toISOString().split('T')[0];
    }
</script>

<div class="pb-24 md:pb-0">
    {#if board}
        <!-- 상단 툴바 -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="px-5 py-2.5 bg-secondary-50 rounded-xl text-secondary-900 font-bold border border-secondary-100 flex items-center gap-2">
                    전체 <span class="bg-primary-600 text-white text-[10px] px-1.5 py-0.5 rounded-sm">{total_count}</span>
                </div>
            </div>
            
            <div class="relative w-full md:w-80">
                <Search size={18} class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none" />
                <input 
                    type="text" 
                    bind:value={searchQuery}
                    placeholder="검색어를 입력하세요." 
                    onkeydown={(e) => e.key === 'Enter' && handleSearch(e)}
                    class="w-full pl-12 pr-4 py-3 bg-secondary-50 border border-secondary-100 rounded-xl focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm"
                />
            </div>
        </div>

        <!-- 게시글 목록 -->
        <div class="overflow-hidden border border-secondary-100 rounded-3xl">
            <!-- Desktop Table View -->
            <table class="w-full text-left hidden md:table">
                <thead class="bg-secondary-50 text-secondary-950 font-bold border-b border-secondary-100">
                    <tr>
                        <th class="px-8 py-5 text-sm w-20">번호</th>
                        <th class="px-8 py-5 text-sm">제목</th>
                        <th class="px-8 py-5 text-sm w-32">작성자</th>
                        <th class="px-8 py-5 text-sm w-40 text-center">조회수</th>
                        <th class="px-8 py-5 text-sm w-40 text-center">날짜</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-secondary-100/50">
                    {#each list as item, idx}
                        <tr class="hover:bg-primary-50/30 transition-colors group cursor-pointer" onclick={() => goto(`${base}/bbs/${board.bo_table}/${item.wr_id}`)}>
                            <td class="px-8 py-6 text-sm text-secondary-400">
                                {#if item.wr_is_notice}
                                    <span class="inline-block px-2 py-0.5 bg-primary-600 text-white text-[10px] font-bold rounded-sm">공지</span>
                                {:else}
                                    {(page_current - 1) * board.bo_page_rows + idx + 1}
                                {/if}
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    {#if item.ca_name}
                                        <span class="inline-block px-2.5 py-0.5 bg-secondary-100 text-[11px] font-bold text-secondary-600 rounded-sm">{item.ca_name}</span>
                                    {/if}
                                    <span class="font-medium text-secondary-900 group-hover:text-primary-600 transition-colors">{item.wr_subject}</span>
                                    {#if item.wr_comment}
                                        <span class="flex items-center gap-1 text-xs text-secondary-400">
                                            <MessageSquare size={12} />
                                            {item.wr_comment}
                                        </span>
                                    {/if}
                                </div>
                            </td>
                            <td class="px-8 py-6 text-sm text-secondary-600 italic">{item.wr_name}</td>
                            <td class="px-8 py-6 text-sm text-secondary-500 text-center flex items-center justify-center gap-1">
                                <Eye size={14} />
                                {item.wr_hit}
                            </td>
                            <td class="px-8 py-6 text-sm text-secondary-500 text-center">{formatDate(item.wr_datetime)}</td>
                        </tr>
                    {/each}
                </tbody>
            </table>

            <!-- Mobile View (Cards) -->
            <div class="grid grid-cols-1 md:hidden divide-y divide-secondary-100">
                {#each list as item, idx}
                    <div class="p-6 space-y-4 hover:bg-primary-50/30 transition-colors cursor-pointer" onclick={() => goto(`${base}/bbs/${board.bo_table}/${item.wr_id}`)}>
                        <div class="flex items-center justify-between">
                            {#if item.wr_is_notice}
                                <span class="inline-block px-2.5 py-0.5 bg-primary-600 text-white text-[11px] font-bold rounded-sm">공지</span>
                            {:else}
                                <span class="text-xs text-secondary-400">{(page_current - 1) * board.bo_page_rows + idx + 1}</span>
                            {/if}
                            <span class="text-xs text-secondary-400">{formatDate(item.wr_datetime)}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            {#if item.ca_name}
                                <span class="inline-block px-2.5 py-0.5 bg-secondary-100 text-[11px] font-bold text-secondary-600 rounded-sm">{item.ca_name}</span>
                            {/if}
                            <h3 class="font-bold text-secondary-950 group-hover:text-primary-600 transition-colors">{item.wr_subject}</h3>
                            {#if item.wr_comment}
                                <span class="flex items-center gap-1 text-xs text-secondary-400">
                                    <MessageSquare size={12} />
                                    {item.wr_comment}
                                </span>
                            {/if}
                        </div>
                        <div class="flex items-center gap-4 text-xs text-secondary-500">
                            <span class="flex items-center gap-1.5"><Clock size={14} /> {item.wr_name}</span>
                            <span class="flex items-center gap-1.5"><Eye size={14} /> {item.wr_hit}</span>
                        </div>
                    </div>
                {/each}
            </div>
        </div>

        <!-- 글쓰기 버튼 -->
        <div class="mt-8 flex justify-end">
            <Button href="{base}/bbs/{board.bo_table}/write" variant="default">
                <PenLine class="w-4 h-4 mr-2" />
                글쓰기
            </Button>
        </div>
    {:else}
        <div class="p-8 text-center text-slate-500 border rounded-md">
            게시판 설정을 불러올 수 없습니다.
        </div>
    {/if}
</div>