<script lang="ts">
  import { _, isLoading } from 'svelte-i18n';
</script>

<svelte:head>
  <title>{$_('common.nav.qna')} | KARBON BUILDER</title>
</svelte:head>

{#if $isLoading}
  <div class="min-h-screen flex items-center justify-center">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
  </div>
{:else}
<div class="pt-24 pb-16">
  <section class="container-custom">
    <h1 class="text-4xl font-bold text-secondary-900 mb-8">{$_('common.nav.qna')}</h1>
    <p class="text-lg text-secondary-600">Q&A content coming soon</p>
  </section>
</div>
{/if}
