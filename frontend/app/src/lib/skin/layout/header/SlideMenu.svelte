<script lang="ts">
    import { memberStore, configStore } from "$lib/store";
    import { logout } from "$lib/api";
    import { clearMember } from "$lib/store";
    import { toastStore } from "$lib/store/toast";
    import { goto } from "$app/navigation";
    import { X } from "lucide-svelte";
import { base } from '$app/paths';

    interface Menu {
        me_id: number;
        me_name: string;
        me_link: string;
        me_target: string;
        sub?: Menu[];
    }

    interface Props {
        /** 메뉴 표시 여부 */
        open: boolean;
        /** 메뉴 목록 */
        menus?: Menu[];
        /** 메뉴 닫기 핸들러 */
        onClose: () => void;
    }

    let { open = false, menus = [], onClose }: Props = $props();

    /**
     * 메뉴 링크 클릭 핸들러
     * 내부 링크: SPA 네비게이션
     * 외부 링크: 인앱브라우저
     */
    function handleMenuClick(event: MouseEvent, menu: Menu) {
        const link = menu.me_link;
        const isExternalLink =
            /^https?:\/\//i.test(link) &&
            !link.includes(window.location.hostname);

        if (isExternalLink) {
            event.preventDefault();
            if (
                typeof (window as any).Capacitor !== "undefined" &&
                (window as any).Capacitor.Plugins?.Browser
            ) {
                (window as any).Capacitor.Plugins.Browser.open({ url: link });
            } else if (
                typeof (window as any).cordova !== "undefined" &&
                (window as any).cordova.InAppBrowser
            ) {
                (window as any).cordova.InAppBrowser.open(
                    link,
                    "_blank",
                    "location=yes",
                );
            } else {
                window.open(link, "_blank", "noopener,noreferrer");
            }
        }
        onClose();
    }

    async function handleLogout() {
        try {
            await logout();
            clearMember();
            toastStore.success("로그아웃 되었습니다.");
            onClose();
            goto(base + "/");
        } catch (e: any) {
            toastStore.error(e.message || "로그아웃에 실패했습니다.");
        }
    }
</script>

{#if open}
    <!-- 배경 오버레이 -->
    <!-- svelte-ignore a11y_no_noninteractive_element_interactions -->
    <div
        class="fixed inset-0 bg-black/50 z-[60]"
        onclick={onClose}
        onkeydown={(e) => e.key === "Escape" && onClose()}
        role="presentation"
    ></div>

    <!-- 슬라이드 메뉴 패널 -->
    <div
        class="fixed top-0 right-0 h-full w-80 max-w-[85vw] bg-white/95 backdrop-blur-xl z-[60] slide-in-right flex flex-col"
    >
        <!-- 상단 닫기 버튼 -->
        <div class="flex justify-end p-4 pt-safe">
            <button
                type="button"
                onclick={onClose}
                class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 transition-colors"
                aria-label="메뉴 닫기"
            >
                <X class="w-5 h-5 text-gray-600" />
            </button>
        </div>

        <!-- 회원 정보 섹션 -->
        <div class="px-6 pb-6">
            {#if $memberStore}
                <a
                    href="{base}/member/mypage"
                    onclick={onClose}
                    class="flex items-center gap-4 p-4 rounded-2xl bg-gradient-to-r from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 transition-all"
                >
                    <div
                        class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center shrink-0 backdrop-blur"
                    >
                        <span class="text-white text-xl font-bold">
                            {$memberStore.mb_name.charAt(0)}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-lg truncate">
                            {$memberStore.mb_name}님
                        </p>
                        <p class="text-blue-100 text-sm truncate">
                            {$memberStore.mb_nick ||
                                $memberStore.mb_email ||
                                "회원"}
                        </p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm text-blue-100">포인트</p>
                        <p class="font-bold">
                            {$memberStore.mb_point.toLocaleString()}
                        </p>
                    </div>
                </a>
            {:else}
                <div
                    class="p-6 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100"
                >
                    <p class="text-gray-600 text-sm mb-4 text-center">
                        로그인하고 더 많은 기능을 이용하세요
                    </p>
                    <div class="flex gap-3">
                        <a
                            href="{base}/auth/login"
                            onclick={onClose}
                            class="flex-1 text-center py-3 text-sm font-semibold text-gray-700 bg-white rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors"
                        >
                            로그인
                        </a>
                        <a
                            href="{base}/auth/register"
                            onclick={onClose}
                            class="flex-1 text-center py-3 text-sm font-semibold text-white bg-gray-900 rounded-xl hover:bg-gray-800 transition-colors"
                        >
                            회원가입
                        </a>
                    </div>
                </div>
            {/if}
        </div>

        <!-- 메뉴 목록 -->
        <nav class="flex-1 px-4 overflow-y-auto">
            <div class="space-y-1">
                {#each menus as menu}
                    <a
                        href={menu.me_link}
                        target="_self"
                        data-sveltekit-preload-data="hover"
                        onclick={(e) => handleMenuClick(e, menu)}
                        class="flex items-center justify-between px-4 py-3.5 text-base font-medium text-gray-800 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all group"
                    >
                        <span>{menu.me_name}</span>
                        <svg
                            class="w-4 h-4 text-gray-400 group-hover:text-gray-600 group-hover:translate-x-0.5 transition-all"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </a>
                    {#if menu.sub && menu.sub.length > 0}
                        <div
                            class="ml-4 pl-4 border-l-2 border-gray-100 space-y-0.5"
                        >
                            {#each menu.sub as subItem}
                                <a
                                    href={subItem.me_link}
                                    target={subItem.me_target || "_self"}
                                    onclick={onClose}
                                    class="block px-4 py-2.5 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg transition-colors"
                                >
                                    {subItem.me_name}
                                </a>
                            {/each}
                        </div>
                    {/if}
                {/each}
            </div>

            {#if $memberStore}
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <button
                        type="button"
                        onclick={handleLogout}
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50 rounded-xl transition-colors"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            />
                        </svg>
                        로그아웃
                    </button>
                </div>
            {/if}
        </nav>

        <!-- 하단 정보 -->
        <div class="p-6 pt-4 pb-safe border-t border-gray-100 bg-gray-50/50">
            <div class="flex items-center gap-4 text-xs text-gray-400 mb-3">
                <a
                    href="{base}/policy/terms"
                    onclick={onClose}
                    class="hover:text-gray-600 transition-colors">이용약관</a
                >
                <span>·</span>
                <a
                    href="{base}/policy/privacy"
                    onclick={onClose}
                    class="font-medium text-gray-500 hover:text-gray-700 transition-colors"
                    >개인정보처리방침</a
                >
            </div>
            <p class="text-xs text-gray-400">
                © {new Date().getFullYear()}
                {$configStore?.shop_company_name ||
                    $configStore?.cf_title ||
                    "Gnuboard Karbon"}
            </p>
        </div>
    </div>
{/if}

<style>
    .slide-in-right {
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(0);
        }
    }
</style>
