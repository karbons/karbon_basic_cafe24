<script lang="ts">
    import "../../../app.css";
    import { goto } from "$app/navigation";
    import { page } from "$app/stores";
    import { onMount } from "svelte";
    import {
        setMember,
        clearMember,
        setConfig,
        confirmStore,
    } from "$lib/store";
    import { getProfile, getConfig } from "$lib/api";
    import Alert from "$lib/skin/common/Alert.svelte";
    import Confirm from "$lib/skin/common/Confirm.svelte";
    import Toast from "$lib/skin/common/Toast.svelte";
    import { Button } from "$lib/ui/button";
    import { initDevice } from "$lib/util/device";
    import { base } from '$app/paths';

    let { children } = $props();

    function goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            goto(base + "/");
        }
    }

    onMount(async () => {
        initDevice();
        try {
            const [member, config] = await Promise.allSettled([
                getProfile(),
                getConfig(),
            ]);

            if (member.status === "fulfilled") {
                setMember(member.value);
            } else {
                clearMember();
            }

            if (config.status === "fulfilled") {
                setConfig(config.value);
            }
        } catch (e) {
            console.error("Auth layout initialization failed:", e);
        }
    });
</script>

<!-- Auth Layout: 심플 헤더 + 빈 푸터, BottomNav 없음 -->
<div class="min-h-screen flex flex-col bg-background">
    <!-- Simple Header with Back Button (no border/background for modern look) -->
    <header class="sticky top-0 z-50 w-full pt-safe">
        <div class="container flex h-14 items-center px-4">
            <button
                onclick={goBack}
                class="flex items-center justify-center w-10 h-10 -ml-2 rounded-full hover:bg-muted transition-colors"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="28"
                    height="28"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="m15 18-6-6 6-6" />
                </svg>
                <span class="sr-only">뒤로 가기</span>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
        {@render children()}
    </main>

    <!-- Empty Footer (minimal) -->
    <footer class="py-4 text-center text-xs text-muted-foreground">
        <!-- 빈 푸터 또는 최소한의 정보 -->
    </footer>
</div>

<!-- Global Dialogs -->
<Alert />
<Confirm />
<Toast />
