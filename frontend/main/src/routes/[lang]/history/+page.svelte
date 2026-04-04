<script lang="ts">
  import { _ } from 'svelte-i18n';
  import { onMount } from 'svelte';
  
  let mounted = $state(false);
  onMount(() => {
    mounted = true;
  });

  const historyItems = [
    {
      year: "2024",
      events: [
        "글로벌 AI 솔루션 'Karbon AI' 런칭",
        "아시아 태평양 혁신 기술상 수상",
        "매출액 500억 돌파"
      ]
    },
    {
      year: "2023",
      events: [
        "엔터프라이즈 디지털 전환 프로젝트 100건 달성",
        "실리콘밸리 지사 설립",
        "ISO 27001 정보보안 인증 획득"
      ]
    },
    {
      year: "2021",
      events: [
        "차세대 빌더 플랫폼 2.0 공개",
        "시리즈 B 투자 유치 (200억 규모)",
        "기술 혁신형 중소기업(Inno-Biz) 선정"
      ]
    },
    {
      year: "2018",
      events: [
        "서울 R&D 센터 확장 이전",
        "누적 파트너사 500개 돌파",
        "대한민국 웹 어워드 대상 수상"
      ]
    },
    {
      year: "2015",
      events: [
        "칼본빌더 법인 설립",
        "시작하는 기쁨, 첫 프로젝트 수주"
      ]
    }
  ];
</script>

<svelte:head>
  <title>{$_('common.nav.history')} | KARBON BUILDER</title>
</svelte:head>

<div class="pt-20">
  <!-- Hero Section -->
  <section class="relative py-24 overflow-hidden bg-secondary-950 text-white">
    <div class="container-custom">
      <div class="max-w-2xl text-center mx-auto">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">
          {$_('history.title')} <br/>
          <span class="text-primary-500">{$_('history.titleHighlight')}</span>
        </h1>
        <p class="text-lg text-secondary-400">
          {$_('history.description')}
        </p>
      </div>
    </div>
    
    <!-- Abstract background -->
    <div class="absolute top-0 right-0 w-1/4 h-full bg-primary-600/5 rotate-12 -z-0"></div>
    <div class="absolute bottom-0 left-0 w-1/4 h-full bg-primary-600/5 -rotate-12 -z-0"></div>
  </section>

  <!-- Timeline Section -->
  <section class="py-24 bg-white relative">
    <div class="container-custom">
      <div class="relative">
        <!-- Connecting Line -->
        <div class="absolute left-1/2 top-0 bottom-0 w-px bg-linear-to-b from-primary-200 via-primary-600 to-secondary-200 -translate-x-1/2 hidden md:block"></div>
        
        <div class="space-y-24">
          {#each historyItems as item, i}
            <div class="relative flex flex-col md:flex-row items-center {i % 2 === 0 ? 'md:flex-row' : 'md:flex-row-reverse'}">
              <!-- Dot -->
              <div class="absolute left-1/2 -translate-x-1/2 w-6 h-6 bg-white border-4 border-primary-600 rounded-full z-10 hidden md:block shadow-[0_0_15px_rgba(37,99,235,0.4)]"></div>
              
              <div class="w-full md:w-1/2 {i % 2 === 0 ? 'md:pr-24 md:text-right' : 'md:pl-24 md:text-left'} flex flex-col items-center {i % 2 === 0 ? 'md:items-end' : 'md:items-start'}">
                <div class="inline-block px-6 py-2 bg-primary-50 text-primary-600 font-bold rounded-full text-2xl mb-6 shadow-sm border border-primary-100">
                  {item.year}
                </div>
                <div class="bg-secondary-50 md:bg-transparent p-8 md:p-0 rounded-3xl md:rounded-none w-full max-w-lg md:max-w-none">
                  <ul class="space-y-4">
                    {#each item.events as event}
                      <li class="text-lg md:text-xl font-medium text-secondary-800 leading-snug flex items-center gap-3 {i % 2 === 0 ? 'md:justify-end' : 'md:justify-start'}">
                        <span class="w-1.5 h-1.5 bg-primary-500 rounded-full md:hidden"></span>
                        {event}
                      </li>
                    {/each}
                  </ul>
                </div>
              </div>
              
              <div class="w-full md:w-1/2"></div>
            </div>
          {/each}
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  :global(body) {
    scroll-behavior: smooth;
  }
</style>
