/**
 * Download an on-screen SVG chart (Recharts or hand-built) as a PNG. The chart's
 * colors come from theme CSS variables, which a canvas rasterizer can't resolve,
 * so we clone the SVG and inline every var()/oklch() color to a concrete value
 * first. Exported at 2× for crisp raster, over the chart's own background so it
 * stays readable in whichever theme the user is viewing.
 */

const resolveCache = new Map();

/** Resolve any CSS color (var(), oklch(), named…) to a canvas-safe hex/rgb. */
function resolveColor(value) {
    if (!value) return value;
    if (resolveCache.has(value)) return resolveCache.get(value);

    const probe = document.createElement('span');
    probe.style.color = value;
    probe.style.display = 'none';
    document.body.appendChild(probe);
    const computed = getComputedStyle(probe).color;
    probe.remove();

    const ctx = document.createElement('canvas').getContext('2d');
    ctx.fillStyle = computed || value;
    const out = ctx.fillStyle;
    resolveCache.set(value, out);
    return out;
}

const needsResolve = (v) =>
    typeof v === 'string' && (v.includes('var(') || v.includes('oklch'));

function inlineColors(root) {
    root.querySelectorAll('*').forEach((el) => {
        ['fill', 'stroke'].forEach((attr) => {
            const v = el.getAttribute(attr);
            if (needsResolve(v)) el.setAttribute(attr, resolveColor(v));
        });
        const style = el.getAttribute('style');
        if (needsResolve(style)) {
            el.setAttribute(
                'style',
                style.replace(/var\([^)]+\)|oklch\([^)]+\)/g, (m) =>
                    resolveColor(m),
                ),
            );
        }
    });
}

export function downloadChartPng(
    svg,
    filename,
    { background = '#ffffff', scale = 2 } = {},
) {
    const rect = svg.getBoundingClientRect();
    const width = Math.max(1, Math.round(rect.width));
    const height = Math.max(1, Math.round(rect.height));

    const clone = svg.cloneNode(true);
    clone.setAttribute('width', width);
    clone.setAttribute('height', height);
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    clone.style.fontFamily = getComputedStyle(document.body).fontFamily;
    inlineColors(clone);

    const xml = new XMLSerializer().serializeToString(clone);
    const src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(xml);

    const img = new Image();
    img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = width * scale;
        canvas.height = height * scale;
        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
        ctx.fillStyle = resolveColor(background);
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(img, 0, 0, width, height);
        canvas.toBlob((blob) => {
            if (!blob) return;
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.click();
            URL.revokeObjectURL(url);
        }, 'image/png');
    };
    img.src = src;
}
