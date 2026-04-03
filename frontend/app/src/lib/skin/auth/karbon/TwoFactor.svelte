<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import * as Card from "$lib/ui/card";
    import { Label } from "$lib/ui/label";
    import { verify2FA, send2FACode } from "$lib/api/auth_karbon";
    import { toastStore } from "$lib/store/toast";
    import { goto } from "$app/navigation";
    import { base } from '$app/paths';

    let method = $state<"sms" | "email" | "password">("sms");
    let authCode = $state("");
    let loading = $state(false);
    let codeSent = $state(false);

    async function requestAuthCode() {
        loading = true;
        try {
            await send2FACode(method);
            codeSent = true;
            toastStore.success("인증번호가 발송되었습니다.");
        } catch (e: any) {
            toastStore.error(e.message || "발송 실패");
        } finally {
            loading = false;
        }
    }

    async function handleSubmit() {
        if (!authCode) return;
        loading = true;
        try {
            const res = await verify2FA({ code: authCode, type: method });
            if (res.verified) {
                toastStore.success("인증되었습니다.");
                // Redirect to return url if exists, else home
                goto(base + "/");
            }
        } catch (e: any) {
            toastStore.error(e.message || "인증 실패");
        } finally {
            loading = false;
        }
    }
</script>

<div
    class="flex items-center justify-center min-h-[calc(100vh-200px)] py-10 px-4"
>
    <Card.Root class="w-full max-w-md">
        <Card.Header>
            <Card.Title class="text-center">2차 보안 인증</Card.Title>
            <Card.Description class="text-center">
                안전한 서비스 이용을 위해 추가 인증이 필요합니다.
            </Card.Description>
        </Card.Header>
        <Card.Content class="space-y-6">
            <!-- Method Selection -->
            <div class="grid grid-cols-3 gap-2">
                <button
                    class="p-2 text-sm border rounded-md transition-colors {method ===
                    'sms'
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'hover:bg-muted'}"
                    onclick={() => {
                        method = "sms";
                        codeSent = false;
                        authCode = "";
                    }}
                >
                    문자 인증
                </button>
                <button
                    class="p-2 text-sm border rounded-md transition-colors {method ===
                    'email'
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'hover:bg-muted'}"
                    onclick={() => {
                        method = "email";
                        codeSent = false;
                        authCode = "";
                    }}
                >
                    이메일 인증
                </button>
                <button
                    class="p-2 text-sm border rounded-md transition-colors {method ===
                    'password'
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'hover:bg-muted'}"
                    onclick={() => {
                        method = "password";
                        codeSent = true;
                        authCode = "";
                    }}
                >
                    비밀번호 재확인
                </button>
            </div>

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    handleSubmit();
                }}
                class="space-y-4"
            >
                {#if method !== "password" && !codeSent}
                    <div class="text-center py-4 bg-muted/30 rounded-lg">
                        <p class="text-sm text-muted-foreground mb-4">
                            {method === "sms"
                                ? "등록된 휴대폰 번호로"
                                : "등록된 이메일 주소로"}
                            인증번호를 발송합니다.
                        </p>
                        <Button
                            type="button"
                            onclick={requestAuthCode}
                            disabled={loading}
                        >
                            인증번호 전송
                        </Button>
                    </div>
                {:else}
                    <div
                        class="grid gap-2 animate-in fade-in slide-in-from-bottom-2"
                    >
                        <Label>
                            {method === "password" ? "비밀번호" : "인증번호"}
                        </Label>
                        <Input
                            type={method === "password" ? "password" : "text"}
                            bind:value={authCode}
                            placeholder={method === "password"
                                ? "비밀번호 입력"
                                : "인증번호 6자리"}
                        />
                        {#if method !== "password"}
                            <div class="text-xs text-right">
                                <button
                                    type="button"
                                    class="text-muted-foreground underline"
                                    onclick={requestAuthCode}>재전송</button
                                >
                            </div>
                        {/if}
                    </div>
                    <Button type="submit" class="w-full" disabled={loading}>
                        인증 확인
                    </Button>
                {/if}
            </form>
        </Card.Content>
    </Card.Root>
</div>
