<script lang="ts">
  import '../app.css';
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { page as pageData } from '$app/state';
  import Sidebar from '$lib/components/Sidebar.svelte';
  import { Bell, LogOut, User } from 'lucide-svelte';
  import { isAuthenticated, memberStore, clearMember } from '$lib/stores/auth';
  import { logout as logoutApi } from '$lib/api/auth';
  import { get } from 'svelte/store';

  let { children } = $props();

  let authChecked = $state(false);
  let authenticated = $state(false);
  
  const isAuthRoute = $derived(pageData.url.pathname.startsWith('/auth'));

  onMount(() => {
    // Check initial auth state
    authenticated = get(isAuthenticated);
    authChecked = true;

    // Subscribe to auth changes
    const unsubscribe = isAuthenticated.subscribe((value) => {
      authenticated = value;
      authChecked = true;
      
      // Redirect to login if not authenticated and not on auth route
      if (!value && !pageData.url.pathname.startsWith('/auth')) {
        goto('/auth/login');
      }
    });

    return () => unsubscribe();
  });

  async function handleLogout() {
    try {
      await logoutApi();
    } catch (e) {
      console.error('Logout failed:', e);
    }
    clearMember();
    goto('/auth/login');
  }
</script>

{#if !authChecked}
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
  </div>
{:else if !isAuthRoute && !authenticated}
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
  </div>
{:else}
  {#if isAuthRoute}
    {@render children()}
  {:else}
    <div class="flex h-screen bg-gray-100 font-sans text-gray-900">
      <Sidebar />

      <main class="flex flex-1 flex-col overflow-hidden">
        <header class="flex h-16 items-center justify-between border-b bg-white px-8">
          <h1 class="text-lg font-semibold capitalize">
            {pageData.url.pathname === '/'
              ? 'Welcome'
              : pageData.url.pathname.split('/').filter(Boolean).join(' > ')}
          </h1>
          <div class="flex items-center gap-4">
            <button
              class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500"
            >
              <Bell size={20} />
            </button>
            <div class="flex items-center gap-2">
              <div class="text-sm text-gray-600">
                {$memberStore?.mb_name || $memberStore?.mb_email || 'User'}
              </div>
              <button
                onclick={handleLogout}
                class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500"
                title="로그아웃"
              >
                <LogOut size={20} />
              </button>
            </div>
          </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
          <div class="mx-auto max-w-5xl">
            {@render children()}
          </div>
        </div>
      </main>
    </div>
  {/if}
{/if}