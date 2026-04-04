<script lang="ts">
  import { goto } from '$app/navigation';
  import { login } from '$lib/api/auth';
  import { setMember } from '$lib/stores/auth';
  import { Mail, Lock, Eye, EyeOff, Loader2 } from 'lucide-svelte';

  let email = $state('');
  let password = $state('');
  let showPassword = $state(false);
  let autoLogin = $state(false);
  let error = $state('');
  let loading = $state(false);

  async function handleLogin(e: Event) {
    e.preventDefault();

    if (!email || !password) {
      error = '이메일과 비밀번호를 입력해주세요.';
      return;
    }

    loading = true;
    error = '';

    // Timeout after 10 seconds
    const timeoutId = setTimeout(() => {
      loading = false;
      error = '서버 연결 시간이 초과되었습니다.';
    }, 10000);

    try {
      const response = await login({
        mb_id: email,
        mb_email: email,
        mb_password: password,
        login_type: 'email',
        auto_login: autoLogin,
      });

      clearTimeout(timeoutId);

      const member = response.data?.mb || response.mb;
      if (member) {
        setMember(member);
        goto('/');
      } else {
        error = '로그인 응답이 올바르지 않습니다.';
      }
    } catch (e: any) {
      clearTimeout(timeoutId);
      error = e.message || '로그인에 실패했습니다.';
    } finally {
      loading = false;
      clearTimeout(timeoutId);
    }
  }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
  <div class="w-full max-w-md">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Karbon Studio</h1>
        <p class="text-gray-500 mt-2">로그인 후 이용해주세요</p>
      </div>

      {#if error}
        <div class="bg-red-50 text-red-600 text-sm p-3 rounded-md mb-4">
          {error}
        </div>
      {/if}

      <form onsubmit={handleLogin} class="space-y-5">
        <div class="space-y-2">
          <label for="email" class="text-sm font-medium text-gray-700">이메일</label>
          <div class="relative">
            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="email"
              type="email"
              placeholder="name@example.com"
              bind:value={email}
              disabled={loading}
              required
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label for="password" class="text-sm font-medium text-gray-700">비밀번호</label>
          <div class="relative">
            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="password"
              type={showPassword ? 'text' : 'password'}
              placeholder="비밀번호"
              bind:value={password}
              disabled={loading}
              required
              class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50"
            />
            <button
              type="button"
              onclick={() => (showPassword = !showPassword)}
              class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              {#if showPassword}
                <EyeOff size={18} />
              {:else}
                <Eye size={18} />
              {/if}
            </button>
          </div>
        </div>

        <div class="flex items-center">
          <input
            id="autoLogin"
            type="checkbox"
            bind:checked={autoLogin}
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label for="autoLogin" class="ml-2 text-sm text-gray-600">자동로그인</label>
        </div>

        <button
          type="submit"
          disabled={loading}
          class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          {#if loading}
            <Loader2 class="animate-spin" size={20} />
            로그인 중...
          {:else}
            로그인
          {/if}
        </button>
      </form>

      <div class="mt-6 flex items-center justify-between text-sm">
        <a href="/auth/find" class="text-gray-500 hover:text-gray-700 hover:underline">
          비밀번호 찾기
        </a>
        <a href="/auth/register" class="text-blue-600 hover:text-blue-700 hover:underline">
          회원가입
        </a>
      </div>
    </div>
  </div>
</div>