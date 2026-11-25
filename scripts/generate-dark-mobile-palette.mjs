#!/usr/bin/env node
/*
 * Generates resources/css/dark-mobile-generated.css: the dark mobile mapping (.pk-dark-scope, max-width 767px)
 * for light Tailwind colour classes used by the screens listed in ENTRY_FILES and the components they import.
 *
 * Hand-written rules in app.css (".pk-dark-scope ..." block) always win: classes already mapped there are skipped,
 * and the generated file is imported before that block. Re-run after adding new colour classes to these screens:
 *
 *     node scripts/generate-dark-mobile-palette.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const jsRoot = path.join(root, 'resources/js');
const appCssPath = path.join(root, 'resources/css/app.css');
const outPath = path.join(root, 'resources/css/dark-mobile-generated.css');

const ENTRY_FILES = [
    'Pages/StudySessions/Show.vue',
    'Pages/StudySessions/Exam.vue',
    'Pages/StudySessions/ExamResult.vue',
    'Layouts/SessionZenLayout.vue',
    'Layouts/SessionExamLayout.vue',
];

// Components that keep their own design on dark (brand art, media frames) or are not part of the app shell.
const SKIP_COMPONENTS = new Set([
    'Components/Auth/AuthTopNavigation.vue',
    'Components/Auth/LoginDrawer.vue',
    'Components/Auth/RegisterDrawer.vue',
    'Components/MobileBottomNavigation.vue',
]);

// Tailwind v3 default palette (only families used by the app).
const PALETTE = {
    white: '#ffffff', black: '#000000',
    slate: { 50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a', 950: '#020617' },
    gray: { 50: '#f9fafb', 100: '#f3f4f6', 200: '#e5e7eb', 300: '#d1d5db', 400: '#9ca3af', 500: '#6b7280', 600: '#4b5563', 700: '#374151', 800: '#1f2937', 900: '#111827', 950: '#030712' },
    zinc: { 50: '#fafafa', 100: '#f4f4f5', 200: '#e4e4e7', 300: '#d4d4d8', 400: '#a1a1aa', 500: '#71717a', 600: '#52525b', 700: '#3f3f46', 800: '#27272a', 900: '#18181b', 950: '#09090b' },
    neutral: { 50: '#fafafa', 100: '#f5f5f5', 200: '#e5e5e5', 300: '#d4d4d4', 400: '#a3a3a3', 500: '#737373', 600: '#525252', 700: '#404040', 800: '#262626', 900: '#171717', 950: '#0a0a0a' },
    stone: { 50: '#fafaf9', 100: '#f5f5f4', 200: '#e7e5e4', 300: '#d6d3d1', 400: '#a8a29e', 500: '#78716c', 600: '#57534e', 700: '#44403c', 800: '#292524', 900: '#1c1917', 950: '#0c0a09' },
    red: { 50: '#fef2f2', 100: '#fee2e2', 200: '#fecaca', 300: '#fca5a5', 400: '#f87171', 500: '#ef4444', 600: '#dc2626', 700: '#b91c1c', 800: '#991b1b', 900: '#7f1d1d' },
    rose: { 50: '#fff1f2', 100: '#ffe4e6', 200: '#fecdd3', 300: '#fda4af', 400: '#fb7185', 500: '#f43f5e', 600: '#e11d48', 700: '#be123c', 800: '#9f1239', 900: '#881337' },
    orange: { 50: '#fff7ed', 100: '#ffedd5', 200: '#fed7aa', 300: '#fdba74', 400: '#fb923c', 500: '#f97316', 600: '#ea580c', 700: '#c2410c', 800: '#9a3412', 900: '#7c2d12' },
    amber: { 50: '#fffbeb', 100: '#fef3c7', 200: '#fde68a', 300: '#fcd34d', 400: '#fbbf24', 500: '#f59e0b', 600: '#d97706', 700: '#b45309', 800: '#92400e', 900: '#78350f' },
    yellow: { 50: '#fefce8', 100: '#fef9c3', 200: '#fef08a', 300: '#fde047', 400: '#facc15', 500: '#eab308', 600: '#ca8a04', 700: '#a16207', 800: '#854d0e', 900: '#713f12' },
    green: { 50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac', 400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d', 800: '#166534', 900: '#14532d' },
    emerald: { 50: '#ecfdf5', 100: '#d1fae5', 200: '#a7f3d0', 300: '#6ee7b7', 400: '#34d399', 500: '#10b981', 600: '#059669', 700: '#047857', 800: '#065f46', 900: '#064e3b' },
    sky: { 50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd', 300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9', 600: '#0284c7', 700: '#0369a1', 800: '#075985', 900: '#0c4a6e' },
    blue: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' },
};

// Dark tokens (chess.com/home, see app.css .pk-* components).
const T = {
    panel: 'rgb(0 0 0 / 20%)',
    track: 'rgb(255 255 255 / 10%)',
    hover: 'rgb(255 255 255 / 6%)',
    primary: '#81b64c',
    primaryGradient: 'linear-gradient(#81b64c, #5d9948)',
    text: 'rgb(255 255 255 / 85%)',
    textSecondary: 'rgb(255 255 255 / 72%)',
    textMuted: 'rgb(255 255 255 / 50%)',
    line: 'rgb(255 255 255 / 10%)',
    lineStrong: 'rgb(255 255 255 / 20%)',
    tint: {
        green: { bg: 'rgb(129 182 76 / 15%)', text: '#81b64c', line: 'rgb(129 182 76 / 45%)' },
        red: { bg: 'rgb(224 40 40 / 15%)', text: '#ff8a8a', line: 'rgb(255 138 138 / 35%)' },
        amber: { bg: 'rgb(255 201 74 / 12%)', text: '#ffc94a', line: 'rgb(255 201 74 / 35%)' },
        blue: { bg: 'rgb(122 184 255 / 12%)', text: '#81b64c', line: 'rgb(129 182 76 / 45%)' },
    },
};

const COLOR_RE = /(?<![\w\-[])((?:[a-z-]+:)*)(bg|text|border(?:-[trblxy])?|ring|divide|outline|decoration)-(\[#[0-9a-fA-F]{3,8}\]|white|black|(?:slate|gray|zinc|neutral|stone|red|rose|orange|amber|yellow|green|emerald|sky|blue)-\d{2,3})(\/\d{1,3})?(?![\w\-\]])/g;
const STATE_VARIANTS = { hover: ':hover', focus: ':focus', 'focus-visible': ':focus-visible', active: ':active', disabled: ':disabled' };
const ALWAYS_ON_VARIANTS = new Set(['max-md', 'max-sm', 'max-lg', 'max-xl']);

function collectFiles() {
    const seen = new Set();
    const queue = ENTRY_FILES.map((file) => path.join(jsRoot, file));
    while (queue.length) {
        const file = queue.shift();
        if (seen.has(file) || !fs.existsSync(file)) continue;
        seen.add(file);
        const source = fs.readFileSync(file, 'utf8');
        for (const match of source.matchAll(/from\s+'@\/(Components\/[^']+\.vue)'/g)) {
            if (!SKIP_COMPONENTS.has(match[1])) queue.push(path.join(jsRoot, match[1]));
        }
    }
    return [...seen];
}

function hexToRgb(hex) {
    let h = hex.replace('#', '');
    if (h.length === 3) h = [...h].map((c) => c + c).join('');
    return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16) / 255);
}

function hsl([r, g, b]) {
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const l = (max + min) / 2;
    if (max === min) return { h: 0, s: 0, l, chroma: 0 };
    const d = max - min;
    const s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    let h;
    if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) * 60;
    else if (max === g) h = ((b - r) / d + 2) * 60;
    else h = ((r - g) / d + 4) * 60;
    return { h, s, l, chroma: d };
}

function family({ h, s, chroma }) {
    // Near-whites like #fbfaf7 have a high HSL saturation but read as neutral, so judge by chroma too.
    if (s < 0.3 || chroma < 0.04) return 'neutral';
    if (h >= 75 && h < 170) return 'green';
    if (h >= 170 && h < 260) return 'blue';
    if (h >= 30 && h < 75) return 'amber';
    if (h < 30 || h >= 330) return 'red';
    return 'neutral';
}

function resolveHex(color) {
    if (color.startsWith('[#')) return color.slice(1, -1);
    if (color === 'white' || color === 'black') return PALETTE[color];
    const [name, shade] = color.split('-');
    return PALETTE[name]?.[shade] ?? null;
}

// Returns CSS declarations for one utility, or null to leave it unchanged.
function mapDeclarations(utility, color, alpha) {
    const hex = resolveHex(color);
    if (!hex) return null;
    const c = hsl(hexToRgb(hex));
    const fam = family(c);
    const translucent = alpha !== undefined && Number(alpha.slice(1)) < 100;

    if (utility === 'bg') {
        if (c.l >= 0.9) {
            if (translucent) return `background-color: ${T.panel};`;
            return fam === 'neutral' ? `background-color: ${T.panel};` : `background-color: ${T.tint[fam].bg};`;
        }
        if (c.l >= 0.78) return fam === 'neutral' ? `background-color: ${T.track};` : `background-color: ${T.tint[fam].bg};`;
        if (translucent) return null; // dark overlays, scrims
        if (c.l < 0.3 && (fam === 'neutral' || fam === 'blue')) {
            return `background-color: ${T.primary}; background-image: ${T.primaryGradient}; color: #fff;`;
        }
        if (fam === 'blue' && c.s > 0.6) {
            return `background-color: ${T.primary}; background-image: ${T.primaryGradient}; color: #fff;`;
        }
        return null; // saturated accents (yellow, green, red) read fine on dark
    }

    if (utility === 'text') {
        if (color === 'white' || (c.l >= 0.9 && fam === 'neutral')) return null;
        if (c.l < 0.25) return `color: ${T.text};`;
        if (fam !== 'neutral') return `color: ${T.tint[fam].text};`;
        if (c.l < 0.42) return `color: ${T.textSecondary};`;
        return `color: ${T.textMuted};`;
    }

    // border*, ring, divide, outline, decoration
    let value;
    if (c.l >= 0.78 && fam === 'neutral') value = T.line;
    else if (c.l >= 0.78) value = T.tint[fam].line;
    else if (fam !== 'neutral') value = fam === 'blue' && c.s > 0.6 ? T.primary : T.tint[fam].line;
    else if (c.l >= 0.45) value = T.lineStrong;
    else value = T.primary; // dark outlines mark the selected item
    if (translucent && (color === 'white' || color === 'black' || c.l >= 0.78)) value = T.line;
    if (utility === 'ring') return `--tw-ring-color: ${value};`;
    if (utility === 'outline') return `outline-color: ${value};`;
    if (utility === 'decoration') return `text-decoration-color: ${value};`;
    if (utility === 'divide') return `border-color: ${value};`;
    const side = utility.split('-')[1];
    if (!side) return `border-color: ${value};`;
    const sides = { t: ['top'], b: ['bottom'], l: ['left'], r: ['right'], x: ['left', 'right'], y: ['top', 'bottom'] }[side];
    return sides.map((s) => `border-${s}-color: ${value};`).join(' ');
}

function escapeClass(name) {
    return name.replace(/([[\]#/:().,%!])/g, '\\$1');
}

function buildSelector(className, variants, utility) {
    let pseudo = '';
    for (const variant of variants) {
        if (ALWAYS_ON_VARIANTS.has(variant)) continue;
        if (!(variant in STATE_VARIANTS)) return null; // responsive (sm/md/lg) and other variants never apply on phones here
        pseudo += STATE_VARIANTS[variant];
    }
    const base = `.pk-dark-scope .${escapeClass(className)}${pseudo}`;
    return utility === 'divide' ? `${base} > :not([hidden]) ~ :not([hidden])` : base;
}

const appCss = fs.readFileSync(appCssPath, 'utf8');
const handwritten = appCss.slice(appCss.indexOf('Dark mobile theme for deeper screens'));
const files = collectFiles();
const rules = new Map();

for (const file of files) {
    const source = fs.readFileSync(file, 'utf8');
    for (const match of source.matchAll(COLOR_RE)) {
        const [className, prefix, utility, color, alpha] = match;
        if (rules.has(className)) continue;
        const variants = prefix ? prefix.slice(0, -1).split(':') : [];
        const selector = buildSelector(className, variants, utility);
        if (!selector) continue;
        if (handwritten.includes(`.pk-dark-scope .${escapeClass(className)}`)) continue;
        const declarations = mapDeclarations(utility, color, alpha);
        if (declarations) rules.set(className, { selector, declarations });
    }
}

const byDeclaration = new Map();
for (const { selector, declarations } of rules.values()) {
    if (!byDeclaration.has(declarations)) byDeclaration.set(declarations, []);
    byDeclaration.get(declarations).push(selector);
}

const body = [...byDeclaration.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([declarations, selectors]) => `    ${selectors.sort().join(',\n    ')} {\n        ${declarations.split('; ').map((d) => d.replace(/;?$/, ';')).join('\n        ')}\n    }`)
    .join('\n\n');

const header = `/* Generated by scripts/generate-dark-mobile-palette.mjs from ${files.length} files. Do not edit by hand. */\n`;
fs.writeFileSync(outPath, `${header}@media (max-width: 767px) {\n${body}\n}\n`);
console.log(`dark-mobile-generated.css: ${rules.size} classes from ${files.length} files`);
