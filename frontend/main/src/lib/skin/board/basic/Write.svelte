<script lang="ts">
    import { page } from "$app/stores";
    import { Button } from "$lib/ui/button";
    import { Input } from "$lib/ui/input";
    import { Label } from "$lib/ui/label";
    import MarkdownEditor from "$lib/plugin/editor/markdown/MarkdownEditor.svelte";
    import { Checkbox } from "$lib/ui/checkbox";
    import * as Card from "$lib/ui/card";
    import { Save, X, Plus, Minus } from "lucide-svelte";
    import type { BoardConfig, Write } from "$lib/type/board";
    import { memberStore } from "$lib/store";
    import Captcha from "$lib/util/Captcha.svelte";

    interface Props {
        board: BoardConfig;
        write?: Partial<Write>;
    }

    let {
        board,
        write = {
            wr_subject: "",
            wr_content: "",
            wr_name: "",
            wr_password: "",
            wr_email: "",
            wr_homepage: "",
            wr_link1: "",
            wr_link2: "",
        },
    }: Props = $props();

    let isSubmitting = $state(false);

    // Captcha State
    let captcha_key = $state("");
    let captchaRef: Captcha | null = $state(null);

    import { apiPost, apiPut } from "$lib/api";
    import { goto } from "$app/navigation";
    import { onMount } from "svelte";
import { base } from '$app/paths';

    onMount(() => {
        // 권한 체크
        if (!$memberStore && board.bo_write_level > 1) {
            alert("글을 쓸 권한이 없습니다. 로그인 해주세요.");
            goto(base + "/login"); // or auth route
            return;
        }

        // 새 글 작성이고 기본 내용이 설정되어 있다면 적용
        if (!write.wr_id && !write.wr_content && board.bo_insert_content) {
            write.wr_content = board.bo_insert_content;
        }
    });

    async function handleSubmit(event: Event) {
        event.preventDefault();

        // 비회원 체크
        if (!$memberStore) {
            if (!write.wr_name || !write.wr_password) {
                alert("이름과 비밀번호를 입력해주세요.");
                return;
            }
            if (!captcha_key) {
                alert("자동등록방지 글자를 입력해주세요.");
                return;
            }
        }

        // 글자수 체크
        const textContent = write.wr_content?.replace(/<[^>]*>?/gm, "") || "";
        const length = textContent.length;

        if (board.bo_write_min > 0 && length < board.bo_write_min) {
            alert(`내용을 ${board.bo_write_min}글자 이상 입력해주세요.`);
            return;
        }

        if (board.bo_write_max > 0 && length > board.bo_write_max) {
            alert(`내용은 ${board.bo_write_max}글자 이하로 입력해주세요.`);
            return;
        }

        isSubmitting = true;

        try {
            const formData = new FormData(event.target as HTMLFormElement);

            // 캡차 키 추가
            if (captcha_key) {
                formData.append("captcha_key", captcha_key);
            }

            // 에디터 내용 동기화 확인 (hidden input이 있지만 확실히 하기 위해)
            if (write.wr_content) {
                formData.set("wr_content", write.wr_content);
            }

            // 체크박스 값 처리 (G5 호환)
            // HTML 폼 전송 시, 체크박스가 체크되어 있지 않으면 값이 전송되지 않음
            // 백엔드에서 체크 여부를 확인하려면 isset($_POST['secret']) 등을 사용하거나
            // 모던 방식으로는 명시적으로 보내는 것이 좋음.
            // 여기서는 FormData 그대로 전송.

            if (write.wr_id) {
                // 수정: update.php
                formData.append("wr_id", String(write.wr_id));
                // 파일 업로드를 위해 POST 사용 (backend update.php에 POST 핸들러 추가 필요)
                await apiPost(`/bbs/${board.bo_table}/update`, formData);
            } else {
                // 작성: write.php (POST)
                await apiPost(`/bbs/${board.bo_table}/write`, formData);
            }
            goto(`/bbs/${board.bo_table}`);
        } catch (error: any) {
            alert(error.message || "글 저장 중 오류가 발생했습니다.");
            captchaRef?.refresh(); // Refresh on error
        } finally {
            isSubmitting = false;
        }
    }

    let fileCount = 1;

    function addFile() {
        if (board.bo_upload_count > 0 && fileCount >= board.bo_upload_count) {
            alert(`최대 ${board.bo_upload_count}개까지만 업로드 가능합니다.`);
            return;
        }
        fileCount++;
    }

    function removeFile() {
        if (fileCount > 1) {
            fileCount--;
        }
    }
</script>

<div class="max-w-6xl mx-auto pb-24 md:pb-0">
    <Card.Root class="border-0 md:border shadow-none md:shadow-sm">
        <Card.Header class="px-0 md:px-6">
            <Card.Title
                >{#if write.wr_id}글수정{:else}글쓰기{/if}</Card.Title
            >
            <Card.Description>
                {#if write.wr_id}
                    {board.bo_subject} 글을 수정합니다.
                {:else}
                    {board.bo_subject}에 새로운 글을 작성합니다.
                {/if}
            </Card.Description>
        </Card.Header>

        <Card.Content class="px-0 md:px-6">
            <form method="POST" class="space-y-6" on:submit={handleSubmit}>
                <!-- 카테고리 (사용 시) -->
                {#if board.bo_use_category && board.bo_category_list}
                    <div class="grid w-full gap-2">
                        <Label for="ca_name">Category</Label>
                        <select
                            id="ca_name"
                            name="ca_name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <option value="">선택하세요</option>
                            {#each board.bo_category_list.split("|") as cat}
                                <option
                                    value={cat}
                                    selected={write.ca_name === cat}
                                    >{cat}</option
                                >
                            {/each}
                        </select>
                    </div>
                {/if}

                <!-- 옵션 (비밀글 등) -->
                <div class="flex flex-wrap gap-4">
                    {#if board.bo_use_secret}
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="secret"
                                name="secret"
                                value="secret"
                                class="h-4 w-4 rounded border-gray-300"
                                checked={write.wr_option?.includes("secret")}
                            />
                            <Label for="secret">비밀글</Label>
                        </div>
                    {/if}
                    <!-- 공지사항 (관리자 권한) -->
                    {#if ($memberStore?.mb_level ?? 0) >= 10}
                        <div class="flex items-center space-x-2">
                            <input
                                type="checkbox"
                                id="notice"
                                name="notice"
                                class="h-4 w-4 rounded border-gray-300"
                                checked={write.wr_is_notice}
                            />
                            <Label for="notice">공지사항</Label>
                        </div>
                    {/if}
                </div>

                <!-- 제목 -->
                <div class="grid w-full gap-2">
                    <Label for="wr_subject">Subject</Label>
                    <Input
                        type="text"
                        id="wr_subject"
                        name="wr_subject"
                        bind:value={write.wr_subject}
                        required
                        placeholder="제목을 입력하세요"
                    />
                </div>

                <!-- 내용 -->
                <div class="grid w-full gap-2">
                    <Label for="wr_content">Content</Label>
                    <MarkdownEditor
                        bind:value={write.wr_content}
                        placeholder="내용을 입력하세요"
                        class="min-h-[300px]"
                    />
                    <input
                        type="hidden"
                        name="wr_content"
                        bind:value={write.wr_content}
                    />
                    {#if board.bo_write_min > 0 || board.bo_write_max > 0}
                        <p class="text-xs text-muted-foreground text-right">
                            {#if board.bo_write_min > 0}최소 {board.bo_write_min}글자
                                이상{/if}
                            {#if board.bo_write_max > 0}최대 {board.bo_write_max}글자
                                이하{/if}
                            입력 가능 (현재 {write.wr_content?.replace(
                                /<[^>]*>?/gm,
                                "",
                            ).length || 0}자)
                        </p>
                    {/if}
                </div>

                <!-- 링크 -->
                <div class="grid w-full gap-2">
                    <Label for="wr_link1">Link 1</Label>
                    <Input
                        type="url"
                        id="wr_link1"
                        name="wr_link1"
                        bind:value={write.wr_link1}
                        placeholder="https://"
                    />
                </div>
                <div class="grid w-full gap-2">
                    <Label for="wr_link2">Link 2</Label>
                    <Input
                        type="url"
                        id="wr_link2"
                        name="wr_link2"
                        bind:value={write.wr_link2}
                        placeholder="https://"
                    />
                </div>

                <!-- 파일 첨부 -->
                <!-- 파일 첨부 -->
                {#if (board.bo_upload_count ?? 0) >= 0}
                    <div class="grid w-full gap-2">
                        <div class="flex items-center justify-between">
                            <Label>Files</Label>
                            <div class="flex gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="h-6 w-6"
                                    onclick={addFile}
                                >
                                    <Plus class="h-4 w-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    class="h-6 w-6"
                                    onclick={removeFile}
                                >
                                    <Minus class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <!-- 기존 파일 목록 (수정 시) -->
                        {#if write.wr_id && write.files && write.files.length > 0}
                            <div
                                class="space-y-2 p-3 bg-muted/50 rounded-md border"
                            >
                                <p
                                    class="text-sm font-medium text-muted-foreground"
                                >
                                    기존 파일
                                </p>
                                {#each write.files as file, i}
                                    <div
                                        class="flex items-center gap-3 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            name="bf_file_del[]"
                                            value={file.bf_no}
                                            id="file_del_{file.bf_no}"
                                            class="h-4 w-4 rounded border-gray-300"
                                        />
                                        <label
                                            for="file_del_{file.bf_no}"
                                            class="flex items-center gap-2 flex-1 cursor-pointer"
                                        >
                                            {#if file.bf_type === 1 || /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(file.bf_file)}
                                                <img
                                                    src={file.download_url}
                                                    alt={file.bf_source}
                                                    class="w-10 h-10 object-cover rounded"
                                                />
                                            {/if}
                                            <span class="truncate"
                                                >{file.bf_source}</span
                                            >
                                            <span
                                                class="text-xs text-muted-foreground"
                                                >({(
                                                    file.bf_filesize / 1024
                                                ).toFixed(1)} KB)</span
                                            >
                                        </label>
                                        <span class="text-xs text-red-500"
                                            >삭제</span
                                        >
                                    </div>
                                {/each}
                            </div>
                        {/if}

                        <!-- 신규 파일 업로드 -->
                        <div class="space-y-2">
                            {#each Array(fileCount) as _, i}
                                <div class="flex items-center gap-2">
                                    <Input
                                        type="file"
                                        name="bf_file[]"
                                        onchange={(e: Event) => {
                                            const input =
                                                e.target as HTMLInputElement;
                                            const file = input.files?.[0];
                                            if (
                                                file &&
                                                board.bo_upload_size > 0 &&
                                                file.size > board.bo_upload_size
                                            ) {
                                                alert(
                                                    `파일 용량은 ${(board.bo_upload_size / (1024 * 1024)).toFixed(1)}MB 이하여야 합니다.`,
                                                );
                                                input.value = "";
                                            }
                                        }}
                                    />
                                </div>
                            {/each}
                        </div>
                        <p class="text-[0.8rem] text-muted-foreground">
                            {#if board.bo_upload_count > 0}
                                최대 {board.bo_upload_count}개,
                            {:else}
                                무제한 업로드 가능,
                            {/if}
                            개당 {(
                                board.bo_upload_size /
                                (1024 * 1024)
                            ).toFixed(1)}MB 이하
                        </p>
                    </div>
                {/if}

                <!-- 비회원 입력 (이름, 비밀번호) -->
                <!-- 비회원 작성자 정보: 로그인 안 한 상태에서 새 글 작성 또는 비회원글 수정 시 -->
                {#if !$memberStore && (!write.wr_id || !write.mb_id)}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="grid w-full gap-2">
                            <Label for="wr_name">Name</Label>
                            <Input
                                type="text"
                                id="wr_name"
                                name="wr_name"
                                bind:value={write.wr_name}
                                required
                                placeholder="이름"
                            />
                        </div>
                        <div class="grid w-full gap-2">
                            <Label for="wr_password">Password</Label>
                            <Input
                                type="password"
                                id="wr_password"
                                name="wr_password"
                                bind:value={write.wr_password}
                                required
                                placeholder="비밀번호"
                            />
                        </div>
                    </div>

                    <!-- 자동등록방지 -->
                    <Captcha
                        bind:value={captcha_key}
                        bind:this={captchaRef}
                        label="Captcha"
                        placeholder="자동등록방지 숫자 입력"
                    />
                {/if}

                <!-- 버튼 영역 -->
                <div class="flex justify-end gap-2 pt-4 border-t">
                    <Button
                        variant="outline"
                        href="{base}/bbs/{board.bo_table}"
                        type="button"
                    >
                        <X class="w-4 h-4 mr-2" />
                        취소
                    </Button>
                    <Button type="submit" disabled={isSubmitting}>
                        {#if isSubmitting}
                            Saving...
                        {:else}
                            <Save class="w-4 h-4 mr-2" />
                            저장하기
                        {/if}
                    </Button>
                </div>
            </form>
        </Card.Content>
    </Card.Root>
</div>
