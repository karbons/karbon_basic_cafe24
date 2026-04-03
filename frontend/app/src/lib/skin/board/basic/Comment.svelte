<script lang="ts">
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Textarea } from "$lib/ui/textarea";
    import { Checkbox } from "$lib/ui/checkbox";
    import { Label } from "$lib/ui/label";
    import * as Dialog from "$lib/ui/dialog";
    import { apiGet, apiPost, apiPut, apiDelete } from "$lib/api";
    import { memberStore } from "$lib/store";
    import { onMount } from "svelte";
    import { Lock, Reply, Edit2, Trash2, Send, User } from "lucide-svelte";

    interface Props {
        bo_table: string;
        wr_id: number;
    }

    let { bo_table, wr_id }: Props = $props();

    interface Comment {
        wr_id: number;
        wr_parent: number;
        mb_id: string;
        wr_name: string;
        wr_content: string;
        wr_datetime: string;
        wr_option: string;
        wr_comment: number;
        wr_comment_reply: string;
        is_secret: boolean;
        can_view: boolean;
        can_edit: boolean;
        can_delete: boolean;
    }

    let comments = $state<Comment[]>([]);
    let loading = $state(true);

    // 새 댓글 작성
    let newComment = $state("");
    let newCommentName = $state("");
    let newCommentPassword = $state("");
    let newCommentSecret = $state(false);
    let isSubmitting = $state(false);

    // 답글 작성
    let replyToId = $state<number | null>(null);
    let replyContent = $state("");
    let replyName = $state("");
    let replyPassword = $state("");
    let replySecret = $state(false);

    // 수정
    let editingId = $state<number | null>(null);
    let editContent = $state("");
    let editPassword = $state("");
    let editSecret = $state(false);

    // 삭제 확인
    let showDeleteModal = $state(false);
    let deleteTargetId = $state<number | null>(null);
    let deletePassword = $state("");

    async function loadComments() {
        try {
            loading = true;
            const data = await apiGet<{ comments: Comment[] }>(
                `/bbs/${bo_table}/comment?wr_id=${wr_id}`,
            );
            comments = data.comments || [];
        } catch (e) {
            console.error("댓글 로드 실패:", e);
        } finally {
            loading = false;
        }
    }

    async function submitComment() {
        if (!newComment.trim()) return;

        if (
            !$memberStore &&
            (!newCommentName.trim() || !newCommentPassword.trim())
        ) {
            alert("이름과 비밀번호를 입력해주세요.");
            return;
        }

        try {
            isSubmitting = true;
            await apiPost(`/bbs/${bo_table}/comment`, {
                wr_parent: wr_id,
                wr_content: newComment,
                wr_name: newCommentName,
                wr_password: newCommentPassword,
                wr_secret: newCommentSecret,
            });
            newComment = "";
            newCommentName = "";
            newCommentPassword = "";
            newCommentSecret = false;
            await loadComments();
        } catch (e: any) {
            alert(e.message || "댓글 작성에 실패했습니다.");
        } finally {
            isSubmitting = false;
        }
    }

    async function submitReply() {
        if (!replyContent.trim() || !replyToId) return;

        if (!$memberStore && (!replyName.trim() || !replyPassword.trim())) {
            alert("이름과 비밀번호를 입력해주세요.");
            return;
        }

        try {
            isSubmitting = true;
            await apiPost(`/bbs/${bo_table}/comment`, {
                wr_parent: wr_id,
                comment_id: replyToId,
                wr_content: replyContent,
                wr_name: replyName,
                wr_password: replyPassword,
                wr_secret: replySecret,
            });
            replyToId = null;
            replyContent = "";
            replyName = "";
            replyPassword = "";
            replySecret = false;
            await loadComments();
        } catch (e: any) {
            alert(e.message || "답글 작성에 실패했습니다.");
        } finally {
            isSubmitting = false;
        }
    }

    async function updateComment() {
        if (!editContent.trim() || !editingId) return;

        try {
            isSubmitting = true;
            await apiPut(`/bbs/${bo_table}/comment?comment_id=${editingId}`, {
                wr_content: editContent,
                wr_password: editPassword,
                wr_secret: editSecret,
            });
            editingId = null;
            editContent = "";
            editPassword = "";
            editSecret = false;
            await loadComments();
        } catch (e: any) {
            alert(e.message || "댓글 수정에 실패했습니다.");
        } finally {
            isSubmitting = false;
        }
    }

    async function deleteComment() {
        if (!deleteTargetId) return;

        try {
            isSubmitting = true;
            let url = `/bbs/${bo_table}/comment?comment_id=${deleteTargetId}`;
            if (deletePassword) {
                url += `&wr_password=${encodeURIComponent(deletePassword)}`;
            }
            await apiDelete(url);
            showDeleteModal = false;
            deleteTargetId = null;
            deletePassword = "";
            await loadComments();
        } catch (e: any) {
            alert(e.message || "댓글 삭제에 실패했습니다.");
        } finally {
            isSubmitting = false;
        }
    }

    function startReply(commentId: number) {
        replyToId = commentId;
        editingId = null;
    }

    function startEdit(comment: Comment) {
        editingId = comment.wr_id;
        editContent = comment.wr_content;
        editSecret = comment.is_secret;
        replyToId = null;
    }

    function confirmDelete(comment: Comment) {
        deleteTargetId = comment.wr_id;
        if (!comment.mb_id) {
            showDeleteModal = true;
        } else {
            if (confirm("댓글을 삭제하시겠습니까?")) {
                deleteComment();
            }
        }
    }

    function getIndentLevel(comment: Comment): number {
        return comment.wr_comment_reply ? comment.wr_comment_reply.length : 0;
    }

    onMount(() => {
        loadComments();
    });
</script>

<div class="space-y-6">
    <h3 class="font-bold text-lg">댓글 ({comments.length})</h3>

    <!-- 댓글 목록 -->
    <div class="space-y-4">
        {#if loading}
            <p class="text-gray-500 text-center py-4">로딩 중...</p>
        {:else if comments.length === 0}
            <p class="text-gray-500 text-center py-4">댓글이 없습니다.</p>
        {:else}
            {#each comments as comment (comment.wr_id)}
                <div
                    class="border rounded-lg p-4 bg-white"
                    style="margin-left: {getIndentLevel(comment) * 24}px"
                >
                    {#if editingId === comment.wr_id}
                        <!-- 수정 폼 -->
                        <div class="space-y-3">
                            <Textarea
                                bind:value={editContent}
                                placeholder="댓글 수정"
                                rows={3}
                            />
                            {#if !comment.mb_id}
                                <Input
                                    type="password"
                                    bind:value={editPassword}
                                    placeholder="비밀번호"
                                />
                            {/if}
                            <div class="flex items-center gap-2">
                                <Checkbox
                                    id="edit-secret"
                                    bind:checked={editSecret}
                                />
                                <Label for="edit-secret" class="text-sm"
                                    >비밀글</Label
                                >
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    onclick={updateComment}
                                    disabled={isSubmitting}
                                >
                                    저장
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onclick={() => (editingId = null)}
                                >
                                    취소
                                </Button>
                            </div>
                        </div>
                    {:else}
                        <!-- 댓글 표시 -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <User class="w-4 h-4 text-gray-400" />
                                    <span class="font-medium text-sm"
                                        >{comment.wr_name}</span
                                    >
                                    <span class="text-xs text-gray-400"
                                        >{comment.wr_datetime}</span
                                    >
                                    {#if comment.is_secret}
                                        <Lock class="w-3 h-3 text-orange-500" />
                                    {/if}
                                </div>
                                <p
                                    class="text-gray-700 text-sm whitespace-pre-wrap {comment.is_secret &&
                                    !comment.can_view
                                        ? 'text-gray-400 italic'
                                        : ''}"
                                >
                                    {comment.wr_content}
                                </p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    class="h-8 w-8 p-0"
                                    onclick={() => startReply(comment.wr_id)}
                                >
                                    <Reply class="w-4 h-4" />
                                </Button>
                                {#if comment.can_edit}
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="h-8 w-8 p-0"
                                        onclick={() => startEdit(comment)}
                                    >
                                        <Edit2 class="w-4 h-4" />
                                    </Button>
                                {/if}
                                {#if comment.can_delete}
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        class="h-8 w-8 p-0 text-red-500 hover:text-red-600"
                                        onclick={() => confirmDelete(comment)}
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                {/if}
                            </div>
                        </div>

                        <!-- 답글 폼 -->
                        {#if replyToId === comment.wr_id}
                            <div class="mt-4 pt-4 border-t space-y-3">
                                <Textarea
                                    bind:value={replyContent}
                                    placeholder="답글을 입력하세요"
                                    rows={2}
                                />
                                {#if !$memberStore}
                                    <div class="flex gap-2">
                                        <Input
                                            bind:value={replyName}
                                            placeholder="이름"
                                        />
                                        <Input
                                            type="password"
                                            bind:value={replyPassword}
                                            placeholder="비밀번호"
                                        />
                                    </div>
                                {/if}
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <Checkbox
                                            id="reply-secret"
                                            bind:checked={replySecret}
                                        />
                                        <Label
                                            for="reply-secret"
                                            class="text-sm">비밀글</Label
                                        >
                                    </div>
                                    <div class="flex gap-2">
                                        <Button
                                            size="sm"
                                            onclick={submitReply}
                                            disabled={isSubmitting}
                                        >
                                            답글 등록
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onclick={() => (replyToId = null)}
                                        >
                                            취소
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        {/if}
                    {/if}
                </div>
            {/each}
        {/if}
    </div>

    <!-- 새 댓글 작성 폼 -->
    <div class="border rounded-lg p-4 bg-gray-50 space-y-3">
        <Textarea
            bind:value={newComment}
            placeholder="댓글을 입력하세요"
            rows={3}
        />
        {#if !$memberStore}
            <div class="flex gap-2">
                <Input bind:value={newCommentName} placeholder="이름" />
                <Input
                    type="password"
                    bind:value={newCommentPassword}
                    placeholder="비밀번호"
                />
            </div>
        {/if}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Checkbox id="new-secret" bind:checked={newCommentSecret} />
                <Label for="new-secret" class="text-sm">비밀글</Label>
            </div>
            <Button
                onclick={submitComment}
                disabled={isSubmitting || !newComment.trim()}
            >
                <Send class="w-4 h-4 mr-2" />
                댓글 등록
            </Button>
        </div>
    </div>
</div>

<!-- 비회원 삭제 비밀번호 모달 -->
<Dialog.Root bind:open={showDeleteModal}>
    <Dialog.Content>
        <Dialog.Header>
            <Dialog.Title>비밀번호 확인</Dialog.Title>
            <Dialog.Description>
                댓글 삭제를 위해 비밀번호를 입력해주세요.
            </Dialog.Description>
        </Dialog.Header>
        <div class="py-4">
            <Input
                type="password"
                bind:value={deletePassword}
                placeholder="비밀번호"
            />
        </div>
        <Dialog.Footer>
            <Button variant="outline" onclick={() => (showDeleteModal = false)}
                >취소</Button
            >
            <Button
                variant="destructive"
                onclick={deleteComment}
                disabled={isSubmitting}
            >
                삭제
            </Button>
        </Dialog.Footer>
    </Dialog.Content>
</Dialog.Root>
