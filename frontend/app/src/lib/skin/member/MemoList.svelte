<script lang="ts">
    import { apiGet } from "$lib/api";

    interface Memo {
        me_id: number;
        me_subject: string;
        me_content: string;
        me_send_mb_id: string;
        me_recv_mb_id: string;
        me_send_datetime: string;
        me_read_datetime: string;
    }

    let memos = $state<Memo[]>([]);
    let loading = $state(true);
    let error = $state("");
    let type = $state("recv");

    async function loadMemos() {
        try {
            loading = true;
            const data = await apiGet<{ memos: Memo[] }>(
                `/member/memo?type=${type}`,
            );
            memos = data.memos;
        } catch (e: any) {
            error = e.message || "쪽지를 불러올 수 없습니다.";
        } finally {
            loading = false;
        }
    }

    $effect(() => {
        loadMemos();
    });
</script>

<div class="memo-container">
    <div class="memo-tabs flex gap-2 mb-4 bg-gray-100 p-1 rounded-lg">
        <button
            class="flex-1 py-2 text-sm font-medium rounded-md transition-colors {type ===
            'recv'
                ? 'bg-white shadow text-blue-600'
                : 'text-gray-500 hover:text-gray-700'}"
            onclick={() => {
                type = "recv";
                loadMemos();
            }}
        >
            받은 쪽지
        </button>
        <button
            class="flex-1 py-2 text-sm font-medium rounded-md transition-colors {type ===
            'send'
                ? 'bg-white shadow text-blue-600'
                : 'text-gray-500 hover:text-gray-700'}"
            onclick={() => {
                type = "send";
                loadMemos();
            }}
        >
            보낸 쪽지
        </button>
    </div>

    {#if loading}
        <div class="py-8 text-center text-gray-500">로딩 중...</div>
    {:else if error}
        <div class="text-red-500 bg-red-50 p-4 rounded-lg">{error}</div>
    {:else if memos.length === 0}
        <div
            class="py-12 text-center text-gray-400 border border-dashed border-gray-300 rounded-lg"
        >
            쪽지가 없습니다.
        </div>
    {:else}
        <div class="space-y-3">
            {#each memos as memo}
                <div
                    class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors"
                >
                    <div class="font-medium mb-1 line-clamp-1">
                        {memo.me_subject}
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span
                            >{type === "recv" ? "보낸이" : "받은이"}: {type ===
                            "recv"
                                ? memo.me_send_mb_id
                                : memo.me_recv_mb_id}</span
                        >
                        <span>{memo.me_send_datetime}</span>
                    </div>
                </div>
            {/each}
        </div>
    {/if}
</div>
