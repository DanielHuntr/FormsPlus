<template>
    <div class="fst">
        <div v-if="loading" class="fst__loading">Loading styles…</div>

        <div v-else class="fst__layout">
            <!-- LEFT: CSS editor -->
            <aside class="fst__sidebar">
                <div class="fst__sidebar-inner">

                    <!-- Preset picker -->
                    <div class="fst__group">
                        <div class="fst__group-title">Preset themes</div>
                        <div class="fst__presets">
                            <button
                                v-for="preset in presets"
                                :key="preset.id"
                                class="fst__preset-btn"
                                @click="applyPreset(preset)"
                                :title="preset.label"
                            >{{ preset.label }}</button>
                        </div>
                    </div>

                    <!-- Preview stylesheet -->
                    <div class="fst__group">
                        <div class="fst__group-title">Preview stylesheet</div>
                        <p class="fst__hint">URL to your site's CSS so custom properties (e.g. <code>var(--color-brand)</code>) resolve in the live preview. Not injected into the form itself.</p>
                        <input v-model="styles.preview_stylesheet" type="url" class="fst__input" placeholder="e.g. /css/site.css" @input="debouncedRefresh">
                    </div>

                    <!-- CSS editor -->
                    <div class="fst__group">
                        <div class="fst__group-title-row">
                            <span class="fst__group-title fst__group-title--bare">CSS</span>
                            <button class="fst__css-dock-btn" @click="toggleDock" :title="docked ? 'Undock editor' : 'Dock editor'">
                                <svg v-if="docked" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>
                            </button>
                        </div>
                        <p class="fst__hint">Plain CSS always works. <code>@apply</code> is supported if Tailwind is loaded on your site. Click a class chip to insert a selector at your cursor.</p>

                        <div v-show="docked" ref="cssEditorMount" class="fst__css-editor"></div>
                        <div v-if="!docked" class="fst__css-editor-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            Editor undocked — editing in panel below
                        </div>

                        <div class="fst__class-ref">
                            <p class="fst__class-ref-hint">Available classes — hover for details, click to insert:</p>
                            <div class="fst__class-chips">
                                <button
                                    v-for="cls in availableClasses"
                                    :key="cls.name"
                                    class="fst__class-chip"
                                    :title="cls.description"
                                    @click="insertClass(cls.name)"
                                >.{{ cls.name }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="fst__save-row">
                        <button class="ff-btn ff-btn--primary" :disabled="saving" @click="save">
                            {{ saving ? 'Saving…' : 'Save Styles' }}
                        </button>
                    </div>
                </div>
            </aside>

            <!-- RIGHT: Live preview -->
            <div class="fst__preview-pane">
                <div class="fst__preview-header">
                    <span class="fst__preview-title">Live Preview</span>
                    <div class="fst__mode-toggle">
                        <button
                            class="fst__mode-btn"
                            :class="{ 'fst__mode-btn--active': previewMode === 'light' }"
                            @click="previewMode = 'light'; refreshPreview()"
                        >
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                            Light
                        </button>
                        <button
                            class="fst__mode-btn"
                            :class="{ 'fst__mode-btn--active': previewMode === 'dark' }"
                            @click="previewMode = 'dark'; refreshPreview()"
                        >
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                            Dark
                        </button>
                    </div>
                    <p class="fst__preview-note">Preview uses Tailwind CDN — <code>@apply</code> works here even without it on your site.</p>
                </div>
                <iframe
                    class="fst__preview-frame"
                    :srcdoc="previewHtml"
                    title="Form preview"
                    sandbox="allow-same-origin allow-scripts"
                ></iframe>
            </div>
        </div>

        <!-- Undocked float panel -->
        <div v-if="!loading && !docked" class="fst__css-float">
            <div class="fst__css-float-header">
                <div class="fst__css-float-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    CSS
                </div>
                <button class="fst__css-float-dock-btn" @click="toggleDock">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>
                    Dock
                </button>
            </div>
            <div ref="cssEditorFloatMount" class="fst__css-float-body"></div>
        </div>
    </div>
</template>

<script>
import { EditorView, keymap } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { css as cssLang } from '@codemirror/lang-css';
import { oneDark } from '@codemirror/theme-one-dark';

const PRESETS = [
    {
        id: 'default',
        label: 'Default',
        css: `.flexible-form__field {
    margin-bottom: 1.25rem;
}
.flexible-form__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.25rem;
}
.flexible-form__input {
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    background: #fff;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #111827;
    box-shadow: 0 1px 2px rgba(0,0,0,.05);
    transition: border-color .15s, box-shadow .15s;
}
.flexible-form__input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px #3b82f6;
}
.flexible-form__check-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}
.flexible-form__button {
    display: inline-flex;
    align-items: center;
    border-radius: 0.375rem;
    background: #2563eb;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    border: none;
    cursor: pointer;
    transition: background .15s;
}
.flexible-form__button:hover {
    background: #1d4ed8;
}
.flexible-form__error-msg {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc2626;
}
.flexible-form__instructions {
    margin-top: 0.125rem;
    font-size: 0.75rem;
    color: #6b7280;
}`,
    },
    {
        id: 'minimal',
        label: 'Minimal',
        css: `.flexible-form__field {
    margin-bottom: 1.5rem;
}
.flexible-form__label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6b7280;
    margin-bottom: 0.5rem;
}
.flexible-form__input {
    width: 100%;
    border: none;
    border-bottom: 2px solid #e5e7eb;
    background: transparent;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: #111827;
    transition: border-color .15s;
}
.flexible-form__input:focus {
    outline: none;
    border-color: #111827;
}
.flexible-form__check-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}
.flexible-form__button {
    display: inline-flex;
    align-items: center;
    background: transparent;
    border: 2px solid #111827;
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #111827;
    cursor: pointer;
    transition: background .15s, color .15s;
}
.flexible-form__button:hover {
    background: #111827;
    color: #fff;
}
.flexible-form__error-msg {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #ef4444;
}
.flexible-form__instructions {
    margin-top: 0.125rem;
    font-size: 0.75rem;
    color: #9ca3af;
}`,
    },
    {
        id: 'rounded',
        label: 'Rounded',
        css: `.flexible-form__field {
    margin-bottom: 1.25rem;
}
.flexible-form__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.375rem;
}
.flexible-form__input {
    width: 100%;
    border-radius: 9999px;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    color: #111827;
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.flexible-form__textarea {
    border-radius: 1rem;
}
.flexible-form__input:focus {
    outline: none;
    border-color: #60a5fa;
    background: #fff;
    box-shadow: 0 0 0 2px #bfdbfe;
}
.flexible-form__check-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}
.flexible-form__button {
    display: inline-flex;
    align-items: center;
    border-radius: 9999px;
    background: #3b82f6;
    padding: 0.625rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    box-shadow: 0 4px 6px rgba(0,0,0,.1);
    border: none;
    cursor: pointer;
    transition: background .15s;
}
.flexible-form__button:hover {
    background: #2563eb;
}
.flexible-form__error-msg {
    margin-top: 0.375rem;
    font-size: 0.875rem;
    color: #ef4444;
}
.flexible-form__instructions {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #9ca3af;
}`,
    },
    {
        id: 'dark',
        label: 'Dark',
        css: `.flexible-form {
    background: #030712;
    padding: 2rem;
    border-radius: 0.75rem;
    color-scheme: dark;
}
.flexible-form__field {
    margin-bottom: 1.25rem;
}
.flexible-form__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #d1d5db;
    margin-bottom: 0.25rem;
}
.flexible-form__input {
    width: 100%;
    border-radius: 0.5rem;
    border: 1px solid #374151;
    background: #111827;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: #f3f4f6;
    transition: border-color .15s, box-shadow .15s;
}
.flexible-form__input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px #3b82f6;
}
.flexible-form__check-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #d1d5db;
}
.flexible-form__button {
    display: inline-flex;
    align-items: center;
    border-radius: 0.5rem;
    background: #2563eb;
    padding: 0.625rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    border: none;
    cursor: pointer;
    transition: background .15s;
}
.flexible-form__button:hover {
    background: #3b82f6;
}
.flexible-form__error-msg {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #f87171;
}
.flexible-form__instructions {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
}`,
    },
];

let refreshTimer = null;

export default {
    props: {
        stylesUrl:     { type: String, required: true },
        stylesSaveUrl: { type: String, required: true },
    },

    data() {
        return {
            loading:     true,
            saving:      false,
            docked:      true,
            previewMode: 'light',
            previewHtml: '',
            styles: {
                css:                '',
                preview_stylesheet: '',
            },
            presets: PRESETS,
            availableClasses: [
                { name: 'flexible-form',               description: 'The <form> element' },
                { name: 'flexible-form__field',        description: 'Each field wrapper div' },
                { name: 'flexible-form__label',        description: 'Field labels' },
                { name: 'flexible-form__instructions', description: 'Help/hint text below labels' },
                { name: 'flexible-form__input',        description: 'Text, email, number, date, time, URL inputs · textareas · selects' },
                { name: 'flexible-form__textarea',     description: 'Textarea modifier' },
                { name: 'flexible-form__select',       description: 'Select modifier' },
                { name: 'flexible-form__checkbox',     description: 'Checkbox inputs' },
                { name: 'flexible-form__radio',        description: 'Radio inputs' },
                { name: 'flexible-form__check-label',  description: 'Checkbox/radio label wrappers' },
                { name: 'flexible-form__check-group',  description: 'Container for checkbox/radio options' },
                { name: 'flexible-form__fieldset',     description: 'Fieldset for checkbox/radio groups' },
                { name: 'flexible-form__error-msg',    description: 'Validation error messages' },
                { name: 'flexible-form__input--error', description: 'Input modifier when a field has a validation error' },
                { name: 'flexible-form__button',       description: 'Submit button' },
                { name: 'flexible-form__submit',       description: 'Submit button wrapper div' },
            ],
        };
    },

    async mounted() {
        this._onSave = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.save();
            }
        };
        document.addEventListener('keydown', this._onSave);

        try {
            const { data } = await this.$axios.get(this.stylesUrl);
            this.styles = { ...this.styles, ...data };
        } catch {
            //
        } finally {
            this.loading = false;
        }
        await this.$nextTick();
        this.createEditor(this.$refs.cssEditorMount);
        this.refreshPreview();
    },

    beforeUnmount() {
        document.removeEventListener('keydown', this._onSave);
        this.editorView?.destroy();
    },

    methods: {
        createEditor(container) {
            if (!container) return;
            const cpIsDark = document.documentElement.classList.contains('dark');
            const extensions = [
                basicSetup,
                cssLang(),
                keymap.of([{ key: 'Mod-s', run: () => { this.save(); return true; } }]),
                EditorView.updateListener.of(update => {
                    if (update.docChanged) {
                        this.styles.css = update.state.doc.toString();
                        this.debouncedRefresh();
                    }
                }),
                EditorView.theme({
                    '&': { fontSize: '12.5px', fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace' },
                }),
            ];
            if (!this.docked || cpIsDark) extensions.push(oneDark);
            this.editorView = new EditorView({
                state: EditorState.create({ doc: this.styles.css || '', extensions }),
                parent: container,
            });
        },

        async toggleDock() {
            if (this.editorView) {
                this.styles.css = this.editorView.state.doc.toString();
                this.editorView.destroy();
                this.editorView = null;
            }
            this.docked = !this.docked;
            await this.$nextTick();
            this.createEditor(this.docked ? this.$refs.cssEditorMount : this.$refs.cssEditorFloatMount);
        },

        applyPreset(preset) {
            this.styles.css = preset.css;
            if (this.editorView) {
                const current = this.editorView.state.doc.toString();
                if (current !== preset.css) {
                    this.editorView.dispatch({ changes: { from: 0, to: current.length, insert: preset.css } });
                }
            }
            this.refreshPreview();
        },

        debouncedRefresh() {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(() => this.refreshPreview(), 350);
        },

        refreshPreview() {
            const s = this.styles;
            const dark = this.previewMode === 'dark';
            const bodyBg  = dark ? '#111827' : '#f3f4f6';
            const darkAttr = dark ? ' class="dark"' : '';

            const formHtml = `
<form class="flexible-form">
  <div class="flexible-form__field">
    <label class="flexible-form__label">Your Name</label>
    <input type="text" class="flexible-form__input" placeholder="Jane Smith">
  </div>
  <div class="flexible-form__field">
    <label class="flexible-form__label">Email Address</label>
    <p class="flexible-form__instructions">We'll never share your email.</p>
    <input type="email" class="flexible-form__input" placeholder="jane@example.com">
  </div>
  <div class="flexible-form__field">
    <label class="flexible-form__label">Message</label>
    <textarea class="flexible-form__input flexible-form__textarea" rows="3" placeholder="Your message…"></textarea>
  </div>
  <div class="flexible-form__field">
    <label class="flexible-form__label">How did you hear about us?</label>
    <select class="flexible-form__input flexible-form__select">
      <option value="">Please select…</option>
      <option>Google</option>
      <option>Social media</option>
      <option>Word of mouth</option>
    </select>
  </div>
  <div class="flexible-form__field">
    <fieldset class="flexible-form__fieldset">
      <legend class="flexible-form__label">Interests</legend>
      <div class="flexible-form__check-group" style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
        <label class="flexible-form__check-label">
          <input type="checkbox" class="flexible-form__checkbox"> Design
        </label>
        <label class="flexible-form__check-label">
          <input type="checkbox" class="flexible-form__checkbox" checked> Development
        </label>
      </div>
    </fieldset>
  </div>
  <div class="flexible-form__field">
    <label class="flexible-form__label">Phone <span class="flexible-form__required">*</span></label>
    <input type="tel" class="flexible-form__input flexible-form__input--error" placeholder="+44 7700 …">
    <p class="flexible-form__error-msg">This field is required.</p>
  </div>
  <div class="flexible-form__submit">
    <button type="button" class="flexible-form__button">Submit</button>
  </div>
</form>`;

            this.previewHtml = `<!DOCTYPE html>
<html${darkAttr}>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"><\/script>
<script>tailwind.config = { darkMode: 'class' }<\/script>
${s.preview_stylesheet ? `<link rel="stylesheet" href="${s.preview_stylesheet}">` : ''}
<style>
* { box-sizing: border-box; }
body { padding: 28px; background: ${bodyBg}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
.flexible-form__fields { display: flex; flex-wrap: wrap; gap: 1rem; }
.flexible-form__field { box-sizing: border-box; width: var(--field-width, 100%); }
.flexible-form__fieldset { border: none; padding: 0; margin: 0; }
.flexible-form__submit { margin-top: 8px; }
.flexible-form__required { color: red; }
${s.css || ''}
</style>
</head>
<body>
${formHtml}
</body>
</html>`;
        },

        insertClass(className) {
            if (!this.editorView) return;
            const state = this.editorView.state;
            const from = state.selection.main.head;
            const before = state.doc.sliceString(0, from);
            const prefix = before.length > 0 && !before.endsWith('\n') ? '\n\n' : '';
            const snippet = `${prefix}.${className} {\n    \n}`;
            const newCursor = from + prefix.length + `.${className} {\n    `.length;
            this.editorView.dispatch({
                changes: { from, insert: snippet },
                selection: { anchor: newCursor },
            });
            this.editorView.focus();
            this.debouncedRefresh();
        },

        async save() {
            this.saving = true;
            try {
                await this.$axios.post(this.stylesSaveUrl, this.styles);
                this.$toast.success('Styles saved');
            } catch {
                this.$toast.error('Could not save styles.');
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
