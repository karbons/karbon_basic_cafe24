<script lang="ts">
    import { apiGet } from "$lib/api";

    interface Point {
        po_id: number;
        po_content: string;
        po_point: number;
        po_use_point: number;
        po_datetime: string;
        po_expired: string;
        po_expire_date: string;
    }

    let totalPoint = $state(0);
    let points = $state<Point[]>([]);
    let loading = $state(true);
    let error = $state("");
    let page = $state(1);

    async function loadPoints() {
        try {
            loading = true;
            // props로 totalPoint를 받을 수도 있지만, 여기서 최신 조회가 나을 수 있음
            const data = await apiGet<{
                total_point: number;
                points: Point[];
                pagination: any;
            }>(`/member/point?page=${page}`);

            totalPoint = data.total_point;
            points = data.points;
        } catch (e: any) {
            error = e.message || "포인트 내역을 불러올 수 없습니다.";
        } finally {
            loading = false;
        }
    }

    $effect(() => {
        loadPoints();
    });
</script>

<div class="point-view">
    <div
        class="mb-4 bg-yellow-50 p-4 rounded-lg border border-yellow-100 flex justify-between items-center"
    >
        <span class="text-yellow-700 font-medium">보유 포인트</span>
        <strong class="text-xl text-yellow-800"
            >{totalPoint.toLocaleString()} P</strong
        >
    </div>

    {#if loading}
        <div class="py-8 text-center text-gray-500">로딩 중...</div>
    {:else if error}
        <div class="text-red-500 bg-red-50 p-4 rounded-lg">{error}</div>
    {:else if points.length === 0}
        <div
            class="py-12 text-center text-gray-400 border border-dashed border-gray-300 rounded-lg"
        >
            포인트 내역이 없습니다.
        </div>
    {:else}
        <div class="space-y-3">
            {#each points as point}
                <div
                    class="p-4 border border-gray-200 rounded-lg flex justify-between items-center"
                >
                    <div class="flex-1">
                        <div class="font-medium text-gray-800 mb-1">
                            {point.po_content}
                        </div>
                        <div class="text-xs text-gray-500">
                            {point.po_datetime}
                        </div>
                    </div>
                    <div class="text-right ml-4">
                        {#if point.po_point > 0}
                            <div class="text-green-600 font-bold">
                                +{point.po_point.toLocaleString()}
                            </div>
                        {/if}
                        {#if point.po_use_point > 0}
                            <div class="text-red-600 font-bold">
                                -{point.po_use_point.toLocaleString()}
                            </div>
                        {/if}
                    </div>
                </div>
            {/each}
        </div>
    {/if}
</div>
