<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import { memberStore } from "$lib/store";
    import { fly, fade } from "svelte/transition";
    import { base } from '$app/paths';
    import { _ } from 'svelte-i18n';

    let visible = $state(false);
    let sectionRef: HTMLElement;
    let observer: IntersectionObserver;

    onMount(() => {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        visible = true;
                    } else {
                        visible = false;
                    }
                });
            },
            {
                threshold: 0.1,
            },
        );

        if (sectionRef) {
            observer.observe(sectionRef);
        }
    });

    onDestroy(() => {
        if (observer) {
            observer.disconnect();
        }
    });
</script>

<section
    bind:this={sectionRef}
    class="relative bg-gradient-to-b from-blue-50 to-white pt-28 pb-24 sm:pt-32 sm:pb-40"
>
    {#if visible}
        <div
            class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10"
        >
            <h1
                in:fly={{ y: 20, duration: 800 }}
                class="text-5xl sm:text-7xl font-extrabold tracking-tight text-gray-900 mb-8 sm:mb-12"
            >
                {@html $_('app.hero.title')}
            </h1>
            <p
                in:fly={{ y: 20, duration: 800, delay: 200 }}
                class="max-w-2xl mx-auto text-xl sm:text-2xl text-gray-600 mb-10 sm:mb-16 leading-relaxed"
            >
                {@html $_('app.hero.description')}
            </p>

            <div
                in:fly={{ y: 20, duration: 800, delay: 400 }}
                class="flex flex-col sm:flex-row gap-4 justify-center items-center"
            >
                {#if $memberStore}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a
                            href="{base}/bbs/free"
                            class="w-full sm:w-auto px-8 py-4 bg-blue-600 text-white font-bold rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-1 transition-all duration-300"
                        >
                            {$_('app.hero.startCommunity')}
                        </a>
                        <a
                            href="{base}/member/mypage"
                            class="w-full sm:w-auto px-8 py-4 bg-white text-gray-900 font-bold rounded-2xl border border-gray-200 hover:bg-gray-50 hover:-translate-y-1 transition-all duration-300"
                        >
                            {$_('app.hero.viewMyInfo')}
                        </a>
                    </div>
                {:else}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a
                            href="{base}/auth/login"
                            class="w-full sm:w-auto px-8 py-4 bg-blue-600 text-white font-bold rounded-2xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:-translate-y-1 transition-all duration-300"
                        >
                            {$_('app.hero.loginNow')}
                        </a>
                        <a
                            href="{base}/auth/register"
                            class="w-full sm:w-auto px-8 py-4 bg-white text-gray-900 font-bold rounded-2xl border border-gray-200 hover:bg-gray-50 hover:-translate-y-1 transition-all duration-300"
                        >
                            {$_('app.hero.registerNow')}
                        </a>
                    </div>
                {/if}
            </div>
        </div>
    {/if}

    <!-- Decorative Elements -->
    <div
        class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full pointer-events-none opacity-20"
    >
        <div
            class="absolute top-20 left-10 w-64 h-64 bg-blue-400 rounded-full blur-3xl"
        ></div>
        <div
            class="absolute bottom-10 right-10 w-80 h-80 bg-purple-400 rounded-full blur-3xl"
        ></div>
    </div>
</section>
