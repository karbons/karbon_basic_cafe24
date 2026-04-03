<script lang="ts">
	import { apiGet } from '$lib/api';
	import { memberStore } from '$lib/store';
import { base } from '$app/paths';
	
	interface Member {
		mb_id: string;
		mb_name: string;
		mb_nick: string;
		mb_level: number;
		mb_point: number;
		mb_email: string;
		mb_homepage: string;
		mb_tel: string;
		mb_hp: string;
		mb_profile: string;
	}
	
	let member = $state<Member | null>(null);
	let loading = $state(true);
	let error = $state('');
	
	async function loadProfile() {
		try {
			loading = true;
			const data = await apiGet<Member>('/member/profile');
			member = data;
			memberStore.set(data);
		} catch (e: any) {
			error = e.message || '프로필을 불러올 수 없습니다.';
		} finally {
			loading = false;
		}
	}
	
	$effect(() => {
		loadProfile();
	});
</script>

<div class="profile-container">
	<h1>프로필</h1>
	
	{#if loading}
		<p>로딩 중...</p>
	{:else if error}
		<div class="error">{error}</div>
	{:else if member}
		<div class="profile-info">
			<div class="info-item">
				<label>아이디</label>
				<span>{member.mb_id}</span>
			</div>
			<div class="info-item">
				<label>이름</label>
				<span>{member.mb_name}</span>
			</div>
			<div class="info-item">
				<label>닉네임</label>
				<span>{member.mb_nick}</span>
			</div>
			<div class="info-item">
				<label>레벨</label>
				<span>{member.mb_level}</span>
			</div>
			<div class="info-item">
				<label>포인트</label>
				<span>{member.mb_point}</span>
			</div>
			<div class="info-item">
				<label>이메일</label>
				<span>{member.mb_email}</span>
			</div>
			<div class="info-item">
				<label>전화번호</label>
				<span>{member.mb_tel}</span>
			</div>
			<div class="info-item">
				<label>휴대폰</label>
				<span>{member.mb_hp}</span>
			</div>
		</div>
		
		<div class="profile-actions">
			<a href="{base}/member/edit">정보 수정</a>
			<a href="{base}/member/memo">쪽지</a>
			<a href="{base}/member/scrap">스크랩</a>
			<a href="{base}/member/point">포인트</a>
		</div>
	{/if}
</div>

<style>
	.profile-container {
		max-width: 800px;
		margin: 0 auto;
		padding: 2rem;
	}
	
	.profile-info {
		margin-top: 1rem;
		border: 1px solid #ddd;
		border-radius: 8px;
		padding: 1rem;
	}
	
	.info-item {
		display: flex;
		padding: 0.75rem 0;
		border-bottom: 1px solid #eee;
	}
	
	.info-item:last-child {
		border-bottom: none;
	}
	
	.info-item label {
		width: 120px;
		font-weight: bold;
		color: #666;
	}
	
	.info-item span {
		flex: 1;
	}
	
	.profile-actions {
		margin-top: 2rem;
		display: flex;
		gap: 1rem;
	}
	
	.profile-actions a {
		padding: 0.75rem 1.5rem;
		background-color: #007bff;
		color: white;
		text-decoration: none;
		border-radius: 4px;
		display: inline-block;
	}
	
	.profile-actions a:hover {
		background-color: #0056b3;
		text-decoration: none;
	}
	
	.error {
		color: red;
		padding: 1rem;
		background-color: #ffe6e6;
		border-radius: 4px;
	}
</style>

