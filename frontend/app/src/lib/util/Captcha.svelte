<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import { RefreshCw, Volume2 } from "lucide-svelte";

    interface Props {
        value?: string;
        label?: string;
        placeholder?: string;
        class?: string;
    }

    let {
        value = $bindable(""),
        label = "자동등록방지",
        placeholder = "보이는 글자 입력",
        class: className = "",
    }: Props = $props();

    // API Base URL from environment
    const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/api";

    // Captcha state
    let timestamp = $state(Date.now());

    // Computed URLs
    const imageUrl = $derived(`${API_BASE_URL}/captcha/image?t=${timestamp}`);
    const audioUrl = $derived(`${API_BASE_URL}/captcha/audio?ts=${timestamp}`);

    // Functions
    export function refresh() {
        timestamp = Date.now();
        value = "";
    }

    function playAudio() {
        const audio = new Audio(audioUrl);
        audio.play();
    }
</script>

<div class="grid w-full gap-2 {className}">
    {#if label}
        <Label>{label}</Label>
    {/if}
    <div
        class="flex flex-col sm:flex-row gap-4 items-start sm:items-center border p-4 rounded-md bg-muted/30"
    >
        <div class="flex items-center gap-2">
            <img
                src={imageUrl}
                alt="CAPTCHA"
                class="border rounded-md h-10 w-auto"
            />
            <Button
                variant="outline"
                size="icon"
                onclick={() => refresh()}
                type="button"
                title="새로고침"
            >
                <RefreshCw class="w-4 h-4" />
            </Button>
            <Button
                variant="outline"
                size="icon"
                onclick={playAudio}
                type="button"
                title="음성 듣기"
            >
                <Volume2 class="w-4 h-4" />
            </Button>
        </div>
        <Input {placeholder} bind:value class="max-w-[200px]" />
    </div>
</div>
