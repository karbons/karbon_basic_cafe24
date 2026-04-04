<script lang="ts">
  import { locale } from "svelte-i18n";
  import { base } from "$app/paths";
  import { page } from "$app/state";

  let currentLang = $state("ko");

  // 현재 언어 추출
  $effect(() => {
    const path = page.url.pathname;
    // /main/ko/... 또는 /ko/... 패턴 매칭
    const match =
      path.match(/^\/main\/(ko|en)\//) || path.match(/^\/(ko|en)\//);
    if (match) {
      currentLang = match[1];
    }
  });

  function handleChange(event: Event) {
    const target = event.target as HTMLSelectElement;
    const lang = target.value;

    const currentPath = page.url.pathname;

    // 경로에 /main/ 언어 prefix가 있는 경우 (예: /main/ko/about)
    if (currentPath.match(/^\/main\/(ko|en)\//)) {
      const newPath = currentPath.replace(
        /^\/main\/(ko|en)\//,
        `/main/${lang}/`,
      );
      window.location.href = newPath;
    }
    // 경로에 언어만 있는 경우 (예: /ko/about)
    else if (currentPath.match(/^\/(ko|en)\//)) {
      const newPath = currentPath.replace(/^\/(ko|en)\//, `/${lang}/`);
      window.location.href = newPath;
    }
    // 루트 경로인 경우
    else if (currentPath === "/" || currentPath === "") {
      window.location.href = "/main/ko/";
    }
    // 그 외 (예: /main 또는 /about)
    else {
      let newPath = currentPath;
      if (newPath.startsWith("/main")) {
        newPath = newPath.replace("/main", "");
      }

      window.location.href = `/${lang}${newPath}`;
    }
  }
</script>

<div class="language-dropdown">
  <select
    value={currentLang}
    onchange={handleChange}
    class="px-3 py-2 border border-secondary-200 rounded-lg text-sm bg-white text-secondary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent cursor-pointer"
  >
    <option value="ko">한국어</option>
    <option value="en">English</option>
  </select>
</div>

<style>
  .language-dropdown select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    appearance: none;
  }
</style>
