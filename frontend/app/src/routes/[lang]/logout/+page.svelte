<script lang="ts">
	import { onMount } from 'svelte';
	import { logout } from '$lib/api';
	import { clearMember } from '$lib/store';
	import { goto } from '$app/navigation';
	
	onMount(async () => {
		try {
			await logout();
			clearMember();
			goto('/');
		} catch (e) {
			// 로그아웃 실패해도 클라이언트 상태는 초기화
			clearMember();
			goto('/');
		}
	});
</script>

<div class="logout-container">
	<p>로그아웃 중...</p>
</div>

<style>
	.logout-container {
		text-align: center;
		padding: 2rem;
	}
</style>

