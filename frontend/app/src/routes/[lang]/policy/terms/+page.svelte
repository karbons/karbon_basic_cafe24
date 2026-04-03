<script lang="ts">
    import type { PageData } from "./$types";

    interface Props {
        data: PageData;
    }

    import { pageTitle } from "$lib/store/ui";

    let { data }: Props = $props();

    $effect(() => {
        if (data.content) {
            $pageTitle = data.content.co_subject;
        } else {
            $pageTitle = "이용약관";
        }
        return () => {
            $pageTitle = "";
        };
    });
</script>

<div class="max-w-4xl mx-auto px-4 py-8">
    {#if data.content}
        <h1 class="hidden md:block text-3xl font-bold mb-8 pb-4 border-b">
            {data.content.co_subject}
        </h1>

        <div class="prose max-w-none">
            {@html data.content.co_content}
        </div>
    {:else}
        <div class="text-center py-20">
            <h1 class="text-2xl font-bold mb-4">이용약관</h1>
            <p class="text-muted-foreground">
                {data.error || "내용을 찾을 수 없습니다."}
            </p>
        </div>
    {/if}
</div>
