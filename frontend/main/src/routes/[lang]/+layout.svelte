<script lang="ts">
  import { onMount } from 'svelte';
  import { page } from '$app/state';
  import { _, isLoading, waitLocale } from 'svelte-i18n';
  import { Facebook, Instagram, Youtube, Mail, Phone, MapPin } from 'lucide-svelte';
  import favicon from '$lib/assets/favicon.svg';
  import { base } from '$app/paths';
  import { initLocale, initLocaleAndWait } from '$lib/i18n';
  import { setMenus } from '$lib/store/menu';
  import { getMenus } from '$lib/api/menu';
  import { Header } from '$lib/skin/layout/header';

  let { children } = $props();
  
  let i18nReady = $state(false);
  
  const langFromPath = page.url.pathname.split('/')[2] || 'ko';
  
  function withLang(path: string): string {
    return `/${langFromPath}${path}`;
  }

  onMount(async () => {
    initLocale(langFromPath);
    await waitLocale();
    i18nReady = true;
    
    try {
      const menus = await getMenus('pc');
      setMenus(menus, langFromPath);
    } catch (e) {
      console.warn('Failed to load menus:', e);
    }
  });
</script>

<svelte:head>
  <link rel="icon" href={favicon} />
  {#if i18nReady}
    <title>KARBON BUILDER - {$_('common.nav.home')}</title>
  {:else}
    <title>KARBON BUILDER</title>
  {/if}
</svelte:head>

{#if $isLoading || !i18nReady}
  <div class="min-h-screen flex items-center justify-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
  </div>
{:else}
<div class="min-h-screen flex flex-col">
  <Header lang={langFromPath} />

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
            <li><a href="{base}{withLang('/services')}" class="hover:text-white transition-colors">{$_('home.services.web.title')}</a></li>
            <li><a href="{base}{withLang('/services')}" class="hover:text-white transition-colors">{$_('home.services.design.title')}</a></li>
            <li><a href="{base}{withLang('/services')}" class="hover:text-white transition-colors">{$_('home.services.cloud.title')}</a></li>
          </ul>
        </div>

        <div>
          <h3 class="text-white font-bold mb-6">{$_('common.footer.support')}</h3>
          <ul class="space-y-4 text-sm">
            <li><a href="{base}{withLang('/about')}" class="hover:text-white transition-colors">{$_('common.nav.about')}</a></li>
            <li><a href="{base}{withLang('/notice')}" class="hover:text-white transition-colors">{$_('common.nav.notice')}</a></li>
            <li><a href="{base}{withLang('/qna')}" class="hover:text-white transition-colors">{$_('common.nav.qna')}</a></li>
            <li><a href="{base}{withLang('/qna')}" class="hover:text-white transition-colors">{$_('common.actions.contact')}</a></li>
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
{/if}
