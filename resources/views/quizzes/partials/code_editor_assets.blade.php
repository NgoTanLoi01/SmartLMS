<style>
    .sl-code-editor { overflow:hidden; color:#dbeafe; background:#0f172a; border:1px solid #26344d; border-radius:12px; box-shadow:inset 0 1px 0 rgba(255,255,255,.03); }
    .sl-code-editor__toolbar { min-height:42px; display:flex; justify-content:space-between; align-items:center; gap:12px; padding:8px 12px; color:#a9bad3; background:#162238; border-bottom:1px solid #293750; font-size:.72rem; font-weight:750; }
    .sl-code-editor__meta { display:flex; align-items:center; gap:8px; }
    .sl-code-editor__language { padding:3px 8px; color:#93c5fd; background:#1e3a63; border-radius:999px; font-size:.65rem; letter-spacing:.04em; }
    .sl-code-editor__format { border:1px solid #40516e; border-radius:7px; padding:5px 9px; color:#c9d8ec; background:#202e46; font:inherit; cursor:pointer; transition:.15s; }
    .sl-code-editor__format:hover { color:#fff; background:#2c4265; border-color:#6584b5; }
    .sl-code-editor__stage { position:relative; height:400px; min-height:240px; resize:vertical; overflow:hidden; background:#0b1220; }
    .sl-code-editor--compact .sl-code-editor__stage { height:290px; }
    .sl-code-editor__highlight, .sl-code-editor__source { position:absolute; inset:0; width:100%; height:100%; margin:0; border:0; border-radius:0; padding:14px 16px 14px 0; overflow:auto; white-space:pre; tab-size:2; font:500 .84rem/1.68 'DM Mono', ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    .sl-code-editor__highlight { z-index:1; pointer-events:none; color:#dbeafe; background:transparent; }
    .sl-code-editor__highlight code { display:block; min-width:max-content; counter-reset:sl-code-line; }
    .sl-code-line { display:block; min-height:1.68em; padding-left:58px; padding-right:16px; counter-increment:sl-code-line; }
    .sl-code-line::before { content:counter(sl-code-line); display:inline-block; width:38px; margin-left:-50px; margin-right:12px; color:#52647f; text-align:right; user-select:none; }
    .sl-code-editor__source { z-index:2; padding-left:58px; color:transparent; caret-color:#f8fafc; background:transparent; outline:0; resize:none; -webkit-text-fill-color:transparent; }
    .sl-code-editor__source::selection { background:rgba(59,130,246,.42); }
    .sl-code-editor:focus-within { border-color:#4c8df6; box-shadow:0 0 0 3px rgba(59,130,246,.16); }
    .sl-token-comment { color:#6f859f; font-style:italic; }
    .sl-token-punctuation { color:#7dd3fc; }
    .sl-token-tag { color:#fb7185; }
    .sl-token-attr { color:#fbbf24; }
    .sl-token-string { color:#86efac; }
    .sl-token-selector { color:#c4b5fd; }
    .sl-token-property { color:#67e8f9; }
    @media (forced-colors:active) { .sl-code-editor__highlight { display:none; } .sl-code-editor__source { color:CanvasText; -webkit-text-fill-color:CanvasText; background:Canvas; } }
    @media (max-width:767.98px) { .sl-code-editor__stage { height:330px; } .sl-code-editor--compact .sl-code-editor__stage { height:260px; } }
</style>

<script>
(() => {
    if (window.SmartLmsCodeEditor) return;

    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const placeholders = source => {
        const values = [];
        return {
            store: html => `\u0000${values.push(html) - 1}\u0000`,
            restore: value => value.replace(/\u0000(\d+)\u0000/g, (_, index) => values[Number(index)]),
            source,
        };
    };

    const highlightTag = rawTag => {
        if (rawTag.startsWith('<!--')) return `<span class="sl-token-comment">${escapeHtml(rawTag)}</span>`;
        let escaped = escapeHtml(rawTag);
        const protectedValues = placeholders(escaped);
        escaped = escaped.replace(/(&quot;[\s\S]*?&quot;|&#039;[\s\S]*?&#039;)/g, value => protectedValues.store(`<span class="sl-token-string">${value}</span>`));
        escaped = escaped.replace(/([:@a-zA-Z_][:\w.-]*)(\s*=)/g, '<span class="sl-token-attr">$1</span>$2');
        escaped = escaped.replace(/^(&lt;\/?)([a-zA-Z][\w:-]*)/, '<span class="sl-token-punctuation">$1</span><span class="sl-token-tag">$2</span>');
        escaped = escaped.replace(/(\/?&gt;)$/, '<span class="sl-token-punctuation">$1</span>');
        return protectedValues.restore(escaped);
    };

    const highlightCss = css => {
        let escaped = escapeHtml(css);
        const protectedValues = placeholders(escaped);
        escaped = escaped.replace(/\/\*[\s\S]*?\*\//g, value => protectedValues.store(`<span class="sl-token-comment">${value}</span>`));
        escaped = escaped.replace(/(&quot;[\s\S]*?&quot;|&#039;[\s\S]*?&#039;)/g, value => protectedValues.store(`<span class="sl-token-string">${value}</span>`));
        escaped = escaped.replace(/(^|[}\n])(\s*)([^@{}\n][^{}]*?)(\s*)(?=\{)/g, (_, prefix, space, selector, tail) => `${prefix}${space}${protectedValues.store(`<span class="sl-token-selector">${selector}</span>`)}${tail}`);
        escaped = escaped.replace(/([a-zA-Z-]+)(\s*:)/g, (_, property, colon) => protectedValues.store(`<span class="sl-token-property">${property}</span>${colon}`));
        return protectedValues.restore(escaped);
    };

    const highlight = code => {
        const source = String(code ?? '');
        const tagPattern = /(<!--[\s\S]*?-->|<[^>]*>)/g;
        let cursor = 0;
        let inStyle = false;
        let html = '';
        let match;
        while ((match = tagPattern.exec(source)) !== null) {
            const text = source.slice(cursor, match.index);
            html += inStyle ? highlightCss(text) : escapeHtml(text);
            const tag = match[0];
            const closingStyle = /^<\s*\/\s*style\b/i.test(tag);
            if (closingStyle) inStyle = false;
            html += highlightTag(tag);
            if (/^<\s*style\b/i.test(tag) && ! closingStyle) inStyle = true;
            cursor = match.index + tag.length;
        }
        html += inStyle ? highlightCss(source.slice(cursor)) : escapeHtml(source.slice(cursor));
        return html.split('\n').map(line => `<span class="sl-code-line">${line || ' '}</span>`).join('');
    };

    const formatCss = css => {
        let output = '';
        let buffer = '';
        let indent = 0;
        let quote = null;
        let comment = false;
        const flush = () => {
            const value = buffer.replace(/\s+/g, ' ').trim();
            if (value) output += `${'  '.repeat(indent)}${value}`;
            buffer = '';
        };
        for (let index = 0; index < css.length; index++) {
            const char = css[index];
            const next = css[index + 1] || '';
            if (comment) {
                buffer += char;
                if (char === '*' && next === '/') { buffer += '/'; index++; comment = false; }
                continue;
            }
            if (quote) {
                buffer += char;
                if (char === '\\' && next) { buffer += next; index++; }
                else if (char === quote) quote = null;
                continue;
            }
            if (char === '/' && next === '*') { buffer += '/*'; index++; comment = true; continue; }
            if (char === '"' || char === "'") { quote = char; buffer += char; continue; }
            if (char === '{') { flush(); output = `${output.trimEnd()} {\n`; indent++; continue; }
            if (char === ';') { flush(); output = `${output.trimEnd()};\n`; continue; }
            if (char === '}') { flush(); indent = Math.max(0, indent - 1); output = `${output.trimEnd()}\n${'  '.repeat(indent)}}\n`; continue; }
            buffer += char;
        }
        flush();
        return output.trim();
    };

    const format = code => {
        let source = String(code ?? '').replaceAll('\r\n', '\n').replaceAll('\r', '\n').trim();
        source = source.replace(/(<style\b[^>]*>)([\s\S]*?)(<\/style\s*>)/gi, (_, open, css, close) => `${open}\n${formatCss(css)}\n${close}`);
        const tokens = source.split(/(<!--[\s\S]*?-->|<![^>]*>|<[^>]+>)/g).filter(Boolean);
        const voidTags = new Set(['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr']);
        const lines = [];
        let indent = 0;
        let inStyle = false;
        tokens.forEach(token => {
            token = token.trim();
            if (!token) return;
            if (/^<\s*\/\s*style\b/i.test(token)) inStyle = false;
            if (/^<\s*\//.test(token)) indent = Math.max(0, indent - 1);
            token.split('\n').map(line => inStyle ? line.trimEnd() : line.trim()).filter(line => line.trim() !== '').forEach(line => lines.push(`${'  '.repeat(indent)}${line}`));
            const opening = token.match(/^<\s*([a-z0-9:-]+)/i);
            if (opening && !token.trim().endsWith('/>') && !voidTags.has(opening[1].toLowerCase()) && !new RegExp(`<\\s*\\/\\s*${opening[1]}\\s*>\\s*$`, 'i').test(token)) {
                indent++;
                if (opening[1].toLowerCase() === 'style') inStyle = true;
            }
        });
        return lines.join('\n');
    };

    const mountEditor = editor => {
        if (editor.dataset.codeReady === '1') return;
        const source = editor.querySelector('[data-code-source]');
        const output = editor.querySelector('[data-code-highlight]');
        if (!source || !output) return;
        editor.dataset.codeReady = '1';
        const render = () => { output.innerHTML = highlight(source.value); };
        source.addEventListener('input', render);
        source.addEventListener('scroll', () => {
            output.parentElement.scrollTop = source.scrollTop;
            output.parentElement.scrollLeft = source.scrollLeft;
        });
        source.addEventListener('keydown', event => {
            if (event.key !== 'Tab') return;
            event.preventDefault();
            const start = source.selectionStart;
            const end = source.selectionEnd;
            source.setRangeText('  ', start, end, 'end');
            source.dispatchEvent(new Event('input', {bubbles:true}));
        });
        editor.querySelector('[data-format-code]')?.addEventListener('click', () => {
            const cursor = source.selectionStart;
            source.value = format(source.value);
            source.setSelectionRange(Math.min(cursor, source.value.length), Math.min(cursor, source.value.length));
            source.dispatchEvent(new Event('input', {bubbles:true}));
            source.focus();
        });
        render();
    };

    const mount = (scope = document) => scope.querySelectorAll('[data-code-editor]').forEach(mountEditor);
    window.SmartLmsCodeEditor = {mount, format, highlight};
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', () => mount()) : mount();
})();
</script>
