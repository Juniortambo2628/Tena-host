/**
 * Looks at an uploaded Trusted by picture in the browser and says what it is:
 *   { kind: 'shot' }               a tall phone screenshot (fills the phone)
 *   { kind: 'logo', color, ink }   anything else, treated as the client's logo;
 *                                  color is its main brand colour (or null)
 *   { kind: 'none' }               it didn't load
 */
const PORTRAIT = 1.3;

/** Readable text on a background: dark on light colours, white on dark. */
export function inkFor(hex) {
    const [r, g, b] = [1, 3, 5].map(i => parseInt(hex.slice(i, i + 2), 16) / 255);
    return 0.2126 * r + 0.7152 * g + 0.0722 * b > 0.6 ? '#1B1B1B' : '#FFFFFF';
}

/** The most common strong colour in a logo, skipping white, near-white and transparent pixels. */
function mainColor(img) {
    try {
        const size = 48;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(img, 0, 0, size, size);
        const { data } = ctx.getImageData(0, 0, size, size);
        const buckets = new Map();
        for (let i = 0; i < data.length; i += 4) {
            const [r, g, b, a] = [data[i], data[i + 1], data[i + 2], data[i + 3]];
            if (a < 200 || (r > 225 && g > 225 && b > 225)) continue;
            const key = `${r >> 5},${g >> 5},${b >> 5}`;
            const bucket = buckets.get(key) || { n: 0, r: 0, g: 0, b: 0 };
            bucket.n += 1; bucket.r += r; bucket.g += g; bucket.b += b;
            buckets.set(key, bucket);
        }
        const top = [...buckets.values()].sort((x, y) => y.n - x.n)[0];
        // Too few coloured pixels (a thin outline on white): keep the default look.
        if (!top || top.n < size * size * 0.04) return null;
        const hex = ['r', 'g', 'b'].map(c => Math.round(top[c] / top.n).toString(16).padStart(2, '0')).join('');
        return `#${hex}`;
    } catch {
        return null; // a picture from another site can't be read
    }
}

export default function pictureKind(src) {
    return new Promise(resolve => {
        const img = new Image();
        img.decoding = 'async';
        img.onload = () => {
            const { naturalWidth: w, naturalHeight: h } = img;
            if (w && h / w >= PORTRAIT) return resolve({ kind: 'shot' });
            const color = mainColor(img);
            resolve({ kind: 'logo', color, ink: color ? inkFor(color) : null });
        };
        img.onerror = () => resolve({ kind: 'none' });
        img.src = src;
    });
}
