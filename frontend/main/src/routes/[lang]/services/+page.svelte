<script lang="ts">
  import { _ } from 'svelte-i18n';
  import { Code2, Palette, Cloud, CheckCircle2, ArrowRight } from 'lucide-svelte';

  const services = [
    {
      id: "web",
      icon: Code2,
      color: "bg-blue-500",
      textColor: "text-blue-600",
      lightColor: "bg-blue-50"
    },
    {
      id: "design",
      icon: Palette,
      color: "bg-purple-500",
      textColor: "text-purple-600",
      lightColor: "bg-purple-50"
    },
    {
      id: "cloud",
      icon: Cloud,
      color: "bg-emerald-500",
      textColor: "text-emerald-600",
      lightColor: "bg-emerald-50"
    }
  ];

  const processSteps = [
    { icon: "01" },
    { icon: "02" },
    { icon: "03" },
    { icon: "04" }
  ];
</script>

<svelte:head>
  <title>{$_('common.nav.services')} | KARBON BUILDER</title>
</svelte:head>

<div class="pt-20">
  <!-- Hero Section -->
  <section class="py-24 bg-linear-to-b from-secondary-50 to-white overflow-hidden">
    <div class="container-custom">
      <div class="max-w-3xl text-center mx-auto">
        <span class="inline-block px-4 py-1.5 mb-6 text-sm font-semibold tracking-wider text-primary-600 uppercase bg-primary-100/50 rounded-full">Our Expertise</span>
        <h1 class="text-4xl md:text-6xl font-bold text-secondary-950 mb-6 leading-tight">
          {$_('services.title')} <br/>
          <span class="bg-linear-to-r from-primary-600 to-primary-400 bg-clip-text text-transparent">{$_('services.titleHighlight')}</span>
        </h1>
        <p class="text-xl text-secondary-600">
          {$_('services.description')}
        </p>
      </div>
    </div>
  </section>

  <!-- Service Details -->
  <section class="py-24 bg-white">
    <div class="container-custom">
      <div class="space-y-32">
        {#each services as service, i}
          <div class="flex flex-col lg:flex-row items-center gap-16 {i % 2 !== 0 ? 'lg:flex-row-reverse' : ''}">
            <div class="w-full lg:w-1/2">
              <div class="relative">
                <div class="w-full aspect-square rounded-[3rem] {service.lightColor} overflow-hidden flex items-center justify-center relative">
                  <div class="absolute inset-0 bg-linear-to-br from-white/20 to-transparent"></div>
                  <svelte:component this={service.icon} size={160} class="{service.textColor} opacity-20" strokeWidth={1} />
                  <div class="absolute w-3/4 aspect-video bg-white rounded-3xl shadow-2xl flex items-center justify-center p-8 border border-secondary-100 group hover:scale-[1.02] transition-transform duration-500">
                     <svelte:component this={service.icon} size={64} class="{service.textColor}" />
                  </div>
                </div>
                <!-- Floating decorative circles -->
                <div class="absolute -top-8 -right-8 w-24 h-24 {service.color} opacity-10 rounded-full blur-2xl animate-pulse"></div>
                <div class="absolute -bottom-12 -left-12 w-48 h-48 {service.color} opacity-5 rounded-full blur-3xl"></div>
              </div>
            </div>
            
            <div class="w-full lg:w-1/2">
              <span class="text-sm font-bold {service.textColor} tracking-widest uppercase mb-4 block">{$_(`services.list.${i}.subtitle`)}</span>
              <h2 class="text-4xl md:text-5xl font-bold text-secondary-950 mb-8 leading-tight">{$_(`services.list.${i}.title`)}</h2>
              <p class="text-lg text-secondary-600 mb-10 leading-relaxed">
                {$_(`services.list.${i}.description`)}
              </p>
              
              <ul class="space-y-4 mb-12">
                {#each [0, 1, 2] as featureIndex}
                  <li class="flex items-center gap-4 text-secondary-800 font-medium">
                    <div class="w-6 h-6 rounded-full {service.lightColor} flex items-center justify-center shrink-0">
                      <CheckCircle2 size={16} class="{service.textColor}" />
                    </div>
                    {$_(`services.list.${i}.features.${featureIndex}`)}
                  </li>
                {/each}
              </ul>
              
              <a href="/ko/qna" class="inline-flex items-center gap-2 group font-bold text-secondary-950 hover:text-primary-600 transition-colors">
                상담 신청하기 <ArrowRight size={20} class="group-hover:translate-x-1 transition-transform" />
              </a>
            </div>
          </div>
        {/each}
      </div>
    </div>
  </section>

  <!-- Process Section -->
  <section class="py-24 bg-secondary-50">
    <div class="container-custom">
      <div class="text-center max-w-2xl mx-auto mb-20">
        <h2 class="text-4xl font-bold text-secondary-950 mb-6">{$_('services.process.title')}</h2>
        <p class="text-lg text-secondary-600">{$_('services.process.description')}</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-8">
        {#each processSteps as step, i}
          <div class="relative bg-white p-10 rounded-3xl border border-secondary-100 hover:shadow-lg transition-all duration-300 group">
            <span class="absolute top-6 right-8 text-5xl font-black text-secondary-50 group-hover:text-primary-50 transition-colors duration-300">{step.icon}</span>
            <div class="relative z-10">
              <h3 class="text-xl font-bold text-secondary-950 mb-4">{$_(`services.process.steps.${i}.title`)}</h3>
              <p class="text-secondary-600 text-sm leading-relaxed">
                {$_(`services.process.steps.${i}.desc`)}
              </p>
            </div>
          </div>
          {#if i < 3}
            <div class="hidden lg:flex absolute top-1/2 -translate-y-1/2 left-[calc(25%*${i+1})] -translate-x-1/2 text-secondary-200 pointer-events-none">
              <!-- No arrow needed with better mobile responsive grid -->
            </div>
          {/if}
        {/each}
      </div>
    </div>
  </section>
</div>
