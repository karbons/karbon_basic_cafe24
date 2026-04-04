<script lang="ts">
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
        Link,
        Facebook,
        Twitter,
        Share2,
        ChevronLeft,
    } from "lucide-svelte";
    import type { BoardConfig, Write } from "$lib/type/board";
    import { apiDelete, apiPost } from "$lib/api";
    import { goto } from "$app/navigation";
    import { memberStore } from "$lib/store";
    import { resolveImageUrl } from "$lib/util/image";
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
        goto(`${base}/bbs/${board.bo_table}/${write.wr_id}/edit`);
    }

    async function handlePasswordSubmit() {
        if (!deletePassword) return;

        if (passwordAction === "delete") {
            await executeDelete(deletePassword);
        } else {
            const targetUrl = `${base}/bbs/${board.bo_table}/${write.wr_id}/edit?wr_password=${encodeURIComponent(deletePassword)}`;
            goto(targetUrl);
        }
    }

    async function executeDelete(password?: string) {
        try {
            isDeleting = true;
            let url = `${base}/bbs/${board.bo_table}/delete?wr_id=${write.wr_id}`;
            if (password) {
                url += `&wr_password=${encodeURIComponent(password)}`;
            }
            await apiDelete(url);
            alert("삭제되었습니다.");
            goto(`${base}/bbs/${board.bo_table}`);
        } catch (e: any) {
            alert(e.message || "삭제 중 오류가 발생했습니다.");
        } finally {
            isDeleting = false;
            showPasswordModal = false;
            deletePassword = "";
        }
    }

    function formatDate(dateStr: string): string {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toISOString().split('T')[0];
    }
</script>

<Dialog.Root bind:open={showPasswordModal}>
    <Dialog.Content>
        <Dialog.Header>
            <Dialog.Title>비밀번호 확인</Dialog.Title>
            <Dialog.Description>
                {passwordAction === "delete" ? "게시글 삭제" : "게시글 수정"}를 위해 비밀번호를 입력해주세요.
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
                    onkeydown={(e) => {
                        if (e.key === "Enter") handlePasswordSubmit();
                    }}
                />
            </div>
        </div>
        <Dialog.Footer>
            <Button variant="outline" onclick={() => (showPasswordModal = false)}>취소</Button>
            <Button
                variant={passwordAction === "delete" ? "destructive" : "default"}
                onclick={handlePasswordSubmit}
                disabled={isDeleting || !deletePassword}
            >
                {#if isDeleting}처리중...{:else}확인{/if}
            </Button>
        </Dialog.Footer>
    </Dialog.Content>
</Dialog.Root>

<div class="space-y-8">
    <!-- 게시글 카드 -->
    <Card.Root class="overflow-hidden border-secondary-100">
        <Card.Header class="bg-secondary-50 border-b border-secondary-100">
            <div class="flex items-center gap-4 mb-4">
                <Button variant="ghost" size="sm" href="{base}/bbs/{board.bo_table}" class="-ml-2">
                    <ChevronLeft class="w-4 h-4 mr-1" />
                    목록
                </Button>
                {#if board.bo_use_category && write.ca_name}
                    <Badge variant="secondary">{write.ca_name}</Badge>
                {/if}
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-secondary-950 leading-tight">
                {write.wr_subject}
            </h1>

            <div class="flex flex-wrap items-center gap-6 mt-6 text-sm text-secondary-500">
                <div class="flex items-center gap-2">
                    <User class="w-4 h-4" />
                    <span class="font-medium text-secondary-700">{write.wr_name}</span>
                </div>
                <div class="flex items-center gap-1">
                    <Clock class="w-4 h-4" />
                    <span>{formatDate(write.wr_datetime)}</span>
                </div>
                <div class="flex items-center gap-1">
                    <Eye class="w-4 h-4" />
                    <span>{write.wr_hit}</span>
                </div>
            </div>
        </Card.Header>

        <Card.Content class="pt-8">
            <!-- 마크다운 또는 HTML 컨텐츠 -->
            <div class="prose dark:prose-invert max-w-none [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:mt-6 [&_h1]:mb-4 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:mt-5 [&_h2]:mb-3 [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mt-4 [&_h3]:mb-2 [&_h4]:text-lg [&_h4]:font-semibold [&_h4]:mt-3 [&_h4]:mb-2 [&_p]:my-4 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_blockquote]:border-l-4 [&_blockquote]:border-secondary-200 [&_blockquote]:pl-4 [&_blockquote]:italic [&_code]:bg-secondary-100 [&_code]:px-1 [&_code]:rounded [&_pre]:bg-secondary-100 [&_pre]:p-4 [&_pre]:rounded-lg [&_pre]:overflow-x-auto [&_table]:w-full [&_table]:border-collapse [&_table]:my-4 [&_th]:border [&_th]:border-secondary-200 [&_th]:bg-secondary-50 [&_th]:px-4 [&_th]:py-2 [&_th]:text-left [&_th]:font-semibold [&_td]:border [&_td]:border-secondary-200 [&_td]:px-4 [&_td]:py-2">
                {@html write.wr_content?.startsWith("#") ||
                write.wr_content?.startsWith("-") ||
                write.wr_content?.startsWith("*") ||
                write.wr_content?.includes("\n#") ||
                write.wr_content?.includes("\n-") ||
                !write.wr_content?.includes("<")
                    ? marked(write.wr_content || "")
                    : write.wr_content}
            </div>

            <!-- 파일 첨부 -->
            {#if write.files && write.files.length > 0}
                {@const attachmentFiles = write.files.filter(
                    (f: any) =>
                        f.bf_type !== 1 &&
                        !/\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(f.bf_file),
                )}
                {#if attachmentFiles.length > 0}
                    <div class="mt-8 bg-secondary-50 p-6 rounded-2xl border border-secondary-100">
                        <h4 class="font-bold mb-4 flex items-center gap-2 text-secondary-900">
                            <FolderOpen class="w-4 h-4" />
                            첨부파일
                        </h4>
                        <ul class="space-y-3">
                            {#each attachmentFiles as file}
                                <li class="flex items-center gap-3 text-secondary-700">
                                    <FileText class="w-4 h-4 text-secondary-400" />
                                    <a
                                        href={file.download_url}
                                        class="hover:underline hover:text-primary-600 transition-colors"
                                        target="_blank"
                                        download={file.bf_source}
                                    >
                                        {file.bf_source}
                                    </a>
                                    <span class="text-xs text-secondary-400">
                                        ({(file.bf_filesize / 1024).toFixed(1)} KB)
                                    </span>
                                </li>
                            {/each}
                        </ul>
                    </div>
                {/if}
            {/if}
        </Card.Content>

        <Card.Footer class="flex flex-col gap-4 pt-6 border-t border-secondary-100">
            <!-- SNS 공유 -->
            {#if board.bo_use_sns}
                <div class="flex items-center gap-3 pb-4 border-b border-secondary-100">
                    <span class="text-sm text-secondary-500 font-medium mr-2">공유:</span>
                    <Button variant="outline" size="icon" onclick={() => shareSNS("facebook")} title="Facebook">
                        <Facebook class="w-4 h-4" />
                    </Button>
                    <Button variant="outline" size="icon" onclick={() => shareSNS("twitter")} title="Twitter">
                        <Twitter class="w-4 h-4" />
                    </Button>
                    <Button variant="outline" size="icon" onclick={copyLink} title="링크 복사">
                        <Link class="w-4 h-4" />
                    </Button>
                    
                    <div class="ml-auto">
                        <Button variant="outline" size="sm" onclick={toggleScrap} disabled={isScrapLoading}>
                            {#if scraped}
                                <BookmarkCheck class="w-4 h-4 mr-1 text-yellow-500" />
                                스크랩됨
                            {:else}
                                <BookmarkPlus class="w-4 h-4 mr-1" />
                                스크랩
                            {/if}
                        </Button>
                    </div>
                </div>
            {/if}

            <!-- 하단 버튼 -->
            <div class="flex justify-between w-full pt-2">
                <div class="flex gap-2">
                    <Button variant="outline" href="{base}/bbs/{board.bo_table}">
                        <List class="w-4 h-4 mr-0 md:mr-2" />
                        <span class="hidden md:inline">목록</span>
                    </Button>
                    {#if can_reply}
                        <Button variant="outline" href="{base}/bbs/{board.bo_table}/write?reply={write.wr_id}">
                            <Reply class="w-4 h-4 mr-0 md:mr-2" />
                            <span class="hidden md:inline">답변</span>
                        </Button>
                    {/if}
                </div>

                <div class="flex gap-2">
                    {#if can_delete}
                        <Button variant="ghost" onclick={handleDelete} class="text-red-500 hover:text-red-600 hover:bg-red-50">
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
</div>