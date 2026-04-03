<script lang="ts">
  import { _, isLoading } from 'svelte-i18n';
</script>

<svelte:head>
  <title>{$_('common.nav.services')} | KARBON BUILDER</title>
</svelte:head>

{#if $isLoading}
  <div class="min-h-screen flex items-center justify-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
  </div>
{:else}
<div class="pt-24 pb-16">
  <section class="container-custom">
    <h1 class="text-4xl font-bold text-secondary-900 mb-8">{$_('common.nav.services')}</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="p-6 border rounded-lg">
        <h3 class="text-xl font-semibold mb-4">{$_('home.services.web.title')}</h3>
        <p class="text-secondary-600">{$_('home.services.web.description')}</p>
      </div>
      <div class="p-6 border rounded-lg">
        <h3 class="text-xl font-semibold mb-4">{$_('home.services.design.title')}</h3>
        <p class="text-secondary-600">{$_('home.services.design.description')}</p>
      </div>
      <div class="p-6 border rounded-lg">
        <h3 class="text-xl font-semibold mb-4">{$_('home.services.cloud.title')}</h3>
        <p class="text-secondary-600">{$_('home.services.cloud.description')}</p>
      </div>
    </div>
  </section>
</div>
{/if}
