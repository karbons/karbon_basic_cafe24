<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import { fly } from "svelte/transition";

    let visible = $state(false);
    let sectionRef: HTMLElement;
    let observer: IntersectionObserver;

    onMount(() => {
        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        visible = true;
                        observer.unobserve(entry.target); // 한 번만 실행하고 관찰 중단
                    }
                });
            },
            {
                threshold: 0.1, // 10% 보이면 트리거
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

<section bind:this={sectionRef} class="py-24 sm:py-32 bg-white">
    {#if visible}
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div in:fly={{ y: 30, duration: 800 }} class="mb-16 sm:mb-24">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    왜 카본(Karbon)인가요?
                </h2>
                <p class="text-lg text-gray-600 max-w-3xl">
                    기존 그누보드5의 강력함에 최신 웹 기술의 편리함을
                    더했습니다.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">
                <!-- Card 1 -->
                <div
                    in:fly={{ y: 30, duration: 800, delay: 200 }}
                    class="group p-8 bg-gray-50 rounded-3xl hover:bg-blue-600 transition-all duration-500"
                >
                    <div
                        class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-500 transition-colors"
                    >
                        <svg
                            class="w-8 h-8 text-blue-600 group-hover:text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="text-xl font-bold text-gray-900 mb-4 group-hover:text-white transition-colors"
                    >
                        초고속 성능
                    </h3>
                    <p
                        class="text-gray-600 leading-relaxed group-hover:text-blue-50 transition-colors"
                    >
                        Svelte 5의 리액티브 시스템을 활용하여 불필요한 렌더링을
                        최소화하고 쾌적한 반응 속도를 보장합니다.
                    </p>
                </div>

                <!-- Card 2 -->
                <div
                    in:fly={{ y: 30, duration: 800, delay: 400 }}
                    class="group p-8 bg-gray-50 rounded-3xl hover:bg-blue-600 transition-all duration-500"
                >
                    <div
                        class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-green-500 transition-colors"
                    >
                        <svg
                            class="w-8 h-8 text-green-600 group-hover:text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="text-xl font-bold text-gray-900 mb-4 group-hover:text-white transition-colors"
                    >
                        안정적인 보안
                    </h3>
                    <p
                        class="text-gray-600 leading-relaxed group-hover:text-blue-50 transition-colors"
                    >
                        그누보드5의 검증된 보안 체계를 그대로 계승하면서, REST
                        API 기반의 분리된 아키텍처로 안전성을 높였습니다.
                    </p>
                </div>

                <!-- Card 3 -->
                <div
                    in:fly={{ y: 30, duration: 800, delay: 600 }}
                    class="group p-8 bg-gray-50 rounded-3xl hover:bg-blue-600 transition-all duration-500"
                >
                    <div
                        class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-500 transition-colors"
                    >
                        <svg
                            class="w-8 h-8 text-purple-600 group-hover:text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="text-xl font-bold text-gray-900 mb-4 group-hover:text-white transition-colors"
                    >
                        자유로운 커스텀
                    </h3>
                    <p
                        class="text-gray-600 leading-relaxed group-hover:text-blue-50 transition-colors"
                    >
                        컴포넌트 중심의 설계로 관리자 및 사용자 페이지를 원하는
                        대로 빠르고 유연하게 개발할 수 있습니다.
                    </p>
                </div>
            </div>
        </div>
    {/if}
</section>
