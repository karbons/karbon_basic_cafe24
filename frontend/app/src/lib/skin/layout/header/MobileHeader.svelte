<script lang="ts">
    import { goto } from "$app/navigation";
    import { page } from "$app/stores";
    import { ChevronLeft, Menu } from "lucide-svelte";
    import { base } from "$app/paths";

    interface Props {
        /** 뒤로가기 버튼 표시 여부 */
        showBackButton?: boolean;
        /** 중앙 타이틀 */
        title?: string;
        /** 메뉴 버튼 클릭 핸들러 */
        onMenuClick?: () => void;
        /** 헤더 숨김 상태 (스크롤 시) */
        hidden?: boolean;
    }

    let {
        showBackButton = true,
        title = "",
        onMenuClick,
        hidden = $bindable(false),
    }: Props = $props();

    // 스크롤 감지를 위한 로컬 상태
    let lastScrollY = 0;
    let isAtTop = $state(true);
    const scrollThreshold = 10;

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            goto(base + "/");
        }
    }

    function handleScroll() {
        const currentScrollY = window.scrollY;
        isAtTop = currentScrollY <= 10;

        if (currentScrollY <= 50) {
            hidden = false;
            lastScrollY = currentScrollY;
            return;
        }

        if (Math.abs(currentScrollY - lastScrollY) < scrollThreshold) return;

        if (currentScrollY > lastScrollY) {
            hidden = true;
        } else {
            hidden = false;
        }
        
        lastScrollY = currentScrollY;
    }
</script>

<svelte:window onscroll={handleScroll} />

<!-- 모바일 헤더: md 이하에서만 표시 -->
<header
    class="lg:hidden fixed top-0 left-0 right-0 z-50 transition-all duration-500
    {hidden ? '-translate-y-full opacity-0' : 'translate-y-0 opacity-100'} 
    {isAtTop ? 'bg-transparent' : 'bg-white/90 backdrop-blur-lg shadow-sm border-b border-gray-100/50'}"
>
    <!-- 노치 영역 (Safe Area) -->
    <div class="pt-safe"></div>

    <div class="flex items-center justify-between h-14 px-4">
        <div class="w-10 flex items-center justify-start">
            {#if showBackButton}
                <button
                    onclick={goBack}
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-white/30 backdrop-blur-md hover:bg-white/50 active:bg-gray-200/50 transition-colors shadow-sm"
                    aria-label="뒤로 가기"
                >
                    <ChevronLeft class="w-6 h-6 text-gray-900" />
                </button>
            {/if}
        </div>

        <div class="flex-1 text-center px-2">
            <h1 class="text-lg font-bold text-gray-900 truncate">
                {title}
            </h1>
        </div>

        <div class="w-10 flex items-center justify-end">
            <slot name="right-icons">
                {#if onMenuClick}
                    <button
                        onclick={onMenuClick}
                        class="w-10 h-10 flex items-center justify-center rounded-full bg-white/30 backdrop-blur-md hover:bg-white/50 active:bg-gray-200/50 transition-colors shadow-sm"
                        aria-label="메뉴 열기"
                    >
                        <Menu class="w-6 h-6 text-gray-900" />
                    </button>
                {/if}
            </slot>
        </div>
    </div>
</header>
