<script lang="ts">
    import { base } from "$app/paths";
    import { page } from "$app/state";
    import { _, isLoading } from "svelte-i18n";
    import { X, ChevronRight } from "lucide-svelte";
    import { menuStore, memberStore, isAuthenticated, clearMember } from "$lib/store";
    import { logout } from "$lib/api/auth";
    import { goto } from "$app/navigation";
    import LanguageDropdown from "$lib/components/LanguageDropdown.svelte";

    interface Props {
        open?: boolean;
        lang?: string;
        onClose?: () => void;
    }

    let { open = false, lang = "ko", onClose }: Props = $props();

    function withLang(path: string): string {
        return `/${lang}${path}`;
    }

    function handleClose() {
        onClose?.();
    }

    async function handleLogout() {
        try {
            await logout();
            clearMember();
            goto(withLang("/"));
            handleClose();
        } catch (e) {
            console.error("Logout failed:", e);
        }
    }
</script>

{#if open}
    <div class="fixed inset-0 z-40 bg-white pt-20 px-6 md:hidden">
<nav class="flex flex-col space-y-4">
            {#if $menuStore.length > 0}
                {#each $menuStore as menu}
                    {@const link = menu.me_link.startsWith(`/${lang}/`) ? menu.me_link : withLang(menu.me_link)}
                    <a
                        href="{base}{link}"
                        target={menu.me_target || "_self"}
                        class="text-xl font-semibold text-secondary-900 flex items-center justify-between border-b border-secondary-100 pb-4"
                        onclick={handleClose}
                    >
                        {menu.me_name}
                        <ChevronRight size={20} class="text-secondary-400" />
                    </a>
                {/each}
            {/if}
        </nav>

        <button
            onclick={handleClose}
            class="absolute top-4 right-4 p-2 text-secondary-900"
            aria-label="Close Menu"
        >
            <X size={24} />
        </button>
    </div>
{/if}