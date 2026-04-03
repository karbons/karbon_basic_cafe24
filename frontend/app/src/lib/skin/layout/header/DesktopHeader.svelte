<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import {
        memberStore,
        configStore,
        clearMember,
        menuStore,
    } from "$lib/store";
    import { logout } from "$lib/api";
    import { toastStore } from "$lib/store/toast";
    import { goto } from "$app/navigation";
    import { base } from "$app/paths";

    interface Props {
        /** 슬라이드 메뉴 열기 핸들러 */
        onMenuClick?: () => void;
    }

    let { onMenuClick }: Props = $props();

    let showUserMenu = $state(false);

    function toggleUserMenu() {
        showUserMenu = !showUserMenu;
    }

    async function handleLogout() {
        try {
            await logout();
            clearMember();
            toastStore.success("로그아웃 되었습니다.");
            goto(base + "/");
        } catch (e: any) {
            toastStore.error(e.message || "로그아웃에 실패했습니다.");
        }
    }

    function handleClickOutside(e: MouseEvent) {
        const target = e.target as HTMLElement;
        if (
            !target.closest("#user-menu-button") &&
            !target.closest("#user-menu")
        ) {
            showUserMenu = false;
        }
    }

    onMount(() => {
        document.addEventListener("click", handleClickOutside);
    });

    onDestroy(() => {
        if (typeof document !== "undefined") {
            document.removeEventListener("click", handleClickOutside);
        }
    });
</script>

<!-- 데스크톱 헤더 (lg 이상에서만 표시) -->
<header
    class="hidden lg:block sticky top-0 z-40 bg-white border-b border-gray-200"
>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- 로고 -->
            <div class="flex items-center">
                <a href="{base}/" class="flex items-center gap-2">
                    <div
                        class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center"
                    >
                        <span class="text-white font-bold text-lg">G</span>
                    </div>
                    <span class="text-xl font-bold text-gray-900">
                        {$configStore?.shop_company_name ||
                            $configStore?.cf_title ||
                            "그누보드5"}
                    </span>
                </a>
            </div>

            <!-- 데스크톱 메뉴 -->
            {#if $menuStore.length > 0}
                <nav class="flex items-center gap-1">
                    {#each $menuStore as menu}
                        <div class="relative group">
                            <a
                                href={menu.me_link}
                                target={menu.me_target || "_self"}
                                class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md transition-colors"
                            >
                                {menu.me_name}
                            </a>
                            {#if menu.sub && menu.sub.length > 0}
                                <div
                                    class="absolute left-0 top-full mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50"
                                >
                                    {#each menu.sub as subItem}
                                        <a
                                            href={subItem.me_link}
                                            target={subItem.me_target ||
                                                "_self"}
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 first:rounded-t-md last:rounded-b-md"
                                        >
                                            {subItem.me_name}
                                        </a>
                                    {/each}
                                </div>
                            {/if}
                        </div>
                    {/each}
                </nav>
            {/if}

            <!-- 우측: 사용자 메뉴 -->
            <div class="flex items-center gap-3">
                {#if $memberStore}
                    <!-- 로그인 상태 -->
                    <div class="relative">
                        <button
                            id="user-menu-button"
                            type="button"
                            onclick={toggleUserMenu}
                            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                        >
                            <div
                                class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center"
                            >
                                <span class="text-white text-xs font-semibold">
                                    {$memberStore.mb_name.charAt(0)}
                                </span>
                            </div>
                            <span>{$memberStore.mb_name}님</span>
                            <svg
                                class="w-4 h-4 text-gray-500 {showUserMenu
                                    ? 'rotate-180'
                                    : ''} transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        {#if showUserMenu}
                            <div
                                id="user-menu"
                                class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1 z-50"
                            >
                                <a
                                    href="{base}/member/profile"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    회원정보
                                </a>
                                <a
                                    href="{base}/member/memo"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    쪽지함
                                </a>
                                <a
                                    href="{base}/member/point"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                >
                                    포인트
                                </a>
                                <hr class="my-1 border-gray-200" />
                                <button
                                    type="button"
                                    onclick={handleLogout}
                                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                                >
                                    로그아웃
                                </button>
                            </div>
                        {/if}
                    </div>
                {:else}
                    <!-- 비로그인 상태 -->
                    <div class="flex items-center gap-3">
                        <a
                            href="{base}/auth/login"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 rounded-md transition-colors"
                        >
                            로그인
                        </a>
                        <a
                            href="{base}/auth/register"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                        >
                            회원가입
                        </a>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</header>
