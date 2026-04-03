<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import { Editor } from "@tiptap/core";
    import StarterKit from "@tiptap/starter-kit";
    import Image from "@tiptap/extension-image";
    import Link from "@tiptap/extension-link";
    import { Table } from "@tiptap/extension-table";
    import { TableRow } from "@tiptap/extension-table-row";
    import { TableCell } from "@tiptap/extension-table-cell";
    import { TableHeader } from "@tiptap/extension-table-header";
    import { Markdown } from "tiptap-markdown";
    import {
        Bold,
        Italic,
        Strikethrough,
        List,
        ListOrdered,
        Quote,
        Code,
        Image as ImageIcon,
        Link as LinkIcon,
        Heading1,
        Heading2,
        Heading3,
        Undo,
        Redo,
        Minus,
        Code2,
        Table as TableIcon,
        Plus,
        Trash2,
        ArrowDown,
        ArrowRight,
        X,
        SpellCheck,
    } from "lucide-svelte";
    import { cn } from "$lib/util";
    import SpellCheckModal from "$lib/ui/SpellCheckModal.svelte";

    interface Props {
        value?: string;
        placeholder?: string;
        class?: string;
    }

    let {
        value = $bindable(""),
        placeholder = "",
        class: className,
    }: Props = $props();

    let element: HTMLElement;
    let bubbleMenuElement: HTMLElement;
    let editor: Editor | null = $state(null);
    let isTableActive = $state(false);
    let hasSelection = $state(false);
    let selectionRect = $state({ top: 0, left: 0 });

    onMount(() => {
        editor = new Editor({
            element: element,
            extensions: [
                StarterKit.configure({
                    heading: {
                        levels: [1, 2, 3],
                    },
                }),
                Image,
                Link.configure({
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: "https",
                }),
                Table.configure({
                    resizable: true,
                }),
                TableRow,
                TableCell,
                TableHeader,
                Markdown.configure({
                    html: true,
                    tightLists: true,
                    tightListClass: "tight",
                    bulletListMarker: "-",
                    linkify: true,
                    breaks: false,
                    transformPastedText: true,
                    transformCopiedText: true,
                }),
            ],
            content: value,
            editorProps: {
                attributes: {
                    class: cn(
                        "prose prose-sm sm:prose-base dark:prose-invert focus:outline-none max-w-none min-h-[200px] p-4",
                        // 헤딩 스타일 강제 적용
                        "[&_h1]:text-3xl [&_h1]:font-bold [&_h1]:mt-6 [&_h1]:mb-4",
                        "[&_h2]:text-2xl [&_h2]:font-bold [&_h2]:mt-5 [&_h2]:mb-3",
                        "[&_h3]:text-xl [&_h3]:font-semibold [&_h3]:mt-4 [&_h3]:mb-2",
                        "[&_p]:my-2",
                        "[&_ul]:list-disc [&_ul]:pl-6",
                        "[&_ol]:list-decimal [&_ol]:pl-6",
                        "[&_blockquote]:border-l-4 [&_blockquote]:border-gray-300 [&_blockquote]:pl-4 [&_blockquote]:italic",
                        "[&_code]:bg-gray-100 [&_code]:dark:bg-gray-800 [&_code]:px-1 [&_code]:rounded",
                        "[&_pre]:bg-gray-100 [&_pre]:dark:bg-gray-800 [&_pre]:p-4 [&_pre]:rounded-lg [&_pre]:overflow-x-auto",
                        "[&_hr]:my-6 [&_hr]:border-gray-300",
                        // 취소선 스타일
                        "[&_s]:line-through [&_s]:text-gray-500 [&_del]:line-through [&_del]:text-gray-500",
                        // 표 스타일
                        "[&_table]:w-full [&_table]:border-collapse [&_table]:my-4",
                        "[&_th]:border [&_th]:border-gray-300 [&_th]:bg-gray-100 [&_th]:dark:bg-gray-800 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left",
                        "[&_td]:border [&_td]:border-gray-300 [&_td]:px-3 [&_td]:py-2",
                        className,
                    ),
                },
            },
            onUpdate: ({ editor }) => {
                // 마크다운으로 저장
                value = (editor.storage as any).markdown.getMarkdown();
            },
            onSelectionUpdate: ({ editor }) => {
                // 테이블 활성화 상태 추적
                isTableActive = editor.isActive("table");
                // 선택 영역 여부 및 위치
                const { from, to } = editor.state.selection;
                hasSelection = from !== to && !editor.isActive("table");

                // 선택 영역 위치 계산
                if (hasSelection) {
                    const selection = window.getSelection();
                    if (selection && selection.rangeCount > 0) {
                        const range = selection.getRangeAt(0);
                        const rect = range.getBoundingClientRect();
                        const containerRect =
                            element.parentElement?.getBoundingClientRect();
                        if (containerRect) {
                            selectionRect = {
                                top: rect.top - containerRect.top - 40, // 선택 영역 바로 위에 배치 (메뉴 높이 고려)
                                left:
                                    rect.left -
                                    containerRect.left +
                                    rect.width / 2 -
                                    100, // 중앙 정렬
                            };
                        }
                    }
                }
            },
        });
    });

    onDestroy(() => {
        if (editor) {
            editor.destroy();
        }
    });

    // 외부에서 value가 변경되었을 때
    $effect(() => {
        if (
            editor &&
            value !== (editor.storage as any).markdown?.getMarkdown()
        ) {
            // 커서 위치가 변경되지 않도록 주의
            const currentMarkdown =
                (editor.storage as any).markdown?.getMarkdown() || "";
            if (value !== currentMarkdown && value) {
                editor.commands.setContent(value);
            }
        }
    });

    function toggleBold() {
        editor?.chain().focus().toggleBold().run();
    }

    function toggleItalic() {
        editor?.chain().focus().toggleItalic().run();
    }

    function toggleBulletList() {
        editor?.chain().focus().toggleBulletList().run();
    }

    function toggleOrderedList() {
        editor?.chain().focus().toggleOrderedList().run();
    }

    function toggleBlockquote() {
        editor?.chain().focus().toggleBlockquote().run();
    }

    function toggleCodeBlock() {
        editor?.chain().focus().toggleCodeBlock().run();
    }

    function toggleCode() {
        editor?.chain().focus().toggleCode().run();
    }

    function toggleHeading(level: 1 | 2 | 3) {
        editor?.chain().focus().toggleHeading({ level }).run();
    }

    function setHorizontalRule() {
        editor?.chain().focus().setHorizontalRule().run();
    }

    function setLink() {
        const previousUrl = editor?.getAttributes("link").href;
        const url = window.prompt("URL을 입력하세요", previousUrl);

        if (url === null) {
            return;
        }

        if (url === "") {
            editor?.chain().focus().extendMarkRange("link").unsetLink().run();
            return;
        }

        editor
            ?.chain()
            .focus()
            .extendMarkRange("link")
            .setLink({ href: url })
            .run();
    }

    function addImage() {
        const url = window.prompt("이미지 URL을 입력하세요");

        if (url) {
            editor?.chain().focus().setImage({ src: url }).run();
        }
    }

    function undo() {
        editor?.chain().focus().undo().run();
    }

    function redo() {
        editor?.chain().focus().redo().run();
    }

    function toggleStrike() {
        editor?.chain().focus().toggleStrike().run();
    }

    function insertTable() {
        editor
            ?.chain()
            .focus()
            .insertTable({ rows: 3, cols: 3, withHeaderRow: true })
            .run();
    }

    function addColumnAfter() {
        editor?.chain().focus().addColumnAfter().run();
    }

    function addRowAfter() {
        editor?.chain().focus().addRowAfter().run();
    }

    function deleteTable() {
        editor?.chain().focus().deleteTable().run();
    }

    function deleteRow() {
        editor?.chain().focus().deleteRow().run();
    }

    function deleteColumn() {
        editor?.chain().focus().deleteColumn().run();
    }

    // Spell Check
    const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || "/api";
    let spellCheckOpen = $state(false);
    let spellCheckLoading = $state(false);
    let spellCheckCorrections = $state<any[]>([]);
    let spellCheckCorrectedText = $state("");
    let spellCheckOriginalText = $state("");

    async function checkSpelling() {
        if (!editor) return;

        const text =
            (editor.storage as any).markdown?.getMarkdown() || editor.getText();
        if (!text.trim()) {
            alert("검사할 텍스트가 없습니다.");
            return;
        }

        spellCheckOpen = true;
        spellCheckLoading = true;
        spellCheckOriginalText = text;
        spellCheckCorrections = [];
        spellCheckCorrectedText = "";

        try {
            const response = await fetch(`${API_BASE_URL}/spellcheck`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ text }),
            });

            const data = await response.json();

            if (data.success) {
                spellCheckCorrections = data.corrections || [];
                spellCheckCorrectedText = data.corrected_text || text;
            } else {
                alert(data.error || "맞춤법 검사 중 오류가 발생했습니다.");
                spellCheckOpen = false;
            }
        } catch (e) {
            console.error("Spell check error:", e);
            alert("맞춤법 검사 중 오류가 발생했습니다.");
            spellCheckOpen = false;
        } finally {
            spellCheckLoading = false;
        }
    }

    function applySpellCheck(correctedText: string) {
        if (editor) {
            editor.commands.setContent(correctedText);
            value = correctedText;
        }
    }
</script>

<div class="border rounded-md shadow-sm bg-background relative">
    {#if editor}
        <div class="flex flex-wrap gap-1 p-2 border-b bg-muted/50">
            <!-- 굵게, 기울임 -->
            <button
                type="button"
                onclick={toggleBold}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("bold") && "bg-muted text-primary",
                )}
                title="굵게 (Ctrl+B)"><Bold class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleItalic}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("italic") && "bg-muted text-primary",
                )}
                title="기울임 (Ctrl+I)"><Italic class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleStrike}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("strike") && "bg-muted text-primary",
                )}
                title="취소선 (~~text~~)"
                ><Strikethrough class="size-4" /></button
            >

            <div class="w-px h-6 mx-1 bg-border self-center"></div>

            <!-- 헤딩 -->
            <button
                type="button"
                onclick={() => toggleHeading(1)}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("heading", { level: 1 }) &&
                        "bg-muted text-primary",
                )}
                title="제목 1 (# )"><Heading1 class="size-4" /></button
            >
            <button
                type="button"
                onclick={() => toggleHeading(2)}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("heading", { level: 2 }) &&
                        "bg-muted text-primary",
                )}
                title="제목 2 (## )"><Heading2 class="size-4" /></button
            >
            <button
                type="button"
                onclick={() => toggleHeading(3)}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("heading", { level: 3 }) &&
                        "bg-muted text-primary",
                )}
                title="제목 3 (### )"><Heading3 class="size-4" /></button
            >

            <div class="w-px h-6 mx-1 bg-border self-center"></div>

            <!-- 리스트 -->
            <button
                type="button"
                onclick={toggleBulletList}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("bulletList") && "bg-muted text-primary",
                )}
                title="목록 (- )"><List class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleOrderedList}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("orderedList") && "bg-muted text-primary",
                )}
                title="숫자 목록 (1. )"><ListOrdered class="size-4" /></button
            >

            <div class="w-px h-6 mx-1 bg-border self-center"></div>

            <!-- 인용, 코드 -->
            <button
                type="button"
                onclick={toggleBlockquote}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("blockquote") && "bg-muted text-primary",
                )}
                title="인용구 (> )"><Quote class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleCode}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("code") && "bg-muted text-primary",
                )}
                title="인라인 코드 (`code`)"><Code class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleCodeBlock}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("codeBlock") && "bg-muted text-primary",
                )}
                title="코드 블록 (```)"><Code2 class="size-4" /></button
            >

            <div class="w-px h-6 mx-1 bg-border self-center"></div>

            <!-- 구분선, 링크, 이미지 -->
            <button
                type="button"
                onclick={setHorizontalRule}
                class="p-1.5 rounded hover:bg-muted transition-colors"
                title="구분선 (---)"><Minus class="size-4" /></button
            >
            <button
                type="button"
                onclick={setLink}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("link") && "bg-muted text-primary",
                )}
                title="링크 [text](url)"><LinkIcon class="size-4" /></button
            >
            <button
                type="button"
                onclick={addImage}
                class="p-1.5 rounded hover:bg-muted transition-colors"
                title="이미지 ![alt](url)"><ImageIcon class="size-4" /></button
            >
            <button
                type="button"
                onclick={insertTable}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor.isActive("table") && "bg-muted text-primary",
                )}
                title="표 삽입 (3x3)"><TableIcon class="size-4" /></button
            >
            {#if isTableActive}
                <button
                    type="button"
                    onclick={addRowAfter}
                    class="p-1.5 rounded hover:bg-muted transition-colors"
                    title="행 추가"><ArrowDown class="size-4" /></button
                >
                <button
                    type="button"
                    onclick={deleteRow}
                    class="p-1.5 rounded hover:bg-muted transition-colors text-orange-500"
                    title="행 삭제"><X class="size-4" /></button
                >
                <button
                    type="button"
                    onclick={addColumnAfter}
                    class="p-1.5 rounded hover:bg-muted transition-colors"
                    title="열 추가"><ArrowRight class="size-4" /></button
                >
                <button
                    type="button"
                    onclick={deleteColumn}
                    class="p-1.5 rounded hover:bg-muted transition-colors text-orange-500"
                    title="열 삭제"><X class="size-4" /></button
                >
                <button
                    type="button"
                    onclick={deleteTable}
                    class="p-1.5 rounded hover:bg-muted transition-colors text-red-500"
                    title="표 삭제"><Trash2 class="size-4" /></button
                >
            {/if}

            <!-- 맞춤법 검사 -->
            <button
                type="button"
                onclick={checkSpelling}
                class="p-1.5 rounded hover:bg-muted transition-colors"
                title="맞춤법 검사"><SpellCheck class="size-4" /></button
            >

            <!-- 실행 취소/다시 실행 -->
            <div class="ml-auto flex gap-1">
                <button
                    type="button"
                    onclick={undo}
                    class="p-1.5 rounded hover:bg-muted transition-colors disabled:opacity-50"
                    disabled={!editor.can().undo()}
                    title="실행 취소 (Ctrl+Z)"><Undo class="size-4" /></button
                >
                <button
                    type="button"
                    onclick={redo}
                    class="p-1.5 rounded hover:bg-muted transition-colors disabled:opacity-50"
                    disabled={!editor.can().redo()}
                    title="다시 실행 (Ctrl+Y)"><Redo class="size-4" /></button
                >
            </div>
        </div>
    {/if}

    <div bind:this={element} class="min-h-[300px]"></div>

    <!-- Bubble Menu (floating toolbar) -->
    {#if hasSelection}
        <div
            bind:this={bubbleMenuElement}
            class="absolute z-50 bg-background border rounded-lg shadow-lg p-1 flex gap-1 transition-opacity"
            style="top: {selectionRect.top}px; left: {Math.max(
                0,
                selectionRect.left,
            )}px;"
        >
            <button
                type="button"
                onclick={toggleBold}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor?.isActive("bold") && "bg-muted text-primary",
                )}
                title="굵게"><Bold class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleItalic}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor?.isActive("italic") && "bg-muted text-primary",
                )}
                title="기울임"><Italic class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleStrike}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor?.isActive("strike") && "bg-muted text-primary",
                )}
                title="취소선"><Strikethrough class="size-4" /></button
            >
            <div class="w-px h-6 mx-0.5 bg-border self-center"></div>
            <button
                type="button"
                onclick={toggleCode}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor?.isActive("code") && "bg-muted text-primary",
                )}
                title="코드"><Code class="size-4" /></button
            >
            <button
                type="button"
                onclick={setLink}
                class={cn(
                    "p-1.5 rounded hover:bg-muted transition-colors",
                    editor?.isActive("link") && "bg-muted text-primary",
                )}
                title="링크"><LinkIcon class="size-4" /></button
            >
        </div>
    {/if}
</div>

<!-- Spell Check Modal -->
<SpellCheckModal
    bind:open={spellCheckOpen}
    loading={spellCheckLoading}
    corrections={spellCheckCorrections}
    correctedText={spellCheckCorrectedText}
    originalText={spellCheckOriginalText}
    onApply={applySpellCheck}
/>

<style>
    /* ProseMirror 기본 스타일 */
    :global(.ProseMirror) {
        outline: none;
    }
    :global(.ProseMirror p.is-editor-empty:first-child::before) {
        content: attr(data-placeholder);
        float: left;
        color: #adb5bd;
        pointer-events: none;
        height: 0;
    }
</style>
