<script lang="ts">
    import { resolveImageUrl } from '$lib/util/image';
    import type { ShopItem } from '$lib/type/shop';
import { base } from '$app/paths';
    
    interface Props {
        item: ShopItem;
    }
    
    let { item }: Props = $props();
    
    const formattedPrice = $derived(new Intl.NumberFormat('ko-KR').format(item.it_price) + '원');
    const formattedCustPrice = $derived(item.it_cust_price > 0 ? new Intl.NumberFormat('ko-KR').format(item.it_cust_price) + '원' : '');
</script>

<div class="group relative bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
    <a href="{base}/shop/item/{item.it_id}" class="block">
        <div class="aspect-square overflow-hidden bg-gray-100">
            {#if item.it_img_url}
                <img 
                    src={resolveImageUrl(item.it_img_url)} 
                    alt={item.it_name}
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                />
            {:else}
                <div class="w-full h-full flex items-center justify-center text-gray-400">
                    이미지 없음
                </div>
            {/if}
        </div>
        
        <div class="p-4">
            <h3 class="text-sm font-medium text-gray-900 line-clamp-2 min-h-[2.5rem]">
                {item.it_name}
            </h3>
            
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-lg font-bold text-blue-600">{formattedPrice}</span>
                {#if formattedCustPrice}
                    <span class="text-xs text-gray-400 line-through">{formattedCustPrice}</span>
                {/if}
            </div>
            
            <div class="mt-3 flex gap-1">
                {#if item.it_type1}<span class="px-1.5 py-0.5 text-[10px] bg-red-100 text-red-600 rounded">히트</span>{/if}
                {#if item.it_type2}<span class="px-1.5 py-0.5 text-[10px] bg-blue-100 text-blue-600 rounded">추천</span>{/if}
                {#if item.it_type3}<span class="px-1.5 py-0.5 text-[10px] bg-green-100 text-green-600 rounded">신상</span>{/if}
            </div>
        </div>
    </a>
</div>
