<script lang="ts">
  import { goto } from '$app/navigation';
  import { findPassword } from '$lib/api/auth';
  import { Mail, User, Loader2 } from 'lucide-svelte';

  let mb_email = $state('');
  let mb_name = $state('');
  let error = $state('');
  let success = $state(false);
  let loading = $state(false);

  async function handleFindPassword(e: Event) {
    e.preventDefault();

    if (!mb_email || !mb_name) {
      error = '이메일과 이름을 입력해주세요.';
      return;
    }

    loading = true;
    error = '';

    try {
      await findPassword({
        mb_email,
        mb_name,
      });
      success = true;
    } catch (e: any) {
      error = e.message || '비밀번호 찾기에 실패했습니다.';
    } finally {
      loading = false;
    }
  }
</script>

<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
  <div class="w-full max-w-md">
    <div class="bg-white rounded-lg shadow-lg p-8">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">비밀번호 찾기</h1>
        <p class="text-gray-500 mt-2">가입한 이메일과 이름을 입력해주세요</p>
      </div>

      {#if error}
        <div class="bg-red-50 text-red-600 text-sm p-3 rounded-md mb-4">
          {error}
        </div>
      {/if}

      {#if success}
        <div class="bg-green-50 text-green-600 text-sm p-4 rounded-md mb-4">
          <p class="font-medium">비밀번호 재설정 이메일을 전송했습니다.</p>
          <p class="mt-2">이메일의 링크를 클릭하여 비밀번호를 재설정해주세요.</p>
        </div>
      {:else}
        <form onsubmit={handleFindPassword} class="space-y-5">
          <div class="space-y-2">
            <label for="mb_name" class="text-sm font-medium text-gray-700">이름</label>
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
            <label for="mb_email" class="text-sm font-medium text-gray-700">이메일</label>
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

          <button
            type="submit"
            disabled={loading}
            class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            {#if loading}
              <Loader2 class="animate-spin" size={20} />
              전송 중...
            {:else}
              비밀번호 찾기
            {/if}
          </button>
        </form>
      {/if}

      <div class="mt-6 text-center text-sm">
        <a href="/auth/login" class="text-gray-500 hover:text-gray-700 hover:underline">
          로그인 페이지로 돌아가기
        </a>
      </div>
    </div>
  </div>
</div>