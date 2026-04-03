<script lang="ts">
    import { page } from "$app/stores";
    import { Button } from "$lib/ui/button";
    import * as Card from "$lib/ui/card";
    import { CheckCircle2 } from "lucide-svelte";
import { base } from '$app/paths';

    const name = $page.url.searchParams.get("name") || "Customer";
</script>

<div
    class="flex items-center justify-center min-h-[calc(100vh-200px)] py-10 px-4"
>
    <Card.Root class="w-full max-w-md border-primary/20 bg-primary/5">
        <Card.Content class="pt-10 pb-10 text-center space-y-6">
            <div class="flex justify-center">
                <div
                    class="h-20 w-20 rounded-full bg-green-100 flex items-center justify-center"
                >
                    <CheckCircle2 class="h-10 w-10 text-green-600" />
                </div>
            </div>

            <div class="space-y-2">
                <h2 class="text-2xl font-bold tracking-tight">
                    Welcome to Karbon!
                </h2>
                <p class="text-muted-foreground">
                    <span class="font-semibold text-foreground">{name}</span>님,
                    회원가입을 축하합니다.
                </p>
            </div>

            <div class="grid gap-2">
                <Button
                    href="{base}/auth/login"
                    size="lg"
                    class="w-full font-semibold"
                >
                    로그인 페이지로 이동
                </Button>
                <Button href="{base}/" variant="ghost" class="w-full">
                    홈으로 돌아가기
                </Button>
            </div>
        </Card.Content>
    </Card.Root>
</div>
