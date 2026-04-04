<script lang="ts">
    import { goto } from "$app/navigation";
    import { base } from "$app/paths";
    import { page } from "$app/state";
    import { Menu as MenuIcon, X } from "lucide-svelte";
    import { _, isLoading } from "svelte-i18n";
    import { menuStore, memberStore, isAuthenticated, clearMember } from "$lib/store";
    import { logout } from "$lib/api/auth";
    import LanguageDropdown from "$lib/components/LanguageDropdown.svelte";

    interface Props {
        lang?: string;
        onMenuClick?: () => void;
    }

    let { lang = "ko", onMenuClick }: Props = $props();

    let showUserMenu = $state(false);

    function withLang(path: string): string {
        return `/${lang}${path}`;
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
</script>

<header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100">
    <div class="flex items-center justify-between h-16 px-4 py-2">
        <a href="{base}{withLang('/')}" class="text-xl font-bold text-primary-600 tracking-tight">
            KARBON<span class="text-secondary-900">BUILDER</span>
        </a>

        <div class="flex items-center gap-2">
            {#if $isAuthenticated && $memberStore}
                <a
                    href="{base}{withLang('/member/mypage')}"
                    class="p-2 text-secondary-700 hover:text-primary-600"
                >
                    <div class="w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center">
                        <span class="text-white text-xs font-semibold">
                            {$memberStore.mb_name?.charAt(0) || "?"}
                        </span>
                    </div>
                </a>
            {/if}
            <button
                onclick={onMenuClick}
                class="p-2 text-secondary-900"
                aria-label="Toggle Menu"
            >
                <MenuIcon size={24} />
            </button>
        </div>
    </div>
</header>