<script lang="ts">
  import { _ } from 'svelte-i18n';
  import { Target, Eye, ShieldCheck, Zap, Lightbulb, Users } from 'lucide-svelte';
  import { onMount } from 'svelte';

  let mounted = $state(false);
  onMount(() => {
    mounted = true;
  });

  const valueIcons = [Lightbulb, ShieldCheck, Users, Zap];
</script>

<svelte:head>
  <title>{$_('common.nav.about')} | KARBON BUILDER</title>
</svelte:head>

<div class="pt-20">
  <!-- Hero Section -->
  <section class="relative py-24 overflow-hidden">
    <div class="absolute inset-0 bg-linear-to-br from-primary-50 to-white -z-10"></div>
    <div class="container-custom">
      <div class="max-w-3xl">
        <h1 class="text-4xl md:text-6xl font-bold text-secondary-950 mb-6 leading-tight animate-fade-in-up">
          {$_('about.title')} <br/>
          <span class="text-primary-600 block mt-2 text-5xl md:text-7xl italic tracking-tight">{$_('about.titleHighlight')}</span>
        </h1>
        <p class="text-xl text-secondary-600 leading-relaxed mb-8 animate-fade-in-up" style="animation-delay: 0.1s">
          {$_('about.description')}
        </p>
      </div>
    </div>
    
    <!-- Decorative elements -->
    <div class="absolute top-24 right-0 w-1/3 h-64 bg-primary-100/50 blur-3xl -z-10 rounded-full"></div>
    <div class="absolute bottom-0 left-24 w-64 h-64 bg-secondary-100/50 blur-3xl -z-10 rounded-full"></div>
  </section>

  <!-- Mission & Vision -->
  <section class="py-24 bg-white">
    <div class="container-custom">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <div class="p-10 rounded-3xl bg-secondary-50 border border-secondary-100 hover:shadow-xl transition-all duration-500 group">
          <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center text-white mb-8 group-hover:scale-110 transition-transform">
            <Target size={28} />
          </div>
          <h2 class="text-3xl font-bold text-secondary-950 mb-4">{$_('about.mission.title')}</h2>
          <p class="text-lg text-secondary-600 leading-relaxed">
            {$_('about.mission.description')}
          </p>
        </div>
        
        <div class="p-10 rounded-3xl bg-primary-600 border border-primary-500 hover:shadow-xl transition-all duration-500 group">
          <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-primary-600 mb-8 group-hover:scale-110 transition-transform">
            <Eye size={28} />
          </div>
          <h2 class="text-3xl font-bold text-white mb-4">{$_('about.vision.title')}</h2>
          <p class="text-lg text-white/90 leading-relaxed">
            {$_('about.vision.description')}
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- Core Values -->
  <section class="py-24 bg-secondary-50">
    <div class="container-custom">
      <div class="text-center max-w-2xl mx-auto mb-20">
        <h2 class="text-4xl font-bold text-secondary-950 mb-6">{$_('about.values.title')}</h2>
        <p class="text-lg text-secondary-600">{$_('about.values.subtitle')}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        {#each ['innovation', 'insight', 'trust', 'excellence'] as value, i}
          <div class="bg-white p-8 rounded-2xl shadow-sm border border-secondary-100 hover:-translate-y-2 transition-all duration-300">
            <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-xl flex items-center justify-center mb-6">
              <svelte:component this={valueIcons[i]} size={24} />
            </div>
            <h3 class="text-xl font-bold text-secondary-950 mb-3">{$_(`about.values.${value}.title`)}</h3>
            <p class="text-secondary-600 text-sm leading-relaxed">
              {$_(`about.values.${value}.description`)}
            </p>
          </div>
        {/each}
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-24 bg-secondary-950 text-white overflow-hidden relative">
    <div class="container-custom relative z-10 text-center">
      <h2 class="text-3xl md:text-4xl font-bold mb-8">{$_('home.cta.title')}</h2>
      <p class="text-secondary-400 mb-10 max-w-2xl mx-auto">{$_('home.cta.subtitle')}</p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
        <a href="/ko/qna" class="btn-primary py-4 px-8 text-lg w-full sm:w-auto">
          {$_('common.actions.contact')}
        </a>
      </div>
    </div>
    
    <!-- Background glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full h-full bg-primary-600/10 blur-[120px] -z-0 rounded-full"></div>
  </section>
</div>

<style>
  :global(.animate-fade-in-up) {
    animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
  }

  @keyframes fadeInUp {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>
