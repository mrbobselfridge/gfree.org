<style>
    .twyxtco-code-textarea-shell {
        position: relative;
        width: 100%;
        height: 100%;
        min-height: inherit;
        overflow: hidden;
        border-radius: inherit;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.875rem;
        line-height: 1.55;
    }

    .twyxtco-code-textarea-highlight,
    textarea.twyxtco-code-textarea-input {
        width: 100%;
        margin: 0;
        overflow: auto;
        white-space: pre-wrap;
        word-break: normal;
        overflow-wrap: anywhere;
        tab-size: 4;
        font: inherit;
        line-height: inherit;
    }

    .twyxtco-code-textarea-highlight {
        position: absolute;
        inset: 0;
        pointer-events: none;
        padding: 0.625rem 0.75rem;
        color: rgb(55 65 81);
    }

    textarea.twyxtco-code-textarea-input {
        position: relative;
        z-index: 1;
        display: block;
        background: transparent !important;
        color: transparent !important;
        caret-color: rgb(17 24 39);
        -webkit-text-fill-color: transparent;
    }

    textarea.twyxtco-code-textarea-input::placeholder {
        color: rgb(156 163 175);
        -webkit-text-fill-color: rgb(156 163 175);
    }

    textarea.twyxtco-code-textarea-input::selection {
        background: rgb(251 191 36 / 0.32);
    }

    .dark .twyxtco-code-textarea-highlight {
        color: rgb(209 213 219);
    }

    .dark textarea.twyxtco-code-textarea-input {
        caret-color: white;
    }

    .twyxtco-code-token-comment {
        color: rgb(107 114 128);
        font-style: italic;
    }

    .twyxtco-code-token-tag,
    .twyxtco-code-token-selector {
        color: rgb(180 83 9);
        font-weight: 650;
    }

    .twyxtco-code-token-attr,
    .twyxtco-code-token-property {
        color: rgb(5 117 111);
    }

    .twyxtco-code-token-string {
        color: rgb(21 128 61);
    }

    .twyxtco-code-token-keyword {
        color: rgb(124 58 237);
        font-weight: 650;
    }

    .twyxtco-code-token-number,
    .twyxtco-code-token-entity {
        color: rgb(2 132 199);
    }

    .dark .twyxtco-code-token-comment {
        color: rgb(156 163 175);
    }

    .dark .twyxtco-code-token-tag,
    .dark .twyxtco-code-token-selector {
        color: rgb(251 191 36);
    }

    .dark .twyxtco-code-token-attr,
    .dark .twyxtco-code-token-property {
        color: rgb(45 212 191);
    }

    .dark .twyxtco-code-token-string {
        color: rgb(134 239 172);
    }

    .dark .twyxtco-code-token-keyword {
        color: rgb(196 181 253);
    }

    .dark .twyxtco-code-token-number,
    .dark .twyxtco-code-token-entity {
        color: rgb(125 211 252);
    }
</style>

<script>
    (() => {
        if (window.twyxtcoCodeTextareasLoaded) {
            return;
        }

        window.twyxtcoCodeTextareasLoaded = true;

        const selector = 'textarea[data-twyxtco-code-textarea="true"]';
        const token = (name, value) => `<span class="twyxtco-code-token-${name}">${value}</span>`;
        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderMatches = (value, pattern, callback) => {
            let html = '';
            let index = 0;
            let match = null;

            while ((match = pattern.exec(value)) !== null) {
                html += escapeHtml(value.slice(index, match.index));
                html += callback(match[0]);
                index = match.index + match[0].length;
            }

            return html + escapeHtml(value.slice(index));
        };

        const highlightAttributes = (value) => renderMatches(
            value,
            /[a-zA-Z_:][\w:.-]*(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s"'=<>`]+))?/g,
            (match) => {
                const parts = match.match(/^([a-zA-Z_:][\w:.-]*)([\s\S]*)$/);

                if (! parts) {
                    return escapeHtml(match);
                }

                const [, name, rest] = parts;

                return `${token('attr', escapeHtml(name))}${rest ? token('string', escapeHtml(rest)) : ''}`;
            },
        );

        const highlightHtmlTag = (value) => {
            const parts = value.match(/^(<\/?)([a-zA-Z][\w:.-]*)([\s\S]*?)(\/?>)$/);

            if (! parts) {
                return token('tag', escapeHtml(value));
            }

            const [, open, name, attributes, close] = parts;

            return `${escapeHtml(open)}${token('tag', escapeHtml(name))}${highlightAttributes(attributes)}${escapeHtml(close)}`;
        };

        const highlightHtml = (value) => renderMatches(
            value,
            /<!--[\s\S]*?-->|<\/?[a-zA-Z][^<>]*>|&[a-zA-Z0-9#]+;/g,
            (match) => {
                if (match.startsWith('<!--')) {
                    return token('comment', escapeHtml(match));
                }

                if (match.startsWith('&')) {
                    return token('entity', escapeHtml(match));
                }

                return highlightHtmlTag(match);
            },
        );

        const highlightCss = (value) => renderMatches(
            value,
            /\/\*[\s\S]*?\*\/|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|#(?:[0-9a-fA-F]{3}){1,2}\b|[\w-]+(?=\s*:)/g,
            (match) => {
                if (match.startsWith('/*')) {
                    return token('comment', escapeHtml(match));
                }

                if (match.startsWith('"') || match.startsWith("'")) {
                    return token('string', escapeHtml(match));
                }

                if (match.startsWith('#')) {
                    return token('number', escapeHtml(match));
                }

                return token('property', escapeHtml(match));
            },
        );

        const highlightJavascript = (value) => renderMatches(
            value,
            /\/\/.*?$|\/\*[\s\S]*?\*\/|`(?:\\.|[\s\S])*?`|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|\b(?:const|let|var|function|return|if|else|for|while|new|class|import|export|from|async|await|true|false|null|undefined)\b|\b\d+(?:\.\d+)?\b/gm,
            (match) => {
                if (match.startsWith('//') || match.startsWith('/*')) {
                    return token('comment', escapeHtml(match));
                }

                if (match.startsWith('"') || match.startsWith("'") || match.startsWith('`')) {
                    return token('string', escapeHtml(match));
                }

                if (/^\d/.test(match)) {
                    return token('number', escapeHtml(match));
                }

                return token('keyword', escapeHtml(match));
            },
        );

        const highlight = (value, language) => {
            if (language === 'css') {
                return highlightCss(value);
            }

            if (language === 'javascript') {
                return highlightJavascript(value);
            }

            return highlightHtml(value);
        };

        const enhance = (textarea) => {
            if (textarea.dataset.twyxtcoCodeEnhanced === 'true') {
                return;
            }

            textarea.dataset.twyxtcoCodeEnhanced = 'true';
            textarea.classList.add('twyxtco-code-textarea-input');

            const shell = document.createElement('div');
            shell.className = 'twyxtco-code-textarea-shell';

            const highlightElement = document.createElement('pre');
            highlightElement.className = 'twyxtco-code-textarea-highlight';
            highlightElement.setAttribute('aria-hidden', 'true');

            textarea.parentNode.insertBefore(shell, textarea);
            shell.appendChild(highlightElement);
            shell.appendChild(textarea);

            const sync = () => {
                highlightElement.innerHTML = `${highlight(textarea.value, textarea.dataset.twyxtcoCodeLanguage || 'html')}\n`;
                highlightElement.scrollTop = textarea.scrollTop;
                highlightElement.scrollLeft = textarea.scrollLeft;
            };

            textarea.addEventListener('input', sync);
            textarea.addEventListener('change', sync);
            textarea.addEventListener('keyup', sync);
            textarea.addEventListener('paste', () => window.setTimeout(sync, 0));
            textarea.addEventListener('focus', sync);
            textarea.addEventListener('scroll', sync);

            sync();
            window.setTimeout(sync, 100);
            window.setTimeout(sync, 500);
        };

        const enhanceAll = () => {
            document.querySelectorAll(selector).forEach(enhance);
        };

        const observeNewFields = () => {
            if (window.twyxtcoCodeTextareasObserver || ! document.body) {
                return;
            }

            window.twyxtcoCodeTextareasObserver = new MutationObserver(() => {
                window.requestAnimationFrame(enhanceAll);
            });

            window.twyxtcoCodeTextareasObserver.observe(document.body, {
                childList: true,
                subtree: true,
            });
        };

        document.addEventListener('DOMContentLoaded', enhanceAll);
        document.addEventListener('DOMContentLoaded', observeNewFields);
        document.addEventListener('livewire:navigated', enhanceAll);
        document.addEventListener('livewire:initialized', enhanceAll);
        document.addEventListener('livewire:update', enhanceAll);
        document.addEventListener('livewire:initialized', observeNewFields);
        window.setTimeout(enhanceAll, 150);
        window.setTimeout(observeNewFields, 150);
    })();
</script>
