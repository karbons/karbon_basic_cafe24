<script lang="ts">
    import { memberStore } from "$lib/store";
import { base } from '$app/paths';
</script>

<section class="py-20 sm:py-32">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div
            class="relative bg-gray-900 rounded-[3rem] px-8 py-16 sm:px-20 sm:py-24 overflow-hidden shadow-2xl"
        >
            <div class="relative z-10 text-center">
                <h2
                    class="text-3xl sm:text-5xl font-bold text-white mb-8 tracking-tight"
                >
                    준비되셨나요? 지금 시작해 보세요.
                </h2>
                <p
                    class="text-gray-400 text-lg sm:text-xl mb-12 max-w-2xl mx-auto leading-relaxed"
                >
                    그누보드5의 강력함을 현대적인 웹 기술과 함께 만끽하세요.
                    무료로 시작하고 활발한 커뮤니티에 참여하세요.
                </p>
                {#if !$memberStore}
                    <a
                        href="{base}/auth/register"
                        class="inline-block px-10 py-5 bg-white text-gray-900 font-bold rounded-2xl hover:bg-blue-50 hover:scale-105 transition-all duration-300"
                    >
                        무료 회원가입 하기
                    </a>
                {:else}
                    <a
                        href="{base}/bbs/free"
                        class="inline-block px-10 py-5 bg-blue-600 text-white font-bold rounded-2xl hover:bg-blue-700 hover:scale-105 transition-all duration-300"
                    >
                        자유게시판 바로가기
                    </a>
                {/if}
            </div>

            <!-- Decorative Background Shapes -->
            <div
                class="absolute -top-24 -right-24 w-96 h-96 bg-blue-900 rounded-full blur-[100px] opacity-50"
            ></div>
            <div
                class="absolute -bottom-24 -left-24 w-96 h-96 bg-purple-900 rounded-full blur-[100px] opacity-30"
            ></div>
        </div>
    </div>
</section>
