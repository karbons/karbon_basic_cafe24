<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import { CreditCard, User, ChevronRight, ShoppingBag } from "lucide-svelte";
    import { Button } from "$lib/ui/button";
    import type { PageData } from "./$types";
    import { preparePayment } from "$lib/api/shop";
    import { Browser } from "@capacitor/browser";
    import { App } from "@capacitor/app";

    interface Props {
        data: PageData;
    }

    let { data }: Props = $props();
    let pay_method = $state("card");
    
    // 주문자 정보 (예시)
    let orderer = $state({
        name: "",
        hp: "",
        email: "",
        zip: "",
        addr1: "",
        addr2: ""
    });

    $effect(() => {
        $pageTitle = "주문서 작성";
        
        // 결제 완료 후 앱 복귀 리스너
        const appUrlListener = App.addListener('appUrlOpen', (event) => {
            console.log('App opened with URL:', event.url);
            if (event.url.includes('payment-result')) {
                const url = new URL(event.url);
                const status = url.searchParams.get('status');
                const od_id = url.searchParams.get('od_id');
                
                Browser.close();
                
                if (status === 'success') {
                    window.location.href = `/shop/order/success?od_id=${od_id}`;
                } else {
                    alert('결제가 취소되었거나 실패하였습니다.');
                }
            }
        });

        return () => {
            $pageTitle = "";
            appUrlListener.remove();
        };
    });

    async function handlePayment() {
        if (!orderer.name || !orderer.hp) {
            alert('주문자 정보를 입력해주세요.');
            return;
        }

        try {
            const res = await preparePayment({
                ...orderer,
                od_pay_type: pay_method,
                od_price: data.totalPrice,
                // 장바구니 아이템 등 추가 정보
            });

            // 인앱 브라우저로 결제 브리지 페이지 열기
            await Browser.open({ url: res.bridge_url });
            
        } catch (error: any) {
            alert(error.message);
        }
    }
</script>

<div class="min-h-screen">
    <!-- Header Space + Safe Area -->
    <div class="pt-safe h-14"></div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-32">
        <!-- 주문 상품 요약 -->
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <ShoppingBag class="w-5 h-5" /> 주문 상품
            </h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50">
                {#each data.list as item}
                    <div class="p-4 flex gap-4">
                        <img src={item.it_img_url} alt={item.it_name} class="w-16 h-16 rounded-lg object-cover bg-gray-50" />
                        <div class="flex-1">
                            <p class="font-bold text-gray-900 line-clamp-1">{item.it_name}</p>
                            <p class="text-xs text-gray-500 mt-1">{item.ct_qty}개 / {new Intl.NumberFormat('ko-KR').format(item.ct_price)}원</p>
                        </div>
                    </div>
                {/each}
            </div>
        </section>

        <!-- 주문자 정보 -->
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <User class="w-5 h-5" /> 주문자 정보
            </h2>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">이름</label>
                    <input type="text" bind:value={orderer.name} class="w-full rounded-xl border-gray-200 focus:ring-blue-500" placeholder="홍길동" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">연락처</label>
                        <input type="tel" bind:value={orderer.hp} class="w-full rounded-xl border-gray-200 focus:ring-blue-500" placeholder="010-0000-0000" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">이메일</label>
                        <input type="email" bind:value={orderer.email} class="w-full rounded-xl border-gray-200 focus:ring-blue-500" placeholder="user@example.com" />
                    </div>
                </div>
            </div>
        </section>

        <!-- 결제 수단 -->
        <section class="mb-8">
            <h2 class="text-lg font-bold mb-4 flex items-center gap-2">
                <CreditCard class="w-5 h-5" /> 결제 수단
            </h2>
            <div class="grid grid-cols-2 gap-3">
                <button 
                    class="p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 {pay_method === 'card' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-gray-100 bg-white text-gray-500'}"
                    onclick={() => pay_method = 'card'}
                >
                    <CreditCard class="w-6 h-6" />
                    <span class="font-bold">신용카드</span>
                </button>
                <button 
                    class="p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 {pay_method === 'bank' ? 'border-blue-600 bg-blue-50 text-blue-600' : 'border-gray-100 bg-white text-gray-500'}"
                    onclick={() => pay_method = 'bank'}
                >
                    <ChevronRight class="w-6 h-6 rotate-90" />
                    <span class="font-bold">무통장입금</span>
                </button>
            </div>
        </section>

        <!-- 하단 고정 결제 바 -->
        <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] z-50 pb-safe">
            <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
                <div class="flex flex-col">
                    <span class="text-xs text-gray-500">총 결제금액</span>
                    <span class="text-xl font-black text-blue-600">{new Intl.NumberFormat('ko-KR').format(data.totalPrice)}원</span>
                </div>
                <Button 
                    variant="default" 
                    size="lg" 
                    class="flex-1 rounded-2xl py-6 font-bold shadow-lg shadow-blue-200"
                    onclick={handlePayment}
                >
                    결제하기
                </Button>
            </div>
        </div>
    </div>
</div>
