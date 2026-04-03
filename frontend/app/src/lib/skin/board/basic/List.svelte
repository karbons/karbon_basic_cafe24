<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import * as Pagination from "$lib/ui/pagination";
    import {
        Search,
        PenLine,
        LayoutGrid,
        List as ListIcon,
        Rows,
    } from "lucide-svelte";
    import { onMount } from "svelte";
    import type { BoardConfig, Write } from "$lib/type/board";

    // View Components
    import ListGallery from "./ListGallery.svelte";
    import ListWebzine from "./ListWebzine.svelte";
    import ListTable from "./ListTable.svelte";

    interface Props {
        board: BoardConfig;
        list: Write[];
        total_count: number;
        page_current: number;
    }

    let { board, list = [], total_count = 0, page_current = 1 }: Props = $props();

    // View Mode: 'list' | 'gallery' | 'webzine'
    let viewMode = $state("list");

    onMount(() => {
        if (!board) return;
        // Load saved view mode
        const savedMode = localStorage.getItem(
            `board_view_mode_${board.bo_table}`,
        );
        if (savedMode) {
            viewMode = savedMode;
        } else if (board.bo_gallery_width > 0) {
            // Default to gallery if configured and no user pref
            viewMode = "gallery";
        }
    });

    function setViewMode(mode: string) {
        if (!board) return;
        viewMode = mode;
        localStorage.setItem(`board_view_mode_${board.bo_table}`, mode);
    }

    import { apiGet } from "$lib/api";
import { base } from '$app/paths';

    // pagination helper
    let total_pages = $derived(board ? Math.ceil(total_count / board.bo_page_rows) : 0);

    // Infinite Scroll Logic
    let displayList = $state<Write[]>([]);
    let isLoadingMore = $state(false);
    let currentPage = $state(page_current);
    let hasMore = $state(false);
    let loadMoreTrigger: HTMLElement;

    // Props가 변경되면(페이지 이동 등) 리스트 초기화
    $effect(() => {
        if (!list) return;
        // 공지사항을 최상단에 정렬
        const notices = list.filter((item) => item.wr_is_notice);
        const normal = list.filter((item) => !item.wr_is_notice);
        displayList = [...notices, ...normal];
        currentPage = page_current;
        hasMore = page_current < total_pages;
    });

    async function loadMore() {
        if (!board || isLoadingMore || !hasMore) return;
        isLoadingMore = true;

        try {
            const nextPage = currentPage + 1;
            const data = await apiGet<{ writes: Write[] }>(
                `/board/${board.bo_table}?page=${nextPage}`,
            );
            if (data.writes && data.writes.length > 0) {
                displayList = [...displayList, ...data.writes];
                currentPage = nextPage;
                hasMore = nextPage < total_pages;
            } else {
                hasMore = false;
            }
        } catch (e) {
            console.error("Failed to load more:", e);
        } finally {
            isLoadingMore = false;
        }
    }

    $effect(() => {
        if (viewMode !== "gallery" || !loadMoreTrigger) return;

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) {
                    loadMore();
                }
            },
            { threshold: 0.5 },
        );

        observer.observe(loadMoreTrigger);

        return () => {
            if (loadMoreTrigger) observer.unobserve(loadMoreTrigger);
        };
    });
</script>

<div class="space-y-4 pb-24 md:pb-0">
    {#if board}
        <!-- 상단 툴바 -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4 w-full md:w-auto justify-between">
                <div class="text-sm text-slate-500">
                    Total <span class="font-bold text-primary">{total_count}</span>
                </div>

                <!-- View Mode Toggles -->
                <div class="flex items-center border rounded-md p-1 bg-muted/20">
                    <button
                        class="p-1.5 rounded-sm hover:bg-background transition-colors {viewMode ===
                        'list'
                            ? 'bg-background shadow-sm text-primary'
                            : 'text-muted-foreground'}"
                        onclick={() => setViewMode("list")}
                        title="List View"
                    >
                        <ListIcon class="w-4 h-4" />
                    </button>
                    <button
                        class="p-1.5 rounded-sm hover:bg-background transition-colors {viewMode ===
                        'webzine'
                            ? 'bg-background shadow-sm text-primary'
                            : 'text-muted-foreground'}"
                        onclick={() => setViewMode("webzine")}
                        title="Webzine View"
                    >
                        <Rows class="w-4 h-4" />
                    </button>
                    <button
                        class="p-1.5 rounded-sm hover:bg-background transition-colors {viewMode ===
                        'gallery'
                            ? 'bg-background shadow-sm text-primary'
                            : 'text-muted-foreground'}"
                        onclick={() => setViewMode("gallery")}
                        title="Gallery View"
                    >
                        <LayoutGrid class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <Button
                href="{base}/bbs/{board.bo_table}/write"
                variant="default"
                size="sm"
                class="hidden md:inline-flex"
            >
                <PenLine class="w-4 h-4 mr-2" />
                글쓰기
            </Button>
        </div>

        <!-- 게시글 목록 -->
        <div
            class="rounded-md min-h-[200px] {viewMode === 'gallery'
                ? 'md:border border-0'
                : 'border'}"
        >
            {#if viewMode === "gallery"}
                <ListGallery {board} list={displayList} />

                <!-- 무한 스크롤 트리거 (모바일 & 갤러리 모드 전용) -->
                {#if isLoadingMore}
                    <div class="py-4 text-center text-sm text-gray-500">
                        Loading more...
                    </div>
                {:else if hasMore}
                    <div bind:this={loadMoreTrigger} class="h-10 md:hidden"></div>
                {/if}
            {:else if viewMode === "webzine"}
                <ListWebzine {board} list={displayList} />
            {:else}
                <ListTable {board} list={displayList} />
            {/if}
        </div>

        <!-- 하단 페이지네이션 및 검색 -->
        <div
            class="flex flex-col md:flex-row justify-between items-center gap-4 pt-4 {viewMode ===
            'gallery'
                ? 'hidden md:flex'
                : ''}"
        >
            <!-- 페이지네이션 -->
            <div class="flex gap-1">
                <Pagination.Root
                    count={total_count}
                    perPage={board.bo_page_rows}
                    page={page_current}
                >
                    {#snippet children({ pages, currentPage })}
                        <Pagination.Content>
                            <Pagination.Item>
                                <Pagination.PrevButton />
                            </Pagination.Item>
                            {#each pages as page (page.key)}
                                {#if page.type === "ellipsis"}
                                    <Pagination.Item>
                                        <Pagination.Ellipsis />
                                    </Pagination.Item>
                                {:else}
                                    <Pagination.Item>
                                        <Pagination.Link
                                            {page}
                                            isActive={currentPage === page.value}
                                        >
                                            {page.value}
                                        </Pagination.Link>
                                    </Pagination.Item>
                                {/if}
                            {/each}
                            <Pagination.Item>
                                <Pagination.NextButton />
                            </Pagination.Item>
                        </Pagination.Content>
                    {/snippet}
                </Pagination.Root>
            </div>

            <!-- 검색창 -->
            <form
                class="flex gap-2 w-full md:w-auto"
                action="/bbs/{board.bo_table}"
            >
                <div class="relative w-full md:w-[200px]">
                    <Search
                        class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground"
                    />
                    <Input
                        type="search"
                        name="stx"
                        placeholder="Search..."
                        class="pl-8"
                    />
                </div>
            </form>
        </div>

        <!-- 모바일 전용 플로팅 글쓰기 버튼 -->
        <Button
            href="{base}/bbs/{board.bo_table}/write"
            class="fixed bottom-20 right-6 z-40 w-14 h-14 rounded-full shadow-lg md:hidden flex items-center justify-center p-0"
        >
            <PenLine class="w-6 h-6" />
        </Button>
    {:else}
        <div class="p-8 text-center text-slate-500 border rounded-md">
            게시판 설정을 불러올 수 없습니다.
        </div>
    {/if}
</div>
