<script lang="ts">
    import { menuStore } from "$lib/store";
    import { MainDesktopHeader, MainMobileHeader, MainSlideMenu } from "./header";

    interface Props {
        lang?: string;
    }

    let { lang = "ko" }: Props = $props();

    let showSlideMenu = $state(false);

    function openSlideMenu() {
        showSlideMenu = true;
    }

    function closeSlideMenu() {
        showSlideMenu = false;
    }
</script>

<MainDesktopHeader {lang} onMenuClick={openSlideMenu} />
<MainMobileHeader {lang} onMenuClick={openSlideMenu} />
<MainSlideMenu open={showSlideMenu} {lang} onClose={closeSlideMenu} />