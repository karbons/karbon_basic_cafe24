<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import { ShoppingCart, Heart, Share2 } from "lucide-svelte";
    import { Button } from "$lib/ui/button";
    import type { PageData } from "./$types";
    import { addToCart } from "$lib/api/shop";

    interface Props {
        data: PageData;
    }

    let { data }: Props = $props();
    let qty = $state(1);
    let selectedOption = $state("");

    $effect(() => {
        if (data.item) {
            $pageTitle = data.item.it_name;
        }
        return () => {
            $pageTitle = "";
        };
    });

    const formattedPrice = $derived(data.item ? new Intl.NumberFormat('ko-KR').format(data.item.it_price) + '원' : '0원');

    async function handleAddToCart() {
        if (!data.item) return;
        try {
            await addToCart({
                it_id: data.item.it_id,
                it_name: data.item.it_name,
                it_price: data.item.it_price,
                ct_qty: qty,
                io_id: selectedOption,
                io_type: 0,
                io_value: selectedOption
            });
            alert('장바구니에 담겼습니다.');
        } catch (error: any) {
            alert(error.message);
        }
    }
</script>

<div class="min-h-screen">
    <!-- Header Space + Safe Area -->
    <div class="pt-safe h-14"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {#if data.item}
            <div class="lg:grid lg:grid-cols-2 lg:gap-x-12">
                <!-- 이미지 영역 -->
                <div class="mb-8 lg:mb-0">
                    <div class="aspect-square rounded-2xl overflow-hidden bg-gray-100 shadow-inner">
                        {#if data.item.images && data.item.images.length > 0}
                            <img 
                                src={data.item.images[0]} 
                                alt={data.item.it_name}
                                class="w-full h-full object-cover"
                            />
                        {:else}
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                이미지 없음
                            </div>
                        {/if}
                    </div>
                </div>

                <!-- 정보 및 주문 영역 -->
                <div class="flex flex-col">
                    <div class="mb-6">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-600 rounded-full font-medium">BEST</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight leading-tight">
                            {data.item.it_name}
                        </h1>
                        <p class="mt-2 text-gray-500">{data.item.it_basic || ''}</p>
                    </div>

                    <div class="border-t border-b border-gray-100 py-6 mb-6">
                        <div class="flex items-baseline gap-3">
                            <span class="text-3xl font-extrabold text-blue-600">{formattedPrice}</span>
                            {#if data.item.it_cust_price > 0}
                                <span class="text-lg text-gray-400 line-through">
                                    {new Intl.NumberFormat('ko-KR').format(data.item.it_cust_price)}원
                                </span>
                            {/if}
                        </div>
                    </div>

                    <!-- 옵션 선택 -->
                    {#if data.options && data.options.length > 0}
                        <div class="mb-6">
                            <label for="option" class="block text-sm font-semibold text-gray-900 mb-2">옵션 선택</label>
                            <select 
                                id="option" 
                                bind:value={selectedOption}
                                class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">필수 옵션을 선택해주세요</option>
                                {#each data.options as opt}
                                    <option value={opt.io_id}>
                                        {opt.io_value} (+{opt.io_price}원)
                                    </option>
                                {/each}
                            </select>
                        </div>
                    {/if}

                    <!-- 수량 조절 -->
                    <div class="mb-8">
                        <label for="qty" class="block text-sm font-semibold text-gray-900 mb-2">수량</label>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden">
                                <button class="px-4 py-2 hover:bg-gray-50 transition-colors" onclick={() => qty = Math.max(1, qty - 1)}>-</button>
                                <input type="number" bind:value={qty} class="w-16 border-none text-center focus:ring-0" min="1" />
                                <button class="px-4 py-2 hover:bg-gray-50 transition-colors" onclick={() => qty += 1}>+</button>
                            </div>
                        </div>
                    </div>

                    <!-- 버튼 영역 -->
                    <div class="flex gap-3 mt-auto">
                        <Button variant="outline" size="lg" class="flex-1 rounded-2xl border-2 py-6 font-bold" onclick={handleAddToCart}>
                            <ShoppingCart class="w-5 h-5 mr-2" /> 장바구니
                        </Button>
                        <Button variant="default" size="lg" class="flex-[2] rounded-2xl py-6 font-bold shadow-lg shadow-blue-200">
                            바로 구매하기
                        </Button>
                    </div>
                </div>
            </div>

            <div class="mt-16 border-t border-gray-100 pt-16">
                <div class="flex gap-8 border-b border-gray-100 mb-8">
                    <button class="pb-4 text-lg font-bold border-b-2 border-blue-600 text-blue-600">상세정보</button>
                    <button class="pb-4 text-lg font-medium text-gray-500 hover:text-gray-900">상품후기</button>
                    <button class="pb-4 text-lg font-medium text-gray-500 hover:text-gray-900">Q&A</button>
                </div>
                <div class="prose max-w-none">
                    {@html data.item.it_mobile_explan || data.item.it_explan}
                </div>
            </div>
        {:else}
            <div class="p-8 text-center text-gray-500">
                상품 정보를 불러올 수 없습니다.
            </div>
        {/if}
    </div>
</div>
