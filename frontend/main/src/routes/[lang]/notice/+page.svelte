<script lang="ts">
  import { _ } from 'svelte-i18n';
  import { Search, ChevronRight, MessageSquare, Clock, User } from 'lucide-svelte';

  const notices = [
    { id: 1, title: "칼본빌더 홈페이지 리뉴얼 안내", date: "2026-04-01", author: "관리자", category: "공지" },
    { id: 2, title: "2026년 상반기 시스템 점검 안내", date: "2026-03-15", author: "관리자", category: "정검" },
    { id: 3, title: "개인정보 처리방침 개정 공지", date: "2026-02-28", author: "관리자", category: "안내" },
    { id: 4, title: "신규 서비스 'Karbon AI' 베타 테스트 모집", date: "2026-02-10", author: "운영팀", category: "이벤트" }
  ];
</script>

<svelte:head>
  <title>{$_('common.nav.notice')} | KARBON BUILDER</title>
</svelte:head>

<div class="pt-20">
  <!-- Hero Section -->
  <section class="py-24 bg-linear-to-r from-secondary-900 to-secondary-950 text-white relative overflow-hidden">
    <div class="container-custom relative z-10 text-center">
      <h1 class="text-4xl md:text-5xl font-bold mb-6">{$_('notice.title')}</h1>
      <p class="text-secondary-400 max-w-xl mx-auto">{$_('notice.description')}</p>
    </div>
    <!-- Abstract pattern -->
    <div class="absolute inset-0 opacity-10">
      <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
        <path d="M0 0 L100 0 L100 100 Z" fill="currentColor" />
      </svg>
    </div>
  </section>

  <!-- Notice Table Section -->
  <section class="py-24 bg-white">
    <div class="container-custom">
      <!-- Table Header/Search -->
      <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-12">
        <div class="flex items-center gap-6">
          <div class="px-5 py-2.5 bg-secondary-50 rounded-xl text-secondary-900 font-bold border border-secondary-100 flex items-center gap-2">
            전체 <span class="bg-primary-600 text-white text-[10px] px-1.5 py-0.5 rounded-sm">4</span>
          </div>
        </div>
        
        <div class="relative w-full md:w-80">
          <Search size={18} class="absolute left-4 top-1/2 -translate-y-1/2 text-secondary-400 pointer-events-none" />
          <input 
            type="text" 
            placeholder="검색어를 입력하세요." 
            class="w-full pl-12 pr-4 py-3 bg-secondary-50 border border-secondary-100 rounded-xl focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm"
          />
        </div>
      </div>

      <!-- Notice List (Mobile Card View + Desktop Table View) -->
      <div class="overflow-hidden border border-secondary-100 rounded-3xl">
        <table class="w-full text-left hidden md:table">
          <thead class="bg-secondary-50 text-secondary-950 font-bold border-b border-secondary-100">
            <tr>
              <th class="px-8 py-5 text-sm w-20">{$_('notice.table.no')}</th>
              <th class="px-8 py-5 text-sm">{$_('notice.table.title')}</th>
              <th class="px-8 py-5 text-sm w-32">{$_('notice.table.author')}</th>
              <th class="px-8 py-5 text-sm w-40 text-center">{$_('notice.table.date')}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-100/50">
            {#each notices as notice}
              <tr class="hover:bg-primary-50/30 transition-colors group cursor-pointer">
                <td class="px-8 py-6 text-sm text-secondary-400">{notice.id}</td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-3">
                    <span class="inline-block px-2.5 py-0.5 bg-secondary-100 text-[11px] font-bold text-secondary-600 rounded-sm">{notice.category}</span>
                    <span class="font-medium text-secondary-900 group-hover:text-primary-600 transition-colors">{notice.title}</span>
                  </div>
                </td>
                <td class="px-8 py-6 text-sm text-secondary-600 italic">{notice.author}</td>
                <td class="px-8 py-6 text-sm text-secondary-500 text-center">{notice.date}</td>
              </tr>
            {/each}
          </tbody>
        </table>

        <!-- Mobile View (Crads) -->
        <div class="grid grid-cols-1 md:hidden divide-y divide-secondary-100">
          {#each notices as notice}
            <div class="p-6 space-y-4 hover:bg-primary-50/30 transition-colors cursor-pointer">
              <div class="flex items-center justify-between">
                <span class="inline-block px-2.5 py-0.5 bg-secondary-100 text-[11px] font-bold text-secondary-600 rounded-sm">{notice.category}</span>
                <span class="text-xs text-secondary-400">{notice.date}</span>
              </div>
              <h3 class="font-bold text-secondary-950 group-hover:text-primary-600 transition-colors">{notice.title}</h3>
              <div class="flex items-center gap-4 text-xs text-secondary-500">
                <span class="flex items-center gap-1.5"><User size={14} /> {notice.author}</span>
              </div>
            </div>
          {/each}
        </div>
      </div>

      <!-- Pagination -->
      <div class="mt-12 flex justify-center gap-2">
        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary-600 text-white font-bold">1</button>
        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-secondary-100 text-secondary-600 hover:bg-secondary-50 transition-colors font-medium">2</button>
        <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-secondary-100 text-secondary-600 hover:bg-secondary-50 transition-colors">
          <ChevronRight size={18}/>
        </button>
      </div>
    </div>
  </section>

  <!-- Support Section -->
  <section class="py-24 bg-secondary-50 border-t border-secondary-100">
    <div class="container-custom">
      <div class="bg-white p-12 rounded-[2.5rem] border border-secondary-100 shadow-sm flex flex-col md:flex-row items-center justify-between gap-12">
        <div class="space-y-4 text-center md:text-left">
          <h2 class="text-3xl font-bold text-secondary-950">공지사항 구독하기</h2>
          <p class="text-secondary-600 text-lg">최신 소식을 이메일로 빠르게 받아보세요.</p>
        </div>
        <div class="flex w-full md:w-auto gap-4">
          <input type="email" placeholder="이메일 주소를 입력하세요" class="flex-grow md:w-80 px-6 py-4 bg-secondary-50 border border-secondary-100 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm"/>
          <button class="btn-primary px-8 whitespace-nowrap">구독하기</button>
        </div>
      </div>
    </div>
  </section>
</div>
