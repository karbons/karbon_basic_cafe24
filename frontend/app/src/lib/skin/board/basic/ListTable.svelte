<script lang="ts">
    import { page } from "$app/stores";
    import * as Table from "$lib/ui/table";
    import { Badge } from "$lib/ui/badge";
    import type { BoardConfig, Write } from "$lib/type/board";
import { base } from '$app/paths';

    interface Props {
        board: BoardConfig;
        list: Write[];
    }

    let { board, list }: Props = $props();

    // 제목 길이 제한 (bo_subject_len)
    function truncateSubject(subject: string, isMobile = false): string {
        const maxLen = isMobile
            ? board.bo_mobile_subject_len || 30
            : board.bo_subject_len || 60;
        if (subject.length <= maxLen) return subject;
        return subject.substring(0, maxLen) + "...";
    }
</script>

<Table.Root>
    <Table.Header>
        <Table.Row>
            <Table.Head class="w-[60px] text-center">No</Table.Head>
            {#if board.bo_use_category}
                <Table.Head class="w-[100px] text-center">Category</Table.Head>
            {/if}
            <Table.Head>
                <a
                    href="?sst=wr_subject&sod={$page.url.searchParams.get(
                        'sod',
                    ) === 'asc'
                        ? 'desc'
                        : 'asc'}"
                    class="flex items-center gap-1 hover:text-foreground"
                >
                    Subject
                    {#if $page.url.searchParams.get("sst") === "wr_subject"}
                        <span
                            >{$page.url.searchParams.get("sod") === "asc"
                                ? "↑"
                                : "↓"}</span
                        >
                    {/if}
                </a>
            </Table.Head>
            <Table.Head class="w-[100px] text-center">Author</Table.Head>
            <Table.Head class="w-[100px] text-center">
                <a
                    href="?sst=wr_datetime&sod={$page.url.searchParams.get(
                        'sod',
                    ) === 'asc'
                        ? 'desc'
                        : 'asc'}"
                    class="flex items-center gap-1 justify-center hover:text-foreground"
                >
                    Date
                    {#if $page.url.searchParams.get("sst") === "wr_datetime"}
                        <span
                            >{$page.url.searchParams.get("sod") === "asc"
                                ? "↑"
                                : "↓"}</span
                        >
                    {/if}
                </a>
            </Table.Head>
            <Table.Head class="w-[80px] text-center">
                <a
                    href="?sst=wr_hit&sod={$page.url.searchParams.get('sod') ===
                    'asc'
                        ? 'desc'
                        : 'asc'}"
                    class="flex items-center gap-1 justify-center hover:text-foreground"
                >
                    Hit
                    {#if $page.url.searchParams.get("sst") === "wr_hit"}
                        <span
                            >{$page.url.searchParams.get("sod") === "asc"
                                ? "↑"
                                : "↓"}</span
                        >
                    {/if}
                </a>
            </Table.Head>
        </Table.Row>
    </Table.Header>
    <Table.Body>
        {#each list as item}
            <Table.Row
                class={item.wr_is_notice
                    ? "bg-blue-50 dark:bg-blue-950/30"
                    : ""}
            >
                <Table.Cell class="text-center font-medium">
                    {#if item.wr_is_notice}
                        <span class="text-blue-600 font-bold">공지</span>
                    {:else}
                        {item.wr_num}
                    {/if}
                </Table.Cell>
                {#if board.bo_use_category}
                    <Table.Cell class="text-center">
                        {#if item.ca_name}
                            <Badge variant="secondary" class="font-normal"
                                >{item.ca_name}</Badge
                            >
                        {/if}
                    </Table.Cell>
                {/if}
                <Table.Cell>
                    <a
                        href="{base}/bbs/{board.bo_table}/{item.wr_id}"
                        class="hover:underline flex items-center gap-2 {item.wr_is_notice
                            ? 'font-semibold text-blue-700 dark:text-blue-400'
                            : ''}"
                        title={item.wr_subject}
                    >
                        {#if item.wr_is_notice}
                            <Badge
                                variant="default"
                                class="text-[10px] h-4 px-1 bg-blue-600"
                                >공지</Badge
                            >
                        {/if}
                        {truncateSubject(item.wr_subject)}
                        {#if item.wr_comment > 0}
                            <span class="text-xs text-primary font-bold"
                                >[{item.wr_comment}]</span
                            >
                        {/if}
                        {#if item.is_new}
                            <Badge
                                variant="destructive"
                                class="text-[10px] h-4 px-1">N</Badge
                            >
                        {/if}
                        {#if item.is_hot}
                            <Badge class="text-[10px] h-4 px-1 bg-orange-500"
                                >H</Badge
                            >
                        {/if}
                    </a>
                </Table.Cell>
                <Table.Cell class="text-center">{item.wr_name}</Table.Cell>
                <Table.Cell class="text-center text-xs text-slate-500"
                    >{item.wr_datetime.substring(2, 10)}</Table.Cell
                >
                <Table.Cell class="text-center text-xs text-slate-500"
                    >{item.wr_hit}</Table.Cell
                >
            </Table.Row>
        {:else}
            <Table.Row>
                <Table.Cell
                    colspan={board.bo_use_category ? 6 : 5}
                    class="h-24 text-center"
                >
                    게시글이 없습니다.
                </Table.Cell>
            </Table.Row>
        {/each}
    </Table.Body>
</Table.Root>
