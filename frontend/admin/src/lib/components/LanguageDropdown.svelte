<script lang="ts">
  import { locale } from 'svelte-i18n';
  import { base } from '$app/paths';
  import { onMount } from 'svelte';
  
  let currentLang = $state('ko');
  
  onMount(() => {
    const unsubscribe = locale.subscribe((value) => {
      if (value) {
        currentLang = value;
      }
    });
    
    return () => {
      unsubscribe();
    };
  });
  
  function changeLanguage(lang: string) {
    if (lang === currentLang) return;
    
    const currentPath = window.location.pathname;
    const newPath = currentPath.replace(/\/(ko|en)\//, `/${lang}/`);
    
    if (newPath !== currentPath) {
      window.location.href = newPath;
    }
  }
</script>

<div class="language-dropdown">
  <select
    bind:value={currentLang}
    onchange={(e) => changeLanguage(e.currentTarget.value)}
    class="px-3 py-2 border border-secondary-200 rounded-lg text-sm bg-white text-secondary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent cursor-pointer"
  >
    <option value="ko">한국어</option>
    <option value="en">English</option>
  </select>
</div>

<style>
  .language-dropdown select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    appearance: none;
  }
</style>
