<script lang="ts">
    import { loginKarbon, sendOtp } from "$lib/api/auth_karbon";
    import { setMember } from "$lib/store";
    import { goto } from "$app/navigation";
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import { Checkbox } from "$lib/ui/checkbox";
    import { signInWithFirebaseToken } from "$lib/firebase/firebase";
    import { isHybridApp } from "$lib/util/auth_helpers";
    import { toastStore } from "$lib/store/toast";
import { base } from '$app/paths';

    let { data } = $props<{ data: any }>();

    let loginMethod = $state<"email" | "phone">("email");
    let email = $state("");
    let password = $state("");
    let phone = $state("");
    let otp = $state("");
    let otpSent = $state(false);
    let auto_login = $state(false);
    let error = $state("");
    let loading = $state(false);

    async function handleSNSLogin(provider: string) {
        if (isHybridApp()) {
            console.log(`Native ${provider} login`);
        } else {
            console.log(`Web ${provider} login`);
        }
    }

    async function requestOtp() {
        if (!phone) {
            error = "휴대폰 번호를 입력해주세요.";
            return;
        }
        loading = true;
        try {
            await sendOtp(phone);
            otpSent = true;
            toastStore.success("인증번호가 발송되었습니다.");
        } catch (e: any) {
            error = e.message;
        } finally {
            loading = false;
        }
    }

    async function handleLogin() {
        if (loginMethod === "email") {
            if (!email || !password) {
                error = "이메일과 비밀번호를 입력해주세요.";
                return;
            }
        } else {
            if (!phone || !otp) {
                error = "휴대폰 번호와 인증번호를 입력해주세요.";
                return;
            }
        }

        loading = true;
        error = "";

        try {
            let payload: any = { auto_login };
            if (loginMethod === "email") {
                payload.mb_id = email;
                payload.mb_email = email;
                payload.mb_password = password;
                payload.login_type = "email";
            } else {
                payload.mb_hp = phone;
                payload.mb_otp = otp;
                payload.login_type = "phone";
            }

            const response = await loginKarbon(payload);
            setMember(response.mb);

            if (response.firebase_token) {
                signInWithFirebaseToken(response.firebase_token).catch((e) =>
                    console.error(e),
                );
            }

            goto(base + "/");
        } catch (e: any) {
            error = e.message || "로그인에 실패했습니다.";
        } finally {
            loading = false;
        }
    }
</script>

<!-- Modern Auth Layout (no card) -->
<div class="flex flex-col min-h-[calc(100vh-120px)] px-6 py-8 max-w-md mx-auto">
    <!-- Title -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight">로그인</h1>
        <p class="text-muted-foreground mt-2">
            이메일 또는 휴대폰 번호로 로그인하세요.
        </p>
    </div>

    <!-- Error -->
    {#if error}
        <div
            class="bg-destructive/15 text-destructive text-sm p-3 rounded-md mb-4"
        >
            {error}
        </div>
    {/if}

    <!-- Tab Selector -->
    <div class="grid w-full grid-cols-2 mb-6 bg-muted p-1 rounded-lg">
        <button
            class="text-sm font-medium py-2 rounded-md transition-all {loginMethod ===
            'email'
                ? 'bg-background shadow-sm text-foreground'
                : 'text-muted-foreground hover:text-foreground'}"
            onclick={() => {
                loginMethod = "email";
                error = "";
            }}
            type="button"
        >
            이메일
        </button>
        <button
            class="text-sm font-medium py-2 rounded-md transition-all {loginMethod ===
            'phone'
                ? 'bg-background shadow-sm text-foreground'
                : 'text-muted-foreground hover:text-foreground'}"
            onclick={() => {
                loginMethod = "phone";
                error = "";
            }}
            type="button"
        >
            휴대폰
        </button>
    </div>

    <!-- Forms -->
    {#if loginMethod === "email"}
        <form
            onsubmit={(e) => {
                e.preventDefault();
                handleLogin();
            }}
            class="space-y-4"
        >
            <div class="space-y-2">
                <Label for="email">이메일</Label>
                <Input
                    id="email"
                    type="email"
                    placeholder="name@example.com"
                    bind:value={email}
                    disabled={loading}
                    required
                    class="h-12"
                />
            </div>
            <div class="space-y-2">
                <Label for="password">비밀번호</Label>
                <Input
                    id="password"
                    type="password"
                    placeholder="비밀번호"
                    bind:value={password}
                    disabled={loading}
                    required
                    class="h-12"
                />
            </div>
            <div class="flex items-center space-x-2">
                <Checkbox id="auto_login_email" bind:checked={auto_login} />
                <Label for="auto_login_email" class="text-sm">자동로그인</Label>
            </div>
            <Button
                type="submit"
                class="w-full h-12 text-base"
                disabled={loading}
            >
                {loading ? "로그인 중..." : "로그인"}
            </Button>
        </form>
    {:else}
        <form
            onsubmit={(e) => {
                e.preventDefault();
                handleLogin();
            }}
            class="space-y-4"
        >
            <div class="space-y-2">
                <Label for="phone">휴대폰 번호</Label>
                <div class="flex gap-2">
                    <Input
                        id="phone"
                        type="tel"
                        placeholder="010-1234-5678"
                        bind:value={phone}
                        disabled={loading || otpSent}
                        required
                        class="h-12"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        onclick={requestOtp}
                        disabled={loading || otpSent}
                        class="h-12 whitespace-nowrap"
                    >
                        {otpSent ? "전송완료" : "인증번호"}
                    </Button>
                </div>
            </div>
            {#if otpSent}
                <div class="space-y-2">
                    <Label for="otp">인증번호</Label>
                    <Input
                        id="otp"
                        type="text"
                        placeholder="인증번호 6자리"
                        bind:value={otp}
                        disabled={loading}
                        required
                        class="h-12"
                    />
                </div>
            {/if}
            <div class="flex items-center space-x-2">
                <Checkbox id="auto_login_phone" bind:checked={auto_login} />
                <Label for="auto_login_phone" class="text-sm">자동로그인</Label>
            </div>
            <Button
                type="submit"
                class="w-full h-12 text-base"
                disabled={loading}
            >
                {loading ? "로그인 중..." : "로그인"}
            </Button>
        </form>
    {/if}

    <!-- Links -->
    <div
        class="flex items-center justify-between text-sm text-muted-foreground mt-6"
    >
        <a href="{base}/auth/find" class="hover:underline hover:text-primary"
            >비밀번호 찾기</a
        >
        <a href="{base}/auth/register" class="hover:underline hover:text-primary"
            >회원가입</a
        >
    </div>

    <!-- SNS Login -->
    <div class="mt-auto pt-8">
        <div class="relative mb-6">
            <div class="absolute inset-0 flex items-center">
                <span class="w-full border-t"></span>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground"
                    >SNS 로그인</span
                >
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <Button
                variant="outline"
                class="w-full h-12 bg-[#03C75A] hover:bg-[#03C75A]/90 text-white border-none"
                onclick={() => handleSNSLogin("naver")}
            >
                네이버
            </Button>
            <Button
                variant="outline"
                class="w-full h-12 bg-[#FEE500] hover:bg-[#FEE500]/90 text-black border-none"
                onclick={() => handleSNSLogin("kakao")}
            >
                카카오
            </Button>
            <Button
                variant="outline"
                class="w-full h-12"
                onclick={() => handleSNSLogin("google")}
            >
                Google
            </Button>
            <Button
                variant="outline"
                class="w-full h-12"
                onclick={() => handleSNSLogin("apple")}
            >
                Apple
            </Button>
        </div>
    </div>
</div>
