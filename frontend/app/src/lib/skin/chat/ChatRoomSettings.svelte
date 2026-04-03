<script lang="ts">
    import * as Dialog from "$lib/ui/dialog";
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import { memberStore } from "$lib/store";
    import { apiPost } from "$lib/api";
    import { toastStore } from "$lib/store/toast";
    import {
        updateChatRoomSettings,
        type ChatRoomSettings,
    } from "$lib/store/chat";

    export let open = false;
    export let roomId: string;
    export let currentSettings: ChatRoomSettings | null = null;
    export let onSave: () => void;

    let roomAlias = "";
    let roomImage = ""; // TODO: File upload implementation
    let bgColor = "";
    let isSaving = false;

    // 초기값 설정
    $: if (open && currentSettings) {
        roomAlias = currentSettings.room_alias || "";
        bgColor = currentSettings.bg_color || "";
    } else if (open) {
        roomAlias = "";
        bgColor = "";
    }

    async function handleSave() {
        if (!roomId) return;
        isSaving = true;

        try {
            const data = {
                room_id: roomId,
                room_alias: roomAlias,
                bg_color: bgColor,
            };

            await apiPost("/chat/settings", data);

            // 스토어 업데이트
            await updateChatRoomSettings(roomId, data);

            toastStore.success("채팅방 설정이 저장되었습니다.");
            onSave();
            open = false;
        } catch (e: any) {
            toastStore.error(e.message || "설정 저장 실패");
        } finally {
            isSaving = false;
        }
    }
</script>

<Dialog.Root bind:open>
    <Dialog.Content
        class="w-full h-full max-w-none rounded-none border-0 sm:max-w-[425px] sm:h-auto sm:rounded-lg sm:border z-[80] block sm:grid"
    >
        <Dialog.Header class="text-left">
            <Dialog.Title>채팅방 설정</Dialog.Title>
            <Dialog.Description>
                이 채팅방의 설정을 변경합니다. 변경사항은 본인에게만 적용됩니다.
            </Dialog.Description>
        </Dialog.Header>
        <div class="grid gap-4 py-4 px-1">
            <div class="grid gap-2">
                <Label for="alias">채팅방 이름 (별명)</Label>
                <Input
                    id="alias"
                    bind:value={roomAlias}
                    placeholder="기본 채팅방 이름 대신 표시됩니다"
                />
            </div>
            <div class="grid gap-2">
                <Label for="bgcolor">배경 색상</Label>
                <div class="flex items-center gap-2">
                    <Input
                        id="bgcolor"
                        type="color"
                        bind:value={bgColor}
                        class="w-12 h-10 p-1 cursor-pointer"
                    />
                    <Input
                        bind:value={bgColor}
                        placeholder="#FFFFFF"
                        class="flex-1"
                    />
                </div>
            </div>
            <!-- TODO: 배경 이미지 업로드 -->
        </div>
        <Dialog.Footer class="gap-2 sm:gap-0">
            <div class="flex flex-row gap-2 w-full sm:justify-end">
                <Button
                    variant="outline"
                    onclick={() => (open = false)}
                    class="flex-1 sm:flex-none">취소</Button
                >
                <Button
                    onclick={handleSave}
                    disabled={isSaving}
                    class="flex-1 sm:flex-none"
                >
                    {#if isSaving}저장 중...{:else}저장{/if}
                </Button>
            </div>
        </Dialog.Footer>
    </Dialog.Content>
</Dialog.Root>
