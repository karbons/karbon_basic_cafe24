<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import { Package, Truck, CreditCard, ChevronLeft } from "lucide-svelte";
    import { Button } from "$lib/ui/button";
    import type { PageData } from "./$types";
import { base } from '$app/paths';

    interface Props {
        data: PageData;
    }

    let { data }: Props = $props();

    $effect(() => {
        $pageTitle = "주문 상세 내역";
        return () => {
            $pageTitle = "";
        };
    });
</script>

<div class="min-h-screen">
    <!-- Header Space + Safe Area -->
    <div class="pt-safe h-14"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">주문 상세</h1>
            <p class="text-sm text-gray-500 mt-1">주문번호: {data.order.od_id}</p>
        </div>

        <!-- 주문 상태 요약 -->
        <div class="bg-blue-600 rounded-3xl p-8 text-white mb-8 shadow-lg shadow-blue-200">
            <div class="flex items-center justify-between mb-4">
                <span class="text-blue-100 font-medium">현재 주문 상태</span>
                <span class="bg-white/20 px-4 py-1 rounded-full text-sm font-bold backdrop-blur-sm">{data.order.od_status}</span>
            </div>
            <h2 class="text-3xl font-black">{data.order.od_status === '입금' ? '입금 확인 중입니다' : '상품을 준비 중입니다'}</h2>
            <p class="mt-2 text-blue-100 opacity-80">{data.order.od_time}에 주문되었습니다.</p>
        </div>

        <!-- 상품 정보 -->
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <Package class="w-5 h-5" /> 주문 상품
            </h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50 overflow-hidden">
                {#each data.order.items || [] as item}
                    <div class="p-6 flex gap-4">
                        <div class="w-20 h-20 bg-gray-50 rounded-xl flex items-center justify-center text-gray-300">
                            <Package class="w-8 h-8" />
                        </div>
                        <div class="flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-gray-900">{item.it_name}</h3>
                                {#if item.ct_option}
                                    <p class="text-xs text-gray-500 mt-1">옵션: {item.ct_option}</p>
                                {/if}
                            </div>
                            <div class="flex justify-between items-end mt-2">
                                <span class="text-sm text-gray-500">{item.ct_qty}개</span>
                                <span class="font-bold text-gray-900">{new Intl.NumberFormat('ko-KR').format(item.ct_price * item.ct_qty)}원</span>
                            </div>
                        </div>
                    </div>
                {/each}
            </div>
        </section>

        <!-- 결제 정보 -->
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <CreditCard class="w-5 h-5" /> 결제 정보
            </h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">주문금액</span>
                    <span class="text-gray-900 font-medium">{new Intl.NumberFormat('ko-KR').format(data.order.od_cart_price)}원</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">배송비</span>
                    <span class="text-gray-900 font-medium">+{new Intl.NumberFormat('ko-KR').format(data.order.od_send_cost)}원</span>
                </div>
                <div class="pt-4 border-t border-gray-50 flex justify-between items-center">
                    <span class="text-base font-bold text-gray-900">총 결제금액</span>
                    <span class="text-xl font-black text-blue-600">{new Intl.NumberFormat('ko-KR').format(data.order.od_receipt_price || (data.order.od_cart_price + data.order.od_send_cost))}원</span>
                </div>
                <div class="mt-4 p-4 bg-gray-50 rounded-xl text-xs text-gray-500">
                    결제수단: {data.order.od_pay_type}
                </div>
            </div>
        </section>

        <!-- 배송지 정보 -->
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <Truck class="w-5 h-5" /> 배송지 정보
            </h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <p class="font-bold text-gray-900 mb-2">{data.order.od_name}</p>
                <p class="text-sm text-gray-600 mb-1">{data.order.od_tel} / {data.order.od_hp}</p>
                <p class="text-sm text-gray-600">[{data.order.od_zip1}] {data.order.od_addr1} {data.order.od_addr2}</p>
            </div>
        </section>

        <div class="flex gap-4">
            <Button href="{base}/member/order" variant="outline" class="flex-1 rounded-2xl py-6 font-bold border-2">
                <ChevronLeft class="w-5 h-5 mr-2" /> 목록으로
            </Button>
            <Button variant="default" class="flex-1 rounded-2xl py-6 font-bold shadow-lg shadow-blue-200">
                문의하기
            </Button>
        </div>
    </div>
</div>
