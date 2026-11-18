// UX4G migration codemod for Blade views: Bootstrap buttons -> UX4G buttons, Bootstrap Icons -> Material Symbols.
// Usage: node scripts/ux4g-codemod.mjs [--write] <file-or-dir>...   (dry run by default; prints a per-file summary)
// Rules are documented in docs/UX4G-COMPONENT-MAPPING.md. Anything it cannot map safely is left alone and reported.
import fs from 'node:fs';
import path from 'node:path';

const BTN_VARIANT = {
    'btn-primary': 'ux4g-btn-primary',
    'btn-outline-primary': 'ux4g-btn-outline-primary',
    'btn-secondary': 'ux4g-btn-outline-neutral',
    'btn-outline-secondary': 'ux4g-btn-outline-neutral',
    'btn-light': 'ux4g-btn-outline-neutral',
    'btn-outline-light': 'ux4g-btn-outline-neutral',
    'btn-outline-dark': 'ux4g-btn-outline-neutral',
    'btn-danger': 'ux4g-btn-danger',
    'btn-outline-danger': 'ux4g-btn-outline-danger',
    'btn-link': 'ux4g-btn-text-primary',
};
// Module button classes that act as a variant. They are KEPT (JS hooks); their visual CSS is removed per module.
const BTN_ALIAS = {
    'mst-btn-submit': 'ux4g-btn-primary',
    'mst-btn-cancel': 'ux4g-btn-outline-neutral', // was red; Cancel is not destructive
};
const BTN_SIZE = { 'btn-sm': 'ux4g-btn-s', 'btn-lg': 'ux4g-btn-l' };
// Bootstrap classes that must stay Bootstrap (no UX4G equivalent, or owned by other chrome).
const BTN_SKIP = /^(btn-close|btn-check|btn-group.*|btn-toolbar|btn-(success|warning|info|dark)|btn-outline-(success|warning|info)|programme-dt-btn.*)$/;

const ICON = {
    'layout-three-columns': 'view_column', printer: 'print', download: 'download', 'exclamation-circle': 'error',
    'check-circle': 'check_circle', 'filetype-csv': 'csv', trash: 'delete', 'plus-lg': 'add', plus: 'add',
    'filetype-pdf': 'picture_as_pdf', 'file-earmark-spreadsheet': 'table_view', 'file-earmark-pdf': 'picture_as_pdf',
    'file-earmark-excel': 'table_view', pencil: 'edit', 'pencil-square': 'edit', 'file-earmark-text': 'description',
    shuffle: 'shuffle', 'info-circle': 'info', 'exclamation-lg': 'priority_high', 'arrow-counterclockwise': 'undo',
    eye: 'visibility', search: 'search', 'x-lg': 'close', x: 'close', 'arrow-left': 'arrow_back', save: 'save',
    'filter': 'filter_list', funnel: 'filter_list', 'three-dots': 'more_horiz', 'three-dots-vertical': 'more_vert',
    people: 'group', person: 'person', lock: 'lock', envelope: 'mail', 'calendar-event': 'event', calendar: 'calendar_month',
    upload: 'upload', 'arrow-repeat': 'refresh', 'check-lg': 'check', check: 'check', 'exclamation-triangle': 'warning',
    'x-circle': 'cancel', speedometer2: 'speed', 'person-badge': 'badge', calendar3: 'calendar_month', send: 'send',
    'send-fill': 'send', building: 'apartment', 'info-circle-fill': 'info', 'check-circle-fill': 'check_circle',
    'printer-fill': 'print', 'plus-circle': 'add_circle',
};

function convertButtons(src, report) {
    return src.replace(/class="([^"]*)"/g, (whole, cls) => {
        const tokens = cls.split(/\s+/).filter(Boolean);
        if (!tokens.includes('btn')) return whole;
        if (/\{\{[^}]*btn/.test(cls) || /@(if|class)/.test(cls)) { report.skipped.push(`dynamic: ${cls.slice(0, 70)}`); return whole; }
        if (tokens.some(t => BTN_SKIP.test(t))) return whole;
        const variant = tokens.find(t => BTN_VARIANT[t]);
        const alias = tokens.find(t => BTN_ALIAS[t]);
        if (!variant && !alias) { report.skipped.push(`no variant: ${cls.slice(0, 70)}`); return whole; }
        const size = tokens.find(t => BTN_SIZE[t]);
        const out = tokens.filter(t => t !== 'btn' && t !== variant && t !== size);
        out.unshift(variant ? BTN_VARIANT[variant] : BTN_ALIAS[alias], size ? BTN_SIZE[size] : 'ux4g-btn-m');
        report.buttons++;
        return `class="${out.join(' ')}"`;
    });
}

function convertIcons(src, report) {
    // <i class="bi bi-NAME [extra]" [attrs]></i>  ->  Material Symbols ligature, always aria-hidden.
    return src.replace(/<i class="bi bi-([a-z0-9-]+)((?: [^"]*)?)"([^>]*)>\s*<\/i>/g, (whole, name, extra, attrs) => {
        const glyph = ICON[name];
        if (!glyph) { report.unmapped.add(name); return whole; }
        report.icons++;
        const aria = /aria-hidden=/.test(attrs) ? attrs : `${attrs} aria-hidden="true"`;
        return `<i class="material-icons material-symbols-rounded sargam-icon-text${extra}"${aria}>${glyph}</i>`;
    });
}

const args = process.argv.slice(2);
const write = args.includes('--write');
const files = [];
const walk = p => fs.statSync(p).isDirectory() ? fs.readdirSync(p).forEach(f => walk(path.join(p, f))) : p.endsWith('.blade.php') && files.push(p);
args.filter(a => a !== '--write').forEach(walk);

const total = { files: 0, buttons: 0, icons: 0 };
for (const f of files) {
    const raw = fs.readFileSync(f, 'utf8');
    const report = { buttons: 0, icons: 0, skipped: [], unmapped: new Set() };
    const next = convertIcons(convertButtons(raw, report), report);
    if (next === raw && !report.skipped.length && !report.unmapped.size) continue;
    console.log(`${f}: ${report.buttons} buttons, ${report.icons} icons` +
        (report.unmapped.size ? ` | unmapped icons: ${[...report.unmapped].join(', ')}` : '') +
        (report.skipped.length ? `\n    skipped: ${report.skipped.join('\n    skipped: ')}` : ''));
    if (next !== raw) { total.files++; total.buttons += report.buttons; total.icons += report.icons; if (write) fs.writeFileSync(f, next); }
}
console.log(`${write ? 'WROTE' : 'DRY RUN'}: ${total.files} files, ${total.buttons} buttons, ${total.icons} icons`);
