<script lang="ts">
    import { registerKarbon, sendOtp } from "$lib/api/auth_karbon";
    import { goto } from "$app/navigation";
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import { Checkbox } from "$lib/ui/checkbox";
    import { toastStore } from "$lib/store/toast";
    import Captcha from "$lib/util/Captcha.svelte";
    import { base } from '$app/paths';

    interface RegisterData {
        config?: Record<string, any>;
        provision?: { co_content: string };
        privacy?: { co_content: string };
        [key: string]: any;
    }

    let { data } = $props<{ data: RegisterData }>();

    let step = $state(1);
    let regMethod = $state<"email" | "phone">("email");

    let mb_name = $state("");
    let email = $state("");
    let password = $state("");
    let phone = $state("");
    let otp = $state("");
    let otpSent = $state(false);

    let agree_provision = $state(false);
    let agree_privacy = $state(false);

    let captcha_key = $state("");
    let captchaRef: Captcha | null = $state(null);

    let loading = $state(false);
    let error = $state("");

    function refreshCaptcha() {
        captchaRef?.refresh();
    }

    function nextStep() {
        error = "";
        if (!agree_provision || !agree_privacy) {
            error = "회원가입 약관 및 개인정보처리방침에 모두 동의해야 합니다.";
            return;
        }
        step = 2;
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

    async function handleRegister() {
        error = "";

        if (!mb_name) {
            error = "이름을 입력해주세요.";
            return;
        }

        if (regMethod === "email") {
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

        if (!captcha_key) {
            error = "자동등록방지 글자를 입력해주세요.";
            return;
        }

        loading = true;

        try {
            const payload: any = {
                mb_name,
                mb_nick: mb_name,
                captcha_key,
                mb_mailling: 1,
                mb_open: 1,
            };

            if (regMethod === "email") {
                payload.mb_email = email;
                payload.mb_password = password;
            } else {
                payload.mb_hp = phone;
                payload.mb_otp = otp;
            }

            await registerKarbon(payload);
            toastStore.success("회원가입이 완료되었습니다.");
            goto(base + "/auth/success?name=" + encodeURIComponent(mb_name));
        } catch (e: any) {
            error = e.message || "회원가입에 실패했습니다.";
            refreshCaptcha();
        } finally {
            loading = false;
        }
    }
</script>

<!-- Modern Auth Layout (no card) -->
<div class="flex flex-col min-h-[calc(100vh-120px)] px-6 py-8 max-w-md mx-auto">
    <!-- Title & Progress -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold tracking-tight">회원가입</h1>
        <div class="flex gap-2 mt-4">
            <div
                class="h-1.5 flex-1 rounded-full transition-colors {step >= 1
                    ? 'bg-primary'
                    : 'bg-muted'}"
            ></div>
            <div
                class="h-1.5 flex-1 rounded-full transition-colors {step >= 2
                    ? 'bg-primary'
                    : 'bg-muted'}"
            ></div>
        </div>
        <p class="text-sm text-muted-foreground mt-2">
            {step === 1 ? "1단계: 약관 동의" : "2단계: 정보 입력"}
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

    <form
        onsubmit={(e) => {
            e.preventDefault();
            if (step === 2) handleRegister();
        }}
        class="flex-1 flex flex-col"
    >
        {#if step === 1}
            <!-- Step 1: Terms -->
            <div class="space-y-4 flex-1">
                <!-- All Agree -->
                <div
                    class="flex items-center space-x-3 p-4 bg-muted/50 rounded-lg"
                >
                    <Checkbox
                        id="agree_all"
                        checked={agree_provision && agree_privacy}
                        onCheckedChange={(v) => {
                            const c = v === true;
                            agree_provision = c;
                            agree_privacy = c;
                        }}
                    />
                    <Label for="agree_all" class="font-semibold"
                        >전체 동의</Label
                    >
                </div>

                <!-- Provision -->
                <div class="space-y-2">
                    <Label class="text-sm font-medium">회원가입약관</Label>
                    <div
                        class="h-28 overflow-y-auto border rounded-lg p-3 text-xs text-muted-foreground bg-muted/20"
                    >
                        {#if data.provision}
                            {@html data.provision.co_content}
                        {:else}
                            약관 내용을 불러올 수 없습니다.
                        {/if}
                    </div>
                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="agree_provision"
                            bind:checked={agree_provision}
                        />
                        <Label for="agree_provision" class="text-sm"
                            >회원가입약관 동의 (필수)</Label
                        >
                    </div>
                </div>

                <!-- Privacy -->
                <div class="space-y-2">
                    <Label class="text-sm font-medium">개인정보처리방침</Label>
                    <div
                        class="h-28 overflow-y-auto border rounded-lg p-3 text-xs text-muted-foreground bg-muted/20"
                    >
                        {#if data.privacy}
                            {@html data.privacy.co_content}
                        {:else}
                            개인정보처리방침 내용을 불러올 수 없습니다.
                        {/if}
                    </div>
                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="agree_privacy"
                            bind:checked={agree_privacy}
                        />
                        <Label for="agree_privacy" class="text-sm"
                            >개인정보처리방침 동의 (필수)</Label
                        >
                    </div>
                </div>
            </div>

            <Button
                type="button"
                class="w-full h-12 text-base mt-6"
                onclick={nextStep}
                disabled={!agree_provision || !agree_privacy}
            >
                다음
            </Button>
        {:else}
            <!-- Step 2: Info Input -->
            <div class="space-y-6 flex-1">
                <!-- Tab Selector -->
                <div class="grid w-full grid-cols-2 bg-muted p-1 rounded-lg">
                    <button
                        class="text-sm font-medium py-2 rounded-md transition-all {regMethod ===
                        'email'
                            ? 'bg-background shadow-sm text-foreground'
                            : 'text-muted-foreground hover:text-foreground'}"
                        onclick={() => {
                            regMethod = "email";
                            error = "";
                        }}
                        type="button"
                    >
                        이메일로 가입
                    </button>
                    <button
                        class="text-sm font-medium py-2 rounded-md transition-all {regMethod ===
                        'phone'
                            ? 'bg-background shadow-sm text-foreground'
                            : 'text-muted-foreground hover:text-foreground'}"
                        onclick={() => {
                            regMethod = "phone";
                            error = "";
                        }}
                        type="button"
                    >
                        휴대폰으로 가입
                    </button>
                </div>

                <!-- Form Fields -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="mb_name">이름</Label>
                        <Input
                            id="mb_name"
                            type="text"
                            placeholder="실명 입력"
                            bind:value={mb_name}
                            disabled={loading}
                            required
                            class="h-12"
                        />
                    </div>

                    {#if regMethod === "email"}
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
                                placeholder="비밀번호 설정"
                                bind:value={password}
                                disabled={loading}
                                required
                                class="h-12"
                            />
                        </div>
                    {:else}
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
                    {/if}
                </div>

                <!-- Captcha -->
                <Captcha
                    bind:value={captcha_key}
                    bind:this={captchaRef}
                    class="pt-2"
                />
            </div>

            <div class="flex gap-3 mt-6">
                <Button
                    type="button"
                    variant="outline"
                    class="flex-1 h-12"
                    onclick={() => (step = 1)}
                    disabled={loading}
                >
                    이전
                </Button>
                <Button
                    type="submit"
                    class="flex-[2] h-12 text-base"
                    disabled={loading}
                >
                    {loading ? "가입 처리 중..." : "회원가입"}
                </Button>
            </div>
        {/if}
    </form>
</div>
