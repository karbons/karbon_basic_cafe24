<script lang="ts">
    import { configStore } from "$lib/store";
import { base } from '$app/paths';
</script>

<footer
    class="bg-slate-50 border-t border-slate-200 mt-auto text-sm text-slate-600 hidden md:block"
>
    <!-- 상단 정책 링크 라인 -->
    <div class="border-b border-slate-200">
        <div
            class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap gap-4 md:gap-6 justify-center md:justify-start font-medium"
        >
            <a href="{base}/policy/terms" class="hover:text-primary">이용약관</a>
            <a href="{base}/policy/privacy" class="hover:text-primary font-bold"
                >개인정보처리방침</a
            >
            <!-- <a href="{base}/policy/email-refusal" class="hover:text-primary"
                >이메일무단수집거부</a
            > -->
        </div>
    </div>

    <!-- 하단 정보 영역 -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="space-y-4 md:space-y-6">
            <!-- 회사명 -->
            <h2 class="font-bold text-lg text-slate-800">
                {$configStore?.shop_company_name ||
                    $configStore?.cf_title ||
                    "Gnuboard Karbon"}
            </h2>

            <!-- 정보 나열 (PC: 가로 배치 / Mobile: 세로 스택) -->
            <div class="space-y-1 md:space-y-2">
                <div
                    class="flex flex-col md:flex-row md:flex-wrap md:gap-x-6 md:gap-y-1"
                >
                    <p>
                        <span class="font-medium">대표자:</span>
                        {$configStore?.shop_company_owner ||
                            $configStore?.cf_admin_name ||
                            "-"}
                    </p>
                    <p>
                        <span class="font-medium">개인정보관리책임자:</span>
                        {$configStore?.shop_info_name ||
                            $configStore?.cf_privacy_officer ||
                            "-"}
                    </p>
                    <p>
                        <span class="font-medium">사업자등록번호:</span>
                        {$configStore?.shop_saupja_no ||
                            $configStore?.cf_info1 ||
                            "-"}
                    </p>
                    <p>
                        <span class="font-medium">통신판매업신고번호:</span>
                        {$configStore?.shop_tongsin_no ||
                            $configStore?.cf_info2 ||
                            "-"}
                    </p>
                </div>

                <div
                    class="flex flex-col md:flex-row md:flex-wrap md:gap-x-6 md:gap-y-1"
                >
                    <p>
                        <span class="font-medium">주소:</span>
                        {#if $configStore?.shop_zip || $configStore?.shop_addr}
                            ({$configStore?.shop_zip || ""}) {$configStore?.shop_addr ||
                                ""}
                        {:else}
                            ({$configStore?.cf_zip || ""}) {$configStore?.cf_addr1 ||
                                ""}
                            {$configStore?.cf_addr2 || ""}
                        {/if}
                    </p>
                </div>

                <div
                    class="flex flex-col md:flex-row md:flex-wrap md:gap-x-6 md:gap-y-1"
                >
                    <p>
                        <span class="font-medium">TEL:</span>
                        <a
                            href="tel:{$configStore?.shop_tel ||
                                $configStore?.cf_tel}"
                            class="hover:text-primary"
                            >{$configStore?.shop_tel ||
                                $configStore?.cf_tel ||
                                "-"}</a
                        >
                    </p>
                    <p>
                        <span class="font-medium">FAX:</span>
                        {$configStore?.shop_fax || $configStore?.cf_fax || "-"}
                    </p>
                    <p>
                        <span class="font-medium">Email:</span>
                        <a
                            href="mailto:{$configStore?.shop_info_email ||
                                $configStore?.cf_admin_email}"
                            class="hover:text-primary"
                            >{$configStore?.shop_info_email ||
                                $configStore?.cf_admin_email ||
                                "-"}</a
                        >
                    </p>
                </div>

                {#if $configStore?.cf_info3}
                    <div>
                        <p>{$configStore.cf_info3}</p>
                    </div>
                {/if}
            </div>

            <!-- 카피라이트 -->
            <div class="pt-4 text-slate-400 text-xs">
                <p>
                    Copyright &copy; {new Date().getFullYear()}
                    {$configStore?.shop_company_name ||
                        $configStore?.cf_title ||
                        "Gnuboard Karbon"}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>
