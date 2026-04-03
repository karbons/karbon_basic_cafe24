<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Check } from "lucide-svelte";
    import { cn } from "$lib/util";

    const plans = [
        {
            name: "Basic",
            price: "무료",
            description: "개인 및 소규모 프로젝트를 위한 기본적인 기능",
            features: [
                "게시판 5개 생성 가능",
                "기본 스킨 제공",
                "회원 관리 (최대 100명)",
                "커뮤니티 지원",
            ],
            cta: "무료로 시작하기",
            popular: false,
        },
        {
            name: "Pro",
            price: "₩29,000",
            period: "/월",
            description: "성장하는 비즈니스와 전문가를 위한 고급 기능",
            features: [
                "무제한 게시판 생성",
                "모든 프리미엄 스킨",
                "회원 관리 (무제한)",
                "API 액세스 권한",
                "우선 기술 지원",
                "광고 제거",
            ],
            cta: "Pro 시작하기",
            popular: true,
        },
        {
            name: "Enterprise",
            price: "문의",
            description: "대규모 조직을 위한 맞춤형 솔루션 및 지원",
            features: [
                "전용 클라우드 인프라",
                "SSO 통합",
                "커스텀 기능 개발",
                "24/7 전담 매니저",
                "SLA 보장",
                "보안 감사 지원",
            ],
            cta: "영업팀 문의",
            popular: false,
        },
    ];
</script>

<section class="py-24 bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                요금제 안내
            </h2>
            <p class="text-lg text-gray-600">
                프로젝트 규모에 맞는 최적의 플랜을 선택하세요.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {#each plans as plan}
                <div
                    class={cn(
                        "relative rounded-3xl p-8 xl:p-10 transition-all duration-300 flex flex-col",
                        plan.popular
                            ? "bg-white ring-2 ring-blue-600 shadow-xl scale-105 z-10"
                            : "bg-white ring-1 ring-gray-200 hover:shadow-lg",
                    )}
                >
                    {#if plan.popular}
                        <div
                            class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2"
                        >
                            <span
                                class="inline-flex items-center px-4 py-1 rounded-full text-sm font-semibold bg-blue-600 text-white shadow-sm"
                            >
                                인기 플랜
                            </span>
                        </div>
                    {/if}

                    <div class="mb-8">
                        <h3
                            class={cn(
                                "text-lg font-semibold leading-8",
                                plan.popular
                                    ? "text-blue-600"
                                    : "text-gray-900",
                            )}
                        >
                            {plan.name}
                        </h3>
                        <p
                            class="mt-4 text-sm leading-6 text-gray-600 min-h-[48px]"
                        >
                            {plan.description}
                        </p>
                        <div class="mt-6 flex items-baseline gap-x-1">
                            <span
                                class="text-4xl font-bold tracking-tight text-gray-900"
                                >{plan.price}</span
                            >
                            {#if plan.period}
                                <span
                                    class="text-sm font-semibold leading-6 text-gray-600"
                                    >{plan.period}</span
                                >
                            {/if}
                        </div>
                    </div>

                    <ul
                        role="list"
                        class="mt-8 space-y-3 text-sm leading-6 text-gray-600 flex-1"
                    >
                        {#each plan.features as feature}
                            <li class="flex gap-x-3">
                                <Check
                                    class="h-6 w-5 flex-none text-blue-600"
                                />
                                {feature}
                            </li>
                        {/each}
                    </ul>

                    <div class="mt-8">
                        <Button
                            class={cn(
                                "w-full h-12 text-base font-semibold rounded-xl",
                                plan.popular
                                    ? "bg-blue-600 hover:bg-blue-700"
                                    : "bg-blue-50 text-blue-700 hover:bg-blue-100",
                            )}
                            variant={plan.popular ? "default" : "outline"}
                        >
                            {plan.cta}
                        </Button>
                    </div>
                </div>
            {/each}
        </div>
    </div>
</section>
