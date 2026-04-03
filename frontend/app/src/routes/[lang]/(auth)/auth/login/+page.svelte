<script lang="ts">
	const skin = import.meta.env.VITE_AUTH_SKIN || "basic";
</script>

{#await import(`$lib/skin/auth/${skin}/Login.svelte`) then module}
	<module.default />
{:catch error}
	<div class="p-4 text-center text-destructive">
		Failed to load Login component skin: {skin}<br />
		{error.message}
	</div>
{/await}
