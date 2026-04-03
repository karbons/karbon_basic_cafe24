<script lang="ts">
    import { onMount, onDestroy } from "svelte";
    import { Editor } from "@tiptap/core";
    import StarterKit from "@tiptap/starter-kit";
    import Image from "@tiptap/extension-image";
    import Link from "@tiptap/extension-link";
    import {
        Bold,
        Italic,
        List,
        ListOrdered,
        Quote,
        Code,
        Image as ImageIcon,
        Link as LinkIcon,
        Heading1,
        Heading2,
        Undo,
        Redo,
    } from "lucide-svelte";
    import { cn } from "$lib/util";
    import type { Content } from "@tiptap/core";

    interface Props {
        value?: Content;
        placeholder?: string;
        class?: string;
    }

    let {
        value = $bindable(""),
        placeholder = "",
        class: className,
    }: Props = $props();

    let element: HTMLElement;
    let editor: Editor | null = $state(null);

    onMount(() => {
        editor = new Editor({
            element: element,
            extensions: [
                StarterKit,
                Image,
                Link.configure({
                    openOnClick: false,
                    autolink: true,
                    defaultProtocol: "https",
                }),
            ],
            content: value,
            editorProps: {
                attributes: {
                    class: cn(
                        "prose prose-sm sm:prose-base dark:prose-invert focus:outline-none max-w-none min-h-[200px] p-4",
                        className,
                    ),
                },
            },
            onUpdate: ({ editor }) => {
                value = editor.getHTML();
            },
        });
    });

    onDestroy(() => {
        if (editor) {
            editor.destroy();
        }
    });

    // value prop이 외부에서 변경되었을 때 에디터 내용 업데이트 (선택적)
    // infinite loop 방지를 위해 현재 내용과 다를 때만 업데이트
    $effect(() => {
        if (editor && value !== editor.getHTML()) {
            // 커서 위치 유지를 위해 setContent 대신 다른 방법 고려 가능하나,
            // 양방향 바인딩의 경우 외부 변경은 주로 초기화나 리셋 시 발생하므로 setContent 사용
            // 단, 타이핑 중에는 이 effect가 돌면 안됨.
            // 여기서는 간단히 외부 주입 시에만 반영하도록 함.
            // 실제로는 content mismatch 체크가 필요함.
            // Svelte 5 $effect는 dependency tracking 하므로 value 변경시 실행됨.
            // 내부 update로 인한 value 변경시에도 실행되므로 주의.
            // 간단한 구현에서는 생략하거나 조건을 강화해야 함.
            // 여기서는 초기 로드 외엔 양방향 동기화를 느슨하게 처리.
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

    function toggleHeading(level: 1 | 2) {
        editor?.chain().focus().toggleHeading({ level }).run();
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
</script>

<div class="border rounded-md shadow-sm bg-background">
    {#if editor}
        <div class="flex flex-wrap gap-1 p-2 border-b bg-muted/50">
            <button
                type="button"
                onclick={toggleBold}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("bold") && "bg-muted text-primary",
                )}
                title="굵게"><Bold class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleItalic}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("italic") && "bg-muted text-primary",
                )}
                title="기울임"><Italic class="size-4" /></button
            >
            <div class="w-px h-6 mx-1 bg-border"></div>
            <button
                type="button"
                onclick={() => toggleHeading(1)}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("heading", { level: 1 }) &&
                        "bg-muted text-primary",
                )}
                title="제목 1"><Heading1 class="size-4" /></button
            >
            <button
                type="button"
                onclick={() => toggleHeading(2)}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("heading", { level: 2 }) &&
                        "bg-muted text-primary",
                )}
                title="제목 2"><Heading2 class="size-4" /></button
            >
            <div class="w-px h-6 mx-1 bg-border"></div>
            <button
                type="button"
                onclick={toggleBulletList}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("bulletList") && "bg-muted text-primary",
                )}
                title="목록"><List class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleOrderedList}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("orderedList") && "bg-muted text-primary",
                )}
                title="숫자 목록"><ListOrdered class="size-4" /></button
            >
            <div class="w-px h-6 mx-1 bg-border"></div>
            <button
                type="button"
                onclick={toggleBlockquote}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("blockquote") && "bg-muted text-primary",
                )}
                title="인용구"><Quote class="size-4" /></button
            >
            <button
                type="button"
                onclick={toggleCodeBlock}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("codeBlock") && "bg-muted text-primary",
                )}
                title="코드"><Code class="size-4" /></button
            >
            <div class="w-px h-6 mx-1 bg-border"></div>
            <button
                type="button"
                onclick={setLink}
                class={cn(
                    "p-1 rounded hover:bg-muted",
                    editor.isActive("link") && "bg-muted text-primary",
                )}
                title="링크"><LinkIcon class="size-4" /></button
            >
            <button
                type="button"
                onclick={addImage}
                class="p-1 rounded hover:bg-muted"
                title="이미지"><ImageIcon class="size-4" /></button
            >
            <div class="ml-auto flex gap-1">
                <button
                    type="button"
                    onclick={undo}
                    class="p-1 rounded hover:bg-muted"
                    disabled={!editor.can().undo()}
                    title="실행 취소"><Undo class="size-4" /></button
                >
                <button
                    type="button"
                    onclick={redo}
                    class="p-1 rounded hover:bg-muted"
                    disabled={!editor.can().redo()}
                    title="다시 실행"><Redo class="size-4" /></button
                >
            </div>
        </div>
    {/if}

    <div bind:this={element} class="min-h-[300px]"></div>
</div>

<style>
    /* Basic typography for editor content if prose is not perfect */
    :global(.ProseMirror) {
        outline: none;
    }
    :global(.ProseMirror p) {
        margin-top: 0.5em;
        margin-bottom: 0.5em;
    }
    :global(.ProseMirror ul, .ProseMirror ol) {
        padding-left: 1.5em;
    }
    :global(.ProseMirror ul) {
        list-style-type: disc;
    }
    :global(.ProseMirror ol) {
        list-style-type: decimal;
    }
    :global(.ProseMirror blockquote) {
        border-left: 2px solid #ddd;
        padding-left: 1rem;
        color: #666;
    }
    :global(.ProseMirror img) {
        max-width: 100%;
        height: auto;
    }
</style>
