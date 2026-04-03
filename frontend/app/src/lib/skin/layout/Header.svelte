<script lang="ts">
	/**
	 * Header 컴포넌트
	 *
	 * PC와 모바일 헤더를 통합 관리하는 래퍼 컴포넌트입니다.
	 *
	 * - PC: DesktopHeader (로고 + 네비게이션 + 사용자 메뉴)
	 * - 모바일: MobileHeader (뒤로가기 + 타이틀 + 아이콘)
	 * - 슬라이드 메뉴: SlideMenu (우측에서 슬라이드)
	 *
	 * @example
	 * <!-- 기본 사용 -->
	 * <Header />
	 *
	 * <!-- 뒤로가기 버튼 없이 (홈 화면) -->
	 * <Header showBackButton={false} title="홈" />
	 *
	 * <!-- 커스텀 우측 아이콘 -->
	 * <Header title="상세">
	 *   <svelte:fragment slot="right-icons">
	 *     <button>공유</button>
	 *   </svelte:fragment>
	 * </Header>
	 */
	import { menuStore } from "$lib/store";
	import { pageTitle } from "$lib/store/ui";
	import { DesktopHeader, MobileHeader, SlideMenu } from "./header";

	interface Props {
		/** 모바일 뒤로가기 버튼 표시 여부 (기본: true) */
		showBackButton?: boolean;
		/** 모바일 헤더 숨김 상태 */
		hidden?: boolean;
	}

	let { showBackButton = true, hidden = false }: Props = $props();

	let showSlideMenu = $state(false);

	function openSlideMenu() {
		showSlideMenu = true;
	}

	function closeSlideMenu() {
		showSlideMenu = false;
	}
</script>

<!-- 데스크톱 헤더 (lg 이상) -->
<DesktopHeader onMenuClick={openSlideMenu} />

<!-- 모바일 헤더 (lg 미만) -->
<MobileHeader
	{showBackButton}
	title={$pageTitle || ""}
	onMenuClick={openSlideMenu}
	{hidden}
/>

<!-- 슬라이드 메뉴 -->
<SlideMenu open={showSlideMenu} menus={$menuStore} onClose={closeSlideMenu} />
