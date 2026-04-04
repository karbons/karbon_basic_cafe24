import { UPLOAD_STORAGE, AWS_S3_URL, API_BASE_URL } from '$lib/config';

export function resolveImageUrl(path: string | undefined): string {
    if (!path) return '';

    if (path.startsWith('http://') || path.startsWith('https://')) {
        if (UPLOAD_STORAGE === 's3' && path.includes('/data/')) {
            const relativePart = path.split('/data/').pop();
            if (relativePart) {
                return `${AWS_S3_URL.replace(/\/$/, '')}/data/${relativePart}`;
            }
        }
        return path;
    }

    if (UPLOAD_STORAGE === 's3') {
        const baseUrl = AWS_S3_URL.replace(/\/$/, '');
        const relativePath = path.startsWith('/') ? path : `/${path}`;
        return `${baseUrl}${relativePath}`;
    }

    const serverUrl = API_BASE_URL.replace(/\/api$/, '');
    const relativePath = path.startsWith('/') ? path : `/${path}`;
    return `${serverUrl}${relativePath}`;
}

export function resolveThumbnailUrl(path: string | undefined): string {
    if (!path) return '';
    
    const parts = path.split('/');
    const filename = parts.pop();
    const folder = parts.join('/');
    const thumbPath = `${folder}/thumbnails/${filename}`;
    
    return resolveImageUrl(thumbPath);
}
