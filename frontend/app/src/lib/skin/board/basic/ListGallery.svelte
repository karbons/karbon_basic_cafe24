<script lang="ts">
    import { Badge } from "$lib/ui/badge";
    import { Image as ImageIcon } from "lucide-svelte";
    import { resolveImageUrl } from "$lib/util/image";
    import type { BoardConfig, Write } from "$lib/type/board";
import { base } from '$app/paths';

    interface Props {
        board: BoardConfig;
        list: Write[];
    }

    let { board, list }: Props = $props();
</script>

<div
    class="grid gap-4 md:gap-6 p-0 md:p-6"
    style="grid-template-columns: repeat(auto-fill, minmax({board.bo_gallery_width ||
        250}px, 1fr));"
>
    {#each list as item}
        <div
            class="group border rounded-lg overflow-hidden hover:shadow-lg transition-all bg-card text-card-foreground"
        >
            <a
                href="{base}/bbs/{board.bo_table}/{item.wr_id}"
                class="block overflow-hidden relative"
                style="aspect-ratio: {board.bo_gallery_width ||
                    4}/{board.bo_gallery_height || 3};"
            >
                {#if item.thumbnail}
                    <img
                        src={resolveImageUrl(item.thumbnail)}
                        alt={item.thumbnail_alt || item.wr_subject}
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform"
                        loading="lazy"
                    />
                {:else}
                    <div
                        class="absolute inset-0 flex flex-col items-center justify-center bg-slate-100 text-slate-300"
                    >
                        <ImageIcon class="w-12 h-12 opacity-20 mb-2" />
                        <span class="text-xs font-medium">No Image</span>
                    </div>
                {/if}
                <div
                    class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors"
                ></div>
            </a>
            <div class="p-4">
                {#if board.bo_use_category && item.ca_name}
                    <Badge variant="secondary" class="mb-2 text-xs font-normal"
                        >{item.ca_name}</Badge
                    >
                {/if}
                <div class="font-bold truncate mb-2 text-lg">
                    <a
                        href="{base}/bbs/{board.bo_table}/{item.wr_id}"
                        class="hover:underline"
                    >
                        {item.wr_subject}
                    </a>
                    {#if item.wr_comment > 0}
                        <span class="text-xs text-primary ml-1"
                            >[{item.wr_comment}]</span
                        >
                    {/if}
                </div>
                <div
                    class="flex justify-between items-center text-xs text-muted-foreground mt-4"
                >
                    <span class="flex items-center gap-1">
                        {item.wr_name}
                    </span>
                    <span>{item.wr_datetime.substring(2, 10)}</span>
                </div>
            </div>
        </div>
    {:else}
        <div
            class="col-span-full h-24 flex items-center justify-center text-muted-foreground"
        >
            게시글이 없습니다.
        </div>
    {/each}
</div>
