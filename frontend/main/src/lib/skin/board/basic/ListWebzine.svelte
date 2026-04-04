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

<div class="flex flex-col divide-y">
    {#each list as item}
        <div class="flex gap-4 p-4 hover:bg-muted/30 transition-colors group">
            <!-- Thumbnail Area -->
            <a
                href="{base}/bbs/{board.bo_table}/{item.wr_id}"
                class="shrink-0 w-[120px] h-[90px] md:w-[180px] md:h-[120px] bg-slate-100 rounded-md overflow-hidden flex items-center justify-center relative border"
            >
                {#if item.thumbnail}
                    <img
                        src={resolveImageUrl(item.thumbnail)}
                        alt={item.thumbnail_alt || item.wr_subject}
                        class="absolute inset-0 w-full h-full object-cover"
                        loading="lazy"
                    />
                {:else}
                    <div class="text-slate-300 flex flex-col items-center">
                        <ImageIcon class="w-8 h-8 opacity-20" />
                    </div>
                {/if}
            </a>

            <!-- Content Area -->
            <div class="flex flex-col flex-1 justify-between py-1">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        {#if board.bo_use_category && item.ca_name}
                            <Badge
                                variant="outline"
                                class="text-[10px] h-5 font-normal"
                                >{item.ca_name}</Badge
                            >
                        {/if}
                        <span class="text-xs text-slate-500"
                            >{item.wr_datetime.substring(2, 10)}</span
                        >
                    </div>
                    <h3 class="font-bold text-lg mb-1 leading-snug">
                        <a
                            href="{base}/bbs/{board.bo_table}/{item.wr_id}"
                            class="group-hover:text-primary transition-colors"
                        >
                            {item.wr_subject}
                        </a>
                        {#if item.wr_comment > 0}
                            <span class="text-sm text-primary ml-1 align-top"
                                >[{item.wr_comment}]</span
                            >
                        {/if}
                    </h3>
                    <p class="text-sm text-muted-foreground line-clamp-2">
                        {item.wr_content
                            ? item.wr_content
                                  .replace(/<[^>]*>?/gm, "")
                                  .substring(0, 100)
                            : ""}
                    </p>
                </div>
                <div
                    class="flex items-center gap-4 text-xs text-slate-500 mt-2"
                >
                    <span>{item.wr_name}</span>
                    <span>Hit {item.wr_hit}</span>
                </div>
            </div>
        </div>
    {:else}
        <div class="p-8 text-center text-muted-foreground">
            게시글이 없습니다.
        </div>
    {/each}
</div>
