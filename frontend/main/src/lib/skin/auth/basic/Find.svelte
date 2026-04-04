<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import * as Card from "$lib/ui/card";
    import { apiPost } from "$lib/api";
    import * as Alert from "$lib/ui/alert";
import { base } from '$app/paths';

    let mode = $state<"id" | "pw">("id");
    let email_for_id = $state("");
    let id_for_pw = $state("");
    let email_for_pw = $state("");
    let loading = $state(false);

    // 결과 상태
    let foundIdResult = $state<{ mb_id: string; reg_date: string } | null>(
        null,
    );
    // PW 찾기 결과 상태
    let foundPwSuccess = $state(false);
    let foundPwMessage = $state("");
    let error = $state("");

    async function handleFindId() {
        if (!email_for_id) return;

        loading = true;
        error = "";
        foundIdResult = null;
        foundPwSuccess = false;

        try {
            const res = await apiPost<{ mb_id: string; reg_date: string }>(
                "/auth/find/id",
                {
                    mb_email: email_for_id,
                },
            );
            foundIdResult = res;
        } catch (e: any) {
            error = e.message || "아이디 찾기에 실패했습니다.";
        } finally {
            loading = false;
        }
    }

    async function handleFindPw() {
        if (!id_for_pw || !email_for_pw) return;

        loading = true;
        error = "";
        foundIdResult = null;
        foundPwSuccess = false;

        try {
            const res = await apiPost<{ message: string }>("/auth/find/pw", {
                mb_id: id_for_pw,
                mb_email: email_for_pw,
            });
            // API 응답 구조에 따라 res.message 또는 success 메시지를 사용
            foundPwMessage = "이메일로 임시 비밀번호가 발송되었습니다.";
            foundPwSuccess = true;
        } catch (e: any) {
            error = e.message || "비밀번호 찾기에 실패했습니다.";
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
                >계정 찾기</Card.Title
            >
            <Card.Description class="text-center">
                아이디 또는 비밀번호를 잊으셨나요?
            </Card.Description>
        </Card.Header>
        <Card.Content>
            <!-- Simple Tab Switcher -->
            <div
                class="grid w-full grid-cols-2 mb-6 p-1 bg-muted rounded-lg bg-slate-100"
            >
                <button
                    class="text-sm font-medium py-1.5 rounded-md transition-all {mode ===
                    'id'
                        ? 'bg-white shadow-sm text-foreground'
                        : 'text-muted-foreground hover:text-foreground'}"
                    onclick={() => {
                        mode = "id";
                        error = "";
                        foundIdResult = null;
                        foundPwSuccess = false;
                    }}
                >
                    아이디 찾기
                </button>
                <button
                    class="text-sm font-medium py-1.5 rounded-md transition-all {mode ===
                    'pw'
                        ? 'bg-white shadow-sm text-foreground'
                        : 'text-muted-foreground hover:text-foreground'}"
                    onclick={() => {
                        mode = "pw";
                        error = "";
                        foundIdResult = null;
                        foundPwSuccess = false;
                    }}
                >
                    비밀번호 찾기
                </button>
            </div>

            {#if error}
                <div
                    class="mb-4 p-3 bg-destructive/15 text-destructive rounded-md text-sm font-medium"
                >
                    {error}
                </div>
            {/if}

            {#if mode === "id"}
                {#if foundIdResult}
                    <div
                        class="space-y-4 animate-in fade-in slide-in-from-bottom-2"
                    >
                        <Alert.Root class="bg-primary/10 border-primary/20">
                            <Alert.Title class="text-primary font-bold"
                                >아이디 찾기 성공</Alert.Title
                            >
                            <Alert.Description class="mt-2">
                                <p class="text-sm text-muted-foreground">
                                    회원님의 아이디는 다음과 같습니다.
                                </p>
                                <div
                                    class="text-2xl font-bold text-foreground mt-1 mb-2"
                                >
                                    {foundIdResult.mb_id}
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    가입일: {foundIdResult.reg_date}
                                </p>
                            </Alert.Description>
                        </Alert.Root>
                        <Button class="w-full" href="{base}/auth/login"
                            >로그인 하러 가기</Button
                        >
                        <Button
                            variant="outline"
                            class="w-full"
                            onclick={() => (foundIdResult = null)}
                            >다시 찾기</Button
                        >
                    </div>
                {:else}
                    <form
                        onsubmit={(e) => {
                            e.preventDefault();
                            handleFindId();
                        }}
                        class="grid gap-4"
                    >
                        <div class="grid gap-2">
                            <Label for="email_for_id">이메일</Label>
                            <Input
                                id="email_for_id"
                                type="email"
                                placeholder="가입 시 등록한 이메일"
                                bind:value={email_for_id}
                                required
                                disabled={loading}
                            />
                        </div>
                        <Button type="submit" disabled={loading}>
                            {loading ? "확인 중..." : "아이디 찾기"}
                        </Button>
                    </form>
                {/if}
            {:else}
                <!-- 비밀번호 찾기 -->
                {#if foundPwSuccess}
                    <div
                        class="space-y-4 animate-in fade-in slide-in-from-bottom-2"
                    >
                        <Alert.Root class="bg-green-500/10 border-green-500/20">
                            <Alert.Title class="text-green-600 font-bold"
                                >임시 비밀번호 발송 완료</Alert.Title
                            >
                            <Alert.Description
                                class="mt-2 text-sm text-muted-foreground"
                            >
                                {foundPwMessage}
                                <br />로그인 후 반드시 비밀번호를 변경해주세요.
                            </Alert.Description>
                        </Alert.Root>
                        <Button class="w-full" href="{base}/auth/login"
                            >로그인 하러 가기</Button
                        >
                    </div>
                {:else}
                    <form
                        onsubmit={(e) => {
                            e.preventDefault();
                            handleFindPw();
                        }}
                        class="grid gap-4"
                    >
                        <div class="grid gap-2">
                            <Label for="id_for_pw">아이디</Label>
                            <Input
                                id="id_for_pw"
                                type="text"
                                placeholder="아이디 입력"
                                bind:value={id_for_pw}
                                required
                                disabled={loading}
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="email_for_pw">이메일</Label>
                            <Input
                                id="email_for_pw"
                                type="email"
                                placeholder="가입 시 등록한 이메일"
                                bind:value={email_for_pw}
                                required
                                disabled={loading}
                            />
                        </div>
                        <Button type="submit" disabled={loading}>
                            {loading ? "확인 중..." : "비밀번호 찾기"}
                        </Button>
                    </form>
                {/if}
            {/if}

            <div
                class="flex items-center justify-center text-sm text-muted-foreground mt-6 gap-4"
            >
                <a
                    href="{base}/auth/login"
                    class="hover:underline hover:text-primary"
                >
                    로그인
                </a>
                <span class="text-gray-300">|</span>
                <a
                    href="{base}/auth/register"
                    class="hover:underline hover:text-primary"
                >
                    회원가입
                </a>
            </div>
        </Card.Content>
    </Card.Root>
</div>
