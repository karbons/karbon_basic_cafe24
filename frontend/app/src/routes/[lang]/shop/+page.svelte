<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import { ShoppingBag } from "lucide-svelte";
    import ItemList from "$lib/skin/shop/basic/ItemList.svelte";
    import type { PageData } from "./$types";
import { base } from '$app/paths';

    interface Props {
        data: PageData;
    }

    let { data }: Props = $props();

    $effect(() => {
        $pageTitle = "쇼핑몰";
        return () => {
            $pageTitle = "";
        };
    });
</script>

<!-- 페이지 전체 배경을 활용하기 위해 최상단에 Safe Area 적용 -->
<div class="min-h-screen">
    <!-- Header Space + Safe Area -->
    <div class="pt-safe h-14"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 쇼핑몰 헤더/배너 영역 -->
        <div class="mb-10 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-8 text-white shadow-lg overflow-hidden relative">
            <div class="relative z-10">
                <h2 class="text-3xl font-bold mb-2">Karbon 쇼핑몰</h2>
                <p class="text-blue-100 opacity-90">그누보드 기반의 현대적인 하이브리드 커머스</p>
            </div>
            <ShoppingBag class="absolute -right-10 -bottom-10 w-64 h-64 text-white opacity-10 rotate-12" />
        </div>

        <!-- 추천 상품 섹션 -->
        <section class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">추천 상품</h2>
                <a href="{base}/shop/list" class="text-sm font-medium text-blue-600 hover:text-blue-700">전체보기 &rarr;</a>
            </div>
            
            <ItemList items={data.items} />
        </section>
    </div>
</div>
