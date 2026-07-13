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

/**
 * Rasterize a color and read the pixel back as [r, g, b, a]. This is the only
 * reliable way to concretize modern color spaces — a canvas's fillStyle
 * readback returns oklch() unchanged in some browsers, but *painting* it always
 * yields sRGB. Handles var(), oklch(), named, hex — anything the browser paints.
 */
function paintRead(color) {
    const ctx = document.createElement('canvas').getContext('2d');
    ctx.clearRect(0, 0, 1, 1);
    ctx.fillStyle = color;
    ctx.fillRect(0, 0, 1, 1);
    return ctx.getImageData(0, 0, 1, 1).data;
}

const asRgb = ([r, g, b, alpha]) =>
    alpha < 255
        ? `rgba(${r}, ${g}, ${b}, ${(alpha / 255).toFixed(3)})`
        : `rgb(${r}, ${g}, ${b})`;

/** Resolve any CSS color to a concrete, universally-supported rgb()/rgba(). */
function resolveColor(value) {
    if (!value) return value;
    // var() is theme-dependent, so don't cache it; hex/named/oklch are stable.
    const varDependent = value.includes('var(');
    if (!varDependent && resolveCache.has(value)) {
        return resolveCache.get(value);
    }

    let color = value;
    if (varDependent) {
        const probe = document.createElement('span');
        probe.style.color = value;
        probe.style.display = 'none';
        document.body.appendChild(probe);
        color = getComputedStyle(probe).color;
        probe.remove();
    }

    const out = asRgb(paintRead(color));
    if (!varDependent) resolveCache.set(value, out);
    return out;
}

const needsResolve = (v) =>
    typeof v === 'string' && (v.includes('var(') || v.includes('oklch'));

// Grayscale is a print treatment: dark text on light, marks desaturated to
// their luminance. Text is forced dark so it stays readable when a dark-theme
// chart is exported over a white/transparent background.
const DARK_INK = '#222222';

/** Desaturate a color to its Rec.709 luminance, preserving alpha. */
function toGray(color) {
    const [r, g, b, alpha] = paintRead(color);
    const lum = Math.round(0.2126 * r + 0.7152 * g + 0.0722 * b);
    return asRgb([lum, lum, lum, alpha]);
}

function inlineColors(root, grayscale) {
    root.querySelectorAll('*').forEach((el) => {
        const isText = el.tagName && el.tagName.toLowerCase() === 'text';

        ['fill', 'stroke'].forEach((attr) => {
            const v = el.getAttribute(attr);
            if (!v || v === 'none') return;

            if (grayscale) {
                const base = needsResolve(v) ? resolveColor(v) : v;
                el.setAttribute(
                    attr,
                    isText && attr === 'fill' ? DARK_INK : toGray(base),
                );
            } else if (needsResolve(v)) {
                el.setAttribute(attr, resolveColor(v));
            }
        });

        const style = el.getAttribute('style');
        if (needsResolve(style)) {
            el.setAttribute(
                'style',
                style.replace(/var\([^)]+\)|oklch\([^)]+\)/g, (m) =>
                    grayscale ? toGray(resolveColor(m)) : resolveColor(m),
                ),
            );
        }
    });
}

const SVG_NS = 'http://www.w3.org/2000/svg';

/** A self-contained clone of the chart: concrete colors, its own font. */
function prepareClone(svg, { grayscale = false } = {}) {
    const rect = svg.getBoundingClientRect();
    const width = Math.max(1, Math.round(rect.width));
    const height = Math.max(1, Math.round(rect.height));

    const clone = svg.cloneNode(true);
    clone.setAttribute('width', width);
    clone.setAttribute('height', height);
    clone.setAttribute('xmlns', SVG_NS);
    clone.style.fontFamily = getComputedStyle(document.body).fontFamily;
    inlineColors(clone, grayscale);

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
    { background = '#ffffff', scale = 2, grayscale = false } = {},
) {
    const { clone, width, height } = prepareClone(svg, { grayscale });
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
    { background = 'transparent', grayscale = false } = {},
) {
    const { clone, width, height } = prepareClone(svg, { grayscale });
    if (background && background !== 'transparent') {
        clone.insertBefore(
            backgroundRect(width, height, background),
            clone.firstChild,
        );
    }
    const xml = new XMLSerializer().serializeToString(clone);
    triggerDownload(new Blob([xml], { type: 'image/svg+xml' }), filename);
}
