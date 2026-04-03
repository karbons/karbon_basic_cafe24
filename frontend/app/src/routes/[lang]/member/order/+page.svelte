<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import { FileText, ChevronRight, Package } from "lucide-svelte";
    import type { PageData } from "./$types";
import { base } from '$app/paths';

    interface Props {
        data: PageData;
    }

    let { data }: Props = $props();

    $effect(() => {
        $pageTitle = "주문 내역";
        return () => {
            $pageTitle = "";
        };
    });
</script>

<div class="min-h-screen">
    <!-- Header Space + Safe Area -->
    <div class="pt-safe h-14"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {#if data.list.length > 0}
            <div class="space-y-4">
                {#each data.list as order}
                    <a 
                        href="{base}/member/order/{order.od_id}" 
                        class="block bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:border-blue-200 transition-all group"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-xs font-bold text-gray-400 block mb-1">{order.od_time}</span>
                                <span class="text-sm font-black text-gray-900">주문번호: {order.od_id}</span>
                            </div>
                            <div class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-bold">
                                {order.od_status}
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-500 transition-colors">
                                    <Package class="w-6 h-6" />
                                </div>
                                <div>
                                    <p class="text-base font-bold text-gray-900 line-clamp-1">{order.od_name}</p>
                                    <p class="text-sm text-blue-600 font-bold mt-0.5">
                                        {new Intl.NumberFormat('ko-KR').format(order.od_receipt_price || order.od_cart_price)}원
                                    </p>
                                </div>
                            </div>
                            <ChevronRight class="w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-all group-hover:translate-x-1" />
                        </div>
                    </a>
                {/each}
            </div>
        {:else}
            <div class="min-h-[50vh] flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                    <FileText class="w-10 h-10 text-gray-300" />
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">주문 내역이 없습니다</h2>
                <p class="text-gray-500 mb-8">새로운 상품을 쇼핑해보세요!</p>
                <a href="{base}/shop" class="inline-flex items-center justify-center px-8 py-3 bg-blue-600 text-white font-bold rounded-full shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all">
                    쇼핑하러 가기
                </a>
            </div>
        {/if}
    </div>
</div>
