<script lang="ts">
    import type { ShopItem } from '$lib/type/shop';
    import ItemCard from './ItemCard.svelte';
    
    interface Props {
        items: ShopItem[];
    }
    
    let { items }: Props = $props();
</script>

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
    {#each items as item (item.it_id)}
        <ItemCard {item} />
    {:else}
        <div class="col-span-full py-20 text-center text-gray-500">
            등록된 상품이 없습니다.
        </div>
    {/each}
</div>
