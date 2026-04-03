<script>
	let adminInfo = $state({
		username: '',
		password: '',
		email: ''
	});

	let systemChecks = $state([
		{ name: 'Docker Engine', status: 'success', message: 'Running v24.0.5' },
		{ name: 'Port 80 (HTTP)', status: 'success', message: 'Available' },
		{ name: 'Port 443 (HTTPS)', status: 'success', message: 'Available' },
		{ name: 'Port 3306 (MySQL)', status: 'warning', message: 'Port occupied, will use 3307' },
		{ name: 'PHP 8.2', status: 'success', message: 'Detected' }
	]);

	let installing = $state(false);
	let progress = $state(0);
	let currentStep = $state('');

	const steps = [
		'Downloading Gnuboard5 core...',
		'Configuring Docker containers...',
		'Initializing Database...',
		'Generating .env files...',
		'Finalizing installation...'
	];

	async function startInstall() {
		if (!adminInfo.username || !adminInfo.password || !adminInfo.email) {
			alert('Please fill in all admin information.');
			return;
		}

		installing = true;
		for (let i = 0; i < steps.length; i++) {
			currentStep = steps[i];
			progress = ((i + 1) / steps.length) * 100;
			await new Promise((resolve) => setTimeout(resolve, 1500));
		}
		installing = false;
		alert('Installation completed successfully!');
	}
</script>

<div class="space-y-8">
	<section class="bg-white rounded-xl shadow-sm border p-8">
		<h2 class="text-2xl font-bold mb-2">Welcome to Karbon Builder</h2>
		<p class="text-gray-600">Complete the setup below to initialize your development environment.</p>
	</section>

	<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
		<section class="bg-white rounded-xl shadow-sm border p-6">
			<h3 class="text-lg font-semibold mb-4 flex items-center">
				<span class="mr-2">🔍</span> System Requirements
			</h3>
			<div class="space-y-4">
				{#each systemChecks as check}
					<div class="flex items-center justify-between p-3 rounded-lg border bg-gray-50">
						<div>
							<p class="font-medium text-sm">{check.name}</p>
							<p class="text-xs text-gray-500">{check.message}</p>
						</div>
						<div class="flex items-center">
							{#if check.status === 'success'}
								<span class="text-green-500 text-xs">● SUCCESS</span>
							{:else if check.status === 'warning'}
								<span class="text-yellow-500 text-xs">● WARNING</span>
							{:else}
								<span class="text-red-500 text-xs">● FAIL</span>
							{/if}
						</div>
					</div>
				{/each}
			</div>
		</section>

		<section class="bg-white rounded-xl shadow-sm border p-6">
			<h3 class="text-lg font-semibold mb-4 flex items-center">
				<span class="mr-2">👤</span> Admin Configuration
			</h3>
			<form onsubmit={(e) => { e.preventDefault(); startInstall(); }} class="space-y-4">
				<div>
					<label for="username" class="block text-sm font-medium text-gray-700">Admin Username</label>
					<input
						type="text"
						id="username"
						bind:value={adminInfo.username}
						placeholder="e.g. admin"
						class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
						required
					/>
				</div>
				<div>
					<label for="email" class="block text-sm font-medium text-gray-700">Admin Email</label>
					<input
						type="email"
						id="email"
						bind:value={adminInfo.email}
						placeholder="admin@example.com"
						class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
						required
					/>
				</div>
				<div>
					<label for="password" class="block text-sm font-medium text-gray-700">Admin Password</label>
					<input
						type="password"
						id="password"
						bind:value={adminInfo.password}
						placeholder="••••••••"
						class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
						required
					/>
				</div>
				<button
					type="submit"
					disabled={installing}
					class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-opacity"
				>
					{installing ? 'Installing...' : 'Start Installation'}
				</button>
			</form>
		</section>
	</div>

	{#if installing}
		<section class="bg-white rounded-xl shadow-sm border p-6">
			<div class="flex justify-between items-center mb-4">
				<h3 class="text-lg font-semibold">{currentStep}</h3>
				<span class="text-sm font-medium text-blue-600">{Math.round(progress)}%</span>
			</div>
			<div class="w-full bg-gray-200 rounded-full h-2.5">
				<div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: {progress}%"></div>
			</div>
		</section>
	{/if}
</div>
