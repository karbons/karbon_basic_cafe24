<script lang="ts">
    interface Props {
        data: {
            title: string;
            link: string;
            imageUrl?: string;
        };
    }

    let { data }: Props = $props();
</script>

<div class="flex flex-col gap-2 min-w-[200px] max-w-[260px]">
    <p class="text-sm font-semibold text-foreground line-clamp-2 leading-tight">
        {data.title}
    </p>

    {#if data.imageUrl}
        <img
            src={data.imageUrl}
            alt="Thumbnail"
            class="w-full h-32 object-cover rounded-md border bg-muted"
        />
    {/if}

    <a
        href={data.link}
        class="mt-1 inline-flex items-center justify-center w-full px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:opacity-90 transition-opacity no-underline"
    >
        게시글 바로가기
    </a>
</div>
