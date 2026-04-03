<script lang="ts">
    import { pageTitle } from "$lib/store/ui";
    import { CheckCircle, ShoppingBag, FileText } from "lucide-svelte";
    import { Button } from "$lib/ui/button";
    import { page } from "$app/stores";
import { base } from '$app/paths';

    let od_id = $derived($page.url.searchParams.get('od_id'));

    $effect(() => {
        $pageTitle = "주문 완료";
        return () => {
            $pageTitle = "";
        };
    });
</script>

<div class="min-h-screen">
    <!-- Header Space + Safe Area -->
    <div class="pt-safe h-14"></div>

    <div class="max-w-4xl mx-auto px-6 py-20 flex flex-col items-center text-center">
        <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mb-8">
            <CheckCircle class="w-14 h-14 text-green-500" />
        </div>
        
        <h1 class="text-3xl font-black text-gray-900 mb-4">주문이 완료되었습니다!</h1>
        <p class="text-gray-500 mb-2">정성껏 준비하여 빠르게 배송해 드리겠습니다.</p>
        <div class="bg-gray-50 px-4 py-2 rounded-full text-sm font-medium text-gray-600 mb-12">
            주문번호: <span class="text-gray-900">{od_id || '---------'}</span>
        </div>

        <div class="grid grid-cols-2 gap-4 w-full max-w-sm">
            <Button href="{base}/shop" variant="outline" class="rounded-2xl py-6 border-2 font-bold">
                <ShoppingBag class="w-5 h-5 mr-2" />
                계속 쇼핑하기
            </Button>
            <Button href="{base}/member/order" variant="default" class="rounded-2xl py-6 font-bold">
                <FileText class="w-5 h-5 mr-2" />
                주문내역 보기
            </Button>
        </div>
    </div>
</div>
