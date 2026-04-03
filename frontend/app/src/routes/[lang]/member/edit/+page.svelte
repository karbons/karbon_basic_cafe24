<script lang="ts">
	import { apiGet, apiPost, apiPut } from "$lib/api";
	import { goto } from "$app/navigation";
	import { Button } from "$lib/ui/button";
	import { Input } from "$lib/ui/input";
	import { Label } from "$lib/ui/label";
	import { Checkbox } from "$lib/ui/checkbox";
	import * as Card from "$lib/ui/card";
	import { Separator } from "$lib/ui/separator";
	import { toastStore } from "$lib/store/toast";
import { base } from '$app/paths';

	interface Member {
		mb_id: string;
		mb_name: string;
		mb_nick: string;
		mb_email: string;
		mb_homepage: string;
		mb_tel: string;
		mb_hp: string;
		mb_zip: string;
		mb_addr1: string;
		mb_addr2: string;
		mb_signature: string;
		mb_profile: string;
		mb_mailling: number;
		mb_open: number;
		mb_1: string; // Chat usage
	}

	let member = $state<Member | null>(null);
	let loading = $state(false);
	let error = $state("");

	// Password Confirmation State
	let verified = $state(false);
	let confirm_password = $state("");

	// Edit Form State
	let mb_password = $state("");
	let mb_password_re = $state("");

	// Captcha State
	let captcha_key = $state("");
	let captchaObj = $state({ ts: Date.now() });

	function refreshCaptcha() {
		captchaObj = { ts: Date.now() };
		captcha_key = "";
	}

	function playAudioCaptcha() {
		const audio = new Audio("/api/captcha/audio?ts=" + Date.now());
		audio.play();
	}

	async function handleVerify() {
		if (!confirm_password) {
			error = "비밀번호를 입력해주세요.";
			return;
		}

		loading = true;
		error = "";

		try {
			await apiPost("/member/password_check", {
				mb_password: confirm_password,
			});
			verified = true;
			await loadMember();
		} catch (e: any) {
			error = e.message || "비밀번호 확인에 실패했습니다.";
		} finally {
			loading = false;
		}
	}

	async function loadMember() {
		try {
			member = await apiGet<Member>("/member/profile");
		} catch (e: any) {
			error = e.message || "회원 정보를 불러올 수 없습니다.";
		}
	}

	async function handleSubmit() {
		if (!member) return;

		error = "";

		if (mb_password && mb_password !== mb_password_re) {
			error = "새 비밀번호가 일치하지 않습니다.";
			return;
		}

		if (!captcha_key) {
			error = "자동등록방지 글자를 입력해주세요.";
			return;
		}

		loading = true;

		try {
			// Prepare data
			const updateData = {
				...member,
				mb_password: mb_password || undefined, // Send only if set
				captcha_key,
			};

			await apiPut("/member/update", updateData);
			toastStore.success("회원정보가 수정되었습니다.");
            goto(base + "/member/profile");
		} catch (e: any) {
			error = e.message || "정보 수정에 실패했습니다.";
			refreshCaptcha();
		} finally {
			loading = false;
		}
	}

	function openZipSearch() {
		alert("주소 검색 기능은 추후 구현 예정입니다. 직접 입력해주세요.");
	}
</script>

<div
	class="flex items-center justify-center min-h-[calc(100vh-200px)] py-10 px-4"
>
	<Card.Root class="w-full max-w-2xl">
		<Card.Header class="space-y-4">
			<Card.Title class="text-2xl font-bold text-center"
				>회원정보 수정</Card.Title
			>
		</Card.Header>
		<Card.Content class="grid gap-6">
			{#if error}
				<div
					class="bg-destructive/15 text-destructive text-sm p-3 rounded-md font-medium"
				>
					{error}
				</div>
			{/if}

			{#if !verified}
				<!-- Password Verification Step -->
				<form
					onsubmit={(e) => {
						e.preventDefault();
						handleVerify();
					}}
					class="space-y-6"
				>
					<p class="text-center text-muted-foreground">
						정보를 안전하게 보호하기 위해 비밀번호를 다시 한 번
						확인합니다.
					</p>
					<div class="space-y-2">
						<Label for="confirm_password">비밀번호</Label>
						<Input
							id="confirm_password"
							type="password"
							bind:value={confirm_password}
							disabled={loading}
							required
						/>
					</div>
					<Button type="submit" class="w-full" disabled={loading}>
						{loading ? "확인 중..." : "확인"}
					</Button>
				</form>
			{:else if member}
				<!-- Edit Profile Form -->
				<form
					onsubmit={(e) => {
						e.preventDefault();
						handleSubmit();
					}}
					class="space-y-6"
				>
					<div class="space-y-4">
						<h3 class="font-semibold text-lg border-b pb-2">
							기본 정보
						</h3>

						<div class="grid gap-2">
							<Label>아이디</Label>
							<Input
								value={member.mb_id}
								disabled
								readonly
								class="bg-muted"
							/>
						</div>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div class="grid gap-2">
								<Label for="mb_password"
									>새 비밀번호 (변경 시 입력)</Label
								>
								<Input
									id="mb_password"
									type="password"
									placeholder="변경하지 않으려면 비워두세요"
									bind:value={mb_password}
									disabled={loading}
								/>
							</div>
							<div class="grid gap-2">
								<Label for="mb_password_re"
									>새 비밀번호 확인</Label
								>
								<Input
									id="mb_password_re"
									type="password"
									placeholder="새 비밀번호 확인"
									bind:value={mb_password_re}
									disabled={loading}
								/>
							</div>
						</div>

						<div class="grid gap-2">
							<Label for="mb_name">이름</Label>
							<Input
								id="mb_name"
								bind:value={member.mb_name}
								disabled
								readonly
								class="bg-muted"
							/>
						</div>

						<div class="grid gap-2">
							<Label for="mb_nick">닉네임</Label>
							<Input
								id="mb_nick"
								bind:value={member.mb_nick}
								disabled={loading}
								required
							/>
						</div>

						<div class="grid gap-2">
							<Label for="mb_email">E-mail</Label>
							<Input
								id="mb_email"
								type="email"
								bind:value={member.mb_email}
								disabled={loading}
								required
							/>
						</div>
					</div>

					<div class="space-y-4">
						<h3 class="font-semibold text-lg border-b pb-2">
							상세 정보
						</h3>

						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div class="grid gap-2">
								<Label for="mb_tel">전화번호</Label>
								<Input
									id="mb_tel"
									bind:value={member.mb_tel}
									disabled={loading}
								/>
							</div>
							<div class="grid gap-2">
								<Label for="mb_hp">휴대전화</Label>
								<Input
									id="mb_hp"
									bind:value={member.mb_hp}
									disabled={loading}
								/>
							</div>
						</div>

						<div class="grid gap-2">
							<Label>주소</Label>
							<div class="flex gap-2">
								<Input
									placeholder="우편번호"
									class="w-32"
									bind:value={member.mb_zip}
									disabled={loading}
									readonly
								/>
								<Button
									type="button"
									variant="outline"
									onclick={openZipSearch}>주소검색</Button
								>
							</div>
							<Input
								placeholder="기본주소"
								bind:value={member.mb_addr1}
								disabled={loading}
								readonly
							/>
							<Input
								placeholder="상세주소"
								bind:value={member.mb_addr2}
								disabled={loading}
							/>
						</div>

						<div class="grid gap-2">
							<Label for="mb_homepage">홈페이지</Label>
							<Input
								id="mb_homepage"
								type="url"
								bind:value={member.mb_homepage}
								disabled={loading}
							/>
						</div>

						<div class="grid gap-2">
							<Label for="mb_signature">서명</Label>
							<textarea
								id="mb_signature"
								class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
								bind:value={member.mb_signature}
								disabled={loading}
							></textarea>
						</div>

						<div class="grid gap-2">
							<Label for="mb_profile">자기소개</Label>
							<textarea
								id="mb_profile"
								class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
								bind:value={member.mb_profile}
								disabled={loading}
							></textarea>
						</div>
					</div>

					<div class="space-y-4">
						<h3 class="font-semibold text-lg border-b pb-2">
							설정
						</h3>

						<div class="flex items-center space-x-2">
							<Checkbox
								id="mb_mailling"
								checked={Boolean(Number(member.mb_mailling))}
								onCheckedChange={(v) =>
									member && (member.mb_mailling = v ? 1 : 0)}
							/>
							<Label for="mb_mailling"
								>정보 메일을 수신하겠습니다.</Label
							>
						</div>
						<div class="flex items-center space-x-2">
							<Checkbox
								id="mb_open"
								checked={Boolean(Number(member.mb_open))}
								onCheckedChange={(v) =>
									member && (member.mb_open = v ? 1 : 0)}
							/>
							<Label for="mb_open"
								>다른분들이 나의 정보를 볼 수 있도록 합니다.</Label
							>
						</div>
						<!-- Chat Toggle -->
						<div
							class="flex items-center space-x-2 p-4 bg-muted/20 rounded-lg border"
						>
							<Checkbox
								id="mb_1"
								checked={member.mb_1 === "1"}
								onCheckedChange={(v) =>
									member && (member.mb_1 = v ? "1" : "")}
							/>
							<Label for="mb_1" class="font-bold cursor-pointer"
								>채팅 기능을 사용합니다.</Label
							>
						</div>
					</div>

					<Separator />

					<!-- CAPTCHA -->
					<div class="space-y-4">
						<Label>자동등록방지</Label>
						<div
							class="border p-4 rounded-md bg-muted/30 flex flex-col items-center justify-center gap-4"
						>
							<div class="flex items-center gap-2">
								<img
									src="/api/captcha/image?{captchaObj.ts}"
									alt="CAPTCHA"
									class="border rounded-md"
								/>
								<Button
									variant="outline"
									size="icon"
									onclick={refreshCaptcha}
									type="button"
									title="새로고침"
								>
									<svg
										xmlns="http://www.w3.org/2000/svg"
										width="16"
										height="16"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										stroke-width="2"
										stroke-linecap="round"
										stroke-linejoin="round"
										class="lucide lucide-refresh-cw"
										><path
											d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"
										/><path d="M21 3v5h-5" /><path
											d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"
										/><path d="M8 16H3v5" /></svg
									>
								</Button>
								<Button
									variant="outline"
									size="icon"
									onclick={playAudioCaptcha}
									type="button"
									title="음성 듣기"
								>
									<svg
										xmlns="http://www.w3.org/2000/svg"
										width="16"
										height="16"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										stroke-width="2"
										stroke-linecap="round"
										stroke-linejoin="round"
										class="lucide lucide-volume-2"
										><polygon
											points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"
										/><path
											d="M15.54 8.46a5 5 0 0 1 0 7.07"
										/><path
											d="M19.07 4.93a10 10 0 0 1 0 14.14"
										/></svg
									>
								</Button>
							</div>
							<Input
								placeholder="자동등록방지 문자 입력"
								bind:value={captcha_key}
								disabled={loading}
								class="max-w-[300px] text-center"
							/>
						</div>
					</div>

					<div class="flex gap-4 pt-4">
						<Button
							type="button"
							variant="outline"
							href="{base}/member/profile"
							class="flex-1">취소</Button
						>
						<Button
							type="submit"
							class="flex-[2]"
							disabled={loading}
						>
							{loading ? "저장 중..." : "정보 수정"}
						</Button>
					</div>
				</form>
			{/if}
		</Card.Content>
	</Card.Root>
</div>
