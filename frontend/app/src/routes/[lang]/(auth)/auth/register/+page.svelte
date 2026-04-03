<script lang="ts">
	import type { PageData } from "./$types";

	let { data } = $props<{ data: PageData }>();
	const skin = import.meta.env.VITE_AUTH_SKIN || "basic";
</script>

{#await import(`$lib/skin/auth/${skin}/Register.svelte`) then module}
	<module.default {data} />
{:catch error}
	<div class="p-4 text-center text-destructive">
		Failed to load Register component skin: {skin}<br />
		{error.message}
	</div>
{/await}
