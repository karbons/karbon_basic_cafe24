<script lang="ts">
    import type { HTMLAttributes } from "svelte/elements";
    import { cn } from "$lib/util";

    let {
        ref = $bindable(null),
        class: className,
        children,
        ...restProps
    }: HTMLAttributes<HTMLDivElement> = $props();
</script>

<div
    bind:this={ref}
    class={cn("text-sm [&_p]:leading-relaxed", className)}
    {...restProps}
>
    {@render children?.()}
</div>
