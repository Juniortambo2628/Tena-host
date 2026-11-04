/**
 * Shrinks a photo in the browser before it is uploaded, so a full-size
 * phone photo (4000px, 5-10 MB) fits under the server's upload limit
 * (PHP's default is 2 MB) and the public pages load faster.
 *
 * SVGs, GIFs (may be animated), videos and images that are already small
 * are returned unchanged. Throws a readable error when the browser cannot
 * open the file (an iPhone HEIC photo outside Safari, for example).
 */
const MAX_SIDE = 2400;
const TARGET_BYTES = 1.5 * 1024 * 1024;
const RESIZABLE = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];

function loadImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('This photo couldn’t be opened. iPhone photos: share or export it as a JPEG first, then upload that.'));
        };
        img.src = url;
    });
}

const toBlob = (canvas, type, quality) => new Promise(resolve => canvas.toBlob(resolve, type, quality));

export default async function shrinkImage(file) {
    const type = (file.type || '').toLowerCase();
    const heic = /\.(heic|heif)$/i.test(file.name) || type.includes('heic') || type.includes('heif');
    if (!RESIZABLE.includes(type) && !heic) return file;

    const img = await loadImage(file);
    const scale = Math.min(1, MAX_SIDE / Math.max(img.naturalWidth, img.naturalHeight));
    if (scale === 1 && file.size <= TARGET_BYTES && !heic) return file;

    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.naturalWidth * scale);
    canvas.height = Math.round(img.naturalHeight * scale);
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);

    // WebP keeps transparency (logos, screenshots); fall back to JPEG where it isn't supported.
    let blob = null;
    let outType = 'image/webp';
    for (const quality of [0.86, 0.78, 0.68]) {
        blob = await toBlob(canvas, 'image/webp', quality);
        if (!blob || blob.type !== 'image/webp') {
            outType = 'image/jpeg';
            blob = await toBlob(canvas, 'image/jpeg', quality);
        }
        if (blob && blob.size <= TARGET_BYTES) break;
    }
    if (!blob) return file;

    const base = file.name.replace(/\.[^.]+$/, '') || 'photo';
    return new File([blob], `${base}.${outType === 'image/webp' ? 'webp' : 'jpg'}`, { type: outType, lastModified: Date.now() });
}
