<script lang="ts">
  import { goto } from '$app/navigation';
  import { register as registerApi } from '$lib/api/auth';
  import { Mail, Lock, User, Phone, Eye, EyeOff, Loader2 } from 'lucide-svelte';

  let mb_id = $state('');
  let mb_email = $state('');
  let mb_password = $state('');
  let mb_password_confirm = $state('');
  let mb_name = $state('');
  let mb_hp = $state('');
  let showPassword = $state(false);
  let error = $state('');
  let loading = $state(false);

  async function handleRegister(e: Event) {
    e.preventDefault();

    if (!mb_id || !mb_email || !mb_password || !mb_name) {
      error = '필수 항목을 모두 입력해주세요.';
      return;
    }

    if (mb_password !== mb_password_confirm) {
      error = '비밀번호가 일치하지 않습니다.';
      return;
    }

    if (mb_password.length < 4) {
      error = '비밀번호는 4자 이상이어야 합니다.';
      return;
    }

    loading = true;
    error = '';

    try {
      await registerApi({
        mb_id,
        mb_email,
        mb_password,
        mb_name,
        mb_hp: mb_hp || undefined,
      });

      goto('/auth/login');
    } catch (e: any) {
      error = e.message || '회원가입에 실패했습니다.';
    } finally {
      loading = false;
    }
  }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4 py-8">
  <div class="w-full max-w-md">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">회원가입</h1>
        <p class="text-gray-500 mt-2">Karbon Studio에 가입하세요</p>
      </div>

      {#if error}
        <div class="bg-red-50 text-red-600 text-sm p-3 rounded-md mb-4">
          {error}
        </div>
      {/if}

      <form onsubmit={handleRegister} class="space-y-4">
        <div class="space-y-2">
          <label for="mb_id" class="text-sm font-medium text-gray-700">아이디 *</label>
          <div class="relative">
            <User class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="mb_id"
              type="text"
              placeholder="아이디"
              bind:value={mb_id}
              disabled={loading}
              required
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label for="mb_name" class="text-sm font-medium text-gray-700">이름 *</label>
          <div class="relative">
            <User class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="mb_name"
              type="text"
              placeholder="이름"
              bind:value={mb_name}
              disabled={loading}
              required
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label for="mb_email" class="text-sm font-medium text-gray-700">이메일 *</label>
          <div class="relative">
            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="mb_email"
              type="email"
              placeholder="name@example.com"
              bind:value={mb_email}
              disabled={loading}
              required
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label for="mb_hp" class="text-sm font-medium text-gray-700">휴대폰</label>
          <div class="relative">
            <Phone class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="mb_hp"
              type="tel"
              placeholder="010-1234-5678"
              bind:value={mb_hp}
              disabled={loading}
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50"
            />
          </div>
        </div>

        <div class="space-y-2">
          <label for="mb_password" class="text-sm font-medium text-gray-700">비밀번호 *</label>
          <div class="relative">
            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="mb_password"
              type={showPassword ? 'text' : 'password'}
              placeholder="비밀번호 (4자 이상)"
              bind:value={mb_password}
              disabled={loading}
              required
              minlength="4"
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

        <div class="space-y-2">
          <label for="mb_password_confirm" class="text-sm font-medium text-gray-700">비밀번호 확인 *</label>
          <div class="relative">
            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input
              id="mb_password_confirm"
              type={showPassword ? 'text' : 'password'}
              placeholder="비밀번호 확인"
              bind:value={mb_password_confirm}
              disabled={loading}
              required
              class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-50"
            />
          </div>
        </div>

        <button
          type="submit"
          disabled={loading}
          class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          {#if loading}
            <Loader2 class="animate-spin" size={20} />
            가입 중...
          {:else}
            회원가입
          {/if}
        </button>
      </form>

      <div class="mt-6 text-center text-sm">
        <span class="text-gray-500">이미 계정이 있으신가요? </span>
        <a href="/auth/login" class="text-blue-600 hover:text-blue-700 hover:underline">
          로그인
        </a>
      </div>
    </div>
  </div>
</div>