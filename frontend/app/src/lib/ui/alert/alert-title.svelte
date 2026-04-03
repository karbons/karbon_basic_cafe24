<script lang="ts">
    import type { HTMLAttributes } from "svelte/elements";
    import { cn } from "$lib/util";

    let {
        ref = $bindable(null),
        class: className,
        children,
        ...restProps
    }: HTMLAttributes<HTMLHeadingElement> = $props();
</script>

<h5
    bind:this={ref}
    class={cn("mb-1 font-medium leading-none tracking-tight", className)}
    {...restProps}
>
    {@render children?.()}
</h5>
