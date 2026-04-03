<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import ItemList from "$lib/skin/shop/basic/ItemList.svelte";
    import type { PageData } from "./$types";

    interface Props {
        data: PageData;
    }

    let { data }: Props = $props();

    $effect(() => {
        $pageTitle = data.category?.ca_name || "상품 목록";
        return () => {
            $pageTitle = "";
        };
    });
</script>

<div class="min-h-screen">
    <div class="pt-safe h-14"></div>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                {data.category?.ca_name || "전체 상품"}
            </h1>
            <p class="text-sm text-gray-500 mt-1">총 {data.pagination.total_count}개의 상품이 있습니다.</p>
        </div>
        
        <ItemList items={data.items} />
    </div>
</div>
