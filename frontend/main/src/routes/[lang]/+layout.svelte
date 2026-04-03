<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { _ } from 'svelte-i18n';
  import { Menu, X, ChevronRight, Facebook, Instagram, Youtube, Mail, Phone, MapPin } from 'lucide-svelte';
  import favicon from '$lib/assets/favicon.svg';
  import { base } from '$app/paths';
  import LanguageDropdown from '$lib/components/LanguageDropdown.svelte';

  let { children } = $props();
  
  let isMenuOpen = $state(false);
  let isScrolled = $state(false);

  onMount(() => {
    const handleScroll = () => {
      isScrolled = window.scrollY > 20;
    };
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  });

  function toggleMenu() {
    isMenuOpen = !isMenuOpen;
  }

  function closeMenu() {
    isMenuOpen = false;
  }
</script>

<svelte:head>
  <link rel="icon" href={favicon} />
  <title>KARBON BUILDER - {$_('common.nav.home')}</title>
</svelte:head>

<div class="min-h-screen flex flex-col">
  <header 
    class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 {isScrolled ? 'bg-white/90 backdrop-blur-md shadow-sm py-3' : 'bg-transparent py-5'}"
  >
    <div class="container-custom flex items-center justify-between">
      <a href="{base}/" class="text-2xl font-bold text-primary-600 tracking-tight" onclick={closeMenu}>
        KARBON<span class="text-secondary-900">BUILDER</span>
      </a>

      <nav class="hidden md:flex items-center space-x-8">
        <a 
          href="{base}/about" 
          class="text-sm font-medium transition-colors hover:text-primary-600 {page.url.pathname.includes('/about') ? 'text-primary-600' : 'text-secondary-700'}"
        >
          {$_('common.nav.about')}
        </a>
        <a 
          href="{base}/history" 
          class="text-sm font-medium transition-colors hover:text-primary-600 {page.url.pathname.includes('/history') ? 'text-primary-600' : 'text-secondary-700'}"
        >
          {$_('common.nav.history')}
        </a>
        <a 
          href="{base}/services" 
          class="text-sm font-medium transition-colors hover:text-primary-600 {page.url.pathname.includes('/services') ? 'text-primary-600' : 'text-secondary-700'}"
        >
          {$_('common.nav.services')}
        </a>
        <a 
          href="{base}/qna" 
          class="text-sm font-medium transition-colors hover:text-primary-600 {page.url.pathname.includes('/qna') ? 'text-primary-600' : 'text-secondary-700'}"
        >
          {$_('common.nav.qna')}
        </a>
        <a 
          href="{base}/notice" 
          class="text-sm font-medium transition-colors hover:text-primary-600 {page.url.pathname.includes('/notice') ? 'text-primary-600' : 'text-secondary-700'}"
        >
          {$_('common.nav.notice')}
        </a>
        <a href="{base}/qna" class="btn-primary py-2 px-5 text-sm">{$_('common.actions.contact')}</a>
        <LanguageDropdown />
      </nav>

      <button class="md:hidden p-2 text-secondary-900" onclick={toggleMenu} aria-label="Toggle Menu">
        {#if isMenuOpen}
          <X size={24} />
        {:else}
          <Menu size={24} />
        {/if}
      </button>
    </div>
  </header>

  {#if isMenuOpen}
    <div 
      class="fixed inset-0 z-40 bg-white pt-24 px-6 md:hidden animate-fade-in-up"
    >
      <nav class="flex flex-col space-y-6">
        <a 
          href="{base}/about" 
          class="text-xl font-semibold text-secondary-900 flex items-center justify-between border-b border-secondary-100 pb-4"
          onclick={closeMenu}
        >
          {$_('common.nav.about')}
          <ChevronRight size={20} class="text-secondary-400" />
        </a>
        <a 
          href="{base}/history" 
          class="text-xl font-semibold text-secondary-900 flex items-center justify-between border-b border-secondary-100 pb-4"
          onclick={closeMenu}
        >
          {$_('common.nav.history')}
          <ChevronRight size={20} class="text-secondary-400" />
        </a>
        <a 
          href="{base}/services" 
          class="text-xl font-semibold text-secondary-900 flex items-center justify-between border-b border-secondary-100 pb-4"
          onclick={closeMenu}
        >
          {$_('common.nav.services')}
          <ChevronRight size={20} class="text-secondary-400" />
        </a>
        <a 
          href="{base}/qna" 
          class="text-xl font-semibold text-secondary-900 flex items-center justify-between border-b border-secondary-100 pb-4"
          onclick={closeMenu}
        >
          {$_('common.nav.qna')}
          <ChevronRight size={20} class="text-secondary-400" />
        </a>
        <a 
          href="{base}/notice" 
          class="text-xl font-semibold text-secondary-900 flex items-center justify-between border-b border-secondary-100 pb-4"
          onclick={closeMenu}
        >
          {$_('common.nav.notice')}
          <ChevronRight size={20} class="text-secondary-400" />
        </a>
        <a href="{base}/qna" class="btn-primary w-full py-4 text-lg" onclick={closeMenu}>{$_('common.actions.contact')}</a>
        <LanguageDropdown />
      </nav>
    </div>
  {/if}

  <main class="flex-grow">
    {@render children()}
  </main>

  <footer class="bg-secondary-950 text-secondary-300 pt-16 pb-8">
    <div class="container-custom">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
        <div class="space-y-6">
          <a href={base + "/"} class="text-2xl font-bold text-white tracking-tight">
            KARBON<span class="text-primary-400">BUILDER</span>
          </a>
          <p class="text-sm leading-relaxed">
            {$_('home.hero.description')}
          </p>
          <div class="flex space-x-4">
            <span class="hover:text-white transition-colors cursor-pointer"><Facebook size={20} /></span>
            <span class="hover:text-white transition-colors cursor-pointer"><Instagram size={20} /></span>
            <span class="hover:text-white transition-colors cursor-pointer"><Youtube size={20} /></span>
          </div>
        </div>

        <div>
          <h3 class="text-white font-bold mb-6">{$_('common.footer.services')}</h3>
          <ul class="space-y-4 text-sm">
            <li><a href="{base}/services" class="hover:text-white transition-colors">{$_('home.services.web.title')}</a></li>
            <li><a href="{base}/services" class="hover:text-white transition-colors">{$_('home.services.design.title')}</a></li>
            <li><a href="{base}/services" class="hover:text-white transition-colors">{$_('home.services.cloud.title')}</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-white font-bold mb-6">{$_('common.footer.support')}</h3>
          <ul class="space-y-4 text-sm">
            <li><a href="{base}/about" class="hover:text-white transition-colors">{$_('common.nav.about')}</a></li>
            <li><a href="{base}/notice" class="hover:text-white transition-colors">{$_('common.nav.notice')}</a></li>
            <li><a href="{base}/qna" class="hover:text-white transition-colors">{$_('common.nav.qna')}</a></li>
            <li><a href="{base}/qna" class="hover:text-white transition-colors">{$_('common.actions.contact')}</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-white font-bold mb-6">{$_('common.footer.contact')}</h3>
          <ul class="space-y-4 text-sm">
            <li class="flex items-start space-x-3">
              <MapPin size={18} class="text-primary-400 shrink-0" />
              <span>{$_('common.footer.address')}</span>
            </li>
            <li class="flex items-center space-x-3">
              <Phone size={18} class="text-primary-400 shrink-0" />
              <span>{$_('common.footer.phone')}</span>
            </li>
            <li class="flex items-center space-x-3">
              <Mail size={18} class="text-primary-400 shrink-0" />
              <span>{$_('common.footer.email')}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="border-t border-secondary-800 pt-8 flex flex-col md:flex-row justify-between items-center text-xs">
        <p>{$_('common.footer.copyright')}</p>
        <div class="flex space-x-6 mt-4 md:mt-0">
          <span class="hover:text-white transition-colors cursor-pointer">{$_('common.footer.terms')}</span>
          <span class="hover:text-white transition-colors cursor-pointer font-bold">{$_('common.footer.privacy')}</span>
          <span class="hover:text-white transition-colors cursor-pointer">{$_('common.footer.emailReject')}</span>
        </div>
      </div>
    </div>
  </footer>
</div>
