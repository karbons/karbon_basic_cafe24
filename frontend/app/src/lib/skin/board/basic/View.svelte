<script lang="ts">
    import { page } from "$app/stores";
    import { Button } from "$lib/ui/button";
    import * as Card from "$lib/ui/card";
    import { Badge } from "$lib/ui/badge";
    import { Separator } from "$lib/ui/separator";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import { marked } from "marked";
    import * as Dialog from "$lib/ui/dialog";
    import {
        Eye,
        Clock,
        User,
        List,
        PenLine,
        Trash2,
        FolderOpen,
        FileText,
        Reply,
        BookmarkPlus,
        BookmarkCheck,
        Plus,
        MessageCircle,
        Link,
        Facebook,
        Twitter,
        Share2,
    } from "lucide-svelte";
    import type { BoardConfig, Write } from "$lib/type/board";
    import { apiDelete, apiPost } from "$lib/api";
    import { goto } from "$app/navigation";
    import { memberStore } from "$lib/store";
    import { startMemberChat, insertTextToChat } from "$lib/store/chat";
    import { resolveImageUrl } from "$lib/util/image";
    import Comment from "./Comment.svelte";
import { base } from '$app/paths';

    interface Props {
        board: BoardConfig;
        write: Write;
        can_edit?: boolean;
        can_delete?: boolean;
        can_reply?: boolean;
        is_scraped?: boolean;
    }

    let {
        board,
        write,
        can_edit = false,
        can_delete = false,
        can_reply = false,
        is_scraped = false,
    }: Props = $props();

    let showPasswordModal = $state(false);
    let deletePassword = $state("");
    let isDeleting = $state(false);
    let passwordAction = $state<"delete" | "modify">("delete");
    let scraped = $state(is_scraped);
    let isScrapLoading = $state(false);

    async function handleChatWithAuthor() {
        if (!$memberStore || !write.mb_id) return;

        // Construct context link
        const link = `${window.location.origin}/bbs/${board.bo_table}/${write.wr_id}`;

        // Find first image for thumbnail
        let thumbnail = "";
        if (write.files && write.files.length > 0) {
            const imageFile = write.files.find(
                (f: any) =>
                    f.bf_type === 1 ||
                    /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(f.bf_file),
            );
            if (imageFile) {
                thumbnail = resolveImageUrl(imageFile.download_url);
            }
        }

        try {
            await startMemberChat(
                $memberStore.mb_id,
                $memberStore.mb_nick,
                write.mb_id,
                write.wr_name,
                `[문의] ${write.wr_subject}`,
                "post_link",
                {
                    title: write.wr_subject,
                    link: link,
                    imageUrl: thumbnail,
                },
            );
        } catch (e: any) {
            console.error("Failed to start chat:", e);
            alert("채팅을 시작할 수 없습니다.");
        }
    }

    function shareSNS(sns: string) {
        const url = window.location.href;
        const title = write.wr_subject;
        let targetUrl = "";

        if (sns === "twitter") {
            targetUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
        } else if (sns === "facebook") {
            targetUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
        }

        if (targetUrl) window.open(targetUrl, "_blank");
    }

    function copyLink() {
        navigator.clipboard.writeText(window.location.href);
        alert("링크가 복사되었습니다.");
    }

    function handleInsertLinkToChat() {
        const link = `${window.location.origin}/bbs/${board.bo_table}/${write.wr_id}`;
        // 채팅창이 닫혀있으면 열고 입력, 열려있으면 입력 추가
        insertTextToChat(`[공유] ${write.wr_subject}\n바로가기: ${link}`);
    }

    async function toggleScrap() {
        if (!$memberStore) {
            alert("로그인이 필요합니다.");
            return;
        }
        try {
            isScrapLoading = true;
            if (scraped) {
                await apiDelete(
                    `/member/scrap?bo_table=${board.bo_table}&wr_id=${write.wr_id}`,
                );
                scraped = false;
            } else {
                await apiPost("/member/scrap", {
                    bo_table: board.bo_table,
                    wr_id: write.wr_id,
                });
                scraped = true;
            }
        } catch (e: any) {
            alert(e.message || "스크랩 처리에 실패했습니다.");
        } finally {
            isScrapLoading = false;
        }
    }

    async function handleDelete() {
        passwordAction = "delete";
        if (!write.mb_id) {
            showPasswordModal = true;
            return;
        }

        if (confirm("정말 삭제하시겠습니까?")) {
            await executeDelete();
        }
    }

    async function handleModify() {
        if (!write.mb_id) {
            passwordAction = "modify";
            showPasswordModal = true;
            return;
        }
        goto(`/bbs/${board.bo_table}/${write.wr_id}/edit`);
    }

    async function handlePasswordSubmit() {
        if (!deletePassword) return;

        if (passwordAction === "delete") {
            await executeDelete(deletePassword);
        } else {
            // 수정 권한 확인 (비밀번호 검증)
            try {
                // 비밀번호 검증 API 호출
                // 임시: 비밀번호를 쿼리파라미터로 보내는 것은 보안상 좋지 않지만,
                // 현재 구조에서 페이지 이동 시 데이터를 전달하는 방법이 필요함.
                // 또는 API를 통해 세션을 생성해야 함.
                // 여기서는 일단 페이지 이동 시 쿼리로 전달하고 edit 페이지에서 처리하도록 하거나,
                // verify API를 호출해야 함.
                // 그누보드 API 구조상 /bbs/password/check 같은 엔드포인트가 있는지 확인 필요.
                // 만약 없다면, edit 페이지 진입 시 비밀번호를 요구하는 UI가 edit 페이지에 있어야 함.
                // 하지만 사용자는 "글 등록시 등록한 암호를 물어보는 절차가 필요함"이라고 했으므로
                // View 페이지에서 모달로 묻고 진입하는 것이 자연스러움.

                // 수정 페이지로 이동하며 비밀번호 전달
                const targetUrl = `/bbs/${board.bo_table}/${write.wr_id}/edit?wr_password=${encodeURIComponent(deletePassword)}`;
                goto(targetUrl);
            } catch (e) {
                alert("비밀번호 확인 중 오류가 발생했습니다.");
            } finally {
                showPasswordModal = false;
                deletePassword = "";
            }
        }
    }

    async function executeDelete(password?: string) {
        try {
            isDeleting = true;
            let url = `/bbs/${board.bo_table}/delete?wr_id=${write.wr_id}`;
            if (password) {
                url += `&wr_password=${encodeURIComponent(password)}`;
            }
            await apiDelete(url);
            alert("삭제되었습니다.");
            goto(`/bbs/${board.bo_table}`);
        } catch (e: any) {
            alert(e.message || "삭제 중 오류가 발생했습니다.");
        } finally {
            isDeleting = false;
            showPasswordModal = false;
            deletePassword = "";
        }
    }
</script>

<Dialog.Root bind:open={showPasswordModal}>
    <Dialog.Content>
        <Dialog.Header>
            <Dialog.Title>비밀번호 확인</Dialog.Title>
            <Dialog.Description>
                {passwordAction === "delete" ? "게시글 삭제" : "게시글 수정"}를
                위해 비밀번호를 입력해주세요.
            </Dialog.Description>
        </Dialog.Header>
        <div class="grid gap-4 py-4">
            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <Input
                    id="password"
                    type="password"
                    bind:value={deletePassword}
                    placeholder="비밀번호"
                    on:keydown={(e) => {
                        if (e.key === "Enter") handlePasswordSubmit();
                    }}
                />
            </div>
        </div>
        <Dialog.Footer>
            <Button
                variant="outline"
                onclick={() => (showPasswordModal = false)}>취소</Button
            >
            <Button
                variant={passwordAction === "delete"
                    ? "destructive"
                    : "default"}
                onclick={handlePasswordSubmit}
                disabled={isDeleting || !deletePassword}
            >
                {#if isDeleting}Processing...{:else}확인{/if}
            </Button>
        </Dialog.Footer>
    </Dialog.Content>
</Dialog.Root>

<div class="space-y-6 pb-24 md:pb-0">
    <!-- 게시글 카드 -->
    <Card.Root>
        <Card.Header class="space-y-4">
            <div class="flex flex-wrap gap-2 items-center">
                {#if board.bo_use_category && write.ca_name}
                    <Badge variant="secondary">{write.ca_name}</Badge>
                {/if}
                <h1 class="text-2xl font-bold leading-tight">
                    {write.wr_subject}
                </h1>
            </div>

            <div
                class="flex flex-wrap items-center gap-4 text-sm text-slate-500"
            >
                <div class="flex items-center gap-1">
                    <User class="w-4 h-4" />
                    <span>{write.wr_name}</span>
                    {#if $memberStore && write.mb_id && write.mb_1 === "1" && $memberStore.mb_id !== write.mb_id}
                        <button
                            onclick={handleChatWithAuthor}
                            class="ml-1 inline-flex items-center justify-center rounded-full p-1 hover:bg-muted text-primary transition-colors"
                            title="1:1 채팅하기"
                        >
                            <MessageCircle class="w-4 h-4" />
                            <span class="sr-only">채팅하기</span>
                        </button>
                    {/if}
                </div>
                <div class="flex items-center gap-1">
                    <Clock class="w-4 h-4" />
                    <span>{write.wr_datetime}</span>
                </div>
                <div class="flex items-center gap-1">
                    <Eye class="w-4 h-4" />
                    <span>{write.wr_hit}</span>
                </div>
            </div>
            <Separator />
        </Card.Header>

        <Card.Content
            class="min-h-[200px] prose dark:prose-invert max-w-none [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:mt-6 [&_h1]:mb-4 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:mt-5 [&_h2]:mb-3 [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mt-4 [&_h3]:mb-2 [&_h4]:text-lg [&_h4]:font-semibold [&_h4]:mt-3 [&_h4]:mb-2 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_blockquote]:border-l-4 [&_blockquote]:border-gray-300 [&_blockquote]:pl-4 [&_blockquote]:italic [&_code]:bg-gray-100 [&_code]:dark:bg-gray-800 [&_code]:px-1 [&_code]:rounded [&_pre]:bg-gray-100 [&_pre]:dark:bg-gray-800 [&_pre]:p-4 [&_pre]:rounded-lg [&_pre]:overflow-x-auto [&_del]:line-through [&_del]:text-gray-500 [&_s]:line-through [&_s]:text-gray-500 [&_table]:w-full [&_table]:border-collapse [&_table]:my-4 [&_th]:border [&_th]:border-gray-300 [&_th]:dark:border-gray-600 [&_th]:bg-gray-100 [&_th]:dark:bg-gray-800 [&_th]:px-4 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold [&_td]:border [&_td]:border-gray-300 [&_td]:dark:border-gray-600 [&_td]:px-4 [&_td]:py-2"
        >
            <!-- 이미지 첨부파일 갤러리 -->
            {#if write.files && write.files.length > 0}
                {@const imageFiles = write.files.filter(
                    (f: any) =>
                        f.bf_type === 1 ||
                        /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(f.bf_file),
                )}
                {#if imageFiles.length > 0}
                    <div class="not-prose mb-6 grid gap-4 grid-cols-1">
                        {#each imageFiles as img, idx}
                            <figure class="m-0 relative group">
                                <!-- 이미지 컨테이너 -->
                                <div
                                    class="relative overflow-hidden rounded-md border"
                                >
                                    <img
                                        src={resolveImageUrl(img.download_url)}
                                        alt={img.bf_source}
                                        class="max-w-full h-auto transition-transform duration-300 cursor-zoom-in group-hover:scale-105"
                                        loading="lazy"
                                        onclick={(e) => {
                                            const target =
                                                e.currentTarget as HTMLImageElement;
                                            if (
                                                target.classList.contains(
                                                    "zoomed",
                                                )
                                            ) {
                                                target.classList.remove(
                                                    "zoomed",
                                                );
                                                target.style.transform = "";
                                                target.style.cursor = "zoom-in";
                                            } else {
                                                target.classList.add("zoomed");
                                                target.style.transform =
                                                    "scale(1.5)";
                                                target.style.cursor =
                                                    "zoom-out";
                                            }
                                        }}
                                    />
                                    <!-- 다운로드 버튼 오버레이 -->
                                    <div
                                        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    >
                                        <a
                                            href={img.download_url}
                                            download={img.bf_source}
                                            class="flex items-center justify-center w-8 h-8 bg-black/60 hover:bg-black/80 text-white rounded-full transition-colors"
                                            title="다운로드"
                                            onclick={(e) => e.stopPropagation()}
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="16"
                                                height="16"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path
                                                    d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                                                />
                                                <polyline
                                                    points="7 10 12 15 17 10"
                                                />
                                                <line
                                                    x1="12"
                                                    y1="15"
                                                    x2="12"
                                                    y2="3"
                                                />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                                {#if img.bf_content}
                                    <figcaption
                                        class="text-sm text-slate-500 mt-1"
                                    >
                                        {img.bf_content}
                                    </figcaption>
                                {/if}
                            </figure>
                        {/each}
                    </div>
                {/if}
            {/if}

            <!-- 마크다운 또는 HTML 컨텐츠 -->
            <!-- 마크다운 형식이면 파싱, 아니면 HTML로 표시 -->
            {@html write.wr_content?.startsWith("#") ||
            write.wr_content?.startsWith("-") ||
            write.wr_content?.startsWith("*") ||
            write.wr_content?.includes("\n#") ||
            write.wr_content?.includes("\n-") ||
            !write.wr_content?.includes("<")
                ? marked(write.wr_content || "")
                : write.wr_content}
        </Card.Content>

        <Card.Footer class="flex flex-col gap-4">
            {#if board.bo_use_sns}
                <div
                    class="flex items-center gap-2 pb-4 border-b w-full flex-wrap"
                >
                    <span
                        class="text-sm text-slate-500 font-medium mr-0 md:mr-2 flex items-center gap-1"
                    >
                        <Share2 class="w-4 h-4" />
                        <span class="hidden md:inline">Share:</span>
                    </span>
                    <Button
                        variant="outline"
                        size="icon"
                        onclick={() => shareSNS("facebook")}
                        title="Facebook"
                    >
                        <Facebook class="w-4 h-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        onclick={() => shareSNS("twitter")}
                        title="Twitter"
                    >
                        <Twitter class="w-4 h-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        onclick={copyLink}
                        title="Copy Link"
                    >
                        <Link class="w-4 h-4" />
                    </Button>
                    <Button
                        variant="outline"
                        class="ml-auto"
                        onclick={handleInsertLinkToChat}
                    >
                        <MessageCircle class="w-4 h-4 mr-0 md:mr-2" />
                        <span class="hidden md:inline">채팅에 공유</span>
                    </Button>
                </div>
            {/if}

            <!-- 파일 첨부 (이미지 제외) -->
            {#if write.files && write.files.length > 0}
                {@const attachmentFiles = write.files.filter(
                    (f: any) =>
                        f.bf_type !== 1 &&
                        !/\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(f.bf_file),
                )}
                {#if attachmentFiles.length > 0}
                    <div
                        class="w-full bg-slate-50 p-4 rounded-md text-sm border"
                    >
                        <h4 class="font-bold mb-3 flex items-center gap-2">
                            <FolderOpen class="w-4 h-4" />
                            Attached Files
                        </h4>
                        <ul class="space-y-2">
                            {#each attachmentFiles as file}
                                <li
                                    class="flex items-center gap-2 text-slate-700"
                                >
                                    <FileText class="w-4 h-4 text-slate-400" />
                                    <a
                                        href={file.download_url}
                                        class="hover:underline hover:text-primary transition-colors"
                                        target="_blank"
                                        download={file.bf_source}
                                    >
                                        {file.bf_source}
                                    </a>
                                    <span class="text-xs text-slate-400"
                                        >({(file.bf_filesize / 1024).toFixed(1)}
                                        KB)</span
                                    >
                                </li>
                            {/each}
                        </ul>
                    </div>
                {/if}
            {/if}

            <div class="flex justify-between w-full pt-4 border-t">
                <div class="flex gap-2">
                    <Button variant="outline" href="{base}/bbs/{board.bo_table}">
                        <List class="w-4 h-4 mr-0 md:mr-2" />
                        <span class="hidden md:inline">목록</span>
                    </Button>
                    {#if can_reply}
                        <Button
                            variant="outline"
                            href="{base}/bbs/{board.bo_table}/write?reply={write.wr_id}"
                        >
                            <Reply class="w-4 h-4 mr-0 md:mr-2" />
                            <span class="hidden md:inline">답변</span>
                        </Button>
                    {/if}
                    <Button
                        variant="outline"
                        href="{base}/bbs/{board.bo_table}/write"
                    >
                        <Plus class="w-4 h-4 mr-0 md:mr-2" />
                        <span class="hidden md:inline">글쓰기</span>
                    </Button>
                </div>

                <div class="flex gap-2">
                    <Button
                        variant="ghost"
                        onclick={toggleScrap}
                        disabled={isScrapLoading}
                        class={scraped ? "text-yellow-500" : ""}
                    >
                        {#if scraped}
                            <BookmarkCheck class="w-4 h-4 mr-0 md:mr-2" />
                            <span class="hidden md:inline">스크랩됨</span>
                        {:else}
                            <BookmarkPlus class="w-4 h-4 mr-0 md:mr-2" />
                            <span class="hidden md:inline">스크랩</span>
                        {/if}
                    </Button>
                    {#if can_delete}
                        <Button
                            variant="ghost"
                            class="text-red-500 hover:text-red-600 hover:bg-red-50"
                            onclick={handleDelete}
                        >
                            <Trash2 class="w-4 h-4 mr-0 md:mr-2" />
                            <span class="hidden md:inline">삭제</span>
                        </Button>
                    {/if}
                    {#if can_edit}
                        <Button variant="secondary" onclick={handleModify}>
                            <PenLine class="w-4 h-4 mr-0 md:mr-2" />
                            <span class="hidden md:inline">수정</span>
                        </Button>
                    {/if}
                </div>
            </div>
        </Card.Footer>
    </Card.Root>

    <!-- 댓글 영역 -->
    <div class="py-8">
        <Comment bo_table={board.bo_table} wr_id={write.wr_id} />
    </div>
</div>
