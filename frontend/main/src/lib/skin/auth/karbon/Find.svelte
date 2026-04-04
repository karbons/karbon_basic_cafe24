<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import * as Card from "$lib/ui/card";
    import { resetPassword } from "$lib/api/auth_karbon";
    import * as Alert from "$lib/ui/alert";
import { base } from '$app/paths';

    let method = $state<"email" | "phone">("email");
    let email = $state("");
    let phone = $state("");
    let loading = $state(false);
    let error = $state("");
    let successMessage = $state("");

    async function handleReset() {
        if (method === "email" && !email) return;
        if (method === "phone" && !phone) return;

        loading = true;
        error = "";
        successMessage = "";

        try {
            await resetPassword(method, method === "email" ? email : phone);
            successMessage =
                method === "email"
                    ? "이메일로 비밀번호 재설정 링크가 발송되었습니다."
                    : "휴대폰으로 임시 비밀번호가 발송되었습니다.";
        } catch (e: any) {
            error = e.message || "요청 처리에 실패했습니다.";
        } finally {
            loading = false;
        }
    }
</script>

<div
    class="flex items-center justify-center min-h-[calc(100vh-200px)] py-10 px-4"
>
    <Card.Root class="w-full max-w-md">
        <Card.Header class="space-y-1">
            <Card.Title class="text-2xl font-bold text-center"
                >비밀번호 찾기</Card.Title
            >
            <Card.Description class="text-center">
                가입하신 정보로 비밀번호를 재설정합니다.
            </Card.Description>
        </Card.Header>
        <Card.Content>
            {#if successMessage}
                <div
                    class="space-y-4 animate-in fade-in slide-in-from-bottom-2"
                >
                    <Alert.Root class="bg-green-500/10 border-green-500/20">
                        <Alert.Title class="text-green-600 font-bold"
                            >발송 완료</Alert.Title
                        >
                        <Alert.Description
                            class="mt-2 text-sm text-muted-foreground"
                        >
                            {successMessage}
                        </Alert.Description>
                    </Alert.Root>
                    <Button class="w-full" href="{base}/auth/login"
                        >로그인으로 돌아가기</Button
                    >
                </div>
            {:else}
                <!-- Custom Tabs -->
                <div class="w-full mb-6">
                    <div
                        class="grid w-full grid-cols-2 mb-4 bg-muted p-1 rounded-lg"
                    >
                        <button
                            class="text-sm font-medium py-1.5 rounded-md transition-all {method ===
                            'email'
                                ? 'bg-background shadow-sm text-foreground'
                                : 'text-muted-foreground hover:text-foreground'}"
                            onclick={() => {
                                method = "email";
                                error = "";
                            }}
                            type="button"
                        >
                            이메일
                        </button>
                        <button
                            class="text-sm font-medium py-1.5 rounded-md transition-all {method ===
                            'phone'
                                ? 'bg-background shadow-sm text-foreground'
                                : 'text-muted-foreground hover:text-foreground'}"
                            onclick={() => {
                                method = "phone";
                                error = "";
                            }}
                            type="button"
                        >
                            휴대폰
                        </button>
                    </div>

                    <form
                        onsubmit={(e) => {
                            e.preventDefault();
                            handleReset();
                        }}
                        class="grid gap-4"
                    >
                        {#if error}
                            <div
                                class="bg-destructive/15 text-destructive text-sm p-3 rounded-md"
                            >
                                {error}
                            </div>
                        {/if}

                        {#if method === "email"}
                            <div class="grid gap-2">
                                <Label for="email">이메일</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="name@example.com"
                                    bind:value={email}
                                    disabled={loading}
                                    required
                                />
                            </div>
                        {:else}
                            <div class="grid gap-2">
                                <Label for="phone">휴대폰 번호</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    placeholder="010-1234-5678"
                                    bind:value={phone}
                                    disabled={loading}
                                    required
                                />
                            </div>
                        {/if}

                        <Button type="submit" class="w-full" disabled={loading}>
                            {loading ? "전송 중..." : "확인"}
                        </Button>
                    </form>
                </div>
            {/if}

            <div class="text-center text-sm text-muted-foreground mt-4">
                <a href="{base}/auth/login" class="hover:underline hover:text-primary"
                    >로그인으로 돌아가기</a
                >
            </div>
        </Card.Content>
    </Card.Root>
</div>
