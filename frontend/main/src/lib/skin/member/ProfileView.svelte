<script lang="ts">
    import { logout } from "$lib/api";
    import { clearMember } from "$lib/store";
    import { toastStore } from "$lib/store/toast";
    import { goto } from "$app/navigation";
    import { ChevronRight } from "lucide-svelte";
import { base } from '$app/paths';

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

    export let member: Member | null = null;

    async function handleLogout() {
        try {
            await logout();
            clearMember();
            toastStore.success("로그아웃되었습니다.");
            goto(base + "/");
        } catch (e: any) {
            toastStore.error(e.message || "로그아웃 실패");
        }
    }
</script>

<div class="profile-view">
    {#if member}
        <div class="profile-info">
            <!-- 요약 정보는 상단에 있으므로 상세 정보만 표시하거나, 전체를 다 표시할지 결정. 요청은 '프로필' 탭. -->
            <!-- 상세 정보 -->
            <div class="info-group">
                <div class="info-item">
                    <span class="label">이메일</span>
                    <span class="value">{member.mb_email || "-"}</span>
                </div>
                <div class="info-item">
                    <span class="label">전화번호</span>
                    <span class="value">{member.mb_tel || "-"}</span>
                </div>
                <div class="info-item">
                    <span class="label">휴대폰</span>
                    <span class="value">{member.mb_hp || "-"}</span>
                </div>
                <div class="info-item">
                    <span class="label">홈페이지</span>
                    <span class="value">{member.mb_homepage || "-"}</span>
                </div>
                <div class="info-item">
                    <span class="label">가입일</span>
                    <span class="value">{member.mb_datetime || "-"}</span>
                </div>
            </div>

            <!-- 계정 관리 -->
            <div class="info-group mt-6 border-t pt-2">
                <button
                    onclick={() => handleLogout()}
                    class="info-item w-full text-left hover:bg-gray-50 transition-colors flex items-center justify-between"
                >
                    <span class="label text-gray-900">로그아웃</span>
                    <ChevronRight class="w-4 h-4 text-gray-400" />
                </button>
                <a
                    href="{base}/member/leave"
                    class="info-item w-full text-left hover:bg-gray-50 transition-colors block flex items-center justify-between"
                >
                    <span class="label text-gray-900">회원탈퇴</span>
                    <ChevronRight class="w-4 h-4 text-gray-400" />
                </a>
            </div>
        </div>

        <div class="profile-actions mt-6">
            <a
                href="{base}/member/edit"
                class="w-full block text-center py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
            >
                정보 수정
            </a>
        </div>
    {:else}
        <p class="text-center py-4 text-gray-500">
            회원 정보를 불러올 수 없습니다.
        </p>
    {/if}
</div>

<style>
    .profile-view {
        padding: 1rem 0;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center; /* 아이콘 세로 정렬 */
        padding: 1rem 0;
        border-bottom: 1px solid #eee;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .label {
        color: #666;
        font-weight: 500;
    }

    .value {
        font-weight: 500;
        text-align: right;
    }
</style>
