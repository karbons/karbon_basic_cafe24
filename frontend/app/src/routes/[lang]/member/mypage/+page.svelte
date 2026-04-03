<script lang="ts">
    import { onMount } from "svelte";
    import { apiGet, logout } from "$lib/api";
    import { memberStore, clearMember } from "$lib/store";
    import { toastStore } from "$lib/store/toast";
    import { goto } from "$app/navigation";
    import { User, FileText, Bookmark, Coins } from "lucide-svelte";

    import ProfileView from "$lib/skin/member/ProfileView.svelte";
    import MemoList from "$lib/skin/member/MemoList.svelte";
    import PointList from "$lib/skin/member/PointList.svelte";
    import ScrapList from "$lib/skin/member/ScrapList.svelte";

    interface Member {
        mb_id: string;
        mb_name: string;
        mb_nick: string;
        mb_level: number;
        mb_point: number;
        mb_email: string;
        mb_homepage: string;
        mb_tel: string;
        mb_hp: string;
        mb_profile: string;
        mb_datetime: string;
    }

    let member = $state<Member | null>(null);
    let activeTab = $state("profile");
    let loading = $state(true);

    async function loadProfile() {
        try {
            loading = true;
            const data = await apiGet<Member>("/member/profile");
            member = data;
            memberStore.set(data);
        } catch (e) {
            console.error(e);
        } finally {
            loading = false;
        }
    }

    onMount(() => {
        loadProfile();
    });
    import { pageTitle } from "$lib/store/ui";
    import { resolveImageUrl } from "$lib/util/image";

    $effect(() => {
        $pageTitle = "마이페이지";
        return () => {
            $pageTitle = "";
        };
    });
</script>

<div class="max-w-3xl mx-auto px-4 py-6 pb-24 md:pb-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="hidden md:block text-xl font-bold">마이페이지</h1>
    </div>

    {#if loading}
        <div class="flex justify-center py-12">
            <div
                class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin"
            ></div>
        </div>
    {:else if member}
        <!-- 상단 프로필 요약 카드 -->
        <div
            class="bg-white border border-gray-200 rounded-2xl p-6 mb-6 shadow-sm flex items-center gap-6"
        >
            {#if member.mb_profile}
                <div class="w-20 h-20 rounded-full overflow-hidden shrink-0 border">
                    <img
                        src={resolveImageUrl(member.mb_profile)}
                        alt={member.mb_nick}
                        class="w-full h-full object-cover"
                    />
                </div>
            {:else}
                <div
                    class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center text-primary text-3xl font-bold shrink-0"
                >
                    {member.mb_name.charAt(0)}
                </div>
            {/if}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h2 class="text-xl font-bold truncate">{member.mb_nick}</h2>
                    <span
                        class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full font-medium"
                        >Lv. {member.mb_level}</span
                    >
                </div>
                <div class="text-gray-500 text-sm mb-3 truncate">
                    {member.mb_nick || member.mb_email || "회원"}
                </div>
                <div
                    class="inline-flex items-center gap-2 bg-yellow-50 text-yellow-700 px-3 py-1 rounded-lg text-sm font-medium"
                >
                    <Coins class="w-4 h-4" />
                    {member.mb_point.toLocaleString()} P
                </div>
            </div>
        </div>

        <!-- 탭 메뉴 -->
        <div
            class="flex border-b border-gray-200 mb-6 overflow-x-auto no-scrollbar"
        >
            <button
                class="flex-1 min-w-[80px] py-3 text-sm font-medium border-b-2 transition-colors flex flex-col items-center gap-1 {activeTab ===
                'profile'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-gray-500 hover:text-gray-700'}"
                onclick={() => (activeTab = "profile")}
            >
                <!-- <User class="w-5 h-5" /> -->
                프로필
            </button>
            <button
                class="flex-1 min-w-[80px] py-3 text-sm font-medium border-b-2 transition-colors flex flex-col items-center gap-1 {activeTab ===
                'memo'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-gray-500 hover:text-gray-700'}"
                onclick={() => (activeTab = "memo")}
            >
                <!-- <FileText class="w-5 h-5" /> -->
                쪽지
            </button>
            <button
                class="flex-1 min-w-[80px] py-3 text-sm font-medium border-b-2 transition-colors flex flex-col items-center gap-1 {activeTab ===
                'scrap'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-gray-500 hover:text-gray-700'}"
                onclick={() => (activeTab = "scrap")}
            >
                <!-- <Bookmark class="w-5 h-5" /> -->
                스크랩
            </button>
            <button
                class="flex-1 min-w-[80px] py-3 text-sm font-medium border-b-2 transition-colors flex flex-col items-center gap-1 {activeTab ===
                'point'
                    ? 'border-primary text-primary'
                    : 'border-transparent text-gray-500 hover:text-gray-700'}"
                onclick={() => (activeTab = "point")}
            >
                <!-- <Coins class="w-5 h-5" /> -->
                포인트
            </button>
        </div>

        <!-- 탭 컨텐츠 -->
        <div class="min-h-[300px]">
            {#if activeTab === "profile"}
                <ProfileView {member} />
            {:else if activeTab === "memo"}
                <MemoList />
            {:else if activeTab === "scrap"}
                <ScrapList />
            {:else if activeTab === "point"}
                <PointList />
            {/if}
        </div>
    {/if}
</div>

<style>
    /* 스크롤바 숨김 */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
