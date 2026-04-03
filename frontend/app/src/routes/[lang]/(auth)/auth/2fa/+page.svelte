<script lang="ts">
	const skin = import.meta.env.VITE_AUTH_SKIN || "basic";
</script>

{#await import(`$lib/skin/auth/${skin}/TwoFactor.svelte`) then module}
	<module.default />
{:catch error}
	<div class="p-4 text-center text-destructive">
		Failed to load TwoFactor component skin: {skin}<br />
		{error.message}
	</div>
{/await}
