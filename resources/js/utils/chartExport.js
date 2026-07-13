/**
 * Export an on-screen SVG chart (Recharts or hand-built) as a PNG or SVG. The
 * chart's colors come from theme CSS variables, so we clone the SVG and inline
 * every var()/oklch() color to a concrete value first — that makes the SVG
 * self-contained (renders in any editor) and lets a canvas rasterize it.
 *
 * `background` is a color string, or the literal 'transparent' to leave it clear
 * (useful for overlaying on a paper/slide). `scale` sets the PNG resolution
 * (2× = crisp); SVG is vector and ignores it.
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

const SVG_NS = 'http://www.w3.org/2000/svg';

/** A self-contained clone of the chart: concrete colors, its own font. */
function prepareClone(svg) {
    const rect = svg.getBoundingClientRect();
    const width = Math.max(1, Math.round(rect.width));
    const height = Math.max(1, Math.round(rect.height));

    const clone = svg.cloneNode(true);
    clone.setAttribute('width', width);
    clone.setAttribute('height', height);
    clone.setAttribute('xmlns', SVG_NS);
    clone.style.fontFamily = getComputedStyle(document.body).fontFamily;
    inlineColors(clone);

    return { clone, width, height };
}

function backgroundRect(width, height, background) {
    const rect = document.createElementNS(SVG_NS, 'rect');
    rect.setAttribute('x', 0);
    rect.setAttribute('y', 0);
    rect.setAttribute('width', width);
    rect.setAttribute('height', height);
    rect.setAttribute('fill', resolveColor(background));
    return rect;
}

function triggerDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    URL.revokeObjectURL(url);
}

export function downloadChartPng(
    svg,
    filename,
    { background = '#ffffff', scale = 2 } = {},
) {
    const { clone, width, height } = prepareClone(svg);
    const xml = new XMLSerializer().serializeToString(clone);
    const src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(xml);

    const img = new Image();
    img.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = width * scale;
        canvas.height = height * scale;
        const ctx = canvas.getContext('2d');
        ctx.scale(scale, scale);
        if (background && background !== 'transparent') {
            ctx.fillStyle = resolveColor(background);
            ctx.fillRect(0, 0, width, height);
        }
        ctx.drawImage(img, 0, 0, width, height);
        canvas.toBlob((blob) => {
            if (blob) triggerDownload(blob, filename);
        }, 'image/png');
    };
    img.src = src;
}

export function downloadChartSvg(
    svg,
    filename,
    { background = 'transparent' } = {},
) {
    const { clone, width, height } = prepareClone(svg);
    if (background && background !== 'transparent') {
        clone.insertBefore(
            backgroundRect(width, height, background),
            clone.firstChild,
        );
    }
    const xml = new XMLSerializer().serializeToString(clone);
    triggerDownload(new Blob([xml], { type: 'image/svg+xml' }), filename);
}
