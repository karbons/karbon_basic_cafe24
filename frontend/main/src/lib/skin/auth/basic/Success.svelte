<script lang="ts">
    import { page } from "$app/stores";
    import { Button } from "$lib/ui/button";
    import * as Card from "$lib/ui/card";
import { base } from '$app/paths';

    const name = $page.url.searchParams.get("name") || "회원";
</script>

<div
    class="flex items-center justify-center min-h-[calc(100vh-200px)] py-10 px-4"
>
    <Card.Root class="w-full max-w-md">
        <Card.Header>
            <Card.Title class="text-center text-2xl">회원가입 완료</Card.Title>
        </Card.Header>
        <Card.Content class="text-center space-y-4">
            <div class="text-lg">
                <span class="font-bold text-primary">{name}</span>님,
                환영합니다!<br />
                회원가입이 성공적으로 완료되었습니다.
            </div>
            <p class="text-sm text-muted-foreground">
                이제 로그인하여 서비스를 이용하실 수 있습니다.
            </p>
            <div class="pt-4">
                <Button href="{base}/auth/login" class="w-full"
                    >로그인하러 가기</Button
                >
            </div>
        </Card.Content>
    </Card.Root>
</div>
