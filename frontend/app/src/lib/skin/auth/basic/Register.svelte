<script lang="ts">
    import { apiPost } from "$lib/api";
    import { goto } from "$app/navigation";
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import { Checkbox } from "$lib/ui/checkbox";
    import * as Card from "$lib/ui/card";
    import { Separator } from "$lib/ui/separator";
    import { toastStore } from "$lib/store/toast";
import { base } from '$app/paths';

    interface RegisterData {
        config?: Record<string, any>;
        provision?: { co_content: string };
        privacy?: { co_content: string };
        [key: string]: any;
    }

    let { data } = $props<{ data: RegisterData }>();

    let step = $state(1); // 1: 약관동의, 2: 정보입력

    let mb_id = $state("");
    let mb_password = $state("");
    let mb_password_re = $state("");
    let mb_name = $state("");
    let mb_nick = $state("");
    let mb_email = $state("");

    // 추가 필드 (설정에 따라 보임)
    let mb_homepage = $state("");
    let mb_tel = $state("");
    let mb_hp = $state("");
    let mb_zip = $state("");
    let mb_addr1 = $state("");
    let mb_addr2 = $state("");
    let mb_signature = $state("");
    let mb_profile = $state("");
    let mb_recommend = $state("");

    let agree_provision = $state(false);
    let agree_privacy = $state(false);
    let mb_mailling = $state(true);
    let mb_open = $state(true);

    let captcha_key = $state("");
    let captchaObj = $state({ ts: Date.now() });

    let loading = $state(false);
    let error = $state("");

    // Config shortcut
    const config = data.config || {};
    console.log(config);

    function refreshCaptcha() {
        captchaObj = { ts: Date.now() };
        captcha_key = "";
    }

    function playAudioCaptcha() {
        const audio = new Audio("/api/captcha/audio?ts=" + Date.now());
        audio.play();
    }

    function nextStep() {
        error = "";
        if (!agree_provision || !agree_privacy) {
            error = "회원가입 약관 및 개인정보처리방침에 모두 동의해야 합니다.";
            return;
        }
        step = 2;
    }

    function prevStep() {
        error = "";
        step = 1;
    }

    function openZipSearch() {
        alert("주소 검색 기능은 추후 구현 예정입니다. 직접 입력해주세요.");
    }

    function selfCert() {
        alert("본인인증 모듈이 연동되지 않았습니다.");
    }

    async function handleRegister() {
        error = "";

        if (!mb_id || !mb_password || !mb_name || !mb_nick || !mb_email) {
            error = "필수 항목을 모두 입력해주세요.";
            return;
        }

        if (mb_password !== mb_password_re) {
            error = "비밀번호와 비밀번호 확인이 일치하지 않습니다.";
            return;
        }

        if (!captcha_key) {
            error = "자동등록방지 글자를 입력해주세요.";
            return;
        }

        loading = true;

        try {
            await apiPost("/auth/register", {
                mb_id,
                mb_password,
                mb_name,
                mb_nick,
                mb_email,
                mb_homepage,
                mb_tel,
                mb_hp,
                mb_zip,
                mb_addr1,
                mb_addr2,
                mb_signature,
                mb_profile,
                mb_recommend,
                mb_mailling: mb_mailling ? 1 : 0,
                mb_open: mb_open ? 1 : 0,
                captcha_key,
            });

            toastStore.success("회원가입이 완료되었습니다. 로그인해주세요.");
            goto(base + "/auth/login");
        } catch (e: any) {
            error = e.message || "회원가입에 실패했습니다.";
            refreshCaptcha();
        } finally {
            loading = false;
        }
    }
</script>

<div
    class="flex items-center justify-center min-h-[calc(100vh-200px)] py-10 px-4"
>
    <Card.Root class="w-full max-w-2xl">
        <Card.Header class="space-y-4">
            <Card.Title class="text-2xl font-bold text-center"
                >회원가입</Card.Title
            >

            <!-- Progress Bar -->
            <div
                class="flex items-center justify-center gap-10 relative pt-2 pb-4"
            >
                <!-- Connecting Line -->
                <div
                    class="absolute top-7 left-1/4 right-1/4 h-[2px] bg-muted z-0"
                ></div>
                <div
                    class="absolute top-7 left-1/4 right-1/4 h-[2px] bg-primary z-0 transition-all duration-300 origin-left"
                    style="transform: scaleX({step === 1 ? 0 : 1});"
                ></div>

                <!-- Step 1 -->
                <div class="flex flex-col items-center gap-2 z-10 relative">
                    <div
                        class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors duration-300 {step >=
                        1
                            ? 'bg-primary border-primary text-primary-foreground'
                            : 'bg-background border-muted text-muted-foreground'}"
                    >
                        1
                    </div>
                    <span
                        class="text-xs font-medium {step >= 1
                            ? 'text-primary'
                            : 'text-muted-foreground'}">약관동의</span
                    >
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center gap-2 z-10 relative">
                    <div
                        class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-colors duration-300 {step >=
                        2
                            ? 'bg-primary border-primary text-primary-foreground'
                            : 'bg-background border-muted text-muted-foreground'}"
                    >
                        2
                    </div>
                    <span
                        class="text-xs font-medium {step >= 2
                            ? 'text-primary'
                            : 'text-muted-foreground'}">정보입력</span
                    >
                </div>
            </div>

            <Card.Description class="text-center sr-only">
                현재 {step}단계: {#if step === 1}약관 동의{:else}회원 정보 입력{/if}
            </Card.Description>
        </Card.Header>
        <Card.Content class="grid gap-6">
            {#if error}
                <div
                    class="bg-destructive/15 text-destructive text-sm p-3 rounded-md font-medium"
                >
                    {error}
                </div>
            {/if}

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    if (step === 2) handleRegister();
                }}
                class="grid gap-6"
            >
                {#if step === 1}
                    <!-- [Step 1] 약관 동의 -->
                    <div
                        class="space-y-4 animate-in fade-in slide-in-from-right-4 duration-300"
                    >
                        <div class="flex items-center space-x-2 border-b pb-4">
                            <Checkbox
                                id="agree_all"
                                checked={agree_provision && agree_privacy}
                                onCheckedChange={(v) => {
                                    const checked = v === true;
                                    agree_provision = checked;
                                    agree_privacy = checked;
                                }}
                            />
                            <Label
                                for="agree_all"
                                class="font-bold cursor-pointer text-base"
                                >회원가입 약관 및 개인정보처리방침안내의 내용에
                                모두 동의합니다.</Label
                            >
                        </div>

                        <div class="space-y-2">
                            <Label>회원가입약관</Label>
                            <div
                                class="h-48 overflow-y-auto border rounded-md p-3 text-sm text-muted-foreground bg-muted/50"
                            >
                                {#if data.provision}
                                    {@html data.provision.co_content}
                                {:else}
                                    약관 내용을 불러올 수 없습니다.
                                {/if}
                            </div>
                            <div class="flex items-center space-x-2 pt-2">
                                <Checkbox
                                    id="agree_provision"
                                    bind:checked={agree_provision}
                                />
                                <Label
                                    for="agree_provision"
                                    class="font-medium cursor-pointer"
                                    >회원가입약관의 내용에 동의합니다.</Label
                                >
                            </div>
                        </div>

                        <Separator />

                        <div class="space-y-2">
                            <Label>개인정보처리방침안내</Label>
                            <div
                                class="h-48 overflow-y-auto border rounded-md p-3 text-sm text-muted-foreground bg-muted/50"
                            >
                                {#if data.privacy}
                                    {@html data.privacy.co_content}
                                {:else}
                                    개인정보처리방침 내용을 불러올 수 없습니다.
                                {/if}
                            </div>
                            <div class="flex items-center space-x-2 pt-2">
                                <Checkbox
                                    id="agree_privacy"
                                    bind:checked={agree_privacy}
                                />
                                <Label
                                    for="agree_privacy"
                                    class="font-medium cursor-pointer"
                                    >개인정보처리방침안내의 내용에 동의합니다.</Label
                                >
                            </div>
                        </div>
                    </div>

                    <Button
                        type="button"
                        class="w-full h-12 text-lg mt-4"
                        onclick={nextStep}
                        disabled={!agree_provision || !agree_privacy}
                    >
                        다음 단계로
                    </Button>
                {:else}
                    <!-- [Step 2] 정보 입력 -->
                    <div
                        class="space-y-6 animate-in fade-in slide-in-from-right-4 duration-300"
                    >
                        <!-- 사이트 이용 정보 -->
                        <div class="space-y-4">
                            <h3
                                class="font-semibold text-lg flex items-center gap-2"
                            >
                                <span
                                    class="bg-primary/10 text-primary w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                    >1</span
                                >
                                사이트 이용 정보
                            </h3>
                            <div class="grid gap-2 pl-8">
                                <Label for="mb_id">아이디</Label>
                                <Input
                                    id="mb_id"
                                    type="text"
                                    placeholder="아이디 (영문, 숫자 가능)"
                                    bind:value={mb_id}
                                    disabled={loading}
                                    required
                                />
                            </div>
                            <div
                                class="grid grid-cols-1 md:grid-cols-2 gap-4 pl-8"
                            >
                                <div class="grid gap-2">
                                    <Label for="mb_password">비밀번호</Label>
                                    <Input
                                        id="mb_password"
                                        type="password"
                                        placeholder="비밀번호"
                                        bind:value={mb_password}
                                        disabled={loading}
                                        required
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="mb_password_re"
                                        >비밀번호 확인</Label
                                    >
                                    <Input
                                        id="mb_password_re"
                                        type="password"
                                        placeholder="비밀번호 확인"
                                        bind:value={mb_password_re}
                                        disabled={loading}
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <Separator />

                        <!-- 개인 정보 -->
                        <div class="space-y-4">
                            <h3
                                class="font-semibold text-lg flex items-center gap-2"
                            >
                                <span
                                    class="bg-primary/10 text-primary w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                    >2</span
                                >
                                개인 정보
                            </h3>
                            <div class="grid gap-2 pl-8">
                                <Label for="mb_name">이름</Label>
                                <Input
                                    id="mb_name"
                                    type="text"
                                    placeholder="실명"
                                    bind:value={mb_name}
                                    disabled={loading}
                                    required
                                />
                            </div>
                            <div class="grid gap-2 pl-8">
                                <Label for="mb_nick">닉네임</Label>
                                <Input
                                    id="mb_nick"
                                    type="text"
                                    placeholder="공개적으로 표시될 이름"
                                    bind:value={mb_nick}
                                    disabled={loading}
                                    required
                                />
                                <p class="text-xs text-muted-foreground">
                                    공백없이 한글,영문,숫자만 입력 가능 (한글
                                    2자, 영문 4자 이상)
                                </p>
                            </div>
                            <div class="grid gap-2 pl-8">
                                <Label for="mb_email">E-mail</Label>
                                <Input
                                    id="mb_email"
                                    type="email"
                                    placeholder="example@email.com"
                                    bind:value={mb_email}
                                    disabled={loading}
                                    required
                                />
                                {#if config.cf_cert_use}
                                    <div class="mt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onclick={selfCert}>본인인증</Button
                                        >
                                    </div>
                                {/if}
                            </div>

                            {#if config.cf_use_homepage}
                                <div class="grid gap-2 pl-8">
                                    <Label for="mb_homepage"
                                        >홈페이지 {config.cf_req_homepage
                                            ? "(필수)"
                                            : ""}</Label
                                    >
                                    <Input
                                        id="mb_homepage"
                                        type="url"
                                        placeholder="https://example.com"
                                        bind:value={mb_homepage}
                                        disabled={loading}
                                        required={config.cf_req_homepage}
                                    />
                                </div>
                            {/if}

                            {#if config.cf_use_tel}
                                <div class="grid gap-2 pl-8">
                                    <Label for="mb_tel"
                                        >전화번호 {config.cf_req_tel
                                            ? "(필수)"
                                            : ""}</Label
                                    >
                                    <Input
                                        id="mb_tel"
                                        type="tel"
                                        placeholder="02-1234-5678"
                                        bind:value={mb_tel}
                                        disabled={loading}
                                        required={config.cf_req_tel}
                                    />
                                </div>
                            {/if}

                            {#if config.cf_use_hp}
                                <div class="grid gap-2 pl-8">
                                    <Label for="mb_hp"
                                        >휴대전화 {config.cf_req_hp
                                            ? "(필수)"
                                            : ""}</Label
                                    >
                                    <Input
                                        id="mb_hp"
                                        type="tel"
                                        placeholder="010-1234-5678"
                                        bind:value={mb_hp}
                                        disabled={loading}
                                        required={config.cf_req_hp}
                                    />
                                </div>
                            {/if}

                            {#if config.cf_use_addr}
                                <div class="grid gap-2 pl-8">
                                    <Label
                                        >주소 {config.cf_req_addr
                                            ? "(필수)"
                                            : ""}</Label
                                    >
                                    <div class="flex gap-2">
                                        <Input
                                            placeholder="우편번호"
                                            class="w-32"
                                            bind:value={mb_zip}
                                            disabled={loading}
                                            required={config.cf_req_addr}
                                            readonly
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onclick={openZipSearch}
                                            >주소검색</Button
                                        >
                                    </div>
                                    <Input
                                        placeholder="기본주소"
                                        bind:value={mb_addr1}
                                        disabled={loading}
                                        required={config.cf_req_addr}
                                        readonly
                                    />
                                    <Input
                                        placeholder="상세주소"
                                        bind:value={mb_addr2}
                                        disabled={loading}
                                    />
                                </div>
                            {/if}
                        </div>

                        <Separator />

                        <!-- 기타 개인 설정 -->
                        <div class="space-y-4">
                            <h3
                                class="font-semibold text-lg flex items-center gap-2"
                            >
                                <span
                                    class="bg-primary/10 text-primary w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                    >3</span
                                >
                                기타 설정
                            </h3>

                            {#if config.cf_use_signature}
                                <div class="pl-8 grid gap-2 mb-4">
                                    <Label for="mb_signature"
                                        >서명 {config.cf_req_signature
                                            ? "(필수)"
                                            : ""}</Label
                                    >
                                    <textarea
                                        id="mb_signature"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        placeholder="서명을 입력하세요"
                                        bind:value={mb_signature}
                                        disabled={loading}
                                        required={config.cf_req_signature}
                                    ></textarea>
                                </div>
                            {/if}

                            {#if config.cf_use_profile}
                                <div class="pl-8 grid gap-2 mb-4">
                                    <Label for="mb_profile"
                                        >자기소개 {config.cf_req_profile
                                            ? "(필수)"
                                            : ""}</Label
                                    >
                                    <textarea
                                        id="mb_profile"
                                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        placeholder="자기소개를 입력하세요"
                                        bind:value={mb_profile}
                                        disabled={loading}
                                        required={config.cf_req_profile}
                                    ></textarea>
                                </div>
                            {/if}

                            {#if config.cf_use_recommend}
                                <div class="pl-8 grid gap-2 mb-4">
                                    <Label for="mb_recommend"
                                        >추천인 아이디</Label
                                    >
                                    <Input
                                        id="mb_recommend"
                                        type="text"
                                        placeholder="추천인 아이디"
                                        bind:value={mb_recommend}
                                        disabled={loading}
                                    />
                                </div>
                            {/if}

                            <div class="pl-8 space-y-2">
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="mb_mailling"
                                        bind:checked={mb_mailling}
                                    />
                                    <Label for="mb_mailling"
                                        >정보 메일을 수신하겠습니다.</Label
                                    >
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="mb_open"
                                        bind:checked={mb_open}
                                    />
                                    <Label for="mb_open"
                                        >다른분들이 나의 정보를 볼 수 있도록
                                        합니다.</Label
                                    >
                                </div>
                                <p class="text-xs text-muted-foreground pl-6">
                                    정보공개를 바꾸시면 앞으로 0일 이내에는
                                    변경이 안됩니다.
                                </p>
                            </div>
                        </div>

                        <Separator />

                        <!-- 자동등록방지 -->
                        <div class="space-y-4">
                            <h3
                                class="font-semibold text-lg flex items-center gap-2"
                            >
                                <span
                                    class="bg-primary/10 text-primary w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                    >4</span
                                >
                                자동등록방지
                            </h3>
                            <div
                                class="border p-4 rounded-md bg-muted/30 flex flex-col items-center justify-center gap-4 mx-8"
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
                                    placeholder="위의 글자를 순서대로 입력하세요"
                                    bind:value={captcha_key}
                                    disabled={loading}
                                    class="max-w-[300px] text-center"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 mt-6">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1 h-12 text-lg"
                            onclick={prevStep}
                            disabled={loading}
                        >
                            이전 단계
                        </Button>
                        <Button
                            type="submit"
                            class="flex-[2] h-12 text-lg"
                            disabled={loading}
                        >
                            {loading ? "가입 처리 중..." : "회원가입 완료"}
                        </Button>
                    </div>
                {/if}
            </form>

            <div class="text-center text-sm text-muted-foreground">
                이미 회원이신가요?
                <a href="{base}/auth/login" class="underline hover:text-primary"
                    >로그인하기</a
                >
            </div>
        </Card.Content>
    </Card.Root>
</div>
