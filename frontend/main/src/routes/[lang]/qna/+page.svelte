<script lang="ts">
  import { _ } from 'svelte-i18n';
  import { Plus, Minus, Send, Phone, Mail, MapPin } from 'lucide-svelte';

  let openIndex = $state(0);

  function toggleFaq(index: number) {
    openIndex = openIndex === index ? -1 : index;
  }

  const faqs = [
    {
      question: "프로젝트 개발 기간은 보통 얼마나 걸리나요?",
      answer: "프로젝트의 규모와 복잡도에 따라 다르지만, 일반적인 비즈니스 사이트는 4~8주, 복잡한 플랫폼은 3개월 이상의 기간이 소요됩니다. 상담 시 상세 일정을 안내해 드립니다."
    },
    {
      question: "유지보수 서비스도 제공하시나요?",
      answer: "네, 프로젝트 완료 후에도 안정적인 운영을 위한 유지보수 패키지를 제공합니다. 보안 업데이트, 버그 수정, 그리고 정기적인 성능 최적화가 포함됩니다."
    },
    {
      question: "커스텀 디자인이 가능한가요?",
      answer: "칼본빌더는 템플릿 사용을 지양하고 모든 프로젝트를 고객사의 브랜드 아이덴티티에 맞춘 100% 커스텀 디자인으로 진행합니다."
    },
    {
      question: "글로벌 서비스 구축을 위한 다국어 지원이 가능한가요?",
      answer: "네, 다국어 처리 엔진을 내장하여 전 세계 어디서든 원활하게 접속하고 각 언어별로 최적화된 UI를 제공하는 글로벌 서비스를 구축해 드립니다."
    }
  ];
</script>

<svelte:head>
  <title>{$_('common.nav.qna')} | KARBON BUILDER</title>
</svelte:head>

<div class="pt-20">
  <!-- Hero Section -->
  <section class="py-24 bg-secondary-50 relative overflow-hidden">
    <div class="container-custom relative z-10 text-center">
      <h1 class="text-4xl md:text-5xl font-bold text-secondary-950 mb-6">{$_('qna.title')}</h1>
      <p class="text-secondary-600 max-w-xl mx-auto">{$_('qna.description')}</p>
    </div>
    
    <!-- Abstract pattern -->
    <div class="absolute -top-12 -right-12 w-64 h-64 bg-primary-100/50 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-12 -left-12 w-96 h-96 bg-primary-50/50 rounded-full blur-3xl"></div>
  </section>

  <!-- FAQ Section -->
  <section class="py-24 bg-white">
    <div class="container-custom max-w-4xl">
      <div class="space-y-6">
        {#each faqs as faq, i}
          <div class="overflow-hidden border border-secondary-100 rounded-3xl transition-all duration-300 {openIndex === i ? 'shadow-xl shadow-primary-500/5 bg-primary-50/30' : 'bg-white hover:bg-secondary-50'}">
            <button 
              class="w-full flex items-center justify-between p-8 text-left group"
              onclick={() => toggleFaq(i)}
            >
              <h3 class="text-lg md:text-xl font-bold text-secondary-950 pr-8 group-hover:text-primary-600 transition-colors">{faq.question}</h3>
              <div class="shrink-0 w-10 h-10 rounded-full border border-secondary-200 flex items-center justify-center text-secondary-400 group-hover:border-primary-600 group-hover:text-primary-600 transition-all">
                {#if openIndex === i}
                  <Minus size={20} />
                {:else}
                  <Plus size={20} />
                {/if}
              </div>
            </button>
            {#if openIndex === i}
              <div class="px-8 pb-8 animate-fade-in">
                <div class="pt-0 border-t border-secondary-100/10"></div>
                <p class="text-secondary-600 leading-relaxed text-lg mt-6">
                  {faq.answer}
                </p>
              </div>
            {/if}
          </div>
        {/each}
      </div>

      <!-- Contact CTA -->
      <div class="mt-20 p-12 bg-secondary-950 rounded-[3rem] text-white text-center">
        <h2 class="text-3xl font-bold mb-6">{$_('qna.contactTitle')}</h2>
        <p class="text-secondary-400 mb-10">{$_('qna.contactDesc')}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
           <a href="mailto:contact@karbonbuilder.com" class="flex items-center gap-2 text-lg font-medium hover:text-primary-400 transition-colors">
              <Mail size={20} class="text-primary-400" /> contact@karbonbuilder.com
           </a>
           <span class="hidden sm:block text-secondary-800">|</span>
           <a href="tel:02-1234-5678" class="flex items-center gap-2 text-lg font-medium hover:text-primary-400 transition-colors">
              <Phone size={20} class="text-primary-400" /> 02-1234-5678
           </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact Form Section -->
  <section class="py-24 bg-secondary-50">
    <div class="container-custom">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-20">
        <!-- Contact Info -->
        <div class="space-y-12">
          <div>
            <h2 class="text-4xl font-bold text-secondary-950 mb-8">{$_('common.actions.contact')}</h2>
            <p class="text-lg text-secondary-600 leading-relaxed">
              성공적인 비즈니스를 위한 첫걸음, 칼본빌더가 함께하겠습니다. <br/>
              문의를 남겨주시면 담당 전문가가 24시간 이내에 연락드립니다.
            </p>
          </div>

          <ul class="space-y-8">
            <li class="flex items-start gap-6">
              <div class="w-14 h-14 bg-white rounded-2xl shadow-sm border border-secondary-100 flex items-center justify-center text-primary-600 shrink-0">
                <MapPin size={24} />
              </div>
              <div>
                <h4 class="font-bold text-secondary-950 mb-2">Office</h4>
                <p class="text-secondary-600 text-sm">서울특별시 강남구 테헤란로 123, <br/>칼본빌딩 15층</p>
              </div>
            </li>
            <li class="flex items-start gap-6">
              <div class="w-14 h-14 bg-white rounded-2xl shadow-sm border border-secondary-100 flex items-center justify-center text-primary-600 shrink-0">
                <Phone size={24} />
              </div>
              <div>
                <h4 class="font-bold text-secondary-950 mb-2">Phone</h4>
                <p class="text-secondary-600 text-sm">02-1234-5678</p>
              </div>
            </li>
            <li class="flex items-start gap-6">
              <div class="w-14 h-14 bg-white rounded-2xl shadow-sm border border-secondary-100 flex items-center justify-center text-primary-600 shrink-0">
                <Mail size={24} />
              </div>
              <div>
                <h4 class="font-bold text-secondary-950 mb-2">Email</h4>
                <p class="text-secondary-600 text-sm">contact@karbonbuilder.com</p>
              </div>
            </li>
          </ul>
        </div>

        <!-- Form -->
        <div class="bg-white p-10 md:p-14 rounded-[3rem] shadow-2xl shadow-secondary-900/5 border border-secondary-100">
           <form class="space-y-8">
             <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
               <div class="space-y-3">
                 <label for="name" class="text-xs font-bold text-secondary-500 uppercase tracking-widest pl-2">이름 / 기업명</label>
                 <input type="text" id="name" class="w-full px-6 py-4 bg-secondary-50 border border-secondary-100 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm"/>
               </div>
               <div class="space-y-3">
                 <label for="email" class="text-xs font-bold text-secondary-500 uppercase tracking-widest pl-2">이메일 주소</label>
                 <input type="email" id="email" class="w-full px-6 py-4 bg-secondary-50 border border-secondary-100 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm"/>
               </div>
             </div>
             
             <div class="space-y-3">
               <label for="subject" class="text-xs font-bold text-secondary-500 uppercase tracking-widest pl-2">문의 제목</label>
               <input type="text" id="subject" class="w-full px-6 py-4 bg-secondary-50 border border-secondary-100 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm"/>
             </div>

             <div class="space-y-3">
               <label for="message" class="text-xs font-bold text-secondary-500 uppercase tracking-widest pl-2">문의 내용</label>
               <textarea id="message" rows="5" class="w-full px-6 py-4 bg-secondary-50 border border-secondary-100 rounded-2xl focus:outline-hidden focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm resize-none"></textarea>
             </div>

             <button type="submit" class="btn-primary w-full py-5 text-lg flex items-center justify-center gap-3">
               {$_('common.actions.contact')} <Send size={20} />
             </button>
           </form>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
  }
</style>
