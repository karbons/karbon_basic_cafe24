<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import {
        MessageCircle,
        X,
        Send,
        ArrowLeft,
        Headphones,
        Users,
        Search,
        Settings,
        MoreVertical,
        Bell,
        BellOff,
        Star,
        Pin,
        LogOut,
    } from "lucide-svelte";
    import PostLinkTemplate from "./templates/PostLinkTemplate.svelte";
    import ChatRoomSettings from "./ChatRoomSettings.svelte";
    import * as DropdownMenu from "$lib/ui/dropdown-menu";
    import {
        isChatOpen,
        currentView,
        currentRoomId,
        chatRooms,
        currentMessages,
        isLoading,
        chatEnabled,
        unreadCount,
        initChat,
        openChatRoom,
        startSupportChat,
        sendChatMessage,
        backToRooms,
        toggleChat,
        closeChat,
        cleanupSubscriptions,
        textToInsert,
        loadChatRoomSettings,
        roomSettings,
        updateChatRoomSettings,
    } from "$lib/store/chat";
    import { memberStore } from "$lib/store";
    import { confirmStore } from "$lib/store";
    import type { ChatRoom, ChatMessage } from "$lib/firebase/chat";
    import { markMessagesAsRead } from "$lib/firebase/chat";
    import { toastStore } from "$lib/store/toast";

    let messageInput = $state("");
    let messagesContainer = $state(null as HTMLDivElement | null);

    let textareaElement = $state(null as HTMLTextAreaElement | null);

    // 검색 및 설정 상태
    let isSearchOpen = $state(false);
    let searchTerm = $state("");
    let isSettingsOpen = $state(false);
    let searchInput = $state(null as HTMLInputElement | null);

    // 현재 방 설정
    let currentRoomSettings = $derived(
        $currentRoomId ? $roomSettings[$currentRoomId] : null,
    );

    // 검색 필터링된 메시지
    let filteredMessages = $derived(
        searchTerm
            ? $currentMessages.filter((m) =>
                  m.content.toLowerCase().includes(searchTerm.toLowerCase()),
              )
            : $currentMessages,
    );

    // 높이 자동 조절
    function adjustHeight() {
        if (textareaElement) {
            textareaElement.style.height = "auto";
            textareaElement.style.height =
                Math.min(textareaElement.scrollHeight, 120) + "px"; // 최대 높이 제한
        }
    }

    // 메시지 입력 핸들러
    async function handleSendMessage() {
        if (!messageInput.trim() || !$memberStore) return;

        const content = messageInput;
        messageInput = "";
        if (textareaElement) {
            textareaElement.style.height = "auto"; // 초기화
            textareaElement.focus();
        }

        await sendChatMessage(
            $memberStore.mb_id,
            $memberStore.mb_nick || $memberStore.mb_name || $memberStore.mb_id,
            content,
        );
    }

    // 엔터키 전송
    // 현재 활성화된 채팅방 정보
    let activeRoom = $derived($chatRooms.find((r) => r.id === $currentRoomId));

    // 읽음 처리 (내가 안 읽은 메시지가 있으면)
    $effect(() => {
        if (
            $isChatOpen &&
            $currentRoomId &&
            $memberStore &&
            $currentMessages.length > 0
        ) {
            const unreadExists = $currentMessages.some(
                (m) =>
                    !m.readBy ||
                    (Array.isArray(m.readBy) &&
                        !m.readBy.includes($memberStore!.mb_id)),
            );
            if (unreadExists) {
                markMessagesAsRead($currentRoomId, $memberStore.mb_id);
            }
        }
    });

    // 방 변경 시 설정 로드
    $effect(() => {
        if ($currentRoomId) {
            loadChatRoomSettings($currentRoomId);
            isSearchOpen = false;
            searchTerm = "";
        }
    });

    // 엔터키 전송
    function handleKeydown(event: KeyboardEvent) {
        if (event.isComposing) return; // 한글 조합 중일 때는 무시

        if (event.key === "Enter" && !event.shiftKey) {
            event.preventDefault();
            handleSendMessage();
        }
    }

    // 고객센터 채팅 시작
    async function handleStartSupport() {
        if (!$memberStore) return;

        await startSupportChat(
            $memberStore.mb_id,
            $memberStore.mb_nick || $memberStore.mb_name || $memberStore.mb_id,
        );
    }

    // 채팅방 선택
    async function handleSelectRoom(room: ChatRoom) {
        await openChatRoom(room.id);
    }

    // 상대방 이름 가져오기 (별칭 우선)
    function getOtherName(room: ChatRoom): string {
        // 채팅방 별칭이 있으면 별칭 사용
        const settings = $roomSettings[room.id];
        if (settings && settings.room_alias) {
            return settings.room_alias;
        }

        if (!$memberStore) return "알 수 없음";
        const otherId = room.participants.find((p) => p !== $memberStore.mb_id);
        return otherId
            ? room.participantNames[otherId] || otherId
            : "알 수 없음";
    }

    // 시간 포맷
    function formatTime(date: Date): string {
        const now = new Date();
        const diff = now.getTime() - date.getTime();
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return "방금";
        if (minutes < 60) return `${minutes}분 전`;
        if (hours < 24) return `${hours}시간 전`;
        if (days < 7) return `${days}일 전`;

        return date.toLocaleDateString("ko-KR");
    }

    // 메시지 스크롤
    $effect(() => {
        if ($currentMessages.length && messagesContainer) {
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 50);
        }
    });

    // 외부 텍스트 삽입 처리
    $effect(() => {
        if ($textToInsert) {
            messageInput =
                (messageInput ? messageInput + "\n" : "") + $textToInsert;
            // 높이 조절
            setTimeout(() => {
                if (textareaElement) {
                    textareaElement.style.height = "auto";
                    textareaElement.style.height =
                        Math.min(textareaElement.scrollHeight, 120) + "px";
                    textareaElement.focus();
                }
            }, 0);
            textToInsert.set(null);
        }
    });

    // 초기화
    onMount(() => {
        if ($memberStore) {
            initChat($memberStore.mb_id);
            // 전체 방 설정 로드 (목록용)
            loadChatRoomSettings();
        }
    });

    onDestroy(() => {
        cleanupSubscriptions();
    });

    // 로그인 상태 변경 시 재초기화
    $effect(() => {
        if ($memberStore) {
            initChat($memberStore.mb_id);
        }
    });
    function formatMessage(content: string): string {
        let html = content
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // 검색 하이라이트
        if (searchTerm) {
            const regex = new RegExp(
                `(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")})`,
                "gi",
            );
            html = html.replace(
                regex,
                '<mark class="bg-yellow-200 text-black rounded px-0.5">$1</mark>',
            );
        }

        // 이미지: (URL) -> 이미지 태그
        html = html.replace(
            /\(이미지: (https?:\/\/[^\s)]+)\)/g,
            '<img src="$1" alt="Thumbnail" class="mt-2 rounded mb-1 max-w-full h-auto object-cover max-h-[150px] block border bg-white" />',
        );

        // 바로가기: URL -> 버튼
        html = html.replace(
            /바로가기: (https?:\/\/[^\s<]+)/g,
            '<div class="mt-2"><a href="$1" class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white text-sm font-bold rounded-md hover:bg-blue-700 transition-colors no-underline">게시글 바로가기</a></div>',
        );

        // 줄바꿈
        html = html.replace(/\n/g, "<br/>");

        return html;
    }

    // 설정 토글 함수들
    async function toggleSetting(
        key: "is_alarm" | "is_favorite" | "is_pinned",
    ) {
        if (!$currentRoomId) return;

        const currentVal = currentRoomSettings
            ? currentRoomSettings[key]
            : key === "is_alarm"
              ? 1
              : 0;
        const newVal = currentVal ? 0 : 1;

        await updateChatRoomSettings($currentRoomId, {
            [key]: newVal,
            room_id: $currentRoomId,
            mb_id: $memberStore?.mb_id || "",
        });

        // TODO: API Call to save
        try {
            const res = await fetch("/api/chat/settings", {
                method: "POST",
                body: JSON.stringify({
                    room_id: $currentRoomId,
                    [key]: newVal,
                }),
            });
        } catch (e) {
            console.error(e);
        }
    }

    async function handleLeaveRoom() {
        const result = await confirmStore.show({
            title: "채팅방 나가기",
            message: "채팅방을 나가시겠습니까?\n대화 내용이 모두 삭제됩니다.",
            confirmText: "나가기",
            cancelText: "취소",
            type: "danger",
        });

        if (result) {
            // TODO: Implement leave room logic (Firebase)
            toastStore.info("준비 중인 기능입니다.");
        }
    }
</script>

{#if $chatEnabled && $memberStore}
    <!-- 설정 모달 -->
    {#if $currentRoomId}
        <ChatRoomSettings
            bind:open={isSettingsOpen}
            roomId={$currentRoomId}
            currentSettings={currentRoomSettings}
            onSave={() => loadChatRoomSettings($currentRoomId)}
        />
    {/if}

    <!-- 플로팅 채팅 버튼 (모바일 숨김) -->
    <button
        onclick={toggleChat}
        class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-primary text-primary-foreground rounded-full shadow-lg hover:shadow-xl transition-all duration-200 hidden md:flex items-center justify-center group"
        aria-label="채팅"
    >
        {#if $isChatOpen}
            <X class="w-6 h-6" />
        {:else}
            <MessageCircle class="w-6 h-6" />
            {#if $unreadCount > 0}
                <span
                    class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                >
                    {$unreadCount > 9 ? "9+" : $unreadCount}
                </span>
            {/if}
        {/if}
    </button>

    <!-- 채팅 윈도우 -->
    {#if $isChatOpen}
        <div
            class="fixed inset-0 w-full h-full bg-white flex flex-col overflow-hidden md:inset-auto md:fixed md:bottom-24 md:right-6 md:z-50 md:w-96 md:h-[500px] md:rounded-2xl md:shadow-2xl md:border {$currentView ===
            'chat'
                ? 'z-[60]'
                : 'z-40 pb-16'}"
            style="max-height: 100vh; md:max-height: calc(100vh - 150px);"
        >
            <!-- 헤더 -->
            <div
                class="bg-primary text-primary-foreground px-4 py-3 flex items-center gap-2"
            >
                {#if $currentView === "chat"}
                    <button
                        onclick={backToRooms}
                        class="p-1 hover:bg-white/10 rounded mr-auto"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </button>

                    {#if isSearchOpen}
                        <div
                            class="flex-1 flex items-center bg-white/20 rounded px-2 py-1 mr-2"
                        >
                            <input
                                bind:this={searchInput}
                                type="text"
                                bind:value={searchTerm}
                                placeholder="대화 내용 검색"
                                class="bg-transparent border-none outline-none text-white placeholder-white/70 text-sm w-full"
                                onclick={(e) => e.stopPropagation()}
                            />
                            <button
                                onclick={() => {
                                    isSearchOpen = false;
                                    searchTerm = "";
                                }}
                                class="text-white/70 hover:text-white ml-1"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    {:else}
                        <span
                            class="font-medium truncate flex-1 text-center mr-auto"
                        >
                            {#if $currentRoomId}
                                {#each $chatRooms.filter((r) => r.id === $currentRoomId) as room}
                                    {getOtherName(room)}
                                {/each}
                            {/if}
                        </span>

                        <button
                            onclick={() => {
                                isSearchOpen = true;
                                setTimeout(() => searchInput?.focus(), 50);
                            }}
                            class="p-2 hover:bg-white/10 rounded"
                        >
                            <Search class="w-5 h-5" />
                        </button>

                        <DropdownMenu.Root>
                            <DropdownMenu.Trigger
                                class="p-2 hover:bg-white/10 rounded outline-none"
                            >
                                <Settings class="w-5 h-5" />
                            </DropdownMenu.Trigger>
                            <DropdownMenu.Content
                                align="end"
                                class="w-56 z-[70]"
                            >
                                <DropdownMenu.Label
                                    >채팅방 설정</DropdownMenu.Label
                                >
                                <DropdownMenu.Separator />
                                <DropdownMenu.Item>
                                    <Users class="mr-2 h-4 w-4" />
                                    <span>대화상대 초대</span>
                                </DropdownMenu.Item>
                                <DropdownMenu.Item
                                    onclick={() => toggleSetting("is_alarm")}
                                >
                                    {#if currentRoomSettings?.is_alarm === 0}
                                        <BellOff class="mr-2 h-4 w-4" />
                                        <span>알림 켜기</span>
                                    {:else}
                                        <Bell class="mr-2 h-4 w-4" />
                                        <span>알림 끄기</span>
                                    {/if}
                                </DropdownMenu.Item>
                                <DropdownMenu.Item
                                    onclick={() => toggleSetting("is_favorite")}
                                >
                                    <Star
                                        class="mr-2 h-4 w-4 {currentRoomSettings?.is_favorite
                                            ? 'fill-yellow-400 text-yellow-400'
                                            : ''}"
                                    />
                                    <span>즐겨찾기</span>
                                </DropdownMenu.Item>
                                <DropdownMenu.Item
                                    onclick={() => toggleSetting("is_pinned")}
                                >
                                    <Pin
                                        class="mr-2 h-4 w-4 {currentRoomSettings?.is_pinned
                                            ? 'fill-primary text-primary'
                                            : ''}"
                                    />
                                    <span>상단 고정</span>
                                </DropdownMenu.Item>
                                <DropdownMenu.Separator />
                                <DropdownMenu.Item
                                    onclick={() => (isSettingsOpen = true)}
                                >
                                    <Settings class="mr-2 h-4 w-4" />
                                    <span>채팅방 설정</span>
                                </DropdownMenu.Item>
                                <DropdownMenu.Separator />
                                <DropdownMenu.Item
                                    class="text-red-600"
                                    onclick={handleLeaveRoom}
                                >
                                    <LogOut class="mr-2 h-4 w-4" />
                                    <span>채팅방 나가기</span>
                                </DropdownMenu.Item>
                            </DropdownMenu.Content>
                        </DropdownMenu.Root>
                    {/if}
                {:else}
                    <span class="font-medium mr-auto">채팅</span>
                    <button
                        onclick={closeChat}
                        class="p-1 hover:bg-white/10 rounded"
                    >
                        <X class="w-5 h-5" />
                    </button>
                {/if}
            </div>

            <!-- 콘텐츠 -->
            <div
                class="flex-1 overflow-hidden"
                style={currentRoomSettings?.bg_color
                    ? `background-color: ${currentRoomSettings.bg_color}`
                    : ""}
            >
                {#if $currentView === "rooms"}
                    <!-- 채팅방 목록 -->
                    <div class="h-full overflow-y-auto">
                        <!-- 고객센터 바로가기 -->
                        <button
                            onclick={handleStartSupport}
                            class="w-full p-4 flex items-center gap-3 border-b hover:bg-slate-50 transition-colors"
                        >
                            <div
                                class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center"
                            >
                                <Headphones class="w-6 h-6" />
                            </div>
                            <div class="flex-1 text-left">
                                <div class="font-medium">고객센터</div>
                                <div class="text-sm text-slate-500">
                                    문의사항을 남겨주세요
                                </div>
                            </div>
                        </button>

                        {#if $chatRooms.length === 0}
                            <div class="p-8 text-center text-slate-400">
                                <Users
                                    class="w-12 h-12 mx-auto mb-3 opacity-50"
                                />
                                <p>진행 중인 채팅이 없습니다</p>
                            </div>
                        {:else}
                            {#each $chatRooms as room}
                                {@const myUnread =
                                    (room.unreadCounts &&
                                        $memberStore &&
                                        room.unreadCounts[
                                            $memberStore.mb_id
                                        ]) ||
                                    0}
                                <button
                                    onclick={() => handleSelectRoom(room)}
                                    class="w-full p-4 flex items-center gap-3 border-b hover:bg-slate-50 transition-colors"
                                >
                                    <div
                                        class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-lg font-medium text-slate-600"
                                    >
                                        {getOtherName(room).charAt(0)}
                                    </div>
                                    <div class="flex-1 text-left min-w-0">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span class="font-medium truncate"
                                                >{getOtherName(room)}</span
                                            >
                                            <span
                                                class="text-xs text-slate-400 ml-2 shrink-0"
                                            >
                                                {formatTime(room.lastMessageAt)}
                                            </span>
                                        </div>
                                        <div
                                            class="flex items-center justify-between mt-1"
                                        >
                                            <div
                                                class="text-sm text-slate-500 truncate flex-1 block"
                                            >
                                                {room.lastMessage ||
                                                    "대화를 시작해보세요"}
                                            </div>
                                            {#if myUnread > 0}
                                                <span
                                                    class="ml-2 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center shrink-0"
                                                >
                                                    {myUnread > 99
                                                        ? "99+"
                                                        : myUnread}
                                                </span>
                                            {/if}
                                        </div>
                                    </div>
                                </button>
                            {/each}
                        {/if}
                    </div>
                {:else}
                    <!-- 채팅 메시지 -->
                    <div
                        bind:this={messagesContainer}
                        class="h-full overflow-y-auto p-4 space-y-3"
                    >
                        {#if $isLoading}
                            <div class="flex justify-center py-4">
                                <div
                                    class="w-6 h-6 border-2 border-primary border-t-transparent rounded-full animate-spin"
                                ></div>
                            </div>
                        {:else if $currentMessages.length === 0}
                            <div class="text-center text-slate-400 py-8">
                                대화를 시작해보세요!
                            </div>
                        {:else}
                            {#each filteredMessages as message}
                                {@const isMe =
                                    message.senderId === $memberStore?.mb_id}
                                {@const totalParticipants =
                                    activeRoom?.participants.length || 2}
                                {@const readCount = message.readBy
                                    ? message.readBy.length
                                    : 1}
                                {@const unreadCount = Math.max(
                                    0,
                                    totalParticipants - readCount,
                                )}

                                <div
                                    class="flex {isMe
                                        ? 'justify-end'
                                        : 'justify-start'}"
                                >
                                    <div
                                        class="max-w-[75%] flex flex-col {isMe
                                            ? 'items-end'
                                            : 'items-start'}"
                                    >
                                        {#if !isMe}
                                            <div
                                                class="text-xs text-slate-500 mb-1 ml-1"
                                            >
                                                {message.senderName}
                                            </div>
                                        {/if}

                                        <div
                                            class="flex items-end gap-1 {isMe
                                                ? 'flex-row-reverse'
                                                : 'flex-row'}"
                                        >
                                            <!-- 말풍선 -->
                                            <div
                                                class="px-4 py-2 rounded-2xl {isMe
                                                    ? 'bg-primary text-primary-foreground rounded-br-md'
                                                    : 'bg-slate-100 text-slate-800 rounded-bl-md'}"
                                            >
                                                {#if message.type === "template" && message.templateId === "post_link" && message.templateData}
                                                    <PostLinkTemplate
                                                        data={message.templateData}
                                                    />
                                                {:else}
                                                    {@html formatMessage(
                                                        message.content,
                                                    )}
                                                {/if}
                                            </div>

                                            <!-- 메타 정보 -->
                                            <div
                                                class="flex flex-col text-[10px] text-slate-400 leading-none gap-1 min-w-[30px] {isMe
                                                    ? 'items-end'
                                                    : 'items-start'} shrink-0 pb-1"
                                            >
                                                {#if unreadCount > 0}
                                                    <span
                                                        class="text-yellow-500 font-bold"
                                                        >{unreadCount}</span
                                                    >
                                                {/if}
                                                <span>
                                                    {message.createdAt.toLocaleTimeString(
                                                        "ko-KR",
                                                        {
                                                            hour: "2-digit",
                                                            minute: "2-digit",
                                                        },
                                                    )}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {/each}
                        {/if}
                    </div>
                {/if}
            </div>

            <!-- 입력창 (채팅방에서만) -->
            {#if $currentView === "chat"}
                <div class="p-3 border-t bg-white">
                    <div class="flex items-center gap-2">
                        <textarea
                            bind:this={textareaElement}
                            bind:value={messageInput}
                            onkeydown={handleKeydown}
                            oninput={adjustHeight}
                            placeholder="메시지를 입력하세요"
                            rows="1"
                            class="flex-1 px-4 py-2 border rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary/50 resize-none min-h-[40px] max-h-[120px]"
                        ></textarea>
                        <button
                            onclick={handleSendMessage}
                            disabled={!messageInput.trim()}
                            class="w-10 h-10 bg-primary text-primary-foreground rounded-full flex items-center justify-center hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <Send class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            {/if}
        </div>
    {/if}
{/if}
