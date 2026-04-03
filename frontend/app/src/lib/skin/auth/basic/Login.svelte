<script lang="ts">
    import { login } from "$lib/api";
    import { setMember } from "$lib/store";
    import { goto } from "$app/navigation";
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import { Checkbox } from "$lib/ui/checkbox";
    import * as Card from "$lib/ui/card";
    import { Separator } from "$lib/ui/separator";
    import { signInWithFirebaseToken } from "$lib/firebase/firebase";
import { base } from '$app/paths';

    let mb_id = $state("");
    let mb_password = $state("");
    let auto_login = $state(false);
    let error = $state("");
    let loading = $state(false);

    async function handleLogin() {
        if (!mb_id || !mb_password) {
            error = "아이디와 비밀번호를 입력해주세요.";
            return;
        }

        loading = true;
        error = "";

        try {
            const response = await login({ mb_id, mb_password, auto_login });
            console.log("Login response:", response);
            console.log(
                "Firebase token:",
                response.firebase_token ? "EXISTS" : "NULL",
            );

            setMember(response.mb);

            // Firebase 인증 (채팅용) - 선택적
            if (response.firebase_token) {
                const result = await signInWithFirebaseToken(
                    response.firebase_token,
                );
                console.log("Firebase auth result:", result);
            } else {
                console.warn("Firebase token이 없습니다. 채팅 기능 제한됨.");
            }

            goto(base + "/");
        } catch (e: any) {
            error = e.message || "로그인에 실패했습니다.";
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
                >로그인</Card.Title
            >
            <Card.Description class="text-center">
                서비스 이용을 위해 로그인해주세요.
            </Card.Description>
        </Card.Header>
        <Card.Content class="grid gap-4">
            {#if error}
                <div
                    class="bg-destructive/15 text-destructive text-sm p-3 rounded-md"
                >
                    {error}
                </div>
            {/if}

            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    handleLogin();
                }}
                class="grid gap-4"
            >
                <div class="grid gap-2">
                    <Label for="mb_id">아이디</Label>
                    <Input
                        id="mb_id"
                        type="text"
                        placeholder="아이디를 입력하세요"
                        bind:value={mb_id}
                        disabled={loading}
                        required
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="mb_password">비밀번호</Label>
                    <Input
                        id="mb_password"
                        type="password"
                        bind:value={mb_password}
                        disabled={loading}
                        required
                    />
                </div>

                <div class="flex items-center space-x-2">
                    <Checkbox id="auto_login" bind:checked={auto_login} />
                    <Label
                        for="auto_login"
                        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                        자동로그인
                    </Label>
                </div>

                <Button type="submit" class="w-full" disabled={loading}>
                    {loading ? "로그인 중..." : "로그인"}
                </Button>
            </form>

            <div
                class="flex items-center justify-between text-sm text-muted-foreground mt-2"
            >
                <a href="{base}/auth/find" class="hover:underline hover:text-primary">
                    아이디/비밀번호 찾기
                </a>
                <a
                    href="{base}/auth/register"
                    class="hover:underline hover:text-primary"
                >
                    회원가입
                </a>
            </div>

            <div class="relative my-2">
                <div class="absolute inset-0 flex items-center">
                    <span class="w-full border-t"></span>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-background px-2 text-muted-foreground">
                        Or continue with
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <!-- Social Login Buttons (UI Only) -->
                <Button
                    variant="outline"
                    class="w-full bg-[#03C75A] hover:bg-[#03C75A]/90 text-white border-none"
                >
                    네이버
                </Button>
                <Button
                    variant="outline"
                    class="w-full bg-[#FEE500] hover:bg-[#FEE500]/90 text-black border-none"
                >
                    카카오
                </Button>
                <Button variant="outline" class="w-full">
                    <svg
                        class="mr-2 h-4 w-4"
                        aria-hidden="true"
                        focusable="false"
                        data-prefix="fab"
                        data-icon="google"
                        role="img"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 488 512"
                        ><path
                            fill="currentColor"
                            d="M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z"
                        ></path></svg
                    >
                    Google
                </Button>
                <Button variant="outline" class="w-full">
                    <svg
                        class="mr-2 h-4 w-4"
                        aria-hidden="true"
                        focusable="false"
                        data-prefix="fab"
                        data-icon="apple"
                        role="img"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 384 512"
                        ><path
                            fill="currentColor"
                            d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.5-163.7c27.1-32 23.2-86.8-4-118-35 3-76 19.8-102.3 54.4-4.8 6.1-8.7 12.8-11.3 20-3.3 8.3-4.8 17-4.8 25.4 0 37.9 37 66.2 122.3 18.2z"
                        ></path></svg
                    >
                    Apple
                </Button>
            </div>
        </Card.Content>
    </Card.Root>
</div>
