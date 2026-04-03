<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import { Trash2, ShoppingBag } from "lucide-svelte";
    import { Button } from "$lib/ui/button";
    import type { PageData } from "./$types";
import { base } from '$app/paths';

    interface Props {
        data: PageData;
    }

    let { data }: Props = $props();

    $effect(() => {
        $pageTitle = "장바구니";
        return () => {
            $pageTitle = "";
        };
    });

    const formattedTotalPrice = $derived(new Intl.NumberFormat('ko-KR').format(data.totalPrice) + '원');
</script>

<div class="min-h-screen">
    <!-- Header Space + Safe Area -->
    <div class="pt-safe h-14"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {#if data.list.length > 0}
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <span class="text-sm font-medium text-gray-600">전체 {data.list.length}개 상품</span>
                    <button class="text-sm text-gray-400 hover:text-red-500 flex items-center gap-1 transition-colors">
                        <Trash2 class="w-4 h-4" /> 전체삭제
                    </button>
                </div>

                <ul class="divide-y divide-gray-50">
                    {#each data.list as item}
                        <li class="p-6 flex gap-4 sm:gap-6">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                                <img src={item.it_img_url} alt={item.it_name} class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-gray-900 line-clamp-1">{item.it_name}</h3>
                                    {#if item.ct_option}<p class="text-xs text-gray-500 mt-1">옵션: {item.ct_option}</p>{/if}
                                    <p class="text-sm font-medium text-gray-400 mt-1">{item.ct_qty}개</p>
                                </div>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-lg font-bold text-gray-900">{new Intl.NumberFormat('ko-KR').format(item.ct_price * item.ct_qty)}원</span>
                                    <button class="p-1 text-gray-400 hover:text-gray-900 border rounded-lg transition-colors"><Trash2 class="w-4 h-4" /></button>
                                </div>
                            </div>
                        </li>
                    {/each}
                </ul>
            </div>

            <div class="mt-8 bg-blue-50 rounded-2xl p-6 border border-blue-100">
                <div class="flex justify-between items-center mb-6">
                    <span class="text-lg font-medium text-blue-900">총 결제 예상 금액</span>
                    <span class="text-2xl font-black text-blue-600">{formattedTotalPrice}</span>
                </div>
                <Button href="{base}/shop/order" variant="default" size="lg" class="w-full rounded-2xl py-6 font-bold shadow-lg shadow-blue-200">주문하기</Button>
            </div>
        {:else}
            <div class="min-h-[60vh] flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                    <ShoppingBag class="w-10 h-10 text-gray-300" />
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">장바구니가 비어있습니다</h2>
                <Button href="{base}/shop" variant="outline" class="rounded-full px-8">쇼핑 계속하기</Button>
            </div>
        {/if}
    </div>
</div>
