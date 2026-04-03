<script lang="ts">
    import { api } from '$lib/api/client';

    export let onUploadComplete: (url: string) => void = () => {};
    export let folder = 'common';
    export let maxConcurrency = 3;
    export let chunkSize = 5 * 1024 * 1024; // 5MB

    let files: FileList | null = null;
    let uploading = false;
    let overallProgress = 0;
    let statusMessage = '';

    async function uploadFile(file: File) {
        uploading = true;
        overallProgress = 0;
        statusMessage = '업로드 초기화 중...';

        try {
            // 1. Init
            const initRes = await api.post('/upload/init', {
                fileName: `${folder}/${Date.now()}_${file.name}`,
                mimeType: file.type
            });
            const { uploadId } = initRes.data;
            const fileName = `${folder}/${Date.now()}_${file.name}`;

            // 2. Chunking
            const totalChunks = Math.ceil(file.size / chunkSize);
            const chunks = [];
            for (let i = 0; i < totalChunks; i++) {
                chunks.push({
                    partNumber: i + 1,
                    blob: file.slice(i * chunkSize, (i + 1) * chunkSize)
                });
            }

            const parts: { partNumber: number, etag: string }[] = [];
            let completedChunks = 0;

            // Parallel upload with concurrency limit
            const queue = [...chunks];
            const workers = Array(Math.min(maxConcurrency, queue.length)).fill(null).map(async () => {
                while (queue.length > 0) {
                    const chunk = queue.shift()!;
                    const formData = new FormData();
                    formData.append('uploadId', uploadId);
                    formData.append('partNumber', chunk.partNumber.toString());
                    formData.append('fileName', fileName);
                    formData.append('chunk', chunk.blob);

                    const res = await api.post('/upload/chunk', formData);
                    parts.push({
                        partNumber: chunk.partNumber,
                        etag: res.data.etag
                    });

                    completedChunks++;
                    overallProgress = Math.round((completedChunks / totalChunks) * 100);
                    statusMessage = `업로드 중... (${overallProgress}%)`;
                }
            });

            await Promise.all(workers);

            // 3. Complete
            statusMessage = '파일 결합 중...';
            const completeRes = await api.post('/upload/complete', {
                uploadId,
                fileName,
                parts: JSON.stringify(parts)
            });

            statusMessage = '업로드 완료!';
            onUploadComplete(completeRes.data.url);
        } catch (error: any) {
            console.error(error);
            statusMessage = `업로드 실패: ${error.message}`;
        } finally {
            uploading = false;
        }
    }

    function handleFileChange(e: Event) {
        const target = e.target as HTMLInputElement;
        if (target.files && target.files.length > 0) {
            uploadFile(target.files[0]);
        }
    }
</script>

<div class="flex flex-col gap-4 p-4 border rounded-lg bg-white shadow-sm">
    <div class="flex items-center justify-between">
        <label class="block text-sm font-medium text-gray-700">파일 업로드</label>
        {#if uploading}
            <span class="text-xs text-blue-600 font-semibold">{statusMessage}</span>
        {/if}
    </div>

    <input
        type="file"
        on:change={handleFileChange}
        disabled={uploading}
        class="block w-full text-sm text-gray-500
               file:mr-4 file:py-2 file:px-4
               file:rounded-full file:border-0
               file:text-sm file:font-semibold
               file:bg-blue-50 file:text-blue-700
               hover:file:bg-blue-100
               disabled:opacity-50 disabled:cursor-not-allowed"
    />

    {#if uploading}
        <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: {overallProgress}%"></div>
        </div>
    {/if}
</div>
