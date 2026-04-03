<script lang="ts">
    import { Badge } from "$lib/ui/badge";
    import { Heart, Calendar, Image as ImageIcon } from "lucide-svelte";
    import type { Write } from "$lib/type/board";
import { base } from '$app/paths';

    interface Props {
        posts: Write[];
        boTable: string;
        loading?: boolean;
    }

    let { posts, boTable, loading = false }: Props = $props();

    // Mobile slider state
    let currentIndex = $state(0);
    let touchStartX = $state(0);
    let touchEndX = $state(0);
    let isDragging = $state(false);
    let dragOffset = $state(0);

    function handleTouchStart(e: TouchEvent) {
        touchStartX = e.touches[0].clientX;
        isDragging = true;
        dragOffset = 0;
    }

    function handleTouchMove(e: TouchEvent) {
        if (!isDragging) return;
        touchEndX = e.touches[0].clientX;
        dragOffset = touchEndX - touchStartX;
    }

    function handleTouchEnd() {
        isDragging = false;
        const diff = touchStartX - touchEndX;
        const threshold = 50;

        if (Math.abs(diff) > threshold) {
            if (diff > 0 && currentIndex < posts.length - 1) {
                currentIndex++;
            } else if (diff < 0 && currentIndex > 0) {
                currentIndex--;
            }
        }
        dragOffset = 0;
    }

    function goToSlide(index: number) {
        currentIndex = index;
    }

    // 이름 첫 글자 추출
    function getInitial(name: string): string {
        return name?.charAt(0) || "?";
    }
</script>

<!-- Desktop: Grid Layout -->
<div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    {#each posts as post}
        <a
            href="{base}/bbs/{boTable}/{post.wr_id}"
            class="group block bg-white rounded-3xl overflow-hidden transition-all duration-300"
        >
            <!-- Thumbnail -->
            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                {#if post.thumbnail}
                    <img
                        src={post.thumbnail}
                        alt={post.wr_subject}
                        class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500"
                    />
                {:else}
                    <div
                        class="w-full h-full flex items-center justify-center text-gray-300"
                    >
                        <ImageIcon class="w-16 h-16 opacity-30" />
                    </div>
                {/if}
                {#if post.ca_name}
                    <Badge
                        class="absolute top-4 left-4 bg-white/95 text-gray-800 font-semibold px-3 py-1.5 rounded-full"
                    >
                        {post.ca_name}
                    </Badge>
                {/if}
            </div>

            <!-- Profile Section -->
            <div class="px-5 pt-4 pb-2 flex items-center gap-3">
                <div
                    class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-sm"
                >
                    {getInitial(post.wr_name)}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm truncate">
                        {post.wr_name}
                    </p>
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <Calendar class="w-3 h-3" />
                        {post.wr_datetime?.substring(0, 10)}
                    </p>
                </div>
            </div>

            <!-- Content -->
            <div class="px-5 pb-4">
                <h3
                    class="font-bold text-lg text-gray-900 leading-snug line-clamp-2 group-hover:text-blue-600 transition-colors"
                >
                    {post.wr_subject}
                </h3>
                <p class="text-gray-500 text-sm line-clamp-2 mt-2">
                    {post.wr_content
                        ? post.wr_content
                              .replace(/<[^>]*>?/gm, "")
                              .substring(0, 80)
                        : ""}
                </p>
            </div>

            <!-- Like Button -->
            <div class="px-5 pb-5">
                <button
                    class="flex items-center gap-1.5 text-gray-400 hover:text-red-500 transition-colors text-sm"
                    onclick={(e) => e.preventDefault()}
                >
                    <Heart class="w-5 h-5" />
                    <span>좋아요</span>
                </button>
            </div>
        </a>
    {/each}
</div>

<!-- Mobile: Peek Slider (shows partial next card) -->
<div class="md:hidden -mx-4">
    <div
        class="relative overflow-hidden px-4"
        ontouchstart={handleTouchStart}
        ontouchmove={handleTouchMove}
        ontouchend={handleTouchEnd}
    >
        <div
            class="flex gap-3 transition-transform duration-300 ease-out"
            style="transform: translateX(calc(-{currentIndex *
                85}% + {isDragging ? dragOffset : 0}px))"
        >
            {#each posts as post, index}
                <div class="w-[85%] flex-shrink-0">
                    <a
                        href="{base}/bbs/{boTable}/{post.wr_id}"
                        class="block bg-white rounded-3xl overflow-hidden"
                    >
                        <!-- Thumbnail -->
                        <div
                            class="relative aspect-[16/10] overflow-hidden bg-gray-100"
                        >
                            {#if post.thumbnail}
                                <img
                                    src={post.thumbnail}
                                    alt={post.wr_subject}
                                    class="w-full h-full object-cover"
                                />
                            {:else}
                                <div
                                    class="w-full h-full flex items-center justify-center text-gray-300"
                                >
                                    <ImageIcon class="w-12 h-12 opacity-30" />
                                </div>
                            {/if}
                            {#if post.ca_name}
                                <Badge
                                    class="absolute top-3 left-3 bg-white/95 text-gray-800 font-semibold px-2.5 py-1 rounded-full text-xs"
                                >
                                    {post.ca_name}
                                </Badge>
                            {/if}
                        </div>

                        <!-- Profile Section -->
                        <div class="px-4 pt-3 pb-2 flex items-center gap-2.5">
                            <div
                                class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs"
                            >
                                {getInitial(post.wr_name)}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="font-medium text-gray-900 text-sm truncate"
                                >
                                    {post.wr_name}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {post.wr_datetime?.substring(0, 10)}
                                </p>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="px-4 pb-3">
                            <h3
                                class="font-bold text-base text-gray-900 leading-snug line-clamp-2"
                            >
                                {post.wr_subject}
                            </h3>
                            <p
                                class="text-gray-500 text-sm line-clamp-2 mt-1.5"
                            >
                                {post.wr_content
                                    ? post.wr_content
                                          .replace(/<[^>]*>?/gm, "")
                                          .substring(0, 60)
                                    : ""}
                            </p>
                        </div>

                        <!-- Like Button -->
                        <div class="px-4 pb-4">
                            <button
                                class="flex items-center gap-1.5 text-gray-400 text-sm"
                                onclick={(e) => e.preventDefault()}
                            >
                                <Heart class="w-4 h-4" />
                                <span>좋아요</span>
                            </button>
                        </div>
                    </a>
                </div>
            {/each}
        </div>
    </div>

    <!-- Dots Indicator -->
    {#if posts.length > 1}
        <div class="flex justify-center gap-1.5 mt-5">
            {#each posts as _, index}
                <button
                    onclick={() => goToSlide(index)}
                    class="w-2 h-2 rounded-full transition-all duration-300 {currentIndex ===
                    index
                        ? 'bg-gray-800 w-5'
                        : 'bg-gray-300'}"
                    aria-label="슬라이드 {index + 1}으로 이동"
                ></button>
            {/each}
        </div>
    {/if}
</div>

<!-- Loading Skeleton -->
{#if loading}
    <!-- Desktop Skeleton -->
    <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        {#each Array(3) as _}
            <div class="bg-white rounded-3xl overflow-hidden animate-pulse">
                <div class="aspect-[4/3] bg-gray-200"></div>
                <div class="p-5 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gray-200"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="h-3 bg-gray-200 rounded w-20"></div>
                            <div class="h-2.5 bg-gray-200 rounded w-16"></div>
                        </div>
                    </div>
                    <div class="h-5 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-4 bg-gray-200 rounded w-full"></div>
                </div>
            </div>
        {/each}
    </div>

    <!-- Mobile Skeleton -->
    <div class="md:hidden -mx-4 px-4">
        <div class="w-[85%] bg-white rounded-3xl overflow-hidden animate-pulse">
            <div class="aspect-[16/10] bg-gray-200"></div>
            <div class="p-4 space-y-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-gray-200"></div>
                    <div class="flex-1 space-y-1.5">
                        <div class="h-3 bg-gray-200 rounded w-16"></div>
                        <div class="h-2.5 bg-gray-200 rounded w-12"></div>
                    </div>
                </div>
                <div class="h-5 bg-gray-200 rounded w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-full"></div>
            </div>
        </div>
    </div>
{/if}
