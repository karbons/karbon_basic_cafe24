<script lang="ts">
  import { _ } from 'svelte-i18n';
  import { base } from '$app/paths';
  import { ArrowRight, Zap, Shield, Smartphone, CheckCircle2 } from 'lucide-svelte';

  const features = [
    {
      icon: Zap,
      title: $_('home.features.speed.title'),
      description: $_('home.features.speed.description')
    },
    {
      icon: Shield,
      title: $_('home.features.security.title'),
      description: $_('home.features.security.description')
    },
    {
      icon: Smartphone,
      title: $_('home.features.responsive.title'),
      description: $_('home.features.responsive.description')
    }
  ];

  const stats = [
    { label: $_('home.stats.projects'), value: '500+' },
    { label: $_('home.stats.partners'), value: '120+' },
    { label: $_('home.stats.experts'), value: '50+' },
    { label: $_('home.stats.satisfaction'), value: '98%' }
  ];
</script>

<svelte:head>
  <title>KARBON BUILDER</title>
</svelte:head>

<section class="relative min-h-screen flex items-center pt-20 overflow-hidden">
  <div class="absolute inset-0 -z-10">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-50 to-white"></div>
  </div>

  <div class="container-custom grid lg:grid-cols-2 gap-12 items-center">
    <div>
      <h1 class="text-5xl md:text-7xl font-bold text-secondary-900 leading-tight mb-6">
        {$_('home.hero.title')} <br />
        <span class="text-primary-600">{$_('home.hero.titleHighlight')}</span>
      </h1>
      <p class="text-xl text-secondary-600 mb-10 leading-relaxed max-w-xl">
        {$_('home.hero.description')}
      </p>
      <div class="flex flex-col sm:flex-row gap-4">
        <a href="{base}/services" class="btn-primary text-lg group">
          {$_('home.hero.ctaPrimary')}
          <ArrowRight size={20} class="ml-2 group-hover:translate-x-1 transition-transform" />
        </a>
        <a href="{base}/about" class="btn-secondary text-lg">
          {$_('home.hero.ctaSecondary')}
        </a>
      </div>
    </div>
  </div>
</section>

<section class="py-24 bg-white">
  <div class="container-custom">
    <div class="text-center mb-16">
      <h2 class="section-title">{$_('home.features.title')}</h2>
      <p class="section-subtitle">{$_('home.features.subtitle')}</p>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
      {#each features as feature}
        <div class="p-8 rounded-2xl border border-secondary-100 hover:border-primary-200 hover:shadow-xl transition-all duration-300 group">
          <div class="w-14 h-14 bg-primary-50 rounded-xl flex items-center justify-center mb-6 group-hover:bg-primary-600 transition-colors duration-300">
            <feature.icon size={28} class="text-primary-600 group-hover:text-white transition-colors duration-300" />
          </div>
          <h3 class="text-xl font-bold text-secondary-900 mb-4">{feature.title}</h3>
          <p class="text-secondary-600 leading-relaxed">{feature.description}</p>
        </div>
      {/each}
    </div>
  </div>
</section>

<section class="py-20 bg-primary-600 text-white">
  <div class="container-custom">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
      {#each stats as stat}
        <div>
          <p class="text-4xl md:text-5xl font-bold mb-2">{stat.value}</p>
          <p class="text-primary-100 text-sm md:text-base">{stat.label}</p>
        </div>
      {/each}
    </div>
  </div>
</section>
