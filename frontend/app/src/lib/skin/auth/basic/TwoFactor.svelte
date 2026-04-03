<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import * as Card from "$lib/ui/card";
    import { Label } from "$lib/ui/label";
    import { verify2FA } from "$lib/api/auth_karbon";
    import { toastStore } from "$lib/store/toast";
    import { goto } from "$app/navigation";
    import { base } from '$app/paths';

    let authCode = $state("");
    let loading = $state(false);

    async function handleSubmit() {
        if (!authCode) return;
        loading = true;
        try {
            await verify2FA({ code: authCode, type: "basic" });
            toastStore.success("인증되었습니다.");
            goto(base + "/"); // or return url
        } catch (e: any) {
            toastStore.error("인증에 실패했습니다.");
        } finally {
            loading = false;
        }
    }
</script>

<div
    class="flex items-center justify-center min-h-[calc(100vh-200px)] py-10 px-4"
>
    <Card.Root class="w-full max-w-sm">
        <Card.Header>
            <Card.Title>2차 인증</Card.Title>
            <Card.Description>인증번호를 입력해주세요.</Card.Description>
        </Card.Header>
        <Card.Content>
            <form
                onsubmit={(e) => {
                    e.preventDefault();
                    handleSubmit();
                }}
                class="space-y-4"
            >
                <div class="grid gap-2">
                    <Label>인증코드</Label>
                    <Input bind:value={authCode} placeholder="123456" />
                </div>
                <Button type="submit" class="w-full" disabled={loading}
                    >확인</Button
                >
            </form>
        </Card.Content>
    </Card.Root>
</div>
