<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import { goto } from "$app/navigation";
    import { base } from "$app/paths";
    import { page } from "$app/state";
    import { _, isLoading } from "svelte-i18n";
    import {
        menuStore,
        memberStore,
        isAuthenticated,
        clearMember,
    } from "$lib/store";
    import { logout } from "$lib/api/auth";
    import LanguageDropdown from "$lib/components/LanguageDropdown.svelte";

    interface Props {
        lang?: string;
        onMenuClick?: () => void;
    }

    let { lang = "ko", onMenuClick }: Props = $props();

    function withLang(path: string): string {
        return `/${lang}${path}`;
    }

    let showUserMenu = $state(false);

    function toggleUserMenu() {
        showUserMenu = !showUserMenu;
    }

    async function handleLogout() {
        try {
            await logout();
            clearMember();
            goto(withLang("/"));
        } catch (e) {
            console.error("Logout failed:", e);
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

<header
    class="hidden md:block fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white/95 backdrop-blur-md border-b border-gray-100"
>
    <div class="container-custom flex items-center justify-between py-4">
        <a
            href="{base}{withLang('/')}"
            class="text-2xl font-bold text-primary-600 tracking-tight"
        >
            KARBON<span class="text-secondary-900">BUILDER</span>
        </a>

        <nav class="flex items-center space-x-8">
            {#if $menuStore.length > 0}
                {#each $menuStore as menu}
                    {@const link = menu.me_link.startsWith(`/${lang}/`) ? menu.me_link : withLang(menu.me_link)}
                    <a
                        href="{base}{link}"
                        target={menu.me_target || "_self"}
                        class="text-sm font-medium transition-colors hover:text-primary-600 {page.url.pathname.includes(menu.me_link.replace(`/${lang}/`, '/')) ? 'text-primary-600' : 'text-secondary-700'}"
                    >
                        {menu.me_name}
                    </a>
                {/each}
            {/if}
        </nav>

        <div class="flex items-center gap-4">
            {#if $isAuthenticated && $memberStore}
                <div class="relative">
                    <button
                        id="user-menu-button"
                        type="button"
                        onclick={toggleUserMenu}
                        class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-secondary-700 hover:text-primary-600 transition-colors"
                    >
                        <div
                            class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center"
                        >
                            <span class="text-white text-xs font-semibold">
                                {$memberStore.mb_name?.charAt(0) || "?"}
                            </span>
                        </div>
                        <span>{$memberStore.mb_name}님</span>
                    </button>

                    {#if showUserMenu}
                        <div
                            id="user-menu"
                            class="absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-md shadow-lg py-1 z-50"
                        >
                            <a
                                href="{base}{withLang('/member/profile')}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                {$_("common.nav.mypage")}
                            </a>
                            <a
                                href="{base}{withLang('/member/memo')}"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            >
                                {$_("common.nav.memo")}
                            </a>
                            <hr class="my-1 border-gray-200" />
                            <button
                                type="button"
                                onclick={handleLogout}
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                            >
                                {$_("common.actions.logout")}
                            </button>
                        </div>
                    {/if}
                </div>
            {:else}
                <a
                    href="{base}{withLang('/auth/login')}"
                    class="text-sm font-medium text-secondary-700 hover:text-primary-600 transition-colors"
                >
                    {$_("common.actions.login")}
                </a>
                <a
                    href="{base}{withLang('/auth/register')}"
                    class="btn-primary py-2 px-5 text-sm"
                >
                    {$_("common.actions.register")}
                </a>
            {/if}

            <LanguageDropdown />
        </div>
    </div>
</header>
