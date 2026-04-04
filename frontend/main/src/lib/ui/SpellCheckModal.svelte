<script lang="ts">
    import { Button } from "$lib/ui/button";
    import * as Dialog from "$lib/ui/dialog";
    import { Check, X, AlertCircle, Loader2 } from "lucide-svelte";

    interface Correction {
        token: string;
        suggestions: string[];
        info: string;
    }

    interface Props {
        open?: boolean;
        loading?: boolean;
        corrections?: Correction[];
        correctedText?: string;
        originalText?: string;
        onApply?: (text: string) => void;
        onClose?: () => void;
    }

    let {
        open = $bindable(false),
        loading = false,
        corrections = [],
        correctedText = "",
        originalText = "",
        onApply,
        onClose,
    }: Props = $props();

    function handleApplyAll() {
        if (onApply && correctedText) {
            onApply(correctedText);
        }
        open = false;
    }

    function handleClose() {
        if (onClose) onClose();
        open = false;
    }
</script>

<Dialog.Root bind:open>
    <Dialog.Content class="max-w-lg">
        <Dialog.Header>
            <Dialog.Title class="flex items-center gap-2">
                <AlertCircle class="w-5 h-5 text-primary" />
                맞춤법 검사 결과
            </Dialog.Title>
        </Dialog.Header>

        <div class="py-4">
            {#if loading}
                <div class="flex items-center justify-center py-8">
                    <Loader2
                        class="w-6 h-6 animate-spin text-muted-foreground"
                    />
                    <span class="ml-2 text-muted-foreground">검사 중...</span>
                </div>
            {:else if corrections.length === 0}
                <div
                    class="flex flex-col items-center justify-center py-8 text-center"
                >
                    <Check class="w-12 h-12 text-green-500 mb-2" />
                    <p class="text-lg font-medium">맞춤법 오류가 없습니다!</p>
                    <p class="text-sm text-muted-foreground">
                        입력한 텍스트에 맞춤법 오류가 발견되지 않았습니다.
                    </p>
                </div>
            {:else}
                <div class="space-y-3 max-h-[300px] overflow-y-auto">
                    <p class="text-sm text-muted-foreground mb-3">
                        <span class="font-medium text-foreground"
                            >{corrections.length}개</span
                        >의 오류가 발견되었습니다.
                    </p>
                    {#each corrections as correction, i}
                        <div class="border rounded-lg p-3 bg-muted/30">
                            <div class="flex items-start gap-2">
                                <span
                                    class="text-xs bg-destructive/20 text-destructive px-2 py-0.5 rounded font-medium"
                                >
                                    오류
                                </span>
                                <span
                                    class="font-medium line-through text-destructive"
                                >
                                    {correction.token}
                                </span>
                            </div>
                            <div class="flex items-start gap-2 mt-2">
                                <span
                                    class="text-xs bg-green-500/20 text-green-700 dark:text-green-400 px-2 py-0.5 rounded font-medium"
                                >
                                    교정
                                </span>
                                <span
                                    class="font-medium text-green-700 dark:text-green-400"
                                >
                                    {correction.suggestions[0] || "-"}
                                </span>
                            </div>
                            {#if correction.info}
                                <p
                                    class="text-xs text-muted-foreground mt-2 pl-1"
                                >
                                    {correction.info}
                                </p>
                            {/if}
                        </div>
                    {/each}
                </div>

                {#if correctedText !== originalText}
                    <div class="mt-4 pt-4 border-t">
                        <p class="text-sm font-medium mb-2">교정된 텍스트:</p>
                        <div
                            class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-900 rounded-lg p-3 text-sm max-h-[100px] overflow-y-auto"
                        >
                            {correctedText}
                        </div>
                    </div>
                {/if}
            {/if}
        </div>

        <Dialog.Footer class="flex gap-2">
            <Button variant="outline" onclick={handleClose}>닫기</Button>
            {#if corrections.length > 0}
                <Button onclick={handleApplyAll}>
                    <Check class="w-4 h-4 mr-1" />
                    전체 교정 적용
                </Button>
            {/if}
        </Dialog.Footer>
    </Dialog.Content>
</Dialog.Root>
