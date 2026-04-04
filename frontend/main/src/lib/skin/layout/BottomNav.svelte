<script lang="ts">
    import { page } from "$app/stores";
    import { goto } from "$app/navigation";
    import {
        Home,
        MessageCircle,
        ClipboardList,
        ShoppingBag,
        User,
    } from "lucide-svelte";
    import { toggleChat, unreadCount, closeChat } from "$lib/store/chat";

    // 현재 경로 확인 헬퍼
    $: isActive = (path: string) =>
        $page.url.pathname === path ||
        (path !== "/" && $page.url.pathname.startsWith(path));
    import { memberStore, confirmStore } from "$lib/store";
    import { toastStore } from "$lib/store/toast";
import { base } from '$app/paths';

    async function handleChatClick() {
        if (!$memberStore) {
            const result = await confirmStore.show({
                title: "로그인 필요",
                message:
                    "채팅은 회원만 이용 가능합니다.\n로그인페이지로 이동하시겠습니까?",
                confirmText: "로그인",
                cancelText: "취소",
            });

            if (result) {
                goto(base + "/auth/login");
            }
            return;
        }
        toggleChat();
    }

    async function handleMyPageClick() {
        closeChat();
        if (!$memberStore) {
            const result = await confirmStore.show({
                title: "로그인 필요",
                message:
                    "마이페이지는 회원만 이용 가능합니다.\n로그인페이지로 이동하시겠습니까?",
                confirmText: "로그인",
                cancelText: "취소",
            });

            if (result) {
                goto(base + "/auth/login");
            }
            return;
        }
        goto(base + "/member/mypage");
    }
</script>

<div
    class="fixed bottom-0 left-0 right-0 z-50 h-16 bg-white border-t flex items-center justify-around md:hidden pb-safe"
>
    <a
        href={base + "/"}
        onclick={closeChat}
        class="flex flex-col items-center justify-center w-full h-full {isActive(
            base + '/',
        )
            ? 'text-primary'
            : 'text-slate-500'}"
    >
        <Home class="w-6 h-6" />
        <span class="text-[10px] mt-1">홈</span>
    </a>

    <button
        onclick={handleChatClick}
        class="flex flex-col items-center justify-center w-full h-full text-slate-500 relative"
    >
        <MessageCircle class="w-6 h-6" />
        <span class="text-[10px] mt-1">채팅</span>
        {#if $unreadCount > 0}
            <span
                class="absolute top-2 right-[25%] w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"
            >
                {$unreadCount > 9 ? "9+" : $unreadCount}
            </span>
        {/if}
    </button>

    <a
        href="{base}/bbs/free"
        onclick={closeChat}
        class="flex flex-col items-center justify-center w-full h-full {isActive(
            '/bbs',
        )
            ? 'text-primary'
            : 'text-slate-500'}"
    >
        <ClipboardList class="w-6 h-6" />
        <span class="text-[10px] mt-1">게시판</span>
    </a>

    <a
        href="{base}/shop"
        onclick={closeChat}
        class="flex flex-col items-center justify-center w-full h-full {isActive(
            '/shop',
        )
            ? 'text-primary'
            : 'text-slate-500'}"
    >
        <ShoppingBag class="w-6 h-6" />
        <span class="text-[10px] mt-1">쇼핑</span>
    </a>

    <button
        onclick={handleMyPageClick}
        class="flex flex-col items-center justify-center w-full h-full {isActive(
            '/member',
        )
            ? 'text-primary'
            : 'text-slate-500'}"
    >
        <User class="w-6 h-6" />
        <span class="text-[10px] mt-1">MY</span>
    </button>
</div>

<style>
    /* 아이폰 하단 안전 영역 처리 (Tailwind의 pb-safe가 없을 경우 대비) */
    .pb-safe {
        padding-bottom: env(safe-area-inset-bottom);
    }
</style>
